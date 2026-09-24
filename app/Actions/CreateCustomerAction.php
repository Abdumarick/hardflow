<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;

class CreateCustomerAction
{
    public function execute(User $actor, Business $business, array $data): Customer
    {
        if (! $actor->hasPermissionInBusiness(PermissionName::CustomersCreate, $business) || $business->id !== app(TenantContext::class)->businessId()) {
            throw new AuthorizationException;
        }

        return Customer::query()->create([
            'business_id' => $business->id, 'name' => trim($data['name']), 'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null, 'location' => $data['location'] ?? null,
            'is_credit_customer' => $data['is_credit_customer'] ?? false, 'credit_limit' => $data['credit_limit'] ?? null,
        ]);
    }
}
