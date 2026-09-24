<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['business_id', 'category_id', 'brand_id', 'sku', 'barcode', 'name', 'description', 'image_path', 'is_stock_tracked', 'tracks_expiry', 'is_active'];

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['is_stock_tracked' => 'boolean', 'tracks_expiry' => 'boolean', 'is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function branchSettings(): HasMany
    {
        return $this->hasMany(ProductBranchSetting::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        } $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('barcode', 'like', $like)->orWhereHas('category', fn (Builder $r) => $r->where('name', 'like', $like))->orWhereHas('brand', fn (Builder $r) => $r->where('name', 'like', $like)));
    }
}
