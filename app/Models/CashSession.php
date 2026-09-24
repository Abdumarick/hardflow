<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class CashSession extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'payment_account_id', 'status', 'opening_cash', 'expected_closing_cash', 'actual_closing_cash', 'difference_amount', 'difference_reason', 'opened_by', 'closed_by', 'opened_at', 'closed_at'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['opening_cash' => 'decimal:2', 'expected_closing_cash' => 'decimal:2', 'actual_closing_cash' => 'decimal:2', 'difference_amount' => 'decimal:2', 'opened_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_account_id');
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'subject');
    }
}
