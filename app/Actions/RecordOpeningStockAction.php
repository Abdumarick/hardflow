<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordOpeningStockAction
{
    public function __construct(private ApplyStockMovementAction $movements) {}

    public function execute(User $actor, Branch $branch, Product $product, string $quantity, StockStatus $status = StockStatus::Available, ?string $unitCost = null): StockMovement
    {
        $business = app(TenantContext::class)->businessOrFail();
        if (! $actor->hasPermissionInBusiness(PermissionName::InventoryOpening, $business)) {
            throw new AuthorizationException('You cannot record opening stock.');
        }if ($branch->id !== app(TenantContext::class)->branchId() || $product->business_id !== $business->id) {
            throw ValidationException::withMessages(['product' => 'Invalid branch or product.']);
        }if ((float) $quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Opening quantity must be greater than zero.']);
        }
        if ($unitCost !== null && (! is_numeric($unitCost) || bccomp($unitCost, '0', 2) < 0)) {
            throw ValidationException::withMessages(['unit_cost' => 'Opening unit cost must be zero or greater.']);
        }

        return DB::transaction(function () use ($actor, $branch, $product, $quantity, $status, $unitCost): StockMovement {
            $movement = $this->movements->execute($actor, $branch, $product, $status, StockMovementType::OpeningStock, $quantity, 'Opening stock', null, $unitCost);

            if ($unitCost !== null) {
                $balance = StockBalance::query()->where('branch_id', $branch->id)->where('product_id', $product->id)->where('stock_status', $status->value)->lockForUpdate()->firstOrFail();
                $existingValue = bcmul((string) $movement->balance_before, (string) $balance->average_cost, 6);
                $openingValue = bcmul($quantity, $unitCost, 6);
                $averageCost = bcdiv(bcadd($existingValue, $openingValue, 6), (string) $movement->balance_after, 2);
                $balance->update(['average_cost' => $averageCost]);
            }

            return $movement;
        });
    }
}
