<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GoodsRelease extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'sale_id', 'release_number', 'status', 'created_by', 'confirmed_by', 'released_at', 'confirmed_at', 'notes'];

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['released_at' => 'datetime', 'confirmed_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReleaseItem::class);
    }
}
