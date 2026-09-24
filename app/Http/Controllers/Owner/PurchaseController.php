<?php

namespace App\Http\Controllers\Owner;

use App\Actions\ConfirmGoodsReceiptAction;
use App\Actions\CreateGoodsReceiptAction;
use App\Actions\CreatePurchaseAction;
use App\Actions\CreateSupplierAction;
use App\Actions\DecideSupplierReturnAction;
use App\Actions\OrderPurchaseAction;
use App\Actions\RecordSupplierPaymentAction;
use App\Actions\RequestSupplierReturnAction;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\SupplierReturn;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::PurchasesView, $business), 403);
        $branch = $tenant->branch();
        abort_if($branch === null, 422, 'Select a branch first.');

        $suppliers = Supplier::query()->where('business_id', $business->id)->orderBy('name')->get();

        return view('owner.purchases.index', [
            'business' => $business,
            'branch' => $branch,
            'suppliers' => $suppliers,
            'supplierBalances' => $suppliers->mapWithKeys(fn (Supplier $supplier) => [$supplier->id => $supplier->outstandingBalance()]),
            'products' => Product::query()->where('business_id', $business->id)->where('is_active', true)
                ->with(['productUnits' => fn ($query) => $query->where('can_purchase', true)->where('is_active', true)->with('unit')])->orderBy('name')->get(),
            'purchases' => Purchase::query()->where('branch_id', $branch->id)->with(['supplier', 'items.product', 'items.productUnit.unit', 'receipts.items'])->latest()->get(),
            'returns' => SupplierReturn::query()->where('branch_id', $branch->id)->with(['supplier', 'requester', 'items.product'])->latest()->get(),
            'ledgerEntries' => SupplierLedgerEntry::query()->where('business_id', $business->id)->with('supplier')->latest('occurred_at')->limit(100)->get(),
        ]);
    }

    public function storeSupplier(Request $request, TenantContext $tenant, CreateSupplierAction $action): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'], 'tin' => ['nullable', 'string', 'max:50'], 'location' => ['nullable', 'string', 'max:2000']]);
        $action->execute($request->user(), $tenant->businessOrFail(), $data);

        return back()->with('status', 'Supplier created.');
    }

    public function store(Request $request, TenantContext $tenant, CreatePurchaseAction $action): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'integer'], 'purchase_date' => ['required', 'date'], 'supplier_reference' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'], 'items.*.product_unit_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);
        $data['branch_id'] = $tenant->branchId();
        $action->execute($request->user(), $tenant->businessOrFail(), $data);

        return back()->with('status', 'Purchase draft created.');
    }

    public function order(Request $request, Purchase $purchase, OrderPurchaseAction $action): RedirectResponse
    {
        $action->execute($request->user(), $purchase);

        return back()->with('status', 'Purchase marked as ordered. Stock was not changed.');
    }

    public function receive(Request $request, Purchase $purchase, CreateGoodsReceiptAction $action): RedirectResponse
    {
        $data = $request->validate([
            'received_at' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:2000'], 'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => ['required', 'integer'], 'items.*.received_quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.damaged_quantity' => ['nullable', 'numeric', 'min:0'], 'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.difference_reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $action->execute($request->user(), $purchase, $data);

        return back()->with('status', 'Draft receipt saved. Confirm it to update inventory.');
    }

    public function confirm(Request $request, GoodsReceipt $receipt, ConfirmGoodsReceiptAction $action): RedirectResponse
    {
        $action->execute($request->user(), $receipt);

        return back()->with('status', 'Receipt confirmed and inventory updated.');
    }

    public function requestReturn(Request $request, Purchase $purchase, RequestSupplierReturnAction $action): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'], 'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => ['required', 'integer'], 'items.*.stock_status' => ['required', 'in:available,damaged'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);
        $action->execute($request->user(), $purchase, $data);

        return back()->with('status', 'Supplier return submitted for approval.');
    }

    public function decideReturn(Request $request, SupplierReturn $return, DecideSupplierReturnAction $action): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $data['decision'] === 'approve' ? $action->approve($request->user(), $return, $data['reason']) : $action->reject($request->user(), $return, $data['reason']);

        return back()->with('status', 'Supplier return decision recorded.');
    }

    public function recordPayment(Request $request, Supplier $supplier, RecordSupplierPaymentAction $action): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0'], 'notes' => ['required', 'string', 'max:2000']]);
        $action->execute($request->user(), $supplier, (string) $data['amount'], $data['notes']);

        return back()->with('status', 'Supplier payment recorded in the ledger.');
    }
}
