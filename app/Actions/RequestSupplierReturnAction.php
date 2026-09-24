<?php

namespace App\Actions;

use App\Enums\ApprovalStatus;
use App\Enums\PermissionName;
use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use App\Enums\SupplierReturnStatus;
use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Purchase;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\SupplierReturn;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestSupplierReturnAction
{
    /** @param array{reason:string,items:array<int,array{purchase_item_id:int,stock_status:string,quantity:string}>} $data */
    public function execute(User $actor, Purchase $purchase, array $data): SupplierReturn
    {
        $business = $purchase->branch->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::PurchasesReturnRequest, $business) || $purchase->business_id !== app(TenantContext::class)->businessId() || $purchase->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }
        if (! $purchase->supplier_id || empty($data['items'])) {
            throw ValidationException::withMessages(['return' => 'A supplier purchase and at least one return item are required.']);
        }

        return DB::transaction(function () use ($actor, $purchase, $data) {
            Business::query()->lockForUpdate()->findOrFail($purchase->business_id);
            $supplier = $purchase->supplier()->firstOrFail();
            $number = 'RET-'.str_pad((string) (SupplierReturn::query()->where('business_id', $purchase->business_id)->count() + 1), 6, '0', STR_PAD_LEFT);
            $return = SupplierReturn::query()->create([
                'business_id' => $purchase->business_id, 'branch_id' => $purchase->branch_id, 'supplier_id' => $supplier->id,
                'purchase_id' => $purchase->id, 'return_number' => $number, 'status' => SupplierReturnStatus::Pending,
                'reason' => trim($data['reason']), 'requested_by' => $actor->id,
            ]);
            $total = '0';
            foreach ($data['items'] as $index => $line) {
                $purchaseItem = $purchase->items()->find($line['purchase_item_id']);
                $status = StockStatus::tryFrom($line['stock_status']);
                $quantity = (string) $line['quantity'];
                if (! $purchaseItem || ! in_array($status, [StockStatus::Available, StockStatus::Damaged], true) || ! is_numeric($quantity) || bccomp($quantity, '0', 4) <= 0) {
                    throw ValidationException::withMessages(["items.$index" => 'Select a valid received purchase item, stock class, and quantity.']);
                }
                $balance = StockBalance::query()->where('branch_id', $purchase->branch_id)->where('product_id', $purchaseItem->product_id)->where('stock_status', $status->value)->first();
                if (! $balance || bccomp((string) $balance->quantity, $quantity, 4) < 0) {
                    throw ValidationException::withMessages(["items.$index.quantity" => 'The classified stock is not sufficient for this return.']);
                }
                $unitCost = StockMovement::query()->where('branch_id', $purchase->branch_id)->where('product_id', $purchaseItem->product_id)
                    ->where('stock_status', $status->value)->where('movement_type', StockMovementType::PurchaseReceipt->value)->whereNotNull('unit_cost')->latest('occurred_at')->value('unit_cost') ?? $balance->average_cost;
                $lineTotal = bcmul($quantity, (string) $unitCost, 2);
                $return->items()->create(['business_id' => $purchase->business_id, 'purchase_item_id' => $purchaseItem->id, 'product_id' => $purchaseItem->product_id, 'stock_status' => $status, 'quantity' => $quantity, 'unit_cost' => $unitCost, 'line_total' => $lineTotal]);
                $total = bcadd($total, $lineTotal, 2);
            }
            $return->update(['total_amount' => $total]);
            ApprovalRequest::query()->create(['business_id' => $return->business_id, 'branch_id' => $return->branch_id, 'subject_type' => SupplierReturn::class, 'subject_id' => $return->id, 'action_type' => 'supplier_return.approve', 'status' => ApprovalStatus::Pending, 'amount' => $total, 'request_reason' => trim($data['reason']), 'requested_by' => $actor->id]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $purchase->business_id, 'branch_id' => $purchase->branch_id, 'action' => 'supplier_return.requested', 'subject_type' => SupplierReturn::class, 'subject_id' => $return->id, 'new_values' => ['return_number' => $number, 'total_amount' => $total], 'reason' => $data['reason']]);

            return $return->load('items.product', 'supplier');
        });
    }
}
