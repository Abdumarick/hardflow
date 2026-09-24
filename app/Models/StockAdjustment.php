<?php

namespace App\Models;

use App\Enums\StockAdjustmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StockAdjustment extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'status', 'reason', 'requested_by', 'decided_by', 'decision_reason', 'decided_at'];

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['status' => StockAdjustmentStatus::class, 'decided_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class, 'adjustment_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
