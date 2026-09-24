<?php

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyStockMovementAction
{
    public function execute(User $actor, Branch $branch, Product $product, StockStatus $status, StockMovementType $type, string $delta, ?string $reason = null, ?Model $source = null, ?string $unitCost = null): StockMovement
    {
        if ($branch->business_id !== $product->business_id || ! $product->is_stock_tracked || ! is_numeric($delta) || bccomp($delta, '0', 4) === 0) {
            throw ValidationException::withMessages(['movement' => 'A valid stock-tracked product and non-zero quantity are required.']);
        }
        if (! $actor->is_super_admin && ! $actor->hasActiveBranchAccess($branch)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $branch, $product, $status, $type, $delta, $reason, $source, $unitCost): StockMovement {
            DB::table('stock_balances')->insertOrIgnore(['business_id' => $branch->business_id, 'branch_id' => $branch->id, 'product_id' => $product->id, 'stock_status' => $status->value, 'quantity' => 0, 'average_cost' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $balance = StockBalance::query()->where('branch_id', $branch->id)->where('product_id', $product->id)->where('stock_status', $status->value)->lockForUpdate()->firstOrFail();
            $before = (string) $balance->quantity;
            $after = bcadd($before, $delta, 4);
            if (bccomp($after, '0', 4) < 0) {
                throw ValidationException::withMessages(['quantity' => 'This movement would make stock negative.']);
            }
            $movement = StockMovement::query()->create(['business_id' => $branch->business_id, 'branch_id' => $branch->id, 'product_id' => $product->id, 'stock_status' => $status, 'movement_type' => $type, 'quantity_delta' => $delta, 'balance_before' => $before, 'balance_after' => $after, 'unit_cost' => $unitCost, 'source_type' => $source?->getMorphClass(), 'source_id' => $source?->getKey(), 'performed_by' => $actor->id, 'reason' => $reason, 'occurred_at' => now()]);
            $balance->update(['quantity' => $after]);

            return $movement;
        });
    }
}
