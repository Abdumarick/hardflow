<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Customer extends Model
{
    protected $fillable = ['business_id', 'name', 'phone', 'email', 'location', 'is_credit_customer', 'credit_limit', 'is_active'];

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['is_credit_customer' => 'boolean', 'credit_limit' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CustomerLedgerEntry::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
