<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ProductCsvMatcher
{
    /** @param array<string,mixed> $row */
    public function match(Business $business, array $row): ?Product
    {
        $skuMatch = filled($row['sku'] ?? null)
            ? Product::query()->where('business_id', $business->id)->whereRaw('UPPER(sku) = ?', [strtoupper(trim($row['sku']))])->first()
            : null;
        $barcodeMatch = filled($row['barcode'] ?? null)
            ? Product::query()->where('business_id', $business->id)->where('barcode', trim($row['barcode']))->first()
            : null;

        if ($skuMatch && $barcodeMatch && ! $skuMatch->is($barcodeMatch)) {
            throw ValidationException::withMessages(['file' => 'SKU and barcode identify different existing products.']);
        }
        if ($skuMatch || $barcodeMatch) {
            return ($skuMatch ?? $barcodeMatch)->load(['category', 'brand', 'productUnits.unit', 'productUnits.prices.priceLevel']);
        }

        if (blank($row['category'] ?? null) || blank($row['brand'] ?? null)) {
            return null;
        }
        $matches = Product::query()->where('business_id', $business->id)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($row['name']))])
            ->whereHas('category', fn ($query) => $query->whereRaw('LOWER(name) = ?', [strtolower(trim($row['category']))]))
            ->whereHas('brand', fn ($query) => $query->whereRaw('LOWER(name) = ?', [strtolower(trim($row['brand']))]))
            ->with(['category', 'brand', 'productUnits.unit', 'productUnits.prices.priceLevel'])->limit(2)->get();
        if ($matches->count() > 1) {
            throw ValidationException::withMessages(['file' => 'Name, category and brand match more than one product; provide SKU or barcode.']);
        }

        return $matches->first();
    }
}
