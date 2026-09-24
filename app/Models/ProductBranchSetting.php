<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBranchSetting extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'product_id', 'minimum_stock'];

    protected function casts(): array
    {
        return ['minimum_stock' => 'decimal:4'];
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
