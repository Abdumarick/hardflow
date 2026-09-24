<?php

namespace App\Models;

use App\Enums\SupplierReturnStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupplierReturn extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'supplier_id', 'purchase_id', 'return_number', 'status', 'reason', 'total_amount', 'requested_by', 'decided_by', 'decision_reason', 'decided_at'];

    protected static function booted(): void
    {
        static::creating(fn (self $return) => $return->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['status' => SupplierReturnStatus::class, 'total_amount' => 'decimal:2', 'decided_at' => 'datetime'];
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

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierReturnItem::class);
    }
}
