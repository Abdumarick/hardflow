<?php

namespace App\Models;

use App\Enums\StockStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierReturnItem extends Model
{
    protected $fillable = ['business_id', 'supplier_return_id', 'purchase_item_id', 'product_id', 'stock_status', 'quantity', 'unit_cost', 'line_total'];

    protected function casts(): array
    {
        return ['stock_status' => StockStatus::class, 'quantity' => 'decimal:4', 'unit_cost' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    public function supplierReturn(): BelongsTo
    {
        return $this->belongsTo(SupplierReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }
}
