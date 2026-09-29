<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Models\AccountTransfer;
use App\Models\AuditLog;
use App\Models\PaymentAccount;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestAccountTransferAction
{
    public function execute(User $actor, PaymentAccount $from, PaymentAccount $to, string $amount, string $reason): AccountTransfer
    {
        $business = app(TenantContext::class)->businessOrFail();
        $branchId = app(TenantContext::class)->branchId();
        if (! $actor->hasPermissionInBusiness(PermissionName::AccountTransfersCreate, $business)
            || $from->business_id !== $business->id
            || $to->business_id !== $business->id
            || ($from->branch_id !== null && $from->branch_id !== $branchId)
            || ($to->branch_id !== null && $to->branch_id !== $branchId)) {
            throw new AuthorizationException;
        }
        if ($from->id === $to->id || bccomp($amount, '0', 2) <= 0) {
            throw ValidationException::withMessages(['transfer' => 'Choose two different accounts and a positive amount.']);
        }

        return DB::transaction(function () use ($actor, $from, $to, $amount, $reason, $business, $branchId) {
            $transfer = AccountTransfer::query()->create(['business_id' => $business->id, 'branch_id' => $branchId, 'from_account_id' => $from->id, 'to_account_id' => $to->id, 'transfer_number' => 'TRF-'.now()->format('Ymd').'-'.str_pad((string) (AccountTransfer::query()->where('business_id', $business->id)->count() + 1), 5, '0', STR_PAD_LEFT), 'status' => 'pending', 'amount' => $amount, 'reason' => trim($reason), 'requested_by' => $actor->id]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $business->id, 'branch_id' => $branchId, 'action' => 'account_transfer.requested', 'subject_type' => AccountTransfer::class, 'subject_id' => $transfer->id, 'new_values' => ['amount' => $amount, 'from' => $from->id, 'to' => $to->id], 'reason' => trim($reason)]);

            return $transfer->load('fromAccount', 'toAccount');
        });
    }
}
