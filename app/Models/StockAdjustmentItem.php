<?php

namespace App\Models;

use App\Enums\StockStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    protected $fillable = ['business_id', 'adjustment_id', 'product_id', 'stock_status', 'system_quantity', 'physical_quantity', 'difference_quantity'];

    protected function casts(): array
    {
        return ['stock_status' => StockStatus::class, 'system_quantity' => 'decimal:4', 'physical_quantity' => 'decimal:4', 'difference_quantity' => 'decimal:4'];
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
