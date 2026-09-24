<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class OrderPurchaseAction
{
    public function execute(User $actor, Purchase $purchase): Purchase
    {
        $business = $purchase->branch->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::PurchasesOrder, $business) || $purchase->business_id !== app(TenantContext::class)->businessId() || $purchase->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }
        if ($purchase->status !== PurchaseStatus::Draft || ! $purchase->items()->exists()) {
            throw ValidationException::withMessages(['purchase' => 'Only a draft purchase with items can be ordered.']);
        }
        $purchase->update(['status' => PurchaseStatus::Ordered, 'ordered_at' => now()]);

        return $purchase->refresh();
    }
}
