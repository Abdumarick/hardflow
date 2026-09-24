<?php

namespace App\Models;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Sale extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'customer_id', 'quotation_id', 'sale_number', 'status', 'payment_status', 'fulfillment_status', 'walk_in_name', 'walk_in_phone', 'sale_date', 'due_date', 'subtotal', 'discount_amount', 'total_amount', 'created_by', 'confirmed_by', 'confirmed_at', 'cancelled_by', 'cancelled_at', 'cancellation_reason'];

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['status' => SaleStatus::class, 'payment_status' => PaymentStatus::class, 'fulfillment_status' => FulfillmentStatus::class, 'sale_date' => 'date', 'due_date' => 'date', 'subtotal' => 'decimal:2', 'discount_amount' => 'decimal:2', 'total_amount' => 'decimal:2', 'confirmed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(GoodsRelease::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'subject');
    }
}
