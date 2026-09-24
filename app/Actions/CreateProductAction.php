<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Business;
use App\Models\Category;
use App\Models\NumberSequence;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Unit;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateProductAction
{
    /** @param array{name:string,sku?:?string,barcode?:?string,description?:?string,category_id?:?int,brand_id?:?int,unit_id:int,is_stock_tracked?:bool,tracks_expiry?:bool} $data */
    public function execute(User $actor, Business $business, array $data): Product
    {
        Gate::forUser($actor)->authorize('create', Product::class);
        if ($business->id !== app(TenantContext::class)->businessId()) {
            throw new AuthorizationException;
        }

        foreach ([[Category::class, $data['category_id'] ?? null, 'category_id'], [Brand::class, $data['brand_id'] ?? null, 'brand_id'], [Unit::class, $data['unit_id'], 'unit_id']] as [$model, $id, $key]) {
            if ($id && ! $model::query()->where('business_id', $business->id)->whereKey($id)->exists()) {
                throw ValidationException::withMessages([$key => 'The selected value does not belong to this business.']);
            }
        }

        return DB::transaction(function () use ($actor, $business, $data): Product {
            $sku = trim((string) ($data['sku'] ?? ''));
            if ($sku === '') {
                $sequence = NumberSequence::query()->where('business_id', $business->id)->where('type', 'product')->lockForUpdate()->firstOrFail();
                $sku = $sequence->prefix.str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT);
                $sequence->increment('next_number');
            }
            $sku = Str::upper($sku);
            $barcode = blank($data['barcode'] ?? null) ? null : trim($data['barcode']);

            if (Product::query()->where('business_id', $business->id)->where('sku', $sku)->exists()) {
                throw ValidationException::withMessages(['sku' => 'This SKU is already in use.']);
            }
            if ($barcode && Product::query()->where('business_id', $business->id)->where('barcode', $barcode)->exists()) {
                throw ValidationException::withMessages(['barcode' => 'This barcode is already in use.']);
            }

            $product = Product::query()->create([
                'business_id' => $business->id, 'category_id' => $data['category_id'] ?? null, 'brand_id' => $data['brand_id'] ?? null,
                'sku' => $sku, 'barcode' => $barcode, 'name' => trim($data['name']), 'description' => $data['description'] ?? null,
                'is_stock_tracked' => $data['is_stock_tracked'] ?? true, 'tracks_expiry' => $data['tracks_expiry'] ?? false,
            ]);
            ProductUnit::query()->create(['business_id' => $business->id, 'product_id' => $product->id, 'unit_id' => $data['unit_id'], 'conversion_factor' => 1, 'is_base' => true]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $business->id, 'action' => 'product.created', 'subject_type' => Product::class, 'subject_id' => $product->id, 'new_values' => ['name' => $product->name, 'sku' => $sku, 'base_unit_id' => $data['unit_id']], 'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent()]);

            return $product->load('category', 'brand', 'productUnits.unit');
        });
    }
}
