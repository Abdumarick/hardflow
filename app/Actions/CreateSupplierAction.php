<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Models\Business;
use App\Models\Supplier;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;

class CreateSupplierAction
{
    public function execute(User $actor, Business $business, array $data): Supplier
    {
        if (! $actor->hasPermissionInBusiness(PermissionName::SuppliersCreate, $business) || $business->id !== app(TenantContext::class)->businessId()) {
            throw new AuthorizationException;
        }

        return Supplier::query()->create([
            'business_id' => $business->id,
            'name' => trim($data['name']),
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'tin' => $data['tin'] ?? null,
            'location' => $data['location'] ?? null,
        ]);
    }
}
