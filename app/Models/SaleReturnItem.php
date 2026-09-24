<?php

namespace App\Models;

use App\Enums\StockStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    protected $fillable = ['business_id', 'sale_return_id', 'sale_item_id', 'stock_status', 'quantity', 'unit_amount', 'line_total'];

    protected function casts(): array
    {
        return ['stock_status' => StockStatus::class, 'quantity' => 'decimal:4', 'unit_amount' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}
