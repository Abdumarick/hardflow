<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Purchase extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'supplier_id', 'purchase_number', 'supplier_reference', 'status', 'purchase_date', 'total_amount', 'created_by', 'ordered_at'];

    protected static function booted(): void
    {
        static::creating(fn (self $purchase) => $purchase->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['status' => PurchaseStatus::class, 'purchase_date' => 'date', 'total_amount' => 'decimal:2', 'ordered_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }
}
