<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateProductAverageCostAction
{
    public function execute(User $actor, Branch $branch, Product $product, string $cost, string $reason = 'Buying price imported from product CSV'): bool
    {
        Gate::forUser($actor)->authorize('update', $product);
        if ($branch->business_id !== $product->business_id || ! is_numeric($cost) || bccomp($cost, '0', 2) < 0) {
            throw ValidationException::withMessages(['buying_price' => 'A valid buying price is required.']);
        }

        return DB::transaction(function () use ($actor, $branch, $product, $cost, $reason): bool {
            StockBalance::query()->firstOrCreate([
                'business_id' => $branch->business_id,
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'stock_status' => 'available',
            ], ['quantity' => 0, 'average_cost' => 0]);
            $balance = StockBalance::query()->where('branch_id', $branch->id)->where('product_id', $product->id)->where('stock_status', 'available')->lockForUpdate()->firstOrFail();
            if (bccomp((string) $balance->average_cost, $cost, 2) === 0) {
                return false;
            }

            $previousCost = (string) $balance->average_cost;
            $balance->update(['average_cost' => $cost]);
            AuditLog::query()->create([
                'user_id' => $actor->id,
                'business_id' => $branch->business_id,
                'branch_id' => $branch->id,
                'action' => 'product.average_cost_updated_from_csv',
                'subject_type' => Product::class,
                'subject_id' => $product->id,
                'old_values' => ['average_cost' => $previousCost],
                'new_values' => ['average_cost' => $cost, 'quantity' => (string) $balance->quantity],
                'reason' => $reason,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return true;
        });
    }
}
