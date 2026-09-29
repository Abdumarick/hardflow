<?php

namespace App\Http\Controllers\Owner;

use App\Actions\BorrowNeighbourStockAction;
use App\Actions\CancelSaleAction;
use App\Actions\CompleteSaleCheckoutAction;
use App\Actions\ConfirmGoodsReleaseAction;
use App\Actions\ConfirmSaleAction;
use App\Actions\ConvertQuotationToSaleAction;
use App\Actions\CreateCustomerAction;
use App\Actions\CreateGoodsReleaseAction;
use App\Actions\CreateQuotationAction;
use App\Actions\CreateSaleAction;
use App\Actions\DecideSaleReturnAction;
use App\Actions\RequestSaleReturnAction;
use App\Actions\ReturnNeighbourStockAction;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\GoodsRelease;
use App\Models\NeighbourStockBorrow;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StockBalance;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::SalesView, $business), 403);
        $branch = $tenant->branch();
        abort_if($branch === null, 422, 'Select a branch first.');

        $products = Product::query()->where('business_id', $business->id)->where('is_active', true)
            ->with(['category', 'brand', 'productUnits' => fn ($query) => $query->where('can_sell', true)->where('is_active', true)->with(['unit', 'prices' => fn ($price) => $price->where('is_active', true)->where('current_slot', true)->with('priceLevel')])])->orderBy('name')->get();
        $balances = StockBalance::query()->where('branch_id', $branch->id)->where('stock_status', 'available')->pluck('quantity', 'product_id');
        $posProducts = $products->flatMap(fn (Product $product) => $product->productUnits->map(function ($unit) use ($product, $balances) {
            $prices = $unit->prices->map(fn ($price) => ['level_id' => $price->price_level_id, 'level' => $price->priceLevel->name, 'amount' => (float) $price->amount])->values();

            return ['id' => $product->public_id.'-'.$unit->id, 'product_unit_id' => $unit->id, 'name' => $product->name, 'sku' => $product->sku, 'barcode' => $product->barcode, 'image_url' => $product->image_path ? Storage::url($product->image_path) : null, 'category' => $product->category?->name ?? 'Uncategorized', 'brand' => $product->brand?->name, 'unit' => $unit->unit->symbol, 'conversion' => (float) $unit->conversion_factor, 'stock' => (float) ($balances[$product->id] ?? 0) / (float) $unit->conversion_factor, 'prices' => $prices, 'price' => (float) ($prices->first()['amount'] ?? 0), 'price_level_id' => $prices->first()['level_id'] ?? null];
        })->filter(fn ($item) => $item['price_level_id'] !== null))->values();
        $perPage = in_array((int) $request->integer('per_page', 9), [6, 9, 12, 24], true) ? (int) $request->integer('per_page', 9) : 9;
        $saleSearch = trim((string) $request->input('sale_search'));
        $sales = Sale::query()->where('branch_id', $branch->id)
            ->with(['customer', 'items.product', 'items.productUnit.unit', 'releases.items'])
            ->when($saleSearch !== '', fn ($query) => $query->where(fn ($query) => $query->where('sale_number', 'like', "%{$saleSearch}%")->orWhere('walk_in_name', 'like', "%{$saleSearch}%")->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$saleSearch}%"))))
            ->when($request->filled('sale_status'), fn ($query) => $query->where('status', $request->input('sale_status')))
            ->when($request->filled('fulfillment_status'), fn ($query) => $query->where('fulfillment_status', $request->input('fulfillment_status')))
            ->latest()->paginate($perPage)->withQueryString();

        return view('owner.sales.index', [
            'business' => $business, 'branch' => $branch,
            'customers' => Customer::query()->where('business_id', $business->id)->orderBy('name')->get(),
            'products' => $products, 'posProducts' => $posProducts, 'categories' => $posProducts->pluck('category')->unique()->sort()->values(),
            'sales' => $sales,
            'quotations' => Quotation::query()->where('branch_id', $branch->id)->with(['customer', 'items.product'])->latest()->get(),
            'saleReturns' => SaleReturn::query()->where('branch_id', $branch->id)->with(['sale.customer', 'requester', 'items.saleItem.product'])->latest()->get(),
        ]);
    }

    public function storeCustomer(Request $request, TenantContext $tenant, CreateCustomerAction $action): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email'], 'location' => ['nullable', 'string', 'max:2000'], 'is_credit_customer' => ['nullable', 'boolean'], 'credit_limit' => ['nullable', 'numeric', 'min:0']]);
        $action->execute($request->user(), $tenant->businessOrFail(), $data);

        return back()->with('status', 'Customer created.');
    }

    public function storeSale(Request $request, TenantContext $tenant, CreateSaleAction $action): RedirectResponse
    {
        $data = $this->transactionData($request, false);
        $data['branch_id'] = $tenant->branchId();
        $data['sale_date'] = now()->toDateString();
        $sale = $action->execute($request->user(), $tenant->businessOrFail(), $data);

        return redirect()->route('owner.sales.show', $sale)->with('status', 'Sale draft created. Review payment and confirm the sale.');
    }

    public function show(Request $request, Sale $sale, TenantContext $tenant): View
    {
        $business = $tenant->businessOrFail();
        abort_unless($sale->business_id === $business->id && $sale->branch_id === $tenant->branchId() && $request->user()->hasPermissionInBusiness(PermissionName::SalesView, $business), 403);

        $accounts = PaymentAccount::query()->with('method')->where('business_id', $business->id)->where('is_active', true)
            ->where(fn ($query) => $query->where('branch_id', $sale->branch_id)->orWhereNull('branch_id'))
            ->whereHas('method', fn ($query) => $query->where('is_active', true))->orderBy('name')->get();

        return view('owner.sales.show', ['business' => $business, 'branch' => $tenant->branch(), 'sale' => $sale->load(['customer', 'items.product', 'items.productUnit.unit', 'paymentAllocations.payment.account.method', 'releases.items']), 'accounts' => $accounts, 'neighbourBorrows' => NeighbourStockBorrow::query()->where('sale_id', $sale->id)->with('sale')->latest()->get()]);
    }

    public function completeCheckout(Request $request, Sale $sale, CompleteSaleCheckoutAction $action): RedirectResponse
    {
        $data = $request->validate(['payments' => ['nullable', 'array'], 'payments.*.payment_account_id' => ['required', 'integer', 'distinct'], 'payments.*.amount' => ['required', 'numeric', 'gt:0'], 'payments.*.external_reference' => ['nullable', 'string', 'max:255']]);
        $action->execute($request->user(), $sale, $data['payments'] ?? []);

        return redirect()->route('owner.sales.show', $sale)->with('status', 'Sale confirmed and payment recorded. Create a goods release when the items are handed over.');
    }

    public function confirmSale(Request $request, Sale $sale, ConfirmSaleAction $action): RedirectResponse
    {
        $action->execute($request->user(), $sale);

        return back()->with('status', 'Sale confirmed. Stock remains on hold until goods release.');
    }

    public function cancelSale(Request $request, Sale $sale, CancelSaleAction $action): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $action->execute($request->user(), $sale, $data['reason']);

        return back()->with('status', 'Sale cancelled with an audit record.');
    }

    public function requestReturn(Request $request, Sale $sale, RequestSaleReturnAction $action): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000'], 'items' => ['required', 'array', 'min:1'], 'items.*.sale_item_id' => ['required', 'integer'], 'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'items.*.stock_status' => ['required', 'in:available,damaged,returned,under_inspection']]);
        $action->execute($request->user(), $sale, $data);

        return back()->with('status', 'Customer return submitted for inspection and approval.');
    }

    public function decideReturn(Request $request, SaleReturn $return, DecideSaleReturnAction $action): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $data['decision'] === 'approve' ? $action->approve($request->user(), $return, $data['reason']) : $action->reject($request->user(), $return, $data['reason']);

        return back()->with('status', 'Customer return decision recorded.');
    }

    public function storeQuotation(Request $request, TenantContext $tenant, CreateQuotationAction $action): RedirectResponse
    {
        $data = $this->transactionData($request, true);
        $data['branch_id'] = $tenant->branchId();
        $data['quotation_date'] = now()->toDateString();
        $action->execute($request->user(), $tenant->businessOrFail(), $data);

        return back()->with('status', 'Quotation document created.');
    }

    public function convert(Request $request, Quotation $quotation, ConvertQuotationToSaleAction $action): RedirectResponse
    {
        $action->execute($request->user(), $quotation);

        return back()->with('status', 'Quotation converted to a sale draft.');
    }

    public function release(Request $request, Sale $sale, CreateGoodsReleaseAction $action): RedirectResponse
    {
        $data = $request->validate(['items' => ['required', 'array', 'min:1'], 'items.*.sale_item_id' => ['required', 'integer'], 'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $action->execute($request->user(), $sale, $data);

        return back()->with('status', 'Draft goods release created. Confirm it to reduce stock.');
    }

    public function borrowNeighbourStock(Request $request, Sale $sale, BorrowNeighbourStockAction $action): RedirectResponse
    {
        $data = $request->validate(['sale_item_id' => ['required', 'integer'], 'quantity' => ['required', 'numeric', 'gt:0'], 'neighbour_name' => ['required', 'string', 'max:255'], 'neighbour_phone' => ['nullable', 'string', 'max:50'], 'return_due_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $action->execute($request->user(), $sale, $data);

        return back()->with('status', 'Neighbour stock recorded. You can now create and confirm the goods release.');
    }

    public function returnNeighbourStock(Request $request, NeighbourStockBorrow $borrow, ReturnNeighbourStockAction $action): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'numeric', 'gt:0'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $action->execute($request->user(), $borrow, $data);

        return back()->with('status', 'Neighbour stock return recorded and inventory updated.');
    }

    public function confirmRelease(Request $request, GoodsRelease $release, ConfirmGoodsReleaseAction $action): RedirectResponse
    {
        $action->execute($request->user(), $release);

        return back()->with('status', 'Goods released and inventory updated.');
    }

    public function printSale(Request $request, Sale $sale, TenantContext $tenant): View
    {
        abort_unless($sale->business_id === $tenant->businessId() && $request->user()->hasPermissionInBusiness(PermissionName::SalesView, $tenant->businessOrFail()), 403);

        return view('owner.sales.print', [
            'document' => $sale->load('customer', 'items.product', 'items.productUnit.unit', 'branch.business'),
            'documentNumber' => $sale->sale_number,
            'documentDate' => $sale->sale_date,
            'kind' => 'Sale',
        ]);
    }

    public function printQuotation(Request $request, Quotation $quotation, TenantContext $tenant): View
    {
        abort_unless(
            $quotation->business_id === $tenant->businessId()
            && $quotation->branch_id === $tenant->branchId()
            && $request->user()->hasPermissionInBusiness(PermissionName::QuotationsView, $tenant->businessOrFail()),
            403,
        );

        return view('owner.sales.print', [
            'document' => $quotation->load('customer', 'items.product', 'items.productUnit.unit', 'branch.business'),
            'documentNumber' => $quotation->quotation_number,
            'documentDate' => $quotation->quotation_date,
            'kind' => ucfirst($quotation->document_type),
        ]);
    }

    private function transactionData(Request $request, bool $quotation): array
    {
        $rules = ['customer_id' => ['nullable', 'integer'], 'walk_in_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'], 'walk_in_phone' => ['nullable', 'string', 'max:30'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_unit_id' => ['required', 'integer'], 'items.*.price_level_id' => ['required', 'integer'], 'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'items.*.applied_unit_price' => ['nullable', 'numeric', 'min:0'], 'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'], 'items.*.override_reason' => ['nullable', 'string', 'max:2000']];
        if (! $quotation) {
            $rules['due_date'] = ['nullable', 'date', 'after_or_equal:today'];
        }
        if ($quotation) {
            $rules += ['document_type' => ['required', 'in:quotation,proforma'], 'walk_in_location' => ['nullable', 'string'], 'valid_until' => ['nullable', 'date', 'after_or_equal:today']];
        }

        $data = $request->validate($rules);

        if (! empty($data['customer_id'])) {
            $data['walk_in_name'] = null;
            $data['walk_in_phone'] = null;
        }

        return $data;
    }
}
