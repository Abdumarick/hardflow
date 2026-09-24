<?php
namespace App\Actions;
use App\Enums\PermissionName;
use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use App\Models\NeighbourStockBorrow;
use App\Models\Product;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ReturnNeighbourStockAction {
    public function __construct(private ApplyStockMovementAction $movements) {}
    public function execute(User $actor, NeighbourStockBorrow $borrow, array $data): NeighbourStockBorrow {
        if (!$actor->hasPermissionInBusiness(PermissionName::SalesRelease, $borrow->sale->branch->business) || $borrow->business_id !== app(TenantContext::class)->businessId() || $borrow->branch_id !== app(TenantContext::class)->branchId()) abort(403);
        return DB::transaction(function () use ($actor,$borrow,$data) {
            $locked = NeighbourStockBorrow::query()->lockForUpdate()->findOrFail($borrow->id);
            $remaining = bcsub((string)$locked->quantity, (string)$locked->returned_quantity, 4);
            if (bccomp((string)$data['quantity'], '0', 4) <= 0 || bccomp((string)$data['quantity'], $remaining, 4) > 0) throw ValidationException::withMessages(['quantity'=>'Return quantity exceeds the remaining neighbour loan.']);
            $product = Product::query()->findOrFail($locked->product_id);
            $this->movements->execute($actor, $locked->branch, $product, StockStatus::Available, StockMovementType::AdjustmentOut, bcmul((string)$data['quantity'], (string)$locked->conversion_factor, 4), 'Returned to neighbour: '.$locked->neighbour_name, $locked);
            $returned = bcadd((string)$locked->returned_quantity, (string)$data['quantity'], 4);
            $locked->update(['returned_quantity'=>$returned,'returned_at'=>now()->toDateString(),'return_notes'=>$data['notes'] ?? null,'status'=>bccomp($returned,(string)$locked->quantity,4) >= 0 ? 'returned' : 'partially_returned']);
            return $locked->refresh();
        });
    }
}
