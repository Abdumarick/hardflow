<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Expense extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'expense_category_id', 'payment_account_id', 'expense_number', 'status', 'title', 'vendor', 'description', 'amount', 'expense_date', 'receipt_reference', 'notes', 'created_by', 'posted_by', 'posted_at', 'reversed_by', 'reversed_at', 'reversal_reason'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['status' => ExpenseStatus::class, 'amount' => 'decimal:2', 'expense_date' => 'date', 'posted_at' => 'datetime', 'reversed_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'subject');
    }
}
