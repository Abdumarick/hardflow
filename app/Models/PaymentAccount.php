<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PaymentAccount extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'payment_method_id', 'name', 'account_reference', 'is_active'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(AccountTransfer::class, 'to_account_id');
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(AccountTransfer::class, 'from_account_id');
    }

    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
