<?php
namespace App\Actions;
use App\Enums\PermissionName;
use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use App\Models\NeighbourStockBorrow;
use App\Models\Sale;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\DatabaseManager;
use Illuminate\Validation\ValidationException;
class BorrowNeighbourStockAction {
    public function __construct(private ApplyStockMovementAction $movements, private DatabaseManager $db) {}
    public function execute(User $actor, Sale $sale, array $data): NeighbourStockBorrow {
        if (!$actor->hasPermissionInBusiness(PermissionName::SalesRelease, $sale->branch->business) || $sale->business_id !== app(TenantContext::class)->businessId() || $sale->branch_id !== app(TenantContext::class)->branchId()) abort(403);
        return $this->db->transaction(function () use ($actor,$sale,$data) {
            $item = $sale->items()->with(['product','productUnit'])->lockForUpdate()->findOrFail($data['sale_item_id']);
            $outstanding = bcsub((string)$item->quantity, (string)$item->released_quantity, 4);
            if (bccomp((string)$data['quantity'], '0', 4) <= 0 || bccomp((string)$data['quantity'], $outstanding, 4) > 0) throw ValidationException::withMessages(['quantity'=>'Borrow quantity must not exceed the remaining sale quantity.']);
            $borrow = NeighbourStockBorrow::create(['business_id'=>$sale->business_id,'branch_id'=>$sale->branch_id,'sale_id'=>$sale->id,'sale_item_id'=>$item->id,'product_id'=>$item->product_id,'quantity'=>$data['quantity'],'conversion_factor'=>$item->conversion_factor,'neighbour_name'=>trim($data['neighbour_name']),'neighbour_phone'=>$data['neighbour_phone'] ?? null,'return_due_date'=>$data['return_due_date'] ?? null,'notes'=>$data['notes'] ?? null,'created_by'=>$actor->id]);
            $this->movements->execute($actor, $sale->branch, $item->product, StockStatus::Available, StockMovementType::AdjustmentIn, bcmul((string)$data['quantity'], (string)$item->conversion_factor, 4), 'Borrowed from neighbour: '.$borrow->neighbour_name, $borrow, (string)$item->cost_snapshot);
            return $borrow;
        });
    }
}
