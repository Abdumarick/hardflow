<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = ['business_id', 'product_unit_id', 'price_level_id', 'amount', 'currency', 'effective_at', 'ended_at', 'is_active', 'current_slot', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'effective_at' => 'datetime', 'ended_at' => 'datetime', 'is_active' => 'boolean', 'current_slot' => 'boolean'];
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function priceLevel(): BelongsTo
    {
        return $this->belongsTo(PriceLevel::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
