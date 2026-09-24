<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Enums\StockStatus;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class StartStockCountAction
{
    public function execute(User $actor, Branch $branch): StockCount
    {
        $business = app(TenantContext::class)->businessOrFail();
        if (! $actor->hasPermissionInBusiness(PermissionName::InventoryAdjustRequest, $business) || $branch->id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $branch, $business) {
            $count = StockCount::query()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'status' => 'draft', 'created_by' => $actor->id]);
            $products = Product::query()->where('business_id', $business->id)->where('is_active', true)->where('is_stock_tracked', true)->get();
            $balances = StockBalance::query()->where('branch_id', $branch->id)->get()->keyBy(fn ($b) => $b->product_id.'|'.$b->stock_status->value);
            foreach ($products as $product) {
                foreach (StockStatus::cases() as $status) {
                    $quantity = $balances->get($product->id.'|'.$status->value)?->quantity ?? '0';
                    StockCountItem::query()->create(['business_id' => $business->id, 'stock_count_id' => $count->id, 'product_id' => $product->id, 'stock_status' => $status, 'system_quantity' => $quantity]);
                }
            }

            return $count->load('items.product');
        });
    }
}
