<?php

namespace Tests\Feature\Phase5;

use App\Actions\ChangeProductPriceAction;
use App\Actions\ConfirmGoodsReleaseAction;
use App\Actions\ConfirmSaleAction;
use App\Actions\CompleteSaleCheckoutAction;
use App\Actions\ConvertQuotationToSaleAction;
use App\Actions\CreateBusinessAction;
use App\Actions\CreateCustomerAction;
use App\Actions\CreateGoodsReleaseAction;
use App\Actions\CreateProductAction;
use App\Actions\CreateQuotationAction;
use App\Actions\CreateSaleAction;
use App\Actions\RecordOpeningStockAction;
use App\Models\StockBalance;
use App\Models\PaymentAccount;
use App\Models\PaymentMethod;
use App\Models\Unit;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SalesDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_confirmation_does_not_change_stock_and_partial_release_does(): void
    {
        [$business, $owner, $product, $productUnit, $level] = $this->fixture('SALE');
        $sale = $this->sale($business, $owner, $productUnit->id, $level->id, '4', '150');
        app(ConfirmSaleAction::class)->execute($owner, $sale);
        $this->assertSame('10.0000', StockBalance::query()->first()->quantity);
        $this->assertDatabaseCount('stock_movements', 1);

        $release = app(CreateGoodsReleaseAction::class)->execute($owner, $sale->refresh(), ['items' => [['sale_item_id' => $sale->items()->first()->id, 'quantity' => '2']]]);
        $this->assertSame('10.0000', StockBalance::query()->first()->quantity);
        app(ConfirmGoodsReleaseAction::class)->execute($owner, $release);

        $this->assertSame('8.0000', StockBalance::query()->first()->quantity);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'payment_status' => 'unpaid', 'fulfillment_status' => 'partially_released']);
        $this->assertDatabaseHas('stock_movements', ['movement_type' => 'sale_release', 'quantity_delta' => -2]);
    }

    public function test_duplicate_release_confirmation_is_rejected(): void
    {
        [$business, $owner, $product, $productUnit, $level] = $this->fixture('DUPSALE');
        $sale = $this->sale($business, $owner, $productUnit->id, $level->id, '1', '150');
        app(ConfirmSaleAction::class)->execute($owner, $sale);
        $release = app(CreateGoodsReleaseAction::class)->execute($owner, $sale->refresh(), ['items' => [['sale_item_id' => $sale->items()->first()->id, 'quantity' => '1']]]);
        app(ConfirmGoodsReleaseAction::class)->execute($owner, $release);

        $this->expectException(ValidationException::class);
        try {
            app(ConfirmGoodsReleaseAction::class)->execute($owner, $release->refresh());
        } finally {
            $this->assertSame('9.0000', StockBalance::query()->first()->quantity);
        }
    }

    public function test_below_cost_sale_is_blocked_by_default(): void
    {
        [$business, $owner, $product, $productUnit, $level] = $this->fixture('COST');
        $this->expectException(ValidationException::class);
        $this->sale($business, $owner, $productUnit->id, $level->id, '1', '90');
    }

    public function test_quotation_converts_without_reentry_or_stock_change(): void
    {
        [$business, $owner, $product, $productUnit, $level] = $this->fixture('QUOTE');
        $quotation = app(CreateQuotationAction::class)->execute($owner, $business, [
            'branch_id' => $business->branches->first()->id, 'document_type' => 'proforma',
            'walk_in_name' => 'Mteja Hardware', 'quotation_date' => now()->toDateString(), 'valid_until' => now()->addDays(7)->toDateString(),
            'items' => [['product_unit_id' => $productUnit->id, 'price_level_id' => $level->id, 'quantity' => '2', 'applied_unit_price' => '150', 'discount_amount' => '10']],
        ]);
        $sale = app(ConvertQuotationToSaleAction::class)->execute($owner, $quotation);

        $this->assertSame('Mteja Hardware', $sale->walk_in_name);
        $this->assertSame('2.0000', $sale->items->first()->quantity);
        $this->assertSame('290.00', $sale->total_amount);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('quotations', ['id' => $quotation->id, 'status' => 'converted', 'converted_sale_id' => $sale->id]);
    }

    public function test_owner_can_open_sales_workspace(): void
    {
        [$business, $owner] = $this->fixture('SALESUI');

        $this->actingAs($owner)->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id])
            ->get(route('owner.sales.index'))->assertOk()->assertSeeText('Sales / POS')->assertSeeText('Current Sale')
            ->assertSeeText('Registered customer')->assertSee('name="walk_in_name"', false)->assertSee('name="customer_id"', false);
    }

    public function test_sales_workspace_includes_product_picture_urls_for_pos_cards(): void
    {
        [$business, $owner, $product] = $this->fixture('SALESIMAGE');
        $product->update(['image_path' => 'products/'.$business->id.'/sale-product.png']);

        $this->actingAs($owner)->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id])
            ->get(route('owner.sales.index'))
            ->assertOk()
            ->assertSee('sale-product.png', false)
            ->assertSee('product.image_url', false);
    }

    public function test_pos_accepts_a_typed_customer_name_or_a_registered_customer(): void
    {
        [$business, $owner, , $productUnit, $level] = $this->fixture('POSCUSTOMER');
        $session = ['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id];
        $item = ['product_unit_id' => $productUnit->id, 'price_level_id' => $level->id, 'quantity' => '1', 'applied_unit_price' => '150'];

        $this->actingAs($owner)->withSession($session)->post(route('owner.sales.store'), [
            'walk_in_name' => 'Juma Builder', 'walk_in_phone' => '0712345678', 'items' => [$item],
        ])->assertRedirect();

        $this->assertDatabaseHas('sales', ['business_id' => $business->id, 'customer_id' => null, 'walk_in_name' => 'Juma Builder', 'walk_in_phone' => '0712345678']);

        $customer = app(CreateCustomerAction::class)->execute($owner, $business, ['name' => 'Amina Hardware']);
        $this->actingAs($owner)->withSession($session)->post(route('owner.sales.store'), [
            'customer_id' => $customer->id, 'walk_in_name' => 'Stale typed name', 'walk_in_phone' => '0700000000', 'items' => [$item],
        ])->assertRedirect();

        $this->assertDatabaseHas('sales', ['business_id' => $business->id, 'customer_id' => $customer->id, 'walk_in_name' => null, 'walk_in_phone' => null]);
    }

    public function test_checkout_confirms_a_sale_and_records_split_payments_without_releasing_stock(): void
    {
        [$business, $owner, $product, $productUnit, $level] = $this->fixture('SPLITPAY');
        $sale = $this->sale($business, $owner, $productUnit->id, $level->id, '2', '150');
        $branch = $business->branches->first();
        $cashMethod = PaymentMethod::query()->where('business_id', $business->id)->where('type', 'cash')->firstOrFail();
        $bankMethod = PaymentMethod::query()->where('business_id', $business->id)->where('type', 'bank')->firstOrFail();
        $cash = PaymentAccount::query()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'payment_method_id' => $cashMethod->id, 'name' => 'Checkout till']);
        $bank = PaymentAccount::query()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'payment_method_id' => $bankMethod->id, 'name' => 'Checkout bank']);

        app(CompleteSaleCheckoutAction::class)->execute($owner, $sale, [
            ['payment_account_id' => $cash->id, 'amount' => '100'],
            ['payment_account_id' => $bank->id, 'amount' => '200', 'external_reference' => 'BANK-123'],
        ]);

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'confirmed', 'payment_status' => 'paid']);
        $this->assertDatabaseCount('payments', 2);
        $this->assertSame('10.0000', StockBalance::query()->first()->quantity);
    }

    private function fixture(string $code): array
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => "Business $code", 'code' => $code]);
        $branch = $business->branches->first();
        app(TenantContext::class)->setForUser($owner, $business, $branch);
        $unit = Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc-'.$code]);
        $product = app(CreateProductAction::class)->execute($owner, $business, ['name' => 'Sale Product', 'unit_id' => $unit->id]);
        $productUnit = $product->productUnits()->first();
        $level = $business->priceLevels()->where('is_default', true)->first();
        app(ChangeProductPriceAction::class)->execute($owner, $product, $productUnit, $level, '150', 'Initial retail price');
        app(RecordOpeningStockAction::class)->execute($owner, $branch, $product, '10');
        StockBalance::query()->first()->update(['average_cost' => '100']);

        return [$business, $owner, $product, $productUnit, $level];
    }

    private function sale($business, $owner, int $unitId, int $levelId, string $quantity, string $price)
    {
        return app(CreateSaleAction::class)->execute($owner, $business, ['branch_id' => $business->branches->first()->id, 'sale_date' => now()->toDateString(), 'items' => [['product_unit_id' => $unitId, 'price_level_id' => $levelId, 'quantity' => $quantity, 'applied_unit_price' => $price, 'override_reason' => 'Approved negotiated price']]]);
    }
}
