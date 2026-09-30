<?php

namespace App\Http\Controllers\Owner;

use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\PaymentAccount;
use App\Models\Sale;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        $business = $tenant->businessOrFail();
        $branch = $tenant->branch();
        abort_unless($branch !== null && $request->user()->hasPermissionInBusiness(PermissionName::SalesView, $business), 403);

        $search = trim((string) $request->input('search'));
        $state = $request->string('state')->toString();

        $invoices = Sale::query()
            ->where('branch_id', $branch->id)
            ->with('customer')
            ->withCount('items')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('sale_number', 'like', "%{$search}%")
                ->orWhere('walk_in_name', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))))
            ->when($state === 'unpaid', fn ($query) => $query->where('payment_status', 'unpaid'))
            ->when($state === 'partially_paid', fn ($query) => $query->where('payment_status', 'partially_paid'))
            ->when($state === 'paid', fn ($query) => $query->where('payment_status', 'paid'))
            ->when($state === 'draft', fn ($query) => $query->where('status', 'draft'))
            ->when($state === 'pending_release', fn ($query) => $query->whereIn('fulfillment_status', ['on_hold', 'partially_released']))
            ->latest('sale_date')
            ->paginate(20)
            ->withQueryString();

        return view('owner.invoices.index', compact('business', 'branch', 'invoices', 'search', 'state'));
    }

    public function show(Request $request, Sale $sale, TenantContext $tenant): View
    {
        [$business, $branch, $accounts, $template] = $this->invoiceData($request, $sale, $tenant);

        return view('owner.invoices.show', compact('business', 'branch', 'sale', 'accounts', 'template'));
    }

    public function print(Request $request, Sale $sale, TenantContext $tenant): View
    {
        [$business, $branch, $accounts, $template] = $this->invoiceData($request, $sale, $tenant);

        return view('owner.invoices.print', compact('business', 'branch', 'sale', 'accounts', 'template'));
    }

    private function invoiceData(Request $request, Sale $sale, TenantContext $tenant): array
    {
        $business = $tenant->businessOrFail();
        $branch = $tenant->branch();
        abort_unless(
            $branch !== null
            && $sale->business_id === $business->id
            && $sale->branch_id === $branch->id
            && $request->user()->hasPermissionInBusiness(PermissionName::SalesView, $business),
            403,
        );

        $sale->load(['customer', 'items.product', 'items.productUnit.unit', 'paymentAllocations.payment.account.method']);
        $accounts = PaymentAccount::query()->with('method')->where('business_id', $business->id)->where('is_active', true)
            ->where(fn ($query) => $query->where('branch_id', $branch->id)->orWhereNull('branch_id'))
            ->whereHas('method', fn ($query) => $query->where('is_active', true))->orderBy('name')->get();
        $template = (string) ($business->settings()->where('key', 'invoice_template')->first()?->value ?? 'classic');
        $template = in_array($template, ['classic', 'modern', 'compact'], true) ? $template : 'classic';

        return [$business, $branch, $accounts, $template];
    }
}
