<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Enums\SaleStatus;
use App\Models\AuditLog;
use App\Models\Sale;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelSaleAction
{
    public function __construct(private PostCustomerLedgerEntryAction $ledger) {}

    public function execute(User $actor, Sale $sale, string $reason): Sale
    {
        $business = $sale->branch->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::SalesCancel, $business) || $sale->business_id !== app(TenantContext::class)->businessId() || $sale->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $sale, $reason) {
            $locked = Sale::query()->with(['releases', 'customer', 'paymentAllocations.payment'])->lockForUpdate()->findOrFail($sale->id);
            if ($locked->status === SaleStatus::Cancelled) {
                throw ValidationException::withMessages(['sale' => 'This sale is already cancelled.']);
            }
            if ($locked->releases->contains(fn ($release) => $release->status === 'confirmed')) {
                throw ValidationException::withMessages(['sale' => 'Released goods must be handled through a customer return before this sale can be cancelled.']);
            }
            if ($locked->paymentAllocations->contains(fn ($allocation) => $allocation->payment->status->value === 'confirmed')) {
                throw ValidationException::withMessages(['sale' => 'Reverse confirmed payments before cancelling this sale.']);
            }

            $locked->releases()->where('status', 'draft')->update(['status' => 'cancelled']);
            $locked->update(['status' => SaleStatus::Cancelled, 'cancelled_by' => $actor->id, 'cancelled_at' => now(), 'cancellation_reason' => trim($reason)]);
            if ($locked->customer && $locked->confirmed_at) {
                $this->ledger->execute($actor, $locked->customer, 'sale_cancellation', '0', (string) $locked->total_amount, $locked->branch_id, $locked, $locked->sale_number.'-CANCEL', 'Cancelled sale: '.trim($reason));
            }
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'action' => 'sale.cancelled', 'subject_type' => Sale::class, 'subject_id' => $locked->id, 'new_values' => ['status' => SaleStatus::Cancelled->value], 'reason' => trim($reason)]);

            return $locked->refresh();
        });
    }
}
