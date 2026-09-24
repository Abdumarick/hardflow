<?php

namespace App\Http\Controllers\Owner;

use App\Enums\PermissionName;
use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Sale;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::CustomersView, $business), 403);
        $search = trim((string) $request->input('search'));
        $perPage = in_array((int) $request->integer('per_page', 12), [6, 12, 24, 48], true) ? (int) $request->integer('per_page', 12) : 12;
        $customers = Customer::query()->where('business_id', $business->id)
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when(in_array($request->input('status'), ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
            ->withSum('ledgerEntries as total_debits', 'debit')->withSum('ledgerEntries as total_credits', 'credit')->orderBy('name')->paginate($perPage)->withQueryString();

        $selected = null;
        if ($request->filled('customer')) {
            $selected = Customer::query()->where('business_id', $business->id)->where('public_id', $request->input('customer'))->firstOrFail();
            $selected->load([
                'sales' => fn ($query) => $query->where('branch_id', $tenant->branchId())->with(['items.product', 'paymentAllocations.payment.account.method'])->latest(),
                'payments' => fn ($query) => $query->where('branch_id', $tenant->branchId())->with('account.method')->latest(),
                'ledgerEntries' => fn ($query) => $query->where('branch_id', $tenant->branchId())->latest('occurred_at'),
            ]);
        }

        return view('owner.customers.index', compact('business', 'customers', 'selected'));
    }

    public function update(Request $request, Customer $customer, TenantContext $tenant): RedirectResponse
    {
        $business = $tenant->businessOrFail();
        abort_unless($customer->business_id === $business->id && $request->user()->hasPermissionInBusiness(PermissionName::CustomersUpdate, $business), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email'], 'location' => ['nullable', 'string', 'max:2000'], 'is_credit_customer' => ['nullable', 'boolean'], 'credit_limit' => ['nullable', 'numeric', 'min:0']]);
        $old = $customer->only(['name', 'phone', 'email', 'location', 'is_credit_customer', 'credit_limit']);
        $customer->update([...$data, 'is_credit_customer' => (bool) ($data['is_credit_customer'] ?? false)]);
        AuditLog::query()->create(['user_id' => $request->user()->id, 'business_id' => $business->id, 'action' => 'customer.updated', 'subject_type' => Customer::class, 'subject_id' => $customer->id, 'old_values' => $old, 'new_values' => $customer->only(array_keys($old))]);

        return back()->with('status', 'Customer details updated.');
    }

    public function setActive(Request $request, Customer $customer, TenantContext $tenant): RedirectResponse
    {
        $business = $tenant->businessOrFail();
        abort_unless($customer->business_id === $business->id && $request->user()->hasPermissionInBusiness(PermissionName::CustomersUpdate, $business), 403);
        $data = $request->validate(['is_active' => ['required', 'boolean'], 'reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $customer->update(['is_active' => (bool) $data['is_active']]);
        AuditLog::query()->create(['user_id' => $request->user()->id, 'business_id' => $business->id, 'action' => $data['is_active'] ? 'customer.activated' : 'customer.deactivated', 'subject_type' => Customer::class, 'subject_id' => $customer->id, 'new_values' => ['is_active' => (bool) $data['is_active']], 'reason' => $data['reason']]);

        return back()->with('status', $data['is_active'] ? 'Customer activated.' : 'Customer deactivated.');
    }

    public function updateSale(Request $request, Sale $sale, TenantContext $tenant): RedirectResponse
    {
        $business = $tenant->businessOrFail();
        abort_unless($sale->business_id === $business->id && $sale->branch_id === $tenant->branchId() && $request->user()->hasPermissionInBusiness(PermissionName::SalesCreate, $business), 403);
        if ($sale->status !== SaleStatus::Draft) throw ValidationException::withMessages(['sale' => 'Only draft sales can be edited.']);
        $data = $request->validate(['due_date' => ['nullable', 'date', 'after_or_equal:today']]);
        $old = $sale->only('due_date');
        $sale->update(['due_date' => $data['due_date'] ?? null]);
        AuditLog::query()->create(['user_id' => $request->user()->id, 'business_id' => $business->id, 'branch_id' => $sale->branch_id, 'action' => 'sale.updated_from_customer_panel', 'subject_type' => Sale::class, 'subject_id' => $sale->id, 'old_values' => $old, 'new_values' => $sale->only('due_date')]);

        return back()->with('status', 'Draft sale updated.');
    }

    public function destroySale(Request $request, Sale $sale, TenantContext $tenant): RedirectResponse
    {
        $business = $tenant->businessOrFail();
        abort_unless($sale->business_id === $business->id && $sale->branch_id === $tenant->branchId() && $request->user()->hasPermissionInBusiness(PermissionName::SalesCancel, $business), 403);
        if ($sale->status !== SaleStatus::Draft || $sale->releases()->exists() || $sale->paymentAllocations()->exists()) throw ValidationException::withMessages(['sale' => 'Only an unpaid draft with no goods releases can be deleted.']);
        DB::transaction(function () use ($request, $sale, $business) {
            AuditLog::query()->create(['user_id' => $request->user()->id, 'business_id' => $business->id, 'branch_id' => $sale->branch_id, 'action' => 'sale.deleted_draft', 'subject_type' => Sale::class, 'subject_id' => $sale->id, 'old_values' => ['sale_number' => $sale->sale_number, 'total_amount' => $sale->total_amount]]);
            $sale->items()->delete();
            $sale->delete();
        });

        return back()->with('status', 'Draft sale deleted.');
    }
}
