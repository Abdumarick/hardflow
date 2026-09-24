<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Models\AccountTransfer;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecideAccountTransferAction
{
    public function __construct(private PaymentAccountBalanceAction $balances) {}

    public function execute(User $actor, AccountTransfer $transfer, bool $approve, string $reason): AccountTransfer
    {
        $business = app(TenantContext::class)->businessOrFail();
        if (! $actor->hasPermissionInBusiness(PermissionName::AccountTransfersApprove, $business) || $transfer->business_id !== $business->id || $transfer->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }
        if ($transfer->requested_by === $actor->id) {
            throw ValidationException::withMessages(['transfer' => 'The requester cannot decide their own account transfer.']);
        }

        return DB::transaction(function () use ($actor, $transfer, $approve, $reason) {
            $locked = AccountTransfer::query()->with(['fromAccount', 'toAccount'])->lockForUpdate()->findOrFail($transfer->id);
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['transfer' => 'This transfer has already been decided.']);
            }
            if ($approve && bccomp($this->balances->execute($locked->fromAccount), (string) $locked->amount, 2) < 0) {
                throw ValidationException::withMessages(['transfer' => 'The source account has insufficient confirmed balance.']);
            }
            $status = $approve ? 'approved' : 'rejected';
            $locked->update(['status' => $status, 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_reason' => trim($reason)]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'action' => 'account_transfer.'.$status, 'subject_type' => AccountTransfer::class, 'subject_id' => $locked->id, 'new_values' => ['status' => $status], 'reason' => trim($reason)]);

            return $locked->refresh();
        });
    }
}
