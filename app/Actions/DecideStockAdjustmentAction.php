<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Enums\StockAdjustmentStatus;
use App\Enums\StockMovementType;
use App\Models\AuditLog;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecideStockAdjustmentAction
{
    public function __construct(private ApplyStockMovementAction $movements) {}

    public function approve(User $actor, StockAdjustment $adjustment, string $reason): StockAdjustment
    {
        return $this->decide($actor, $adjustment, true, $reason);
    }

    public function reject(User $actor, StockAdjustment $adjustment, string $reason): StockAdjustment
    {
        return $this->decide($actor, $adjustment, false, $reason);
    }

    private function decide(User $actor, StockAdjustment $adjustment, bool $approve, string $reason): StockAdjustment
    {
        $business = app(TenantContext::class)->businessOrFail();
        if (! $actor->hasPermissionInBusiness(PermissionName::InventoryAdjustApprove, $business) || $adjustment->business_id !== $business->id || $adjustment->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }if ($adjustment->requested_by === $actor->id) {
            throw ValidationException::withMessages(['approval' => 'You cannot approve your own stock adjustment.']);
        }if (blank(trim($reason))) {
            throw ValidationException::withMessages(['reason' => 'A decision reason is required.']);
        }

        return DB::transaction(function () use ($actor, $adjustment, $approve, $reason, $business) {
            $locked = StockAdjustment::query()->whereKey($adjustment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== StockAdjustmentStatus::Pending) {
                throw ValidationException::withMessages(['adjustment' => 'This adjustment has already been decided.']);
            }$locked->load('items.product', 'branch');
            if ($approve) {
                foreach ($locked->items as $item) {
                    $current = StockBalance::query()->where('branch_id', $locked->branch_id)->where('product_id', $item->product_id)->where('stock_status', $item->stock_status->value)->lockForUpdate()->value('quantity') ?? '0';
                    if (bccomp((string) $current, (string) $item->system_quantity, 4) !== 0) {
                        throw ValidationException::withMessages(['adjustment' => 'Stock changed after this request. Create a new physical comparison.']);
                    }if (bccomp((string) $item->difference_quantity, '0', 4) !== 0) {
                        $this->movements->execute($actor, $locked->branch, $item->product, $item->stock_status, bccomp((string) $item->difference_quantity, '0', 4) > 0 ? StockMovementType::AdjustmentIn : StockMovementType::AdjustmentOut, (string) $item->difference_quantity, $locked->reason, $locked);
                    }
                }
            }$status = $approve ? StockAdjustmentStatus::Approved : StockAdjustmentStatus::Rejected;
            $locked->update(['status' => $status, 'decided_by' => $actor->id, 'decision_reason' => trim($reason), 'decided_at' => now()]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $business->id, 'branch_id' => $locked->branch_id, 'action' => $approve ? 'stock_adjustment.approved' : 'stock_adjustment.rejected', 'subject_type' => StockAdjustment::class, 'subject_id' => $locked->id, 'reason' => $reason]);

            return $locked;
        });
    }
}
