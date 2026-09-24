<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AccountTransfer extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'from_account_id', 'to_account_id', 'transfer_number', 'status', 'amount', 'reason', 'requested_by', 'decided_by', 'decided_at', 'decision_reason'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'decided_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'to_account_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
