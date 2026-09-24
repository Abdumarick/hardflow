<?php

namespace App\Http\Controllers\Owner;

use App\Actions\CompleteStockCountAction;
use App\Actions\DecideStockAdjustmentAction;
use App\Actions\RecordOpeningStockAction;
use App\Actions\RequestStockAdjustmentAction;
use App\Actions\StartStockCountAction;
use App\Enums\PermissionName;
use App\Enums\StockStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::InventoryView, $business), 403);
        $branch = $tenant->branch();
        abort_if($branch === null, 422, 'Select a branch first.');
        $products = Product::query()->where('business_id', $business->id)->where('is_active', true)->where('is_stock_tracked', true)
            ->with(['productUnits' => fn ($query) => $query->where('is_base', true), 'branchSettings' => fn ($query) => $query->where('branch_id', $branch->id)])->orderBy('name')->get();
        $balances = StockBalance::query()->where('branch_id', $branch->id)->get()->groupBy('product_id');
        $lowStockCount = $products->filter(function (Product $product) use ($balances): bool {
            $minimum = $product->branchSettings->first()?->minimum_stock;
            $available = $balances->get($product->id)?->firstWhere('stock_status', StockStatus::Available)?->quantity ?? '0';

            return $minimum !== null && bccomp((string) $available, (string) $minimum, 4) <= 0;
        })->count();

        return view('owner.inventory.index', [
            'business' => $business, 'branch' => $branch, 'products' => $products,
            'balances' => $balances, 'lowStockCount' => $lowStockCount,
            'movements' => StockMovement::query()->where('branch_id', $branch->id)->with(['product', 'actor'])->latest('occurred_at')->paginate(25),
            'adjustments' => StockAdjustment::query()->where('branch_id', $branch->id)->with(['items.product', 'requester'])->latest()->get(),
            'counts' => StockCount::query()->where('branch_id', $branch->id)->with(['items.product', 'creator'])->latest()->get(),
        ]);
    }

    public function opening(Request $request, TenantContext $tenant, RecordOpeningStockAction $action): RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer'], 'quantity' => ['required', 'numeric', 'gt:0'], 'stock_status' => ['required', Rule::enum(StockStatus::class)]]);
        $action->execute($request->user(), $tenant->branch(), Product::query()->findOrFail($data['product_id']), (string) $data['quantity'], StockStatus::from($data['stock_status']));

        return back()->with('status', 'Opening stock recorded.');
    }

    public function requestAdjustment(Request $request, TenantContext $tenant, RequestStockAdjustmentAction $action): RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer'], 'physical_quantity' => ['required', 'numeric', 'min:0'], 'stock_status' => ['required', Rule::enum(StockStatus::class)], 'reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $action->execute($request->user(), $tenant->branch(), Product::query()->findOrFail($data['product_id']), StockStatus::from($data['stock_status']), (string) $data['physical_quantity'], $data['reason']);

        return back()->with('status', 'Adjustment requested for approval.');
    }

    public function decide(Request $request, StockAdjustment $adjustment, DecideStockAdjustmentAction $action): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $data['decision'] === 'approve' ? $action->approve($request->user(), $adjustment, $data['reason']) : $action->reject($request->user(), $adjustment, $data['reason']);

        return back()->with('status', 'Adjustment decision recorded.');
    }

    public function startCount(Request $request, TenantContext $tenant, StartStockCountAction $action): RedirectResponse
    {
        $action->execute($request->user(), $tenant->branch());

        return back()->with('status', 'Physical stock count started.');
    }

    public function completeCount(Request $request, StockCount $count, CompleteStockCountAction $action): RedirectResponse
    {
        $data = $request->validate(['quantities' => ['required', 'array'], 'quantities.*' => ['nullable', 'numeric', 'min:0'], 'reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $action->execute($request->user(), $count, $data['quantities'], $data['reason']);

        return back()->with('status', 'Count completed; differences are awaiting approval.');
    }
}
