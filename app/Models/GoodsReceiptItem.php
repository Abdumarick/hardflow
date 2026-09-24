<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    protected $fillable = ['business_id', 'goods_receipt_id', 'purchase_item_id', 'received_quantity', 'accepted_quantity', 'damaged_quantity', 'unit_cost', 'difference_reason'];

    protected function casts(): array
    {
        return ['received_quantity' => 'decimal:4', 'accepted_quantity' => 'decimal:4', 'damaged_quantity' => 'decimal:4', 'unit_cost' => 'decimal:2'];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }
}
