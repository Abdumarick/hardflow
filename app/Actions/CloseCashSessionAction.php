<?php

namespace App\Actions;

use App\Enums\ApprovalStatus;
use App\Enums\PermissionName;
use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\CashSession;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseCashSessionAction
{
    public function __construct(private NotifyApprovalParticipantsAction $notifier) {}

    public function execute(User $actor, CashSession $session, string $actualCash, ?string $reason): CashSession
    {
        $business = app(TenantContext::class)->businessOrFail();
        if (! $actor->hasPermissionInBusiness(PermissionName::CashSessionsManage, $business) || $session->business_id !== $business->id || $session->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }
        if (bccomp($actualCash, '0', 2) < 0) {
            throw ValidationException::withMessages(['actual_cash' => 'Actual closing cash cannot be negative.']);
        }

        return DB::transaction(function () use ($actor, $session, $actualCash, $reason) {
            $locked = CashSession::query()->with('account')->lockForUpdate()->findOrFail($session->id);
            if ($locked->status !== 'open') {
                throw ValidationException::withMessages(['session' => 'This cash session is already closed.']);
            }
            $receipts = (string) $locked->account->payments()->where('status', 'confirmed')->where('created_at', '>=', $locked->opened_at)->sum('amount');
            $incoming = (string) $locked->account->incomingTransfers()->where('status', 'approved')->where('decided_at', '>=', $locked->opened_at)->sum('amount');
            $outgoing = (string) $locked->account->outgoingTransfers()->where('status', 'approved')->where('decided_at', '>=', $locked->opened_at)->sum('amount');
            $expected = bcsub(bcadd(bcadd((string) $locked->opening_cash, $receipts, 2), $incoming, 2), $outgoing, 2);
            $difference = bcsub($actualCash, $expected, 2);
            if (bccomp($difference, '0', 2) !== 0 && mb_strlen(trim((string) $reason)) < 10) {
                throw ValidationException::withMessages(['difference_reason' => 'Explain any cash difference using at least 10 characters.']);
            }
            $requiresApproval = bccomp($difference, '0', 2) !== 0;
            $locked->update(['status' => $requiresApproval ? 'pending_approval' : 'closed', 'expected_closing_cash' => $expected, 'actual_closing_cash' => $actualCash, 'difference_amount' => $difference, 'difference_reason' => $reason, 'closed_by' => $requiresApproval ? null : $actor->id, 'closed_at' => $requiresApproval ? null : now()]);
            if ($requiresApproval) {
                $approval = ApprovalRequest::query()->create(['business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'subject_type' => CashSession::class, 'subject_id' => $locked->id, 'action_type' => 'cash_session.discrepancy', 'status' => ApprovalStatus::Pending, 'amount' => abs((float) $difference), 'request_reason' => trim((string) $reason), 'requested_by' => $actor->id]);
                $this->notifier->reviewers($approval);
            }
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'action' => $requiresApproval ? 'cash_session.discrepancy_requested' : 'cash_session.closed', 'subject_type' => CashSession::class, 'subject_id' => $locked->id, 'new_values' => ['expected' => $expected, 'actual' => $actualCash, 'difference' => $difference], 'reason' => $reason]);

            return $locked->refresh();
        });
    }
}
