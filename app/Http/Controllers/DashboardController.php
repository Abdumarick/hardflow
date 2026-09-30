<?php

namespace App\Http\Controllers;

use App\Actions\PaymentAccountBalanceAction;
use App\Enums\PermissionName;
use App\Models\ApprovalRequest;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Expense;
use App\Models\PaymentAccount;
use App\Models\Sale;
use App\Models\StockBalance;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function updateAppearance(Request $request): RedirectResponse
    {
        $data = $request->validate(['dashboard_layout' => ['required', 'in:classic,smartflow,cards']]);
        $request->user()->update($data);

        return back()->with('status', __('dashboard.appearance_saved'));
    }

    public function updateLocale(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', 'in:en,sw']]);
        $request->session()->put('ui.locale', $data['locale']);

        return back();
    }

    public function __invoke(Request $request, PaymentAccountBalanceAction $balances): View
    {
        $businesses = $request->user()->is_super_admin
            ? Business::query()->with('branches')->orderBy('name')->get()
            : $request->user()->businesses()
                ->wherePivot('is_active', true)
                ->where('businesses.is_active', true)
                ->with('branches')
                ->orderBy('name')
                ->get();

        $currentBusiness = $businesses->firstWhere('id', session('tenant.business_id'));
        $currentBranch = $currentBusiness ? Branch::query()->where('business_id', $currentBusiness->id)->where('id', session('tenant.branch_id'))->first() : null;
        $dashboardData = null;
        $dashboardModules = collect();
        if ($currentBusiness && $currentBranch) {
            $can = fn (PermissionName $permission): bool => $request->user()->hasPermissionInBusiness($permission, $currentBusiness);
            $dashboardModules = collect([
                ['group' => __('ui.operations'), 'label' => __('ui.dashboard'), 'route' => 'dashboard', 'icon' => 'D', 'visible' => true],
                ['group' => __('ui.operations'), 'label' => __('ui.sales'), 'route' => 'owner.sales.index', 'icon' => 'S', 'visible' => $can(PermissionName::SalesView)],
                ['group' => __('ui.operations'), 'label' => 'Invoices', 'route' => 'owner.invoices.index', 'icon' => 'I', 'visible' => $can(PermissionName::SalesView)],
                ['group' => __('ui.operations'), 'label' => 'Customers', 'route' => 'owner.customers.index', 'icon' => 'C', 'visible' => $can(PermissionName::CustomersView)],
                ['group' => __('ui.inventory'), 'label' => __('ui.products'), 'route' => 'owner.catalogue.index', 'icon' => 'P', 'visible' => $can(PermissionName::ProductsView)],
                ['group' => __('ui.inventory'), 'label' => __('ui.inventory'), 'route' => 'owner.inventory.index', 'icon' => 'I', 'visible' => $can(PermissionName::InventoryView)],
                ['group' => __('ui.procurement'), 'label' => __('ui.purchases'), 'route' => 'owner.purchases.index', 'icon' => 'P', 'visible' => $can(PermissionName::PurchasesView)],
                ['group' => __('ui.finance'), 'label' => __('ui.expenses'), 'route' => 'owner.expenses.index', 'icon' => 'E', 'visible' => $can(PermissionName::ExpensesView)],
                ['group' => __('ui.finance'), 'label' => __('ui.payments'), 'route' => 'owner.payments.index', 'icon' => '$', 'visible' => $can(PermissionName::PaymentsView)],
                ['group' => __('ui.management'), 'label' => __('ui.approvals'), 'route' => 'owner.approvals.index', 'icon' => 'A', 'visible' => $can(PermissionName::ApprovalsView)],
                ['group' => __('ui.management'), 'label' => __('ui.reports'), 'route' => 'owner.reports.index', 'icon' => 'R', 'visible' => $can(PermissionName::ReportsView)],
                ['group' => __('ui.management'), 'label' => __('ui.staff'), 'route' => 'owner.staff.index', 'icon' => 'S', 'visible' => $can(PermissionName::UsersView)],
                ['group' => __('ui.management'), 'label' => __('ui.users_roles'), 'route' => 'owner.roles.index', 'icon' => 'R', 'visible' => $can(PermissionName::UsersManageRoles)],
                ['group' => __('ui.management'), 'label' => __('ui.audit_logs'), 'route' => 'owner.audit.index', 'icon' => 'A', 'visible' => $can(PermissionName::AuditView)],
                ['group' => __('ui.system'), 'label' => __('ui.settings'), 'route' => 'owner.settings.index', 'icon' => 'S', 'visible' => $can(PermissionName::SettingsView)],
                ['group' => __('ui.system'), 'label' => __('ui.notifications'), 'route' => 'owner.notifications.index', 'icon' => 'N', 'visible' => $can(PermissionName::NotificationsView)],
                ['group' => __('ui.system'), 'label' => __('ui.profile'), 'route' => 'profile', 'icon' => 'P', 'visible' => true],
            ])->when($request->user()->is_super_admin, fn ($modules) => $modules->push(['group' => __('ui.management'), 'label' => __('ui.businesses'), 'route' => 'super-admin.businesses.index', 'icon' => 'B', 'visible' => true]))->where('visible', true)->values();
            $canViewApprovals = $request->user()->hasPermissionInBusiness(PermissionName::ApprovalsView, $currentBusiness);
            $range = in_array($request->string('range')->toString(), ['today', 'week', 'month'], true) ? $request->string('range')->toString() : 'today';
            $from = match ($range) {
                'week' => now()->startOfWeek(), 'month' => now()->startOfMonth(), default => now()->startOfDay()
            };
            $salesQuery = Sale::query()->where('branch_id', $currentBranch->id)->where('status', 'confirmed')->where('sale_date', '>=', $from);
            $saleIds = (clone $salesQuery)->pluck('id');
            $grossProfit = DB::table('sale_items')->whereIn('sale_id', $saleIds)->selectRaw('COALESCE(SUM(line_total - (quantity * conversion_factor * cost_snapshot)), 0) AS profit')->value('profit');
            $accounts = PaymentAccount::query()->where('business_id', $currentBusiness->id)->where(fn ($query) => $query->whereNull('branch_id')->orWhere('branch_id', $currentBranch->id))->where('is_active', true)->get();
            $cashBalance = $accounts->reduce(fn ($total, $account) => bcadd($total, $balances->execute($account), 2), '0');
            $lowStock = DB::table('product_branch_settings as settings')->join('products', 'products.id', '=', 'settings.product_id')->leftJoin('stock_balances as stock', function ($join) {
                $join->on('stock.product_id', '=', 'settings.product_id')->on('stock.branch_id', '=', 'settings.branch_id')->where('stock.stock_status', 'available');
            })->where('settings.branch_id', $currentBranch->id)->where('settings.minimum_stock', '>', 0)->whereRaw('COALESCE(stock.quantity, 0) <= settings.minimum_stock')->select(['products.name', 'products.sku', 'settings.minimum_stock', DB::raw('COALESCE(stock.quantity, 0) as quantity')])->orderBy('quantity')->limit(6)->get();
            $trend = Sale::query()->where('branch_id', $currentBranch->id)->where('status', 'confirmed')->where('sale_date', '>=', now()->subDays(6)->startOfDay())->selectRaw('sale_date, SUM(total_amount) AS total')->groupBy('sale_date')->pluck('total', 'sale_date');
            $trendDays = collect(range(6, 0))->map(fn ($days) => now()->subDays($days))->map(fn ($date) => ['label' => $date->format('D'), 'amount' => (float) ($trend[$date->toDateString()] ?? 0)]);
            $stockValue = StockBalance::query()->where('branch_id', $currentBranch->id)->where('stock_status', 'available')->selectRaw('COALESCE(SUM(quantity * average_cost), 0) as value')->value('value');
            $debt = DB::table('customer_ledger_entries')->where('business_id', $currentBusiness->id)->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as balance')->value('balance');
            $draftSales = Sale::query()->where('branch_id', $currentBranch->id)->where('status', 'draft');
            $pendingSales = Sale::query()->where('branch_id', $currentBranch->id)
                ->where('status', 'confirmed')
                ->where(fn ($query) => $query->whereIn('payment_status', ['unpaid', 'partially_paid'])->orWhereIn('fulfillment_status', ['on_hold', 'partially_released']));
            $dashboardData = ['range' => $range, 'salesTotal' => (clone $salesQuery)->sum('total_amount'), 'salesCount' => (clone $salesQuery)->count(), 'grossProfit' => $grossProfit, 'expenses' => Expense::query()->where('branch_id', $currentBranch->id)->where('status', 'posted')->where('expense_date', '>=', $from)->sum('amount'), 'debt' => max(0, (float) $debt), 'stockValue' => $stockValue, 'lowStock' => $lowStock, 'canViewSales' => $can(PermissionName::SalesView), 'draftSalesCount' => $can(PermissionName::SalesView) ? $draftSales->count() : null, 'pendingSalesCount' => $can(PermissionName::SalesView) ? $pendingSales->count() : null, 'canViewApprovals' => $canViewApprovals, 'pendingApprovals' => $canViewApprovals ? ApprovalRequest::query()->where('business_id', $currentBusiness->id)->where('status', 'pending')->count() : null, 'cashBalance' => $cashBalance, 'recentSales' => Sale::query()->where('branch_id', $currentBranch->id)->with('customer')->withCount('items')->latest()->limit(6)->get(), 'trend' => $trendDays];
        }

        return view('dashboard', [
            'businesses' => $businesses,
            'currentBusiness' => $currentBusiness,
            'currentBranch' => $currentBranch,
            'dashboardData' => $dashboardData,
            'dashboardModules' => $dashboardModules,
        ]);
    }
}
