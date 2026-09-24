<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReleaseItem extends Model
{
    protected $fillable = ['business_id', 'goods_release_id', 'sale_item_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4'];
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(GoodsRelease::class, 'goods_release_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}
