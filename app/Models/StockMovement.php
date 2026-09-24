<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class StockMovement extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'product_id', 'stock_status', 'movement_type', 'quantity_delta', 'balance_before', 'balance_after', 'unit_cost', 'source_type', 'source_id', 'performed_by', 'reason', 'occurred_at'];

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
        static::updating(fn () => throw new \LogicException('Stock movements are immutable.'));
        static::deleting(fn () => throw new \LogicException('Stock movements are immutable.'));
    }

    protected function casts(): array
    {
        return ['stock_status' => StockStatus::class, 'movement_type' => StockMovementType::class, 'quantity_delta' => 'decimal:4', 'balance_before' => 'decimal:4', 'balance_after' => 'decimal:4', 'unit_cost' => 'decimal:2', 'occurred_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
