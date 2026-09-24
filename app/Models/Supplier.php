<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Supplier extends Model
{
    protected $fillable = ['business_id', 'name', 'phone', 'email', 'tin', 'location', 'is_active'];

    protected static function booted(): void
    {
        static::creating(fn (self $supplier) => $supplier->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(SupplierLedgerEntry::class);
    }

    public function outstandingBalance(): string
    {
        $totals = $this->ledgerEntries()->selectRaw('COALESCE(SUM(debit), 0) AS debits, COALESCE(SUM(credit), 0) AS credits')->first();

        return bcsub((string) $totals->debits, (string) $totals->credits, 2);
    }
}
