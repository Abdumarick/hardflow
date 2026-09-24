<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Enums\PurchaseStatus;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\NumberSequence;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePurchaseAction
{
    /** @param array{branch_id:int,supplier_id?:?int,purchase_date:string,supplier_reference?:?string,items:array<int,array{product_unit_id:int,quantity:string,unit_cost:string}>} $data */
    public function execute(User $actor, Business $business, array $data): Purchase
    {
        if (! $actor->hasPermissionInBusiness(PermissionName::PurchasesCreate, $business) || $business->id !== app(TenantContext::class)->businessId()) {
            throw new AuthorizationException;
        }
        $branch = Branch::query()->where('business_id', $business->id)->find($data['branch_id']);
        if (! $branch || $branch->id !== app(TenantContext::class)->branchId()) {
            throw ValidationException::withMessages(['branch_id' => 'The selected branch is not available.']);
        }
        if (($data['supplier_id'] ?? null) && ! Supplier::query()->where('business_id', $business->id)->where('is_active', true)->find($data['supplier_id'])) {
            throw ValidationException::withMessages(['supplier_id' => 'The selected supplier is not available.']);
        }
        if (empty($data['items'])) {
            throw ValidationException::withMessages(['items' => 'Add at least one purchase item.']);
        }

        return DB::transaction(function () use ($actor, $business, $branch, $data) {
            $sequence = NumberSequence::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->where('type', 'purchase')->lockForUpdate()->firstOrFail();
            $number = $sequence->prefix.str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT);
            $sequence->increment('next_number');
            $purchase = Purchase::query()->create([
                'business_id' => $business->id, 'branch_id' => $branch->id, 'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_number' => $number, 'supplier_reference' => $data['supplier_reference'] ?? null,
                'status' => PurchaseStatus::Draft, 'purchase_date' => $data['purchase_date'], 'created_by' => $actor->id,
            ]);
            $total = '0';
            foreach ($data['items'] as $index => $line) {
                $unit = ProductUnit::query()->with('product')->where('business_id', $business->id)->where('can_purchase', true)->find($line['product_unit_id']);
                if (! $unit || ! $unit->product->is_active || bccomp((string) $line['quantity'], '0', 4) <= 0 || bccomp((string) $line['unit_cost'], '0', 2) < 0) {
                    throw ValidationException::withMessages(["items.$index" => 'Select an active purchasable unit and enter valid quantities and costs.']);
                }
                $lineTotal = bcmul((string) $line['quantity'], (string) $line['unit_cost'], 2);
                $purchase->items()->create([
                    'business_id' => $business->id, 'product_id' => $unit->product_id, 'product_unit_id' => $unit->id,
                    'ordered_quantity' => $line['quantity'], 'unit_cost' => $line['unit_cost'],
                    'conversion_factor' => $unit->conversion_factor, 'line_total' => $lineTotal,
                ]);
                $total = bcadd($total, $lineTotal, 2);
            }
            $purchase->update(['total_amount' => $total]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $business->id, 'branch_id' => $branch->id, 'action' => 'purchase.created', 'subject_type' => Purchase::class, 'subject_id' => $purchase->id, 'new_values' => ['purchase_number' => $number, 'total_amount' => $total]]);

            return $purchase->load('items.product', 'items.productUnit.unit', 'supplier');
        });
    }
}
