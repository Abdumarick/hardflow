<?php

namespace App\Actions;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\PermissionName;
use App\Enums\SaleStatus;
use App\Enums\StockStatus;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\NumberSequence;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSaleAction
{
    /** @param array{branch_id:int,customer_id?:?int,walk_in_name?:?string,walk_in_phone?:?string,sale_date:string,items:array<int,array{product_unit_id:int,price_level_id:int,quantity:string,applied_unit_price?:string,discount_amount?:string,override_reason?:?string}>} $data */
    public function execute(User $actor, Business $business, array $data): Sale
    {
        if (! $actor->hasPermissionInBusiness(PermissionName::SalesCreate, $business) || $business->id !== app(TenantContext::class)->businessId()) {
            throw new AuthorizationException;
        }
        $branch = Branch::query()->where('business_id', $business->id)->find($data['branch_id']);
        if (! $branch || $branch->id !== app(TenantContext::class)->branchId()) {
            throw ValidationException::withMessages(['branch_id' => 'The selected branch is not available.']);
        }
        if (($data['customer_id'] ?? null) && ! Customer::query()->where('business_id', $business->id)->where('is_active', true)->find($data['customer_id'])) {
            throw ValidationException::withMessages(['customer_id' => 'The selected customer is not available.']);
        }
        if (empty($data['items'])) {
            throw ValidationException::withMessages(['items' => 'Add at least one sale item.']);
        }

        return DB::transaction(function () use ($actor, $business, $branch, $data) {
            $customer = null;
            $name = trim((string) ($data['walk_in_name'] ?? '')) ?: 'Walk-in customer';
            $phone = trim((string) ($data['walk_in_phone'] ?? ''));
            if (filled($data['customer_id'] ?? null)) {
                $customer = Customer::query()->findOrFail($data['customer_id']);
            }
            $sequence = NumberSequence::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->where('type', 'sale')->lockForUpdate()->firstOrFail();
            $number = $sequence->prefix.str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT);
            $sequence->increment('next_number');
            $sale = Sale::query()->create([
                'business_id' => $business->id, 'branch_id' => $branch->id, 'customer_id' => $customer?->id,
                'sale_number' => $number, 'status' => SaleStatus::Draft, 'payment_status' => PaymentStatus::Unpaid,
                'fulfillment_status' => FulfillmentStatus::OnHold, 'walk_in_name' => $customer ? null : $name,
                'walk_in_phone' => $customer ? null : ($phone ?: null), 'sale_date' => $data['sale_date'], 'due_date' => $data['due_date'] ?? null, 'created_by' => $actor->id,
            ]);
            $subtotal = '0';
            $discountTotal = '0';
            $allowBelowCost = (bool) (BusinessSetting::query()->where('business_id', $business->id)->where('key', 'allow_selling_below_cost')->first()?->value ?? false);
            $requestedBaseByProduct = [];
            foreach ($data['items'] as $index => $line) {
                $unit = ProductUnit::query()->with(['product', 'unit'])->where('business_id', $business->id)->where('can_sell', true)->where('is_active', true)->find($line['product_unit_id']);
                $price = $unit ? ProductPrice::query()->where('business_id', $business->id)->where('product_unit_id', $unit->id)->where('price_level_id', $line['price_level_id'])->where('is_active', true)->where('current_slot', true)->first() : null;
                if (! $unit || ! $unit->product->is_active || ! $price || ! is_numeric($line['quantity']) || bccomp((string) $line['quantity'], '0', 4) <= 0) {
                    throw ValidationException::withMessages(["items.$index" => 'Select an active sellable product price and enter a valid quantity.']);
                }
                $requestedBaseByProduct[$unit->product_id] = bcadd($requestedBaseByProduct[$unit->product_id] ?? '0', bcmul((string) $line['quantity'], (string) $unit->conversion_factor, 4), 4);
                $availableBase = (string) (StockBalance::query()->where('branch_id', $branch->id)->where('product_id', $unit->product_id)->where('stock_status', StockStatus::Available->value)->lockForUpdate()->value('quantity') ?? '0');
                if (bccomp($requestedBaseByProduct[$unit->product_id], $availableBase, 4) > 0) {
                    $availableInSelectedUnit = bcdiv($availableBase, (string) $unit->conversion_factor, 4);
                    throw ValidationException::withMessages(["items.$index.quantity" => "Insufficient stock for {$unit->product->name}. Only ".rtrim(rtrim($availableInSelectedUnit, '0'), '.')." {$unit->unit->symbol} is available. Reduce the quantity or borrow stock before selling."]);
                }
                $original = (string) $price->amount;
                $applied = (string) ($line['applied_unit_price'] ?? $original);
                $overridden = bccomp($original, $applied, 2) !== 0;
                if ($overridden && (! $actor->hasPermissionInBusiness(PermissionName::SalesOverridePrice, $business) || blank($line['override_reason'] ?? null))) {
                    throw ValidationException::withMessages(["items.$index.applied_unit_price" => 'A price override requires permission and a reason.']);
                }
                $baseCost = (string) (StockBalance::query()->where('branch_id', $branch->id)->where('product_id', $unit->product_id)->where('stock_status', StockStatus::Available->value)->value('average_cost') ?? '0');
                $selectedUnitCost = bcmul($baseCost, (string) $unit->conversion_factor, 2);
                if (! $allowBelowCost && bccomp($applied, $selectedUnitCost, 2) < 0) {
                    throw ValidationException::withMessages(["items.$index.applied_unit_price" => 'Selling below weighted-average cost is not allowed.']);
                }
                $gross = bcmul((string) $line['quantity'], $applied, 2);
                $discount = (string) ($line['discount_amount'] ?? '0');
                if (! is_numeric($discount) || bccomp($discount, '0', 2) < 0 || bccomp($discount, $gross, 2) > 0) {
                    throw ValidationException::withMessages(["items.$index.discount_amount" => 'The line discount is invalid.']);
                }
                $sale->items()->create([
                    'business_id' => $business->id, 'product_id' => $unit->product_id, 'product_unit_id' => $unit->id,
                    'quantity' => $line['quantity'], 'conversion_factor' => $unit->conversion_factor,
                    'original_unit_price' => $original, 'applied_unit_price' => $applied, 'discount_amount' => $discount,
                    'cost_snapshot' => $baseCost, 'line_total' => bcsub($gross, $discount, 2),
                    'price_overridden_by' => $overridden ? $actor->id : null, 'price_override_reason' => $overridden ? trim($line['override_reason']) : null,
                ]);
                $subtotal = bcadd($subtotal, $gross, 2);
                $discountTotal = bcadd($discountTotal, $discount, 2);
            }
            $sale->update(['subtotal' => $subtotal, 'discount_amount' => $discountTotal, 'total_amount' => bcsub($subtotal, $discountTotal, 2)]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $business->id, 'branch_id' => $branch->id, 'action' => 'sale.created', 'subject_type' => Sale::class, 'subject_id' => $sale->id, 'new_values' => ['sale_number' => $number, 'total_amount' => $sale->total_amount]]);

            return $sale->load('items.product', 'customer');
        });
    }
}
