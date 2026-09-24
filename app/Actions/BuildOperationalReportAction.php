<?php

namespace App\Actions;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockBalance;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class BuildOperationalReportAction
{
    public function execute(Branch $branch, string $section, CarbonInterface $from, CarbonInterface $to): array
    {
        return match ($section) {
            'profit' => $this->profit($branch, $from, $to),
            'inventory' => $this->inventory($branch),
            'expenses' => $this->expenses($branch, $from, $to),
            'purchases' => $this->purchases($branch, $from, $to),
            'debt' => $this->debt($branch, $to),
            'payments' => $this->payments($branch, $from, $to),
            'movements' => $this->movements($branch, $from, $to),
            'low-stock' => $this->lowStock($branch),
            'fast-moving' => $this->productVelocity($branch, $from, $to, false),
            'slow-moving' => $this->productVelocity($branch, $from, $to, true),
            'salespeople' => $this->salespeople($branch, $from, $to),
            'supplier-balances' => $this->supplierBalances($branch, $to),
            'outstanding-purchases' => $this->outstandingPurchases($branch),
            default => $this->sales($branch, $from, $to),
        };
    }

    private function sales(Branch $branch, CarbonInterface $from, CarbonInterface $to): array
    {
        $query = Sale::query()->where('branch_id', $branch->id)->where('status', 'confirmed')->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()]);
        $rows = DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')->join('products', 'products.id', '=', 'sale_items.product_id')->leftJoin('categories', 'categories.id', '=', 'products.category_id')->where('sales.branch_id', $branch->id)->where('sales.status', 'confirmed')->whereBetween('sales.sale_date', [$from, $to])->groupBy('categories.name')->selectRaw("COALESCE(categories.name, 'Uncategorized') as label, SUM(sale_items.line_total) as amount")->orderByDesc('amount')->get();
        $total = (float) (clone $query)->sum('total_amount');
        $count = (clone $query)->count();

        return ['cards' => [['Revenue', $total], ['Orders', $count, false], ['Average order', $count ? $total / $count : 0]], 'rows' => $rows, 'title' => 'Sales by category'];
    }

    private function profit(Branch $branch, CarbonInterface $from, CarbonInterface $to): array
    {
        $totals = DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')->where('sales.branch_id', $branch->id)->where('sales.status', 'confirmed')->whereBetween('sales.sale_date', [$from, $to])->selectRaw('COALESCE(SUM(line_total),0) revenue, COALESCE(SUM(quantity * conversion_factor * cost_snapshot),0) cogs')->first();
        $gross = (float) $totals->revenue - (float) $totals->cogs;

        return ['cards' => [['Revenue', $totals->revenue], ['Cost of goods', $totals->cogs], ['Gross profit', $gross], ['Gross margin', $totals->revenue > 0 ? $gross / $totals->revenue * 100 : 0, false, '%']], 'rows' => collect([['label' => 'Revenue', 'amount' => $totals->revenue], ['label' => 'Cost of goods', 'amount' => $totals->cogs], ['label' => 'Gross profit', 'amount' => $gross]])->map(fn ($row) => (object) $row), 'title' => 'Profit composition'];
    }

    private function inventory(Branch $branch): array
    {
        $query = StockBalance::query()->where('branch_id', $branch->id)->where('stock_status', 'available');
        $rows = DB::table('stock_balances')->join('products', 'products.id', '=', 'stock_balances.product_id')->leftJoin('categories', 'categories.id', '=', 'products.category_id')->where('stock_balances.branch_id', $branch->id)->where('stock_balances.stock_status', 'available')->groupBy('categories.name')->selectRaw("COALESCE(categories.name, 'Uncategorized') label, SUM(stock_balances.quantity * stock_balances.average_cost) amount")->orderByDesc('amount')->get();

        return ['cards' => [['Cost value', (clone $query)->selectRaw('COALESCE(SUM(quantity * average_cost),0) value')->value('value')], ['Units in stock', (clone $query)->sum('quantity'), false], ['Products stocked', (clone $query)->distinct('product_id')->count('product_id'), false]], 'rows' => $rows, 'title' => 'Inventory value by category'];
    }

    private function expenses(Branch $branch, CarbonInterface $from, CarbonInterface $to): array
    {
        $query = Expense::query()->where('branch_id', $branch->id)->where('status', 'posted')->whereBetween('expense_date', [$from, $to]);
        $rows = DB::table('expenses')->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')->where('expenses.branch_id', $branch->id)->where('expenses.status', 'posted')->whereBetween('expenses.expense_date', [$from, $to])->groupBy('expense_categories.name')->selectRaw('expense_categories.name label, SUM(expenses.amount) amount')->orderByDesc('amount')->get();
        $total = (float) (clone $query)->sum('amount');
        $count = (clone $query)->count();

        return ['cards' => [['Total expenses', $total], ['Entries', $count, false], ['Average expense', $count ? $total / $count : 0]], 'rows' => $rows, 'title' => 'Expenses by category'];
    }

    private function purchases(Branch $branch, CarbonInterface $from, CarbonInterface $to): array
    {
        $query = Purchase::query()->where('branch_id', $branch->id)->whereNot('status', 'draft')->whereBetween('purchase_date', [$from, $to]);
        $rows = DB::table('purchases')->leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')->where('purchases.branch_id', $branch->id)->where('purchases.status', '!=', 'draft')->whereBetween('purchases.purchase_date', [$from, $to])->groupBy('suppliers.name')->selectRaw("COALESCE(suppliers.name, 'Unassigned supplier') label, SUM(purchases.total_amount) amount")->orderByDesc('amount')->get();
        $total = (float) (clone $query)->sum('total_amount');
        $count = (clone $query)->count();

        return ['cards' => [['Purchase value', $total], ['Purchase orders', $count, false], ['Average order', $count ? $total / $count : 0]], 'rows' => $rows, 'title' => 'Purchases by supplier'];
    }

    private function debt(Branch $branch, CarbonInterface $to): array
    {
        $rows = DB::table('customer_ledger_entries')->join('customers', 'customers.id', '=', 'customer_ledger_entries.customer_id')->where('customer_ledger_entries.branch_id', $branch->id)->where('customer_ledger_entries.occurred_at', '<=', $to->copy()->endOfDay())->groupBy('customers.id', 'customers.name')->havingRaw('SUM(customer_ledger_entries.debit - customer_ledger_entries.credit) > 0')->selectRaw('customers.name label, SUM(customer_ledger_entries.debit - customer_ledger_entries.credit) amount')->orderByDesc('amount')->get();
        $total = (float) $rows->sum('amount');

        return ['cards' => [['Outstanding debt', $total], ['Customers owing', $rows->count(), false], ['Average balance', $rows->count() ? $total / $rows->count() : 0]], 'rows' => $rows, 'title' => 'Customer outstanding balances'];
    }

    private function payments(Branch $branch, CarbonInterface $from, CarbonInterface $to): array
    {
        $query = Payment::query()->where('branch_id', $branch->id)->where('status', 'confirmed')->whereBetween('payment_date', [$from, $to]);
        $rows = DB::table('payments')->join('payment_accounts', 'payment_accounts.id', '=', 'payments.payment_account_id')->join('payment_methods', 'payment_methods.id', '=', 'payment_accounts.payment_method_id')->where('payments.branch_id', $branch->id)->where('payments.status', 'confirmed')->whereBetween('payments.payment_date', [$from, $to])->groupBy('payment_methods.name')->selectRaw('payment_methods.name label, SUM(payments.amount) amount')->orderByDesc('amount')->get();
        $total = (float) (clone $query)->sum('amount');
        $count = (clone $query)->count();

        return ['cards' => [['Payments received', $total], ['Transactions', $count, false], ['Average payment', $count ? $total / $count : 0]], 'rows' => $rows, 'title' => 'Payments by method'];
    }

    private function movements(Branch $branch, CarbonInterface $from, CarbonInterface $to): array
    {
        $query = DB::table('stock_movements')->where('branch_id', $branch->id)->whereBetween('occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
        $rows = (clone $query)->groupBy('movement_type')->selectRaw("REPLACE(movement_type, '_', ' ') label, SUM(ABS(quantity_delta)) amount")->orderByDesc('amount')->get();

        return ['cards' => [['Movement records', (clone $query)->count(), false], ['Units moved', (clone $query)->sum(DB::raw('ABS(quantity_delta)')), false], ['Movement types', $rows->count(), false]], 'rows' => $rows, 'title' => 'Stock movement by type', 'row_money' => false, 'row_suffix' => ' units'];
    }

    private function lowStock(Branch $branch): array
    {
        $query = DB::table('product_branch_settings as rules')->join('products', 'products.id', '=', 'rules.product_id')->leftJoin('stock_balances as stock', function ($join) {
            $join->on('stock.product_id', '=', 'rules.product_id')->on('stock.branch_id', '=', 'rules.branch_id')->where('stock.stock_status', 'available');
        })->where('rules.branch_id', $branch->id)->where('rules.minimum_stock', '>', 0)->whereRaw('COALESCE(stock.quantity, 0) <= rules.minimum_stock');
        $rows = (clone $query)->selectRaw('products.name label, (rules.minimum_stock - COALESCE(stock.quantity, 0)) amount')->orderByDesc('amount')->limit(50)->get();

        return ['cards' => [['Products below minimum', (clone $query)->count(), false], ['Out of stock', (clone $query)->whereRaw('COALESCE(stock.quantity, 0) <= 0')->count(), false], ['Suggested reorder units', $rows->sum('amount'), false]], 'rows' => $rows, 'title' => 'Low-stock reorder gaps', 'row_money' => false, 'row_suffix' => ' units'];
    }

    private function productVelocity(Branch $branch, CarbonInterface $from, CarbonInterface $to, bool $slow): array
    {
        $query = DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')->join('products', 'products.id', '=', 'sale_items.product_id')->where('sales.branch_id', $branch->id)->where('sales.status', 'confirmed')->whereBetween('sales.sale_date', [$from, $to])->groupBy('products.id', 'products.name')->selectRaw('products.name label, SUM(sale_items.quantity * sale_items.conversion_factor) amount');
        $rows = ($slow ? $query->orderBy('amount') : $query->orderByDesc('amount'))->limit(20)->get();

        return ['cards' => [['Products sold', $rows->count(), false], ['Units represented', $rows->sum('amount'), false], ['Reporting days', $from->diffInDays($to) + 1, false]], 'rows' => $rows, 'title' => $slow ? 'Slow-moving sold products' : 'Fast-moving products', 'row_money' => false, 'row_suffix' => ' units'];
    }

    private function salespeople(Branch $branch, CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = DB::table('sales')->join('users', 'users.id', '=', 'sales.created_by')->where('sales.branch_id', $branch->id)->where('sales.status', 'confirmed')->whereBetween('sales.sale_date', [$from, $to])->groupBy('users.id', 'users.name')->selectRaw('users.name label, SUM(sales.total_amount) amount')->orderByDesc('amount')->get();

        return ['cards' => [['Sales value', $rows->sum('amount')], ['Salespeople', $rows->count(), false], ['Average per person', $rows->count() ? $rows->sum('amount') / $rows->count() : 0]], 'rows' => $rows, 'title' => 'Salesperson performance'];
    }

    private function supplierBalances(Branch $branch, CarbonInterface $to): array
    {
        $rows = DB::table('supplier_ledger_entries')->join('suppliers', 'suppliers.id', '=', 'supplier_ledger_entries.supplier_id')->where('supplier_ledger_entries.branch_id', $branch->id)->where('supplier_ledger_entries.occurred_at', '<=', $to->copy()->endOfDay())->groupBy('suppliers.id', 'suppliers.name')->havingRaw('SUM(supplier_ledger_entries.debit - supplier_ledger_entries.credit) > 0')->selectRaw('suppliers.name label, SUM(supplier_ledger_entries.debit - supplier_ledger_entries.credit) amount')->orderByDesc('amount')->get();
        $total = (float) $rows->sum('amount');

        return ['cards' => [['Supplier liability', $total], ['Suppliers owed', $rows->count(), false], ['Average balance', $rows->count() ? $total / $rows->count() : 0]], 'rows' => $rows, 'title' => 'Supplier outstanding balances'];
    }

    private function outstandingPurchases(Branch $branch): array
    {
        $query = DB::table('purchase_items')->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')->leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')->where('purchases.branch_id', $branch->id)->whereIn('purchases.status', ['ordered', 'partially_received'])->whereColumn('purchase_items.received_quantity', '<', 'purchase_items.ordered_quantity');
        $rows = (clone $query)->groupBy('suppliers.name')->selectRaw("COALESCE(suppliers.name, 'Unassigned supplier') label, SUM((purchase_items.ordered_quantity - purchase_items.received_quantity) * purchase_items.unit_cost) amount")->orderByDesc('amount')->get();

        return ['cards' => [['Outstanding value', $rows->sum('amount')], ['Open item lines', (clone $query)->count(), false], ['Units awaiting receipt', (clone $query)->sum(DB::raw('purchase_items.ordered_quantity - purchase_items.received_quantity')), false]], 'rows' => $rows, 'title' => 'Ordered items awaiting receipt'];
    }
}
