<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseItem extends Model
{
    protected $fillable = ['business_id', 'purchase_id', 'product_id', 'product_unit_id', 'ordered_quantity', 'received_quantity', 'unit_cost', 'conversion_factor', 'line_total'];

    protected function casts(): array
    {
        return ['ordered_quantity' => 'decimal:4', 'received_quantity' => 'decimal:4', 'unit_cost' => 'decimal:2', 'conversion_factor' => 'decimal:6', 'line_total' => 'decimal:2'];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function receiptItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}
