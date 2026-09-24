<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Enums\StockAdjustmentStatus;
use App\Models\AuditLog;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteStockCountAction
{
    public function execute(User $actor, StockCount $count, array $physicalQuantities, string $reason): array
    {
        $business = app(TenantContext::class)->businessOrFail();
        if (! $actor->hasPermissionInBusiness(PermissionName::InventoryAdjustRequest, $business) || $count->business_id !== $business->id || $count->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }if (blank(trim($reason))) {
            throw ValidationException::withMessages(['reason' => 'A count reason is required.']);
        }

        return DB::transaction(function () use ($actor, $count, $physicalQuantities, $reason, $business) {
            $locked = StockCount::query()->whereKey($count->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'draft') {
                throw ValidationException::withMessages(['count' => 'This count is already completed.']);
            }$locked->load('items');
            $adjustments = [];
            foreach ($locked->items as $item) {
                if (! array_key_exists($item->id, $physicalQuantities) || $physicalQuantities[$item->id] === '') {
                    continue;
                }$physical = (string) $physicalQuantities[$item->id];
                if (! is_numeric($physical) || (float) $physical < 0) {
                    throw ValidationException::withMessages(['quantities' => 'Physical quantities must be zero or greater.']);
                }$current = StockBalance::query()->where('branch_id', $count->branch_id)->where('product_id', $item->product_id)->where('stock_status', $item->stock_status->value)->lockForUpdate()->value('quantity') ?? '0';
                if (bccomp((string) $current, (string) $item->system_quantity, 4) !== 00) {
                    throw ValidationException::withMessages(['count' => 'Stock changed while counting. Start a new count.']);
                }$item->update(['physical_quantity' => $physical]);
                $difference = bcsub($physical, (string) $item->system_quantity, 4);
                if (bccomp($difference, '0', 4) !== 0) {
                    $adjustment = StockAdjustment::query()->create(['business_id' => $business->id, 'branch_id' => $count->branch_id, 'status' => StockAdjustmentStatus::Pending, 'reason' => trim($reason).' [Stock count '.$count->public_id.']', 'requested_by' => $actor->id]);
                    StockAdjustmentItem::query()->create(['business_id' => $business->id, 'adjustment_id' => $adjustment->id, 'product_id' => $item->product_id, 'stock_status' => $item->stock_status, 'system_quantity' => $item->system_quantity, 'physical_quantity' => $physical, 'difference_quantity' => $difference]);
                    $adjustments[] = $adjustment;
                }
            }$locked->update(['status' => 'completed', 'counted_at' => now()]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $business->id, 'branch_id' => $count->branch_id, 'action' => 'stock_count.completed', 'subject_type' => StockCount::class, 'subject_id' => $count->id, 'new_values' => ['adjustment_ids' => collect($adjustments)->pluck('id')->all()], 'reason' => $reason]);

            return $adjustments;
        });
    }
}
