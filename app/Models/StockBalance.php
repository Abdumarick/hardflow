<?php

namespace App\Models;

use App\Enums\StockStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'product_id', 'stock_status', 'quantity', 'average_cost'];

    protected function casts(): array
    {
        return ['stock_status' => StockStatus::class, 'quantity' => 'decimal:4', 'average_cost' => 'decimal:2'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
