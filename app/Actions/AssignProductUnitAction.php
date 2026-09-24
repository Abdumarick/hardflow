<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssignProductUnitAction
{
    public function execute(User $actor, Product $product, Unit $unit, string $factor, bool $canPurchase = true, bool $canSell = true): ProductUnit
    {
        Gate::forUser($actor)->authorize('update', $product);
        if ($product->business_id !== $unit->business_id) {
            throw new AuthorizationException;
        }if ((float) $factor <= 0) {
            throw ValidationException::withMessages(['conversion_factor' => 'Conversion factor must be greater than zero.']);
        }

        return DB::transaction(function () use ($actor, $product, $unit, $factor, $canPurchase, $canSell) {
            $row = ProductUnit::query()->updateOrCreate(['product_id' => $product->id, 'unit_id' => $unit->id], ['business_id' => $product->business_id, 'conversion_factor' => $factor, 'is_base' => false, 'can_purchase' => $canPurchase, 'can_sell' => $canSell, 'is_active' => true]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $product->business_id, 'action' => 'product.unit_assigned', 'subject_type' => Product::class, 'subject_id' => $product->id, 'new_values' => ['unit_id' => $unit->id, 'conversion_factor' => $factor]]);

            return $row;
        });
    }
}
