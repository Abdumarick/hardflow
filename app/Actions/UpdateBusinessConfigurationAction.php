<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateBusinessConfigurationAction
{
    public function updateBusiness(User $actor, Business $business, array $attributes): Business
    {
        $this->authorize($actor, $business);

        return DB::transaction(function () use ($actor, $business, $attributes): Business {
            $business = Business::query()->lockForUpdate()->findOrFail($business->id);
            $old = $business->only(['name', 'phone', 'email', 'address', 'tin', 'vrn', 'currency', 'timezone', 'locale']);
            $fields = Arr::only($attributes, array_keys($old));
            $business->update($fields);

            foreach (Arr::except($attributes, array_keys($old)) as $key => $value) {
                BusinessSetting::query()->updateOrCreate(
                    ['business_id' => $business->id, 'key' => $key],
                    ['value' => $value],
                );
            }

            $this->audit($actor, $business, null, 'business.settings.updated', $business, $old, $attributes);

            return $business->refresh();
        });
    }

    public function updateBranch(User $actor, Business $business, Branch $branch, array $attributes): Branch
    {
        $this->authorize($actor, $business);
        throw_unless($branch->business_id === $business->id, AuthorizationException::class);

        return DB::transaction(function () use ($actor, $business, $branch, $attributes): Branch {
            $branch = Branch::query()->lockForUpdate()->findOrFail($branch->id);
            $old = $branch->only(['name', 'code', 'phone', 'email', 'address']);
            $fields = Arr::only($attributes, array_keys($old));
            $branch->update($fields);

            foreach (Arr::except($attributes, array_keys($old)) as $key => $value) {
                BranchSetting::query()->updateOrCreate(
                    ['business_id' => $business->id, 'branch_id' => $branch->id, 'key' => $key],
                    ['value' => $value],
                );
            }

            $this->audit($actor, $business, $branch, 'branch.settings.updated', $branch, $old, $attributes);

            return $branch->refresh();
        });
    }

    private function authorize(User $actor, Business $business): void
    {
        throw_unless(
            $actor->hasPermissionInBusiness(PermissionName::SettingsUpdate, $business),
            AuthorizationException::class,
        );
    }

    private function audit(User $actor, Business $business, ?Branch $branch, string $action, object $subject, array $old, array $new): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'business_id' => $business->id,
            'branch_id' => $branch?->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->id,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
