<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PermissionName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var array<int, bool> */
    protected array $activeMembershipCache = [];

    /** @var array<int, bool> */
    protected array $activeBranchAccessCache = [];

    /** @var array<string, bool> */
    protected array $businessPermissionCache = [];

    protected $attributes = [
        'is_super_admin' => false,
        'is_active' => true,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'is_super_admin',
        'is_active',
        'dashboard_layout',
        'disabled_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'disabled_at' => 'datetime',
            'password_reset_blocked_at' => 'datetime',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(BusinessUser::class);
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_users')
            ->withPivot(['is_active', 'joined_at', 'disabled_at'])
            ->withTimestamps();
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_users')
            ->withPivot(['business_id', 'is_active'])
            ->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('business_id')
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function hasActiveMembership(Business|int $business): bool
    {
        $businessId = $business instanceof Business ? $business->id : $business;

        return $this->activeMembershipCache[$businessId] ??= $this->is_active && $this->memberships()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->whereNull('disabled_at')
            ->whereHas('business', fn ($query) => $query->where('is_active', true))
            ->exists();
    }

    public function hasActiveBranchAccess(Branch|int $branch): bool
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        return $this->activeBranchAccessCache[$branchId] ??= $this->is_active && $this->branches()
            ->where('branches.id', $branchId)
            ->where('branches.is_active', true)
            ->wherePivot('is_active', true)
            ->whereHas('business', fn ($query) => $query->where('is_active', true))
            ->exists();
    }

    public function hasPermissionInBusiness(PermissionName|string $permission, Business|int $business): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->is_super_admin) {
            return true;
        }

        $businessId = $business instanceof Business ? $business->id : $business;
        $permissionSlug = $permission instanceof PermissionName ? $permission->value : $permission;

        $cacheKey = $businessId.':'.$permissionSlug;

        if (array_key_exists($cacheKey, $this->businessPermissionCache)) {
            return $this->businessPermissionCache[$cacheKey];
        }

        if (! $this->hasActiveMembership($businessId)) {
            return $this->businessPermissionCache[$cacheKey] = false;
        }

        return $this->businessPermissionCache[$cacheKey] = $this->roles()
            ->wherePivot('business_id', $businessId)
            ->where('roles.business_id', $businessId)
            ->where('roles.is_active', true)
            ->whereHas('permissions', fn ($query) => $query->where('permissions.slug', $permissionSlug))
            ->exists();
    }
}
