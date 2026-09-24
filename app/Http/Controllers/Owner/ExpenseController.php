<?php

namespace App\Http\Controllers\Owner;

use App\Actions\ExpenseWorkflowAction;
use App\Actions\PaymentAccountBalanceAction;
use App\Enums\ExpenseStatus;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentAccount;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function create(Request $request, TenantContext $tenant, PaymentAccountBalanceAction $balances): View
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::ExpensesCreate, $business), 403);
        $branch = $tenant->branch();
        abort_if($branch === null, 422, 'Select a branch first.');
        $accounts = PaymentAccount::query()->where('business_id', $business->id)->where(fn ($query) => $query->whereNull('branch_id')->orWhere('branch_id', $branch->id))->where('is_active', true)->with('method')->get()->each(fn ($account) => $account->current_balance = $balances->execute($account));

        return view('owner.expenses.create', ['business' => $business, 'branch' => $branch, 'accounts' => $accounts, 'categories' => ExpenseCategory::query()->where('business_id', $business->id)->where('is_active', true)->orderBy('name')->get()]);
    }

    public function index(Request $request, TenantContext $tenant, PaymentAccountBalanceAction $balances): View
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::ExpensesView, $business), 403);
        $branch = $tenant->branch();
        abort_if($branch === null, 422, 'Select a branch first.');
        $query = Expense::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->with(['category', 'account.method', 'creator', 'approvals.requester', 'approvals.decider'])->latest('expense_date');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%')->orWhere('expense_number', 'like', '%'.$request->string('search').'%')->orWhere('vendor', 'like', '%'.$request->string('search').'%'));
        }
        if ($request->filled('category')) {
            $query->where('expense_category_id', $request->integer('category'));
        }
        if ($request->filled('account')) {
            $query->where('payment_account_id', $request->integer('account'));
        }
        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', $request->date('to'));
        }
        $expenses = $query->get();
        $accounts = PaymentAccount::query()->where('business_id', $business->id)->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $branch->id))->where('is_active', true)->with('method')->get()->each(fn ($account) => $account->current_balance = $balances->execute($account));

        return view('owner.expenses.index', compact('business', 'branch', 'expenses', 'accounts') + ['categories' => ExpenseCategory::query()->where('business_id', $business->id)->where('is_active', true)->orderBy('name')->get(), 'pendingCount' => Expense::query()->where('branch_id', $branch->id)->where('status', ExpenseStatus::Pending)->count(), 'postedTotal' => Expense::query()->where('branch_id', $branch->id)->where('status', ExpenseStatus::Posted)->sum('amount'), 'monthTotal' => Expense::query()->where('branch_id', $branch->id)->where('status', ExpenseStatus::Posted)->whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount')]);
    }

    public function export(Request $request, TenantContext $tenant): StreamedResponse
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::ExpensesView, $business), 403);
        $query = Expense::query()->where('business_id', $business->id)->where('branch_id', $tenant->branchId())->with(['category', 'account']);
        foreach (['status' => 'status', 'category' => 'expense_category_id', 'account' => 'payment_account_id'] as $input => $column) {
            if ($request->filled($input)) {
                $query->where($column, $request->input($input));
            }
        }
        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', $request->date('to'));
        }

        return response()->streamDownload(function () use ($query) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Expense number', 'Date', 'Title', 'Category', 'Vendor', 'Account', 'Status', 'Amount TZS']);
            $query->orderBy('expense_date')->each(function (Expense $expense) use ($stream) {
                fputcsv($stream, [$expense->expense_number, $expense->expense_date->toDateString(), $expense->title, $expense->category->name, $expense->vendor, $expense->account?->name, $expense->status->value, $expense->amount]);
            });
            fclose($stream);
        }, 'hardflow-expenses-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request, TenantContext $tenant, ExpenseWorkflowAction $workflow): RedirectResponse
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::ExpensesCreate, $business), 403);
        $data = $request->validate(['expense_category_id' => ['required', 'integer'], 'payment_account_id' => ['nullable', 'integer'], 'title' => ['required', 'string', 'max:255'], 'vendor' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:2000'], 'amount' => ['required', 'numeric', 'gt:0'], 'expense_date' => ['required', 'date'], 'receipt_reference' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:2000'], 'intent' => ['nullable', 'in:draft,submit'], 'approval_reason' => ['nullable', 'required_if:intent,submit', 'string', 'max:1000']]);
        $category = ExpenseCategory::query()->where('business_id', $business->id)->findOrFail($data['expense_category_id']);
        $account = isset($data['payment_account_id']) ? PaymentAccount::query()->where('business_id', $business->id)->findOrFail($data['payment_account_id']) : null;
        $number = $business->code.'-EXP-'.now()->format('Ym').'-'.str_pad((string) (Expense::query()->where('business_id', $business->id)->count() + 1), 5, '0', STR_PAD_LEFT);
        $expense = Expense::query()->create(collect($data)->except(['intent', 'approval_reason'])->all() + ['business_id' => $business->id, 'branch_id' => $tenant->branchId(), 'expense_category_id' => $category->id, 'payment_account_id' => $account?->id, 'expense_number' => $number, 'status' => ExpenseStatus::Draft, 'created_by' => $request->user()->id]);
        if (($data['intent'] ?? 'draft') === 'submit') {
            $workflow->submit($request->user(), $expense, $data['approval_reason']);
        }

        return redirect()->route('owner.expenses.index')->with('status', ($data['intent'] ?? 'draft') === 'submit' ? 'Expense created and sent for approval.' : 'Expense saved as draft.');
    }

    public function submit(Request $request, Expense $expense, ExpenseWorkflowAction $action): RedirectResponse
    {
        $action->submit($request->user(), $expense, $request->validate(['reason' => ['required', 'string', 'max:1000']])['reason']);

        return back()->with('status', 'Expense sent for approval.');
    }

    public function decide(Request $request, ApprovalRequest $approval, ExpenseWorkflowAction $action): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'reason' => ['required', 'string', 'max:1000']]);
        $action->decide($request->user(), $approval, $data['decision'] === 'approve', $data['reason']);

        return back()->with('status', 'Approval decision recorded.');
    }

    public function post(Request $request, Expense $expense, ExpenseWorkflowAction $action): RedirectResponse
    {
        $action->post($request->user(), $expense);

        return back()->with('status', 'Expense posted to the payment account.');
    }

    public function reverse(Request $request, Expense $expense, ExpenseWorkflowAction $action): RedirectResponse
    {
        $action->reverse($request->user(), $expense, $request->validate(['reason' => ['required', 'string', 'max:1000']])['reason']);

        return back()->with('status', 'Expense reversed and the account balance restored.');
    }
}
