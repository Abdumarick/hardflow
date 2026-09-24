<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateProductAction
{
    /** @param array{name:string,sku:string,barcode?:?string,description?:?string,category_id?:?int,brand_id?:?int,is_stock_tracked?:bool,tracks_expiry?:bool} $attributes */
    public function execute(User $actor, Product $product, array $attributes): Product
    {
        Gate::forUser($actor)->authorize('update', $product);

        foreach ([[Category::class, $attributes['category_id'] ?? null, 'category_id'], [Brand::class, $attributes['brand_id'] ?? null, 'brand_id']] as [$model, $id, $key]) {
            if ($id && ! $model::query()->where('business_id', $product->business_id)->whereKey($id)->exists()) {
                throw ValidationException::withMessages([$key => 'The selected value does not belong to this business.']);
            }
        }

        $sku = Str::upper(trim($attributes['sku']));
        $barcode = blank($attributes['barcode'] ?? null) ? null : trim($attributes['barcode']);
        if (Product::query()->where('business_id', $product->business_id)->where('sku', $sku)->whereKeyNot($product->id)->exists()) {
            throw ValidationException::withMessages(['sku' => 'This SKU is already in use.']);
        }
        if ($barcode && Product::query()->where('business_id', $product->business_id)->where('barcode', $barcode)->whereKeyNot($product->id)->exists()) {
            throw ValidationException::withMessages(['barcode' => 'This barcode is already in use.']);
        }

        return DB::transaction(function () use ($actor, $product, $attributes, $sku, $barcode): Product {
            $old = $product->only(['name', 'sku', 'barcode', 'category_id', 'brand_id', 'description', 'is_stock_tracked', 'tracks_expiry']);
            $product->update([
                'name' => trim($attributes['name']), 'sku' => $sku, 'barcode' => $barcode,
                'category_id' => $attributes['category_id'] ?? null, 'brand_id' => $attributes['brand_id'] ?? null,
                'description' => $attributes['description'] ?? null,
                'is_stock_tracked' => $attributes['is_stock_tracked'] ?? $product->is_stock_tracked,
                'tracks_expiry' => $attributes['tracks_expiry'] ?? $product->tracks_expiry,
            ]);
            $this->audit($actor, $product, 'product.updated', $old, $product->only(array_keys($old)));

            return $product->refresh();
        });
    }

    public function setActive(User $actor, Product $product, bool $active, string $reason): Product
    {
        Gate::forUser($actor)->authorize('update', $product);
        if (blank(trim($reason))) {
            throw ValidationException::withMessages(['reason' => 'A reason is required.']);
        }

        return DB::transaction(function () use ($actor, $product, $active, $reason): Product {
            $old = ['is_active' => $product->is_active];
            $product->update(['is_active' => $active]);
            $this->audit($actor, $product, $active ? 'product.activated' : 'product.deactivated', $old, ['is_active' => $active], $reason);

            return $product;
        });
    }

    private function audit(User $actor, Product $product, string $action, array $old, array $new, ?string $reason = null): void
    {
        AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $product->business_id, 'action' => $action, 'subject_type' => Product::class, 'subject_id' => $product->id, 'old_values' => $old, 'new_values' => $new, 'reason' => $reason, 'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent()]);
    }
}
