<?php

namespace App\Http\Controllers\Owner;

use App\Actions\NotifyApprovalParticipantsAction;
use App\Enums\ApprovalStatus;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\CashSession;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GovernanceController extends Controller
{
    public function __construct(private NotifyApprovalParticipantsAction $notifier) {}

    public function approvals(Request $request, TenantContext $tenant): View
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::ApprovalsView, $business), 403);

        $query = ApprovalRequest::query()
            ->where('business_id', $business->id)
            ->with(['subject', 'requester', 'decider'])
            ->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('owner.governance.approvals', [
            'business' => $business,
            'approvals' => $query->paginate(30)->withQueryString(),
            'pendingCount' => ApprovalRequest::query()->where('business_id', $business->id)->where('status', 'pending')->count(),
        ]);
    }

    public function decide(Request $request, ApprovalRequest $approval, TenantContext $tenant): RedirectResponse
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::ApprovalsDecide, $business) && $approval->business_id === $business->id, 403);
        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'reason' => ['required', 'string', 'max:1000']]);
        if ($approval->status !== ApprovalStatus::Pending || $approval->requested_by === $request->user()->id) {
            throw ValidationException::withMessages(['approval' => 'This request cannot be decided by this user.']);
        }
        if (! in_array($approval->action_type, ['sale.credit_limit_override', 'cash_session.discrepancy'], true)) {
            throw ValidationException::withMessages(['approval' => 'Use the domain workflow to decide this request.']);
        }
        DB::transaction(function () use ($approval, $data, $request) {
            $approved = $data['decision'] === 'approve';
            $approval->update(['status' => $approved ? ApprovalStatus::Approved : ApprovalStatus::Rejected, 'decided_by' => $request->user()->id, 'decided_at' => now(), 'decision_reason' => trim($data['reason'])]);
            $this->notifier->requester($approval->refresh());
            if ($approval->subject instanceof CashSession) {
                $approval->subject->update($approved ? ['status' => 'closed', 'closed_by' => $request->user()->id, 'closed_at' => now()] : ['status' => 'open', 'expected_closing_cash' => null, 'actual_closing_cash' => null, 'difference_amount' => null, 'difference_reason' => null]);
            }
            AuditLog::query()->create(['user_id' => $request->user()->id, 'business_id' => $approval->business_id, 'branch_id' => $approval->branch_id, 'action' => $approval->action_type.'.'.($approved ? 'approved' : 'rejected'), 'subject_type' => $approval->subject_type, 'subject_id' => $approval->subject_id, 'new_values' => ['approval_status' => $approval->status->value], 'reason' => trim($data['reason'])]);
        });

        return back()->with('status', 'Approval decision recorded.');
    }

    public function notifications(Request $request, TenantContext $tenant): View
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::NotificationsView, $business), 403);

        return view('owner.governance.notifications', [
            'business' => $business,
            'notifications' => $request->user()->notifications()
                ->where('data->business_id', $business->id)
                ->paginate(30),
        ]);
    }

    public function readNotification(Request $request, string $notification, TenantContext $tenant): RedirectResponse
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::NotificationsView, $business), 403);
        $item = $request->user()->notifications()->where('data->business_id', $business->id)->findOrFail($notification);
        $item->markAsRead();

        return back();
    }

    public function audit(Request $request, TenantContext $tenant): View
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::AuditView, $business), 403);
        $query = AuditLog::query()
            ->where('business_id', $business->id)
            ->whereDoesntHave('user', fn ($user) => $user->where('is_super_admin', true))
            ->with(['user', 'branch'])
            ->latest();
        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->string('action').'%');
        }

        return view('owner.governance.audit', [
            'business' => $business,
            'logs' => $query->paginate(40)->withQueryString(),
        ]);
    }
}
