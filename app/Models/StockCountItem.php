<?php

namespace App\Models;

use App\Enums\StockStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountItem extends Model
{
    protected $fillable = ['business_id', 'stock_count_id', 'product_id', 'stock_status', 'system_quantity', 'physical_quantity'];

    protected function casts(): array
    {
        return ['stock_status' => StockStatus::class, 'system_quantity' => 'decimal:4', 'physical_quantity' => 'decimal:4'];
    }

    public function count(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'stock_count_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
