<?php

namespace App\Models;

use App\Enums\SaleReturnStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SaleReturn extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'sale_id', 'return_number', 'status', 'reason', 'requested_by', 'decided_by', 'decided_at', 'decision_reason', 'total_amount'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['status' => SaleReturnStatus::class, 'decided_at' => 'datetime', 'total_amount' => 'decimal:2'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
