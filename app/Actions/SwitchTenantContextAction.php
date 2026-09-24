<?php

namespace App\Actions;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Support\TenantContext;

class SwitchTenantContextAction
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function execute(User $user, Business $business, ?Branch $branch): void
    {
        $this->tenantContext->setForUser($user, $business, $branch);

        session()->put('tenant.business_id', $business->id);
        session()->put('tenant.branch_id', $branch?->id);
    }
}
