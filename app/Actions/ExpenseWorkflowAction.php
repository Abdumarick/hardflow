<?php

namespace App\Actions;

use App\Enums\ApprovalStatus;
use App\Enums\ExpenseStatus;
use App\Enums\PermissionName;
use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\User;
use App\Notifications\ApprovalActivityNotification;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseWorkflowAction
{
    public function __construct(private PaymentAccountBalanceAction $balances) {}

    public function submit(User $actor, Expense $expense, string $reason): Expense
    {
        $this->authorize($actor, $expense, PermissionName::ExpensesSubmit);
        if ($expense->status !== ExpenseStatus::Draft) {
            throw ValidationException::withMessages(['expense' => 'Only a draft expense can be submitted.']);
        }

        return DB::transaction(function () use ($actor, $expense, $reason) {
            $expense->update(['status' => ExpenseStatus::Pending]);
            $approval = ApprovalRequest::query()->create(['business_id' => $expense->business_id, 'branch_id' => $expense->branch_id, 'subject_type' => Expense::class, 'subject_id' => $expense->id, 'action_type' => 'expense.post', 'status' => ApprovalStatus::Pending, 'amount' => $expense->amount, 'request_reason' => trim($reason), 'requested_by' => $actor->id]);
            User::query()
                ->whereHas('memberships', fn ($query) => $query->where('business_id', $expense->business_id)->where('is_active', true))
                ->whereKeyNot($actor->id)
                ->get()
                ->filter(fn (User $user) => $user->hasPermissionInBusiness(PermissionName::ApprovalsDecide, $expense->business_id))
                ->each(fn (User $user) => $user->notify(new ApprovalActivityNotification($approval, 'requested')));
            $this->audit($actor, $expense, 'expense.submitted', $reason);

            return $expense->refresh();
        });
    }

    public function decide(User $actor, ApprovalRequest $approval, bool $approve, string $reason): Expense
    {
        $expense = $approval->subject;
        $this->authorize($actor, $expense, PermissionName::ExpensesApprove);
        if ($approval->status !== ApprovalStatus::Pending || $expense->status !== ExpenseStatus::Pending) {
            throw ValidationException::withMessages(['approval' => 'This request has already been decided.']);
        }
        if ($approval->requested_by === $actor->id) {
            throw ValidationException::withMessages(['approval' => 'The requester cannot approve their own expense.']);
        }

        return DB::transaction(function () use ($actor, $approval, $expense, $approve, $reason) {
            $approval->update(['status' => $approve ? ApprovalStatus::Approved : ApprovalStatus::Rejected, 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_reason' => trim($reason)]);
            $expense->update(['status' => $approve ? ExpenseStatus::Approved : ExpenseStatus::Draft]);
            $approval->requester->notify(new ApprovalActivityNotification($approval->refresh(), 'decided'));
            $this->audit($actor, $expense, $approve ? 'expense.approved' : 'expense.rejected', $reason);

            return $expense->refresh();
        });
    }

    public function post(User $actor, Expense $expense): Expense
    {
        $this->authorize($actor, $expense, PermissionName::ExpensesPost);
        if ($expense->status !== ExpenseStatus::Approved || ! $expense->account) {
            throw ValidationException::withMessages(['expense' => 'An approved expense with a payment account is required.']);
        }
        if (bccomp($this->balances->execute($expense->account), (string) $expense->amount, 2) < 0) {
            throw ValidationException::withMessages(['expense' => 'The selected payment account has insufficient confirmed balance.']);
        }
        $expense->update(['status' => ExpenseStatus::Posted, 'posted_by' => $actor->id, 'posted_at' => now()]);
        $this->audit($actor, $expense, 'expense.posted');

        return $expense->refresh();
    }

    public function reverse(User $actor, Expense $expense, string $reason): Expense
    {
        $this->authorize($actor, $expense, PermissionName::ExpensesReverse);
        if ($expense->status !== ExpenseStatus::Posted || trim($reason) === '') {
            throw ValidationException::withMessages(['expense' => 'Only a posted expense can be reversed, with a reason.']);
        }
        $expense->update(['status' => ExpenseStatus::Reversed, 'reversed_by' => $actor->id, 'reversed_at' => now(), 'reversal_reason' => trim($reason)]);
        $this->audit($actor, $expense, 'expense.reversed', $reason);

        return $expense->refresh();
    }

    private function authorize(User $actor, Expense $expense, PermissionName $permission): void
    {
        $tenant = app(TenantContext::class);
        if (! $actor->hasPermissionInBusiness($permission, $tenant->businessOrFail()) || $expense->business_id !== $tenant->businessId() || $expense->branch_id !== $tenant->branchId()) {
            throw new AuthorizationException;
        }
    }

    private function audit(User $actor, Expense $expense, string $action, ?string $reason = null): void
    {
        AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $expense->business_id, 'branch_id' => $expense->branch_id, 'action' => $action, 'subject_type' => Expense::class, 'subject_id' => $expense->id, 'new_values' => ['status' => $expense->status->value, 'amount' => $expense->amount], 'reason' => $reason]);
    }
}
