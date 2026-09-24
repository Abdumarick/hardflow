<?php

namespace Tests\Feature\Phase4;

use App\Actions\ConfirmGoodsReceiptAction;
use App\Actions\CreateBusinessAction;
use App\Actions\CreateGoodsReceiptAction;
use App\Actions\CreateProductAction;
use App\Actions\CreatePurchaseAction;
use App\Actions\CreateSupplierAction;
use App\Actions\DecideSupplierReturnAction;
use App\Actions\OrderPurchaseAction;
use App\Actions\RecordSupplierPaymentAction;
use App\Actions\RequestSupplierReturnAction;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PurchasingDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_ordering_a_purchase_does_not_change_stock(): void
    {
        [$business, $owner, $product, $unit, $supplier] = $this->fixture('PO');
        $purchase = $this->purchase($business, $owner, $unit->id, $supplier->id, '10', '2500');
        app(OrderPurchaseAction::class)->execute($owner, $purchase);

        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('stock_balances', 0);
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'status' => 'ordered', 'total_amount' => 25000]);
    }

    public function test_confirming_partial_receipt_separates_available_and_damaged_stock_and_updates_cost(): void
    {
        [$business, $owner, $product, $unit, $supplier] = $this->fixture('RECV');
        $purchase = $this->purchase($business, $owner, $unit->id, $supplier->id, '10', '2500');
        app(OrderPurchaseAction::class)->execute($owner, $purchase);
        $item = $purchase->items()->first();
        $receipt = app(CreateGoodsReceiptAction::class)->execute($owner, $purchase->refresh(), [
            'received_at' => now()->toDateTimeString(),
            'items' => [[
                'purchase_item_id' => $item->id, 'received_quantity' => '6', 'damaged_quantity' => '1',
                'unit_cost' => '3000', 'difference_reason' => 'Supplier will deliver the remaining four later',
            ]],
        ]);

        $this->assertDatabaseCount('stock_movements', 0);
        app(ConfirmGoodsReceiptAction::class)->execute($owner, $receipt);

        $this->assertSame('5.0000', StockBalance::query()->where('stock_status', 'available')->first()->quantity);
        $this->assertSame('3000.00', StockBalance::query()->where('stock_status', 'available')->first()->average_cost);
        $this->assertSame('1.0000', StockBalance::query()->where('stock_status', 'damaged')->first()->quantity);
        $this->assertDatabaseHas('purchase_items', ['id' => $item->id, 'received_quantity' => 6]);
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'status' => 'partially_received']);
        $this->assertDatabaseCount('product_cost_history', 1);
        $this->assertDatabaseHas('supplier_ledger_entries', ['supplier_id' => $supplier->id, 'entry_type' => 'charge', 'debit' => 18000, 'credit' => 0]);
    }

    public function test_duplicate_confirmation_cannot_duplicate_stock(): void
    {
        [$business, $owner, $product, $unit, $supplier] = $this->fixture('DUP');
        $purchase = $this->purchase($business, $owner, $unit->id, $supplier->id, '2', '100');
        app(OrderPurchaseAction::class)->execute($owner, $purchase);
        $receipt = app(CreateGoodsReceiptAction::class)->execute($owner, $purchase->refresh(), [
            'received_at' => now()->toDateTimeString(),
            'items' => [['purchase_item_id' => $purchase->items()->first()->id, 'received_quantity' => '2']],
        ]);
        app(ConfirmGoodsReceiptAction::class)->execute($owner, $receipt);

        try {
            app(ConfirmGoodsReceiptAction::class)->execute($owner, $receipt->refresh());
            $this->fail('A confirmed receipt must not be confirmed twice.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_movements', 1);
            $this->assertSame('2.0000', StockBalance::query()->first()->quantity);
        }
    }

    public function test_cross_business_supplier_is_rejected(): void
    {
        [$businessA, $ownerA, $productA, $unitA] = $this->fixture('TEN-A');
        [$businessB, $ownerB, $productB, $unitB, $supplierB] = $this->fixture('TEN-B');
        app(TenantContext::class)->setForUser($ownerA, $businessA, $businessA->branches->first());

        $this->expectException(ValidationException::class);
        $this->purchase($businessA, $ownerA, $unitA->id, $supplierB->id, '1', '10');
    }

    public function test_approved_return_reduces_only_selected_stock_and_credits_supplier_ledger(): void
    {
        [$business, $owner, $product, $unit, $supplier] = $this->fixture('RET');
        $purchase = $this->purchase($business, $owner, $unit->id, $supplier->id, '5', '100');
        app(OrderPurchaseAction::class)->execute($owner, $purchase);
        $purchaseItem = $purchase->items()->first();
        $receipt = app(CreateGoodsReceiptAction::class)->execute($owner, $purchase->refresh(), [
            'received_at' => now()->toDateTimeString(),
            'items' => [['purchase_item_id' => $purchaseItem->id, 'received_quantity' => '5', 'damaged_quantity' => '1']],
        ]);
        app(ConfirmGoodsReceiptAction::class)->execute($owner, $receipt);
        $return = app(RequestSupplierReturnAction::class)->execute($owner, $purchase->refresh(), [
            'reason' => 'Returning the damaged item to the supplier',
            'items' => [['purchase_item_id' => $purchaseItem->id, 'stock_status' => 'damaged', 'quantity' => '1']],
        ]);
        $this->assertSame('1.0000', StockBalance::query()->where('stock_status', 'damaged')->first()->quantity);

        $approver = $this->addOwner($business);
        app(TenantContext::class)->setForUser($approver, $business, $business->branches->first());
        app(DecideSupplierReturnAction::class)->approve($approver, $return, 'Supplier accepted the documented damage');

        $this->assertSame('0.0000', StockBalance::query()->where('stock_status', 'damaged')->first()->quantity);
        $this->assertDatabaseHas('stock_movements', ['movement_type' => 'supplier_return', 'stock_status' => 'damaged', 'quantity_delta' => -1]);
        $this->assertDatabaseHas('supplier_ledger_entries', ['entry_type' => 'return_credit', 'credit' => 100]);
        $this->assertSame('400.00', $supplier->outstandingBalance());
    }

    public function test_supplier_balance_is_derived_from_immutable_charge_payment_and_credit_entries(): void
    {
        [$business, $owner, $product, $unit, $supplier] = $this->fixture('PAY');
        $purchase = $this->purchase($business, $owner, $unit->id, $supplier->id, '2', '500');
        app(OrderPurchaseAction::class)->execute($owner, $purchase);
        $receipt = app(CreateGoodsReceiptAction::class)->execute($owner, $purchase->refresh(), ['received_at' => now()->toDateTimeString(), 'items' => [['purchase_item_id' => $purchase->items()->first()->id, 'received_quantity' => '2']]]);
        app(ConfirmGoodsReceiptAction::class)->execute($owner, $receipt);
        $payment = app(RecordSupplierPaymentAction::class)->execute($owner, $supplier, '300', 'Bank transfer reference HF-001');

        $this->assertSame('700.00', $supplier->outstandingBalance());
        $this->expectException(\LogicException::class);
        $payment->update(['credit' => 999]);
    }

    private function fixture(string $code): array
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => "Business $code", 'code' => $code]);
        $branch = $business->branches->first();
        app(TenantContext::class)->setForUser($owner, $business, $branch);
        $unit = Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc-'.$code]);
        $product = app(CreateProductAction::class)->execute($owner, $business, ['name' => 'Purchased Product', 'unit_id' => $unit->id]);
        $productUnit = $product->productUnits()->first();
        $supplier = app(CreateSupplierAction::class)->execute($owner, $business, ['name' => 'Supplier '.$code]);

        return [$business, $owner, $product, $productUnit, $supplier];
    }

    private function purchase($business, User $owner, int $productUnitId, int $supplierId, string $quantity, string $cost)
    {
        return app(CreatePurchaseAction::class)->execute($owner, $business, [
            'branch_id' => $business->branches->first()->id,
            'supplier_id' => $supplierId,
            'purchase_date' => now()->toDateString(),
            'items' => [['product_unit_id' => $productUnitId, 'quantity' => $quantity, 'unit_cost' => $cost]],
        ]);
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
