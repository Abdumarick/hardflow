<?php

namespace App\Actions;

use App\Enums\ApprovalStatus;
use App\Enums\PermissionName;
use App\Enums\SaleStatus;
use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Sale;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmSaleAction
{
    public function __construct(private PostCustomerLedgerEntryAction $ledger, private NotifyApprovalParticipantsAction $notifier) {}

    public function execute(User $actor, Sale $sale): Sale
    {
        $business = $sale->branch->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::SalesConfirm, $business) || $sale->business_id !== app(TenantContext::class)->businessId() || $sale->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }

        $this->requestCreditApprovalIfRequired($actor, $sale);

        return DB::transaction(function () use ($actor, $sale) {
            $locked = Sale::query()->with('customer')->lockForUpdate()->findOrFail($sale->id);
            if ($locked->status !== SaleStatus::Draft || ! $locked->items()->exists()) {
                throw ValidationException::withMessages(['sale' => 'Only a draft sale containing items can be confirmed.']);
            }
            if ($locked->customer?->is_credit_customer && $locked->customer->credit_limit !== null) {
                $currentDebt = (string) $locked->customer->ledgerEntries()->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) AS balance')->value('balance');
                if (bccomp(bcadd($currentDebt, (string) $locked->total_amount, 2), (string) $locked->customer->credit_limit, 2) > 0) {
                    $policy = BusinessSetting::query()->where('business_id', $locked->business_id)->where('key', 'credit_limit_policy')->first()?->value ?? 'block';
                    if ($policy === 'block') {
                        throw ValidationException::withMessages(['sale' => 'This sale would exceed the customer credit limit.']);
                    }
                    if ($policy === 'require_approval' && ! $locked->approvals()->where('action_type', 'sale.credit_limit_override')->where('status', ApprovalStatus::Approved)->exists()) {
                        throw ValidationException::withMessages(['sale' => 'This sale exceeds the credit limit and is waiting for approval.']);
                    }
                }
            }
            $locked->update(['status' => SaleStatus::Confirmed, 'confirmed_by' => $actor->id, 'confirmed_at' => now()]);
            if ($locked->customer) {
                $this->ledger->execute($actor, $locked->customer, 'sale', (string) $locked->total_amount, '0', $locked->branch_id, $locked, $locked->sale_number, 'Confirmed sale');
            }
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'action' => 'sale.confirmed', 'subject_type' => Sale::class, 'subject_id' => $locked->id]);

            return $locked->refresh();
        });
    }

    private function requestCreditApprovalIfRequired(User $actor, Sale $sale): void
    {
        $sale->loadMissing('customer');
        if (! $sale->customer?->is_credit_customer || $sale->customer->credit_limit === null) {
            return;
        }
        $policy = BusinessSetting::query()->where('business_id', $sale->business_id)->where('key', 'credit_limit_policy')->first()?->value ?? 'block';
        $currentDebt = (string) $sale->customer->ledgerEntries()->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) AS balance')->value('balance');
        if ($policy !== 'require_approval' || bccomp(bcadd($currentDebt, (string) $sale->total_amount, 2), (string) $sale->customer->credit_limit, 2) <= 0 || $sale->approvals()->where('action_type', 'sale.credit_limit_override')->whereIn('status', [ApprovalStatus::Pending->value, ApprovalStatus::Approved->value])->exists()) {
            return;
        }
        $approval = ApprovalRequest::query()->create(['business_id' => $sale->business_id, 'branch_id' => $sale->branch_id, 'subject_type' => Sale::class, 'subject_id' => $sale->id, 'action_type' => 'sale.credit_limit_override', 'status' => ApprovalStatus::Pending, 'amount' => $sale->total_amount, 'request_reason' => 'Sale exceeds customer credit limit.', 'requested_by' => $actor->id]);
        $this->notifier->reviewers($approval);
        AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $sale->business_id, 'branch_id' => $sale->branch_id, 'action' => 'sale.credit_approval_requested', 'subject_type' => Sale::class, 'subject_id' => $sale->id, 'new_values' => ['total_amount' => $sale->total_amount, 'credit_limit' => $sale->customer->credit_limit]]);
    }
}
