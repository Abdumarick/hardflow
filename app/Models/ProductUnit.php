<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUnit extends Model
{
    protected $fillable = ['business_id', 'product_id', 'unit_id', 'conversion_factor', 'is_base', 'can_purchase', 'can_sell', 'is_active'];

    protected function casts(): array
    {
        return ['conversion_factor' => 'decimal:6', 'is_base' => 'boolean', 'can_purchase' => 'boolean', 'can_sell' => 'boolean', 'is_active' => 'boolean'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
