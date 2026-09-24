<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory;

    protected $attributes = [
        'is_main' => false,
        'is_active' => true,
    ];

    protected $fillable = [
        'business_id', 'name', 'code', 'phone', 'email', 'address',
        'is_main', 'is_active', 'disabled_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Branch $branch): void {
            $branch->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'is_active' => 'boolean',
            'disabled_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_users')
            ->withPivot(['business_id', 'is_active'])
            ->withTimestamps();
    }

    public function accessRecords(): HasMany
    {
        return $this->hasMany(BranchUser::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(BranchSetting::class);
    }

    public function numberSequences(): HasMany
    {
        return $this->hasMany(NumberSequence::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
