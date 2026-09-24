<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quotation extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'customer_id', 'quotation_number', 'document_type', 'status', 'walk_in_name', 'walk_in_phone', 'walk_in_location', 'quotation_date', 'valid_until', 'subtotal', 'discount_amount', 'total_amount', 'created_by', 'converted_sale_id'];

    protected static function booted(): void
    {
        static::creating(fn (self $quotation) => $quotation->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['quotation_date' => 'date', 'valid_until' => 'date', 'subtotal' => 'decimal:2', 'discount_amount' => 'decimal:2', 'total_amount' => 'decimal:2'];
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
        return $this->hasMany(QuotationItem::class);
    }

    public function convertedSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'converted_sale_id');
    }
}
