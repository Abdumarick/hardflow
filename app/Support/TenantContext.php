<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class TenantContext
{
    private ?Business $business = null;

    private ?Branch $branch = null;

    public function setForUser(User $user, Business $business, ?Branch $branch = null): void
    {
        if (! $user->is_active || ! $business->is_active) {
            throw new AuthorizationException('The selected account or business is disabled.');
        }

        if (! $user->is_super_admin && ! $user->hasActiveMembership($business)) {
            throw new AuthorizationException('You do not have access to this business.');
        }

        if ($branch !== null) {
            if ($branch->business_id !== $business->id || ! $branch->is_active) {
                throw new AuthorizationException('The selected branch is not available.');
            }

            if (! $user->is_super_admin && ! $user->hasActiveBranchAccess($branch)) {
                throw new AuthorizationException('You do not have access to this branch.');
            }
        }

        $this->business = $business;
        $this->branch = $branch;
    }

    public function clear(): void
    {
        $this->business = null;
        $this->branch = null;
    }

    public function business(): ?Business
    {
        return $this->business;
    }

    public function businessOrFail(): Business
    {
        abort_if($this->business === null, 403, 'Select a business first.');

        return $this->business;
    }

    public function branch(): ?Branch
    {
        return $this->branch;
    }

    public function businessId(): ?int
    {
        return $this->business?->id;
    }

    public function branchId(): ?int
    {
        return $this->branch?->id;
    }
}
