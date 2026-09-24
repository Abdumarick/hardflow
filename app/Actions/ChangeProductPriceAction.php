<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\PriceLevel;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ChangeProductPriceAction
{
    public function execute(User $actor, Product $product, ProductUnit $productUnit, PriceLevel $level, string $amount, string $reason): ProductPrice
    {
        Gate::forUser($actor)->authorize('changePrice', $product);

        if ($product->business_id !== $productUnit->business_id
            || $product->id !== $productUnit->product_id
            || $product->business_id !== $level->business_id) {
            throw new AuthorizationException('The selected pricing records do not belong to this product and business.');
        }

        if (! is_numeric($amount) || (float) $amount < 0) {
            throw ValidationException::withMessages(['amount' => 'Price must be zero or greater.']);
        }

        if (blank(trim($reason))) {
            throw ValidationException::withMessages(['reason' => 'A reason is required for every price change.']);
        }

        return DB::transaction(function () use ($actor, $product, $productUnit, $level, $amount, $reason): ProductPrice {
            $old = ProductPrice::query()
                ->where('product_unit_id', $productUnit->id)
                ->where('price_level_id', $level->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            $old?->update(['is_active' => false, 'current_slot' => null, 'ended_at' => now()]);

            $price = ProductPrice::query()->create([
                'business_id' => $product->business_id,
                'product_unit_id' => $productUnit->id,
                'price_level_id' => $level->id,
                'amount' => $amount,
                'currency' => $product->business->currency,
                'effective_at' => now(),
                'current_slot' => true,
                'created_by' => $actor->id,
            ]);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'business_id' => $product->business_id,
                'action' => 'product.price_changed',
                'subject_type' => Product::class,
                'subject_id' => $product->id,
                'old_values' => ['amount' => $old?->amount],
                'new_values' => ['amount' => $price->amount, 'price_level_id' => $level->id, 'product_unit_id' => $productUnit->id],
                'reason' => trim($reason),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $price;
        });
    }
}
