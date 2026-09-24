<?php

namespace Tests\Feature\Phase5;

use App\Actions\CancelSaleAction;
use App\Actions\ChangeProductPriceAction;
use App\Actions\ConfirmGoodsReleaseAction;
use App\Actions\ConfirmSaleAction;
use App\Actions\CreateBusinessAction;
use App\Actions\CreateCustomerAction;
use App\Actions\CreateGoodsReleaseAction;
use App\Actions\CreateProductAction;
use App\Actions\CreateSaleAction;
use App\Actions\DecideSaleReturnAction;
use App\Actions\RecordOpeningStockAction;
use App\Actions\RequestSaleReturnAction;
use App\Models\SaleReturn;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SalesCancellationAndReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_unreleased_sale_can_be_cancelled_but_released_sale_cannot(): void
    {
        [$business, $owner, $unit, $level] = $this->fixture('CANCEL');
        $sale = $this->sale($business, $owner, $unit->id, $level->id, '2');
        app(CancelSaleAction::class)->execute($owner, $sale, 'Customer cancelled before collection');
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'cancelled', 'cancelled_by' => $owner->id]);

        $releasedSale = $this->sale($business, $owner, $unit->id, $level->id, '2');
        app(ConfirmSaleAction::class)->execute($owner, $releasedSale);
        $release = app(CreateGoodsReleaseAction::class)->execute($owner, $releasedSale->refresh(), ['items' => [['sale_item_id' => $releasedSale->items()->first()->id, 'quantity' => '1']]]);
        app(ConfirmGoodsReleaseAction::class)->execute($owner, $release);

        $this->expectException(ValidationException::class);
        app(CancelSaleAction::class)->execute($owner, $releasedSale->refresh(), 'Attempt after physical release');
    }

    public function test_approved_customer_return_restocks_only_the_inspected_classification(): void
    {
        [$business, $owner, $unit, $level] = $this->fixture('RETURN');
        $customer = app(CreateCustomerAction::class)->execute($owner, $business, ['name' => 'Return Customer', 'is_credit_customer' => true, 'credit_limit' => '1000']);
        $sale = app(CreateSaleAction::class)->execute($owner, $business, ['branch_id' => $business->branches->first()->id, 'customer_id' => $customer->id, 'sale_date' => now()->toDateString(), 'items' => [['product_unit_id' => $unit->id, 'price_level_id' => $level->id, 'quantity' => '3', 'applied_unit_price' => '150']]]);
        app(ConfirmSaleAction::class)->execute($owner, $sale);
        $release = app(CreateGoodsReleaseAction::class)->execute($owner, $sale->refresh(), ['items' => [['sale_item_id' => $sale->items()->first()->id, 'quantity' => '2']]]);
        app(ConfirmGoodsReleaseAction::class)->execute($owner, $release);

        $return = app(RequestSaleReturnAction::class)->execute($owner, $sale->refresh(), ['reason' => 'Customer returned one damaged item', 'items' => [['sale_item_id' => $sale->items()->first()->id, 'quantity' => '1', 'stock_status' => 'damaged']]]);
        $approver = $this->addOwner($business);
        app(TenantContext::class)->setForUser($approver, $business, $business->branches->first());
        app(DecideSaleReturnAction::class)->approve($approver, $return, 'Inspected physically and confirmed damaged');

        $this->assertDatabaseHas('sale_returns', ['id' => $return->id, 'status' => 'approved', 'decided_by' => $approver->id]);
        $this->assertDatabaseHas('stock_movements', ['movement_type' => 'customer_return', 'stock_status' => 'damaged', 'quantity_delta' => 1]);
        $this->assertDatabaseHas('customer_ledger_entries', ['customer_id' => $customer->id, 'entry_type' => 'sale_return', 'credit' => 150]);
        $this->assertDatabaseHas('approval_requests', ['subject_type' => SaleReturn::class, 'subject_id' => $return->id, 'status' => 'approved']);
        $this->assertSame('8.0000', StockBalance::query()->where('stock_status', 'available')->first()->quantity);
        $this->assertSame('1.0000', StockBalance::query()->where('stock_status', 'damaged')->first()->quantity);
    }

    private function fixture(string $code): array
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => "Business $code", 'code' => $code]);
        $branch = $business->branches->first();
        app(TenantContext::class)->setForUser($owner, $business, $branch);
        $unit = Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc-'.$code]);
        $product = app(CreateProductAction::class)->execute($owner, $business, ['name' => 'Return Product', 'unit_id' => $unit->id]);
        $productUnit = $product->productUnits()->first();
        $level = $business->priceLevels()->where('is_default', true)->first();
        app(ChangeProductPriceAction::class)->execute($owner, $product, $productUnit, $level, '150', 'Initial retail price');
        app(RecordOpeningStockAction::class)->execute($owner, $branch, $product, '10');
        StockBalance::query()->first()->update(['average_cost' => '100']);

        return [$business, $owner, $productUnit, $level];
    }

    private function sale($business, User $owner, int $unitId, int $levelId, string $quantity)
    {
        return app(CreateSaleAction::class)->execute($owner, $business, ['branch_id' => $business->branches->first()->id, 'sale_date' => now()->toDateString(), 'items' => [['product_unit_id' => $unitId, 'price_level_id' => $levelId, 'quantity' => $quantity, 'applied_unit_price' => '150']]]);
    }

    private function addOwner($business): User
    {
        $user = User::factory()->create();
        $branch = $business->branches->first();
        $role = $business->roles()->where('slug', 'owner')->firstOrFail();
        DB::table('business_users')->insert(['business_id' => $business->id, 'user_id' => $user->id, 'is_active' => true, 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('branch_users')->insert(['business_id' => $business->id, 'branch_id' => $branch->id, 'user_id' => $user->id, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_roles')->insert(['business_id' => $business->id, 'user_id' => $user->id, 'role_id' => $role->id, 'created_at' => now(), 'updated_at' => now()]);

        return $user;
    }
}
