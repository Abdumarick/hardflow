<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PriceLevel extends Model
{
    use HasFactory;

    protected $fillable = ['business_id', 'name', 'code', 'description', 'is_default', 'is_system', 'is_active'];

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_system' => 'boolean', 'is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
