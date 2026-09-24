<?php

namespace App\Actions;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PermissionName;
use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use App\Enums\SupplierLedgerType;
use App\Models\AuditLog;
use App\Models\GoodsReceipt;
use App\Models\Purchase;
use App\Models\StockBalance;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmGoodsReceiptAction
{
    public function __construct(private ApplyStockMovementAction $movements, private PostSupplierLedgerEntryAction $ledger) {}

    public function execute(User $actor, GoodsReceipt $receipt): GoodsReceipt
    {
        $business = $receipt->purchase->branch->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::PurchasesConfirmReceipt, $business) || $receipt->business_id !== app(TenantContext::class)->businessId() || $receipt->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $receipt) {
            $locked = GoodsReceipt::query()->with(['items.purchaseItem.product', 'branch'])->lockForUpdate()->findOrFail($receipt->id);
            if ($locked->status !== GoodsReceiptStatus::Draft) {
                throw ValidationException::withMessages(['receipt' => 'This receipt has already been confirmed.']);
            }
            $purchase = Purchase::query()->with('supplier')->lockForUpdate()->findOrFail($locked->purchase_id);
            $receiptCharge = '0';

            foreach ($locked->items as $receiptItem) {
                $purchaseItem = $purchase->items()->lockForUpdate()->findOrFail($receiptItem->purchase_item_id);
                $outstanding = bcsub((string) $purchaseItem->ordered_quantity, (string) $purchaseItem->received_quantity, 4);
                if (bccomp((string) $receiptItem->received_quantity, $outstanding, 4) > 0) {
                    throw ValidationException::withMessages(['receipt' => 'Another receipt changed the outstanding quantity. Review this receipt before confirming.']);
                }

                $baseUnitCost = bcdiv((string) $receiptItem->unit_cost, (string) $purchaseItem->conversion_factor, 6);
                $acceptedBase = bcmul((string) $receiptItem->accepted_quantity, (string) $purchaseItem->conversion_factor, 4);
                $damagedBase = bcmul((string) $receiptItem->damaged_quantity, (string) $purchaseItem->conversion_factor, 4);

                if (bccomp($acceptedBase, '0', 4) > 0) {
                    DB::table('stock_balances')->insertOrIgnore([
                        'business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'product_id' => $purchaseItem->product_id,
                        'stock_status' => StockStatus::Available->value, 'quantity' => 0, 'average_cost' => 0, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $balance = StockBalance::query()->where('branch_id', $locked->branch_id)->where('product_id', $purchaseItem->product_id)->where('stock_status', StockStatus::Available->value)->lockForUpdate()->firstOrFail();
                    $previousQuantity = (string) $balance->quantity;
                    $previousAverage = (string) $balance->average_cost;
                    $newAverage = $this->weightedAverage($previousQuantity, $previousAverage, $acceptedBase, $baseUnitCost);
                    $this->movements->execute($actor, $locked->branch, $purchaseItem->product, StockStatus::Available, StockMovementType::PurchaseReceipt, $acceptedBase, 'Confirmed '.$locked->receipt_number, $locked, $baseUnitCost);
                    $balance->refresh()->update(['average_cost' => $newAverage]);
                    DB::table('product_cost_history')->insert([
                        'business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'product_id' => $purchaseItem->product_id,
                        'goods_receipt_item_id' => $receiptItem->id, 'supplied_unit_cost' => $receiptItem->unit_cost,
                        'conversion_factor' => $purchaseItem->conversion_factor, 'base_unit_cost' => $baseUnitCost,
                        'previous_average_cost' => $previousAverage, 'new_average_cost' => $newAverage,
                        'recorded_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                if (bccomp($damagedBase, '0', 4) > 0) {
                    $this->movements->execute($actor, $locked->branch, $purchaseItem->product, StockStatus::Damaged, StockMovementType::PurchaseReceipt, $damagedBase, 'Damaged on '.$locked->receipt_number, $locked, $baseUnitCost);
                }
                $purchaseItem->update(['received_quantity' => bcadd((string) $purchaseItem->received_quantity, (string) $receiptItem->received_quantity, 4)]);
                $receiptCharge = bcadd($receiptCharge, bcmul((string) $receiptItem->received_quantity, (string) $receiptItem->unit_cost, 2), 2);
            }

            if ($purchase->supplier && bccomp($receiptCharge, '0', 2) > 0) {
                $this->ledger->execute($actor, $purchase->supplier, SupplierLedgerType::Charge, $receiptCharge, '0', $locked->branch_id, $locked, 'Confirmed '.$locked->receipt_number);
            }

            $hasOutstanding = $purchase->items()->get()->contains(fn ($item) => bccomp((string) $item->received_quantity, (string) $item->ordered_quantity, 4) < 0);
            $purchase->update(['status' => $hasOutstanding ? PurchaseStatus::PartiallyReceived : PurchaseStatus::Received]);
            $locked->update(['status' => GoodsReceiptStatus::Confirmed, 'confirmed_by' => $actor->id, 'confirmed_at' => now()]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'action' => 'goods_receipt.confirmed', 'subject_type' => GoodsReceipt::class, 'subject_id' => $locked->id, 'new_values' => ['receipt_number' => $locked->receipt_number]]);

            return $locked->refresh()->load('items.purchaseItem.product', 'purchase');
        });
    }

    private function weightedAverage(string $oldQuantity, string $oldCost, string $newQuantity, string $newCost): string
    {
        $quantity = bcadd($oldQuantity, $newQuantity, 4);
        $value = bcadd(bcmul($oldQuantity, $oldCost, 6), bcmul($newQuantity, $newCost, 6), 6);
        $average = bcdiv($value, $quantity, 6);

        return bcadd($average, '0.005', 2);
    }
}
