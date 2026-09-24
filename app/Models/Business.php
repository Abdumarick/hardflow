<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Business extends Model
{
    use HasFactory;

    protected $attributes = [
        'currency' => 'TZS',
        'timezone' => 'Africa/Dar_es_Salaam',
        'locale' => 'en',
        'is_active' => true,
    ];

    protected $fillable = [
        'name', 'slug', 'code', 'phone', 'email', 'address', 'tin', 'vrn',
        'currency', 'timezone', 'locale', 'is_active', 'disabled_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Business $business): void {
            $business->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'disabled_at' => 'datetime'];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(BusinessUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_users')
            ->withPivot(['is_active', 'joined_at', 'disabled_at'])
            ->withTimestamps();
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(BusinessSetting::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function priceLevels(): HasMany
    {
        return $this->hasMany(PriceLevel::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
