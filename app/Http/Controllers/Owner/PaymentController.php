<?php

namespace App\Http\Controllers\Owner;

use App\Actions\CloseCashSessionAction;
use App\Actions\DecideAccountTransferAction;
use App\Actions\OpenCashSessionAction;
use App\Actions\PaymentAccountBalanceAction;
use App\Actions\RecordCustomerPaymentAction;
use App\Actions\RecordSalePaymentAction;
use App\Actions\RequestAccountTransferAction;
use App\Actions\ReverseCustomerPaymentAction;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\AccountTransfer;
use App\Models\BusinessSetting;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request, TenantContext $tenant, PaymentAccountBalanceAction $balances): View
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::PaymentsView, $business), 403);
        $branch = $tenant->branch();
        abort_if($branch === null, 422, 'Select a branch first.');

        $customers = Customer::query()->where('business_id', $business->id)->withSum('ledgerEntries as ledger_debits', 'debit')->withSum('ledgerEntries as ledger_credits', 'credit')->orderBy('name')->get();
        $sales = Sale::query()->where('branch_id', $branch->id)->whereNotNull('customer_id')->where('status', 'confirmed')->where('payment_status', '!=', 'paid')->with(['customer', 'paymentAllocations.payment'])->latest()->get()->map(function ($sale) {
            $sale->outstanding_amount = bcsub((string) $sale->total_amount, (string) $sale->paymentAllocations->where('payment.status.value', 'confirmed')->sum('amount'), 2);

            return $sale;
        });
        $aging = ['current' => 0.0, 'days_1_30' => 0.0, 'days_31_60' => 0.0, 'days_61_plus' => 0.0];
        foreach ($sales as $sale) {
            $days = $sale->due_date?->isPast() ? $sale->due_date->diffInDays(now()) : 0;
            $bucket = $days === 0 ? 'current' : ($days <= 30 ? 'days_1_30' : ($days <= 60 ? 'days_31_60' : 'days_61_plus'));
            $aging[$bucket] += (float) $sale->outstanding_amount;
        }

        $walkInSales = Sale::query()->where('branch_id', $branch->id)->whereNull('customer_id')->where('status', 'confirmed')->where('payment_status', '!=', 'paid')->with('paymentAllocations.payment')->latest()->get()->map(function ($sale) {
            $sale->outstanding_amount = bcsub((string) $sale->total_amount, (string) $sale->paymentAllocations->where('payment.status.value', 'confirmed')->sum('amount'), 2);

            return $sale;
        });

        $accounts = PaymentAccount::query()->where('business_id', $business->id)->with('method')->orderBy('name')->get()->each(function ($account) use ($balances) {
            $account->current_balance = $balances->execute($account);
            $account->confirmed_receipts = $account->current_balance;
        });

        return view('owner.payments.index', ['business' => $business, 'branch' => $branch, 'aging' => $aging, 'creditPolicy' => BusinessSetting::query()->where('business_id', $business->id)->where('key', 'credit_limit_policy')->first()?->value ?? 'block', 'methods' => PaymentMethod::query()->where('business_id', $business->id)->where('is_active', true)->get(), 'accounts' => $accounts, 'customers' => $customers, 'sales' => $sales, 'walkInSales' => $walkInSales, 'payments' => Payment::query()->where('branch_id', $branch->id)->with(['customer', 'account.method', 'allocations.sale'])->latest()->limit(100)->get(), 'transfers' => AccountTransfer::query()->where('branch_id', $branch->id)->with(['fromAccount', 'toAccount', 'requester'])->latest()->get(), 'cashSessions' => CashSession::query()->where('branch_id', $branch->id)->with(['account.method', 'opener'])->latest()->limit(50)->get()]);
    }

    public function storeAccount(Request $request, TenantContext $tenant): RedirectResponse
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::PaymentAccountsManage, $business), 403);
        $data = $request->validate(['payment_method_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:255'], 'account_reference' => ['nullable', 'string', 'max:255']]);
        $method = PaymentMethod::query()->where('business_id', $business->id)->findOrFail($data['payment_method_id']);
        PaymentAccount::query()->create(['business_id' => $business->id, 'branch_id' => $tenant->branchId(), 'payment_method_id' => $method->id, 'name' => $data['name'], 'account_reference' => $data['account_reference'] ?? null]);

        return back()->with('status', 'Payment account created.');
    }

    public function updateCreditPolicy(Request $request, TenantContext $tenant): RedirectResponse
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::PaymentAccountsManage, $business), 403);
        $data = $request->validate(['credit_limit_policy' => ['required', 'in:warn,require_approval,block']]);
        BusinessSetting::query()->updateOrCreate(['business_id' => $business->id, 'key' => 'credit_limit_policy'], ['value' => $data['credit_limit_policy']]);

        return back()->with('status', 'Customer credit-limit policy updated.');
    }

    public function store(Request $request, Customer $customer, RecordCustomerPaymentAction $action): RedirectResponse
    {
        $data = $request->validate(['payment_account_id' => ['required', 'integer'], 'amount' => ['required', 'numeric', 'gt:0'], 'payment_date' => ['nullable', 'date'], 'external_reference' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:2000'], 'allocations' => ['required', 'array', 'min:1'], 'allocations.*.sale_id' => ['required', 'integer', 'distinct'], 'allocations.*.amount' => ['required', 'numeric', 'gt:0']]);
        $account = PaymentAccount::query()->findOrFail($data['payment_account_id']);
        $action->execute($request->user(), $customer, $account, $data);

        return back()->with('status', 'Customer payment recorded and allocated.');
    }

    public function reverse(Request $request, Payment $payment, ReverseCustomerPaymentAction $action): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $action->execute($request->user(), $payment, $data['reason']);

        return back()->with('status', 'Payment reversed and customer balances recalculated.');
    }

    public function storeSalePayment(Request $request, Sale $sale, RecordSalePaymentAction $action): RedirectResponse
    {
        $data = $request->validate(['payment_account_id' => ['required', 'integer'], 'amount' => ['required', 'numeric', 'gt:0'], 'external_reference' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $account = PaymentAccount::query()->findOrFail($data['payment_account_id']);
        $payment = $action->execute($request->user(), $sale, $account, (string) $data['amount'], $data['external_reference'] ?? null, $data['notes'] ?? null);

        return redirect()->route('owner.payments.receipt', $payment)->with('status', 'Payment recorded.');
    }

    public function receipt(Request $request, Payment $payment, TenantContext $tenant): View
    {
        abort_unless($payment->business_id === $tenant->businessId() && $request->user()->hasPermissionInBusiness(PermissionName::PaymentsView, $tenant->businessOrFail()), 403);

        return view('owner.payments.receipt', ['business' => $tenant->businessOrFail(), 'branch' => $tenant->branch(), 'payment' => $payment->load(['customer', 'account.method', 'allocations.sale'])]);
    }

    public function requestTransfer(Request $request, RequestAccountTransferAction $action): RedirectResponse
    {
        $data = $request->validate(['from_account_id' => ['required', 'integer', 'different:to_account_id'], 'to_account_id' => ['required', 'integer'], 'amount' => ['required', 'numeric', 'gt:0'], 'reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $action->execute($request->user(), PaymentAccount::query()->findOrFail($data['from_account_id']), PaymentAccount::query()->findOrFail($data['to_account_id']), (string) $data['amount'], $data['reason']);

        return back()->with('status', 'Account transfer submitted for approval.');
    }

    public function decideTransfer(Request $request, AccountTransfer $transfer, DecideAccountTransferAction $action): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $action->execute($request->user(), $transfer, $data['decision'] === 'approve', $data['reason']);

        return back()->with('status', 'Account transfer decision recorded.');
    }

    public function openCashSession(Request $request, OpenCashSessionAction $action): RedirectResponse
    {
        $data = $request->validate(['payment_account_id' => ['required', 'integer'], 'opening_cash' => ['required', 'numeric', 'min:0']]);
        $action->execute($request->user(), PaymentAccount::query()->findOrFail($data['payment_account_id']), (string) $data['opening_cash']);

        return back()->with('status', 'Cash session opened.');
    }

    public function closeCashSession(Request $request, CashSession $session, CloseCashSessionAction $action): RedirectResponse
    {
        $data = $request->validate(['actual_closing_cash' => ['required', 'numeric', 'min:0'], 'difference_reason' => ['nullable', 'string', 'max:2000']]);
        $action->execute($request->user(), $session, (string) $data['actual_closing_cash'], $data['difference_reason'] ?? null);

        return back()->with('status', 'Cash session closed and reconciled.');
    }

    public function statement(Request $request, Customer $customer, TenantContext $tenant): View
    {
        abort_unless($customer->business_id === $tenant->businessId() && $request->user()->hasPermissionInBusiness(PermissionName::PaymentsView, $tenant->businessOrFail()), 403);

        return view('owner.payments.statement', ['business' => $tenant->businessOrFail(), 'customer' => $customer, 'entries' => $customer->ledgerEntries()->with('source')->oldest('occurred_at')->get()]);
    }
}
