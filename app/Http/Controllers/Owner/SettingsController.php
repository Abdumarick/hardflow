<?php

namespace App\Http\Controllers\Owner;

use App\Actions\UpdateBusinessConfigurationAction;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::SettingsView, $business), 403);

        return view('owner.settings.index', [
            'business' => $business->load(['settings', 'branches.settings']),
            'tab' => in_array($request->string('tab')->toString(), ['company', 'operations', 'invoices', 'branches'], true) ? $request->string('tab')->toString() : 'company',
            'settings' => $business->settings->pluck('value', 'key'),
            'paymentMethods' => PaymentMethod::query()->where('business_id', $business->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function updateBusiness(Request $request, TenantContext $tenant, UpdateBusinessConfigurationAction $update): RedirectResponse
    {
        $business = $tenant->businessOrFail();
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'tin' => ['sometimes', 'nullable', 'string', 'max:80'],
            'vrn' => ['sometimes', 'nullable', 'string', 'max:80'],
            'currency' => ['sometimes', 'required', Rule::in(['TZS', 'USD', 'KES', 'UGX', 'EUR', 'GBP'])],
            'timezone' => ['sometimes', 'required', 'timezone'],
            'locale' => ['sometimes', 'required', Rule::in(['en', 'sw'])],
            'receipt_footer' => ['sometimes', 'nullable', 'string', 'max:500'],
            'fiscal_year_start' => ['sometimes', 'required', 'integer', 'between:1,12'],
            'vat_enabled' => ['sometimes', 'required', 'boolean'],
            'vat_rate' => ['sometimes', 'required', 'numeric', 'between:0,100'],
            'prices_include_tax' => ['sometimes', 'required', 'boolean'],
            'allow_selling_below_cost' => ['sometimes', 'required', 'boolean'],
            'invoice_template' => ['sometimes', 'required', Rule::in(['classic', 'modern', 'compact'])],
        ]);
        $update->updateBusiness($request->user(), $business, $data);

        return back()->with('status', 'Business settings saved successfully.');
    }

    public function updateBranch(Request $request, Branch $branch, TenantContext $tenant, UpdateBusinessConfigurationAction $update): RedirectResponse
    {
        $business = $tenant->businessOrFail();
        abort_unless($branch->business_id === $business->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:30', Rule::unique('branches', 'code')->where('business_id', $business->id)->ignore($branch->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'override_global' => ['required', 'boolean'],
            'currency' => ['nullable', Rule::in(['TZS', 'USD', 'KES', 'UGX', 'EUR', 'GBP'])],
            'vat_rate' => ['nullable', 'numeric', 'between:0,100'],
            'default_payment_method_id' => ['nullable', Rule::exists('payment_methods', 'id')->where('business_id', $business->id)],
            'low_stock_alert' => ['nullable', 'numeric', 'min:0'],
        ]);
        $update->updateBranch($request->user(), $business, $branch, $data);

        return back()->with('status', 'Branch settings saved successfully.');
    }
}
