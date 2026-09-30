<?php

namespace App\Actions;

use App\Enums\AccountType;
use App\Enums\DefaultRole;
use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\BranchUser;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\BusinessUser;
use App\Models\ExpenseCategory;
use App\Models\NumberSequence;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\PriceLevel;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateBusinessAction
{
    /**
     * @param  array{name:string, code:string, branch_name?:string, branch_code?:string, phone?:?string, email?:?string, address?:?string, tin?:?string, vrn?:?string, currency?:string, timezone?:string, locale?:string}  $attributes
     */
    public function execute(User $actor, User $owner, array $attributes): Business
    {
        Gate::forUser($actor)->authorize('create', Business::class);

        if (! $owner->is_active) {
            throw ValidationException::withMessages(['owner' => 'The selected owner is disabled.']);
        }

        return DB::transaction(function () use ($actor, $owner, $attributes): Business {
            $code = Str::upper(trim($attributes['code']));
            $business = Business::query()->create([
                'name' => $attributes['name'],
                'slug' => $this->uniqueSlug($attributes['name']),
                'code' => $code,
                'phone' => $attributes['phone'] ?? null,
                'email' => $attributes['email'] ?? null,
                'address' => $attributes['address'] ?? null,
                'tin' => $attributes['tin'] ?? null,
                'vrn' => $attributes['vrn'] ?? null,
                'currency' => $attributes['currency'] ?? 'TZS',
                'timezone' => $attributes['timezone'] ?? 'Africa/Dar_es_Salaam',
                'locale' => $attributes['locale'] ?? 'en',
            ]);

            $branch = Branch::query()->create([
                'business_id' => $business->id,
                'name' => $attributes['branch_name'] ?? 'Main Branch',
                'code' => Str::upper($attributes['branch_code'] ?? 'MAIN'),
                'phone' => $attributes['phone'] ?? null,
                'email' => $attributes['email'] ?? null,
                'address' => $attributes['address'] ?? null,
                'is_main' => true,
            ]);

            BusinessUser::query()->create([
                'business_id' => $business->id,
                'user_id' => $owner->id,
                'joined_at' => now(),
            ]);

            BranchUser::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'user_id' => $owner->id,
            ]);

            $permissions = collect(PermissionName::cases())->mapWithKeys(
                fn (PermissionName $permission) => [
                    $permission->value => Permission::query()->firstOrCreate(
                        ['slug' => $permission->value],
                        ['name' => $permission->label(), 'module' => $permission->module()],
                    ),
                ],
            );

            $roles = collect(DefaultRole::cases())->mapWithKeys(
                fn (DefaultRole $role) => [
                    $role->value => Role::query()->create([
                        'business_id' => $business->id,
                        'name' => $role->label(),
                        'slug' => $role->value,
                        'is_system' => true,
                    ]),
                ],
            );

            $ownerRole = $roles->get(DefaultRole::Owner->value);
            $ownerRole->permissions()->sync($permissions->pluck('id'));
            $roles->get(DefaultRole::Manager->value)->permissions()->sync(
                $permissions->whereIn('slug', [PermissionName::UsersView->value, PermissionName::UsersUpdate->value])->pluck('id'),
            );

            UserRole::query()->create([
                'business_id' => $business->id,
                'user_id' => $owner->id,
                'role_id' => $ownerRole->id,
            ]);

            foreach ($this->defaultSettings($business) as $key => $value) {
                BusinessSetting::query()->create([
                    'business_id' => $business->id,
                    'key' => $key,
                    'value' => $value,
                ]);
            }

            foreach (['sale' => 'SAL', 'purchase' => 'PUR', 'quotation' => 'QTN', 'payment' => 'PAY', 'product' => 'PRD'] as $type => $suffix) {
                NumberSequence::query()->create([
                    'business_id' => $business->id,
                    'branch_id' => $branch->id,
                    'type' => $type,
                    'prefix' => $code.'-'.$suffix.'-',
                ]);
            }

            foreach ([['Retail', 'RETAIL', true], ['Wholesale', 'WHOLESALE', false]] as [$name, $levelCode, $isDefault]) {
                PriceLevel::query()->create(['business_id' => $business->id, 'name' => $name, 'code' => $levelCode, 'is_default' => $isDefault, 'is_system' => true]);
            }

            foreach ([['Cash', AccountType::Cash, false], ['Lipa kwa M-Pesa', AccountType::MobileMoney, true], ['Airtel Money', AccountType::MobileMoney, true], ['Tigo Pesa', AccountType::MobileMoney, true], ['HaloPesa', AccountType::MobileMoney, true], ['Other Mobile Money', AccountType::MobileMoney, true], ['Bank Transfer', AccountType::Bank, true], ['Card', AccountType::Card, true]] as [$name, $type, $requiresReference]) {
                PaymentMethod::query()->create(['business_id' => $business->id, 'name' => $name, 'type' => $type, 'requires_reference' => $requiresReference]);
            }

            foreach (['Rent & premises', 'Utilities', 'Transport & delivery', 'Salaries & wages', 'Repairs & maintenance', 'Office & administration', 'Marketing', 'Other'] as $name) {
                ExpenseCategory::query()->create(['business_id' => $business->id, 'name' => $name, 'is_active' => true]);
            }

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'action' => 'business.created',
                'subject_type' => Business::class,
                'subject_id' => $business->id,
                'new_values' => [
                    'name' => $business->name,
                    'code' => $business->code,
                    'owner_user_id' => $owner->id,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $business->load('branches', 'memberships', 'roles.permissions', 'settings');
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Business::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSettings(Business $business): array
    {
        return [
            'currency' => $business->currency,
            'timezone' => $business->timezone,
            'locale' => $business->locale,
            'allow_selling_below_cost' => false,
            'credit_limit_policy' => 'block',
            'invoice_template' => 'classic',
        ];
    }
}
