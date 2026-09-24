<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class RecordOpeningStockAction
{
    public function __construct(private ApplyStockMovementAction $movements) {}

    public function execute(User $actor, Branch $branch, Product $product, string $quantity, StockStatus $status = StockStatus::Available): StockMovement
    {
        $business = app(TenantContext::class)->businessOrFail();
        if (! $actor->hasPermissionInBusiness(PermissionName::InventoryOpening, $business)) {
            throw new AuthorizationException('You cannot record opening stock.');
        }if ($branch->id !== app(TenantContext::class)->branchId() || $product->business_id !== $business->id) {
            throw ValidationException::withMessages(['product' => 'Invalid branch or product.']);
        }if ((float) $quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Opening quantity must be greater than zero.']);
        }

        return $this->movements->execute($actor, $branch, $product, $status, StockMovementType::OpeningStock, $quantity, 'Opening stock');
    }
}
