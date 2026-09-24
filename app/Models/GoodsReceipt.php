<?php

namespace App\Models;

use App\Enums\GoodsReceiptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class GoodsReceipt extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'purchase_id', 'receipt_number', 'status', 'received_by', 'confirmed_by', 'received_at', 'confirmed_at', 'notes'];

    protected static function booted(): void
    {
        static::creating(fn (self $receipt) => $receipt->public_id ??= (string) Str::uuid());
        static::updating(function (self $receipt) {
            if ($receipt->getOriginal('status') === GoodsReceiptStatus::Confirmed->value) {
                throw new LogicException('Confirmed goods receipts are immutable.');
            }
        });
        static::deleting(function (self $receipt) {
            if ($receipt->status === GoodsReceiptStatus::Confirmed) {
                throw new LogicException('Confirmed goods receipts cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return ['status' => GoodsReceiptStatus::class, 'received_at' => 'datetime', 'confirmed_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}
