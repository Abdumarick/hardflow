<?php

namespace App\Models;

use App\Enums\SupplierLedgerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class SupplierLedgerEntry extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'supplier_id', 'entry_type', 'debit', 'credit', 'source_type', 'source_id', 'entered_by', 'occurred_at', 'notes'];

    protected static function booted(): void
    {
        static::creating(fn (self $entry) => $entry->public_id ??= (string) Str::uuid());
        static::updating(fn () => throw new \LogicException('Supplier ledger entries are immutable.'));
        static::deleting(fn () => throw new \LogicException('Supplier ledger entries are immutable.'));
    }

    protected function casts(): array
    {
        return ['entry_type' => SupplierLedgerType::class, 'debit' => 'decimal:2', 'credit' => 'decimal:2', 'occurred_at' => 'datetime'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
