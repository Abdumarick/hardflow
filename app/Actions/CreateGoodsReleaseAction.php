<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Enums\SaleStatus;
use App\Models\GoodsRelease;
use App\Models\Sale;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateGoodsReleaseAction
{
    public function execute(User $actor, Sale $sale, array $data): GoodsRelease
    {
        $business = $sale->branch->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::SalesRelease, $business) || $sale->business_id !== app(TenantContext::class)->businessId() || $sale->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }
        if ($sale->status !== SaleStatus::Confirmed || empty($data['items'])) {
            throw ValidationException::withMessages(['sale' => 'A confirmed sale and release items are required.']);
        }

        return DB::transaction(function () use ($actor, $sale, $data) {
            $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            $release = GoodsRelease::query()->create([
                'business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'sale_id' => $locked->id,
                'release_number' => $locked->sale_number.'-REL-'.str_pad((string) ($locked->releases()->count() + 1), 2, '0', STR_PAD_LEFT),
                'status' => 'draft', 'created_by' => $actor->id, 'released_at' => $data['released_at'] ?? now(), 'notes' => $data['notes'] ?? null,
            ]);
            foreach ($data['items'] as $index => $line) {
                $item = $locked->items()->find($line['sale_item_id']);
                $quantity = (string) $line['quantity'];
                if (! $item || ! is_numeric($quantity) || bccomp($quantity, '0', 4) <= 0 || bccomp($quantity, bcsub((string) $item->quantity, (string) $item->released_quantity, 4), 4) > 0) {
                    throw ValidationException::withMessages(["items.$index" => 'The release quantity is invalid or exceeds the sale outstanding quantity.']);
                }
                $release->items()->create(['business_id' => $locked->business_id, 'sale_item_id' => $item->id, 'quantity' => $quantity]);
            }

            return $release->load('items.saleItem.product');
        });
    }
}
