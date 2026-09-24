<?php

namespace App\Models;

use App\Enums\PaymentRecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'customer_id', 'payment_account_id', 'payment_number', 'status', 'payment_date', 'amount', 'external_reference', 'notes', 'received_by', 'reversed_by', 'reversed_at', 'reversal_reason'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['status' => PaymentRecordStatus::class, 'payment_date' => 'date', 'amount' => 'decimal:2', 'reversed_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_account_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
