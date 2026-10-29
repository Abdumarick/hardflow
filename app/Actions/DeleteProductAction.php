<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeleteProductAction
{
    public function execute(User $actor, Product $product): void
    {
        Gate::forUser($actor)->authorize('update', $product);

        DB::transaction(function () use ($actor, $product): void {
            try {
                $unitIds = $product->productUnits()->pluck('id');
                DB::table('product_prices')->whereIn('product_unit_id', $unitIds)->delete();
                $product->productUnits()->delete();
                $product->delete();
            } catch (QueryException) {
                throw ValidationException::withMessages([
                    'product' => "{$product->name} cannot be deleted because it has inventory or transaction history. Deactivate it instead to preserve the records.",
                ]);
            }

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'business_id' => $product->business_id,
                'action' => 'product.deleted',
                'subject_type' => Product::class,
                'subject_id' => $product->id,
                'old_values' => ['name' => $product->name, 'sku' => $product->sku],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        });
    }
}
