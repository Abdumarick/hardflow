<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = ['business_id', 'sale_id', 'product_id', 'product_unit_id', 'quantity', 'released_quantity', 'conversion_factor', 'original_unit_price', 'applied_unit_price', 'discount_amount', 'cost_snapshot', 'line_total', 'price_overridden_by', 'price_override_reason'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'released_quantity' => 'decimal:4', 'conversion_factor' => 'decimal:6', 'original_unit_price' => 'decimal:2', 'applied_unit_price' => 'decimal:2', 'discount_amount' => 'decimal:2', 'cost_snapshot' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
