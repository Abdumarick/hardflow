<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerLedgerEntry extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'customer_id', 'entry_type', 'debit', 'credit', 'source_type', 'source_id', 'reference', 'description', 'occurred_at', 'created_by'];

    protected function casts(): array
    {
        return ['debit' => 'decimal:2', 'credit' => 'decimal:2', 'occurred_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
