<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Enums\StockAdjustmentStatus;
use App\Enums\StockStatus;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockBalance;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestStockAdjustmentAction
{
    public function execute(User $actor, Branch $branch, Product $product, StockStatus $status, string $physical, string $reason): StockAdjustment
    {
        $business = app(TenantContext::class)->businessOrFail();
        if (! $actor->hasPermissionInBusiness(PermissionName::InventoryAdjustRequest, $business)) {
            throw new AuthorizationException;
        }if ($branch->id !== app(TenantContext::class)->branchId() || $product->business_id !== $business->id) {
            throw new AuthorizationException;
        }if (! is_numeric($physical) || (float) $physical < 0) {
            throw ValidationException::withMessages(['physical_quantity' => 'Physical quantity must be zero or greater.']);
        }if (blank(trim($reason))) {
            throw ValidationException::withMessages(['reason' => 'A strong reason is required.']);
        }

        return DB::transaction(function () use ($actor, $branch, $product, $status, $physical, $reason, $business) {
            $balance = StockBalance::query()->where('branch_id', $branch->id)->where('product_id', $product->id)->where('stock_status', $status->value)->lockForUpdate()->first();
            $system = (string) ($balance?->quantity ?? '0');
            $difference = bcsub($physical, $system, 4);
            $adjustment = StockAdjustment::query()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'status' => StockAdjustmentStatus::Pending, 'reason' => trim($reason), 'requested_by' => $actor->id]);
            StockAdjustmentItem::query()->create(['business_id' => $business->id, 'adjustment_id' => $adjustment->id, 'product_id' => $product->id, 'stock_status' => $status, 'system_quantity' => $system, 'physical_quantity' => $physical, 'difference_quantity' => $difference]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $business->id, 'branch_id' => $branch->id, 'action' => 'stock_adjustment.requested', 'subject_type' => StockAdjustment::class, 'subject_id' => $adjustment->id, 'new_values' => ['product_id' => $product->id, 'system_quantity' => $system, 'physical_quantity' => $physical, 'difference_quantity' => $difference], 'reason' => $reason]);

            return $adjustment->load('items');
        });
    }
}
