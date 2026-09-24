<?php

namespace App\Actions;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PermissionName;
use App\Enums\PurchaseStatus;
use App\Models\GoodsReceipt;
use App\Models\Purchase;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateGoodsReceiptAction
{
    /** @param array{received_at:string,notes?:?string,items:array<int,array{purchase_item_id:int,received_quantity:string,damaged_quantity?:string,unit_cost?:string,difference_reason?:?string}>} $data */
    public function execute(User $actor, Purchase $purchase, array $data): GoodsReceipt
    {
        $business = $purchase->branch->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::PurchasesReceive, $business) || $purchase->business_id !== app(TenantContext::class)->businessId() || $purchase->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }
        if (! in_array($purchase->status, [PurchaseStatus::Ordered, PurchaseStatus::PartiallyReceived], true)) {
            throw ValidationException::withMessages(['purchase' => 'Only an ordered purchase can be received.']);
        }
        if (empty($data['items'])) {
            throw ValidationException::withMessages(['items' => 'Add at least one received item.']);
        }

        return DB::transaction(function () use ($actor, $purchase, $data) {
            $lockedPurchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            $receipt = GoodsReceipt::query()->create([
                'business_id' => $lockedPurchase->business_id, 'branch_id' => $lockedPurchase->branch_id, 'purchase_id' => $lockedPurchase->id,
                'receipt_number' => $lockedPurchase->purchase_number.'-GR-'.str_pad((string) ($lockedPurchase->receipts()->count() + 1), 2, '0', STR_PAD_LEFT),
                'status' => GoodsReceiptStatus::Draft, 'received_by' => $actor->id, 'received_at' => $data['received_at'], 'notes' => $data['notes'] ?? null,
            ]);
            foreach ($data['items'] as $index => $line) {
                $item = $lockedPurchase->items()->find($line['purchase_item_id']);
                $received = (string) $line['received_quantity'];
                $damaged = (string) ($line['damaged_quantity'] ?? '0');
                if (! $item || ! is_numeric($received) || ! is_numeric($damaged)) {
                    throw ValidationException::withMessages(["items.$index" => 'Receipt quantities are invalid.']);
                }
                $outstanding = bcsub((string) $item->ordered_quantity, (string) $item->received_quantity, 4);
                if (bccomp($received, '0', 4) <= 0 || bccomp($damaged, '0', 4) < 0 || bccomp($damaged, $received, 4) > 0 || bccomp($received, $outstanding, 4) > 0) {
                    throw ValidationException::withMessages(["items.$index" => 'Receipt quantities are invalid or exceed the outstanding quantity.']);
                }
                if (bccomp($received, $outstanding, 4) < 0 && blank($line['difference_reason'] ?? null)) {
                    throw ValidationException::withMessages(["items.$index.difference_reason" => 'Explain the short or partial delivery.']);
                }
                $receipt->items()->create([
                    'business_id' => $lockedPurchase->business_id, 'purchase_item_id' => $item->id,
                    'received_quantity' => $received, 'accepted_quantity' => bcsub($received, $damaged, 4), 'damaged_quantity' => $damaged,
                    'unit_cost' => $line['unit_cost'] ?? $item->unit_cost, 'difference_reason' => $line['difference_reason'] ?? null,
                ]);
            }

            return $receipt->load('items.purchaseItem.product');
        });
    }
}
