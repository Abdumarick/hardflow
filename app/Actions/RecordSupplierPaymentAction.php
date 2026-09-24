<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Enums\SupplierLedgerType;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class RecordSupplierPaymentAction
{
    public function __construct(private PostSupplierLedgerEntryAction $ledger) {}

    public function execute(User $actor, Supplier $supplier, string $amount, string $notes): SupplierLedgerEntry
    {
        $business = $supplier->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::SuppliersUpdate, $business) || $supplier->business_id !== app(TenantContext::class)->businessId()) {
            throw new AuthorizationException;
        }
        if (! is_numeric($amount) || bccomp($amount, '0', 2) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter a positive payment amount.']);
        }

        return $this->ledger->execute($actor, $supplier, SupplierLedgerType::Payment, '0', $amount, app(TenantContext::class)->branchId(), null, trim($notes));
    }
}
