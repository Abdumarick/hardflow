<?php

namespace App\Actions;

use App\Enums\FulfillmentStatus;
use App\Enums\PermissionName;
use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use App\Models\AuditLog;
use App\Models\GoodsRelease;
use App\Models\Sale;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmGoodsReleaseAction
{
    public function __construct(private ApplyStockMovementAction $movements) {}

    public function execute(User $actor, GoodsRelease $release): GoodsRelease
    {
        $business = $release->sale->branch->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::SalesConfirmRelease, $business) || $release->business_id !== app(TenantContext::class)->businessId() || $release->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $release) {
            $locked = GoodsRelease::query()->with(['items.saleItem.product', 'branch'])->lockForUpdate()->findOrFail($release->id);
            if ($locked->status !== 'draft') {
                throw ValidationException::withMessages(['release' => 'This goods release has already been confirmed.']);
            }
            $sale = Sale::query()->lockForUpdate()->findOrFail($locked->sale_id);
            foreach ($locked->items as $releaseItem) {
                $saleItem = $sale->items()->lockForUpdate()->findOrFail($releaseItem->sale_item_id);
                $outstanding = bcsub((string) $saleItem->quantity, (string) $saleItem->released_quantity, 4);
                if (bccomp((string) $releaseItem->quantity, $outstanding, 4) > 0) {
                    throw ValidationException::withMessages(['release' => 'Another release changed the outstanding quantity. Review this release before confirming.']);
                }
                $baseQuantity = bcmul((string) $releaseItem->quantity, (string) $saleItem->conversion_factor, 4);
                $this->movements->execute($actor, $locked->branch, $saleItem->product, StockStatus::Available, StockMovementType::SaleRelease, bcmul($baseQuantity, '-1', 4), 'Confirmed '.$locked->release_number, $locked, (string) $saleItem->cost_snapshot);
                $saleItem->update(['released_quantity' => bcadd((string) $saleItem->released_quantity, (string) $releaseItem->quantity, 4)]);
            }
            $outstanding = $sale->items()->get()->contains(fn ($item) => bccomp((string) $item->released_quantity, (string) $item->quantity, 4) < 0);
            $sale->update(['fulfillment_status' => $outstanding ? FulfillmentStatus::PartiallyReleased : FulfillmentStatus::Released]);
            $locked->update(['status' => 'confirmed', 'confirmed_by' => $actor->id, 'confirmed_at' => now()]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'action' => 'goods_release.confirmed', 'subject_type' => GoodsRelease::class, 'subject_id' => $locked->id]);

            return $locked->refresh()->load('sale', 'items.saleItem.product');
        });
    }
}
