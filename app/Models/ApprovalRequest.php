<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class ApprovalRequest extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'subject_type', 'subject_id', 'action_type', 'status', 'amount', 'request_reason', 'requested_by', 'decided_by', 'decided_at', 'decision_reason'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['status' => ApprovalStatus::class, 'amount' => 'decimal:2', 'decided_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
