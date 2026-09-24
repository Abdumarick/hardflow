<?php

namespace App\Actions;

use App\Enums\AccountType;
use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\CashSession;
use App\Models\PaymentAccount;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpenCashSessionAction
{
    public function execute(User $actor, PaymentAccount $account, string $openingCash): CashSession
    {
        $business = app(TenantContext::class)->businessOrFail();
        if (! $actor->hasPermissionInBusiness(PermissionName::CashSessionsManage, $business) || $account->business_id !== $business->id || $account->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }
        if ($account->method->type !== AccountType::Cash || bccomp($openingCash, '0', 2) < 0) {
            throw ValidationException::withMessages(['session' => 'A cash account and non-negative opening cash are required.']);
        }

        return DB::transaction(function () use ($actor, $account, $openingCash, $business) {
            if ($account->cashSessions()->where('status', 'open')->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['session' => 'This cash account already has an open session.']);
            }
            $session = CashSession::query()->create(['business_id' => $business->id, 'branch_id' => app(TenantContext::class)->branchId(), 'payment_account_id' => $account->id, 'status' => 'open', 'opening_cash' => $openingCash, 'opened_by' => $actor->id, 'opened_at' => now()]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $business->id, 'branch_id' => $session->branch_id, 'action' => 'cash_session.opened', 'subject_type' => CashSession::class, 'subject_id' => $session->id, 'new_values' => ['opening_cash' => $openingCash]]);

            return $session->load('account');
        });
    }
}
