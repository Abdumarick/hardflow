<?php

namespace App\Actions;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\PermissionName;
use App\Enums\SaleStatus;
use App\Enums\StockStatus;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\NumberSequence;
use App\Models\Quotation;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertQuotationToSaleAction
{
    public function execute(User $actor, Quotation $quotation): Sale
    {
        $business = $quotation->branch->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::QuotationsConvert, $business) || $quotation->business_id !== app(TenantContext::class)->businessId() || $quotation->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $quotation, $business) {
            $locked = Quotation::query()->with(['items.productUnit', 'branch'])->lockForUpdate()->findOrFail($quotation->id);
            if ($locked->status === 'converted' || $locked->converted_sale_id) {
                throw ValidationException::withMessages(['quotation' => 'This quotation has already been converted.']);
            }
            if ($locked->valid_until && $locked->valid_until->isPast()) {
                throw ValidationException::withMessages(['quotation' => 'This quotation has expired.']);
            }
            $sequence = NumberSequence::query()->where('business_id', $locked->business_id)->where('branch_id', $locked->branch_id)->where('type', 'sale')->lockForUpdate()->firstOrFail();
            $number = $sequence->prefix.str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT);
            $sequence->increment('next_number');
            $sale = Sale::query()->create(['business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'customer_id' => $locked->customer_id, 'quotation_id' => $locked->id, 'sale_number' => $number, 'status' => SaleStatus::Draft, 'payment_status' => PaymentStatus::Unpaid, 'fulfillment_status' => FulfillmentStatus::OnHold, 'walk_in_name' => $locked->walk_in_name, 'walk_in_phone' => $locked->walk_in_phone, 'sale_date' => now()->toDateString(), 'subtotal' => $locked->subtotal, 'discount_amount' => $locked->discount_amount, 'total_amount' => $locked->total_amount, 'created_by' => $actor->id]);
            $allowBelowCost = (bool) (BusinessSetting::query()->where('business_id', $business->id)->where('key', 'allow_selling_below_cost')->first()?->value ?? false);
            foreach ($locked->items as $item) {
                $baseCost = (string) (StockBalance::query()->where('branch_id', $locked->branch_id)->where('product_id', $item->product_id)->where('stock_status', StockStatus::Available->value)->value('average_cost') ?? '0');
                $unitCost = bcmul($baseCost, (string) $item->conversion_factor, 2);
                if (! $allowBelowCost && bccomp((string) $item->applied_unit_price, $unitCost, 2) < 0) {
                    throw ValidationException::withMessages(['quotation' => 'A quoted price is now below current cost and cannot be converted.']);
                }
                $overridden = bccomp((string) $item->original_unit_price, (string) $item->applied_unit_price, 2) !== 0;
                $sale->items()->create(['business_id' => $locked->business_id, 'product_id' => $item->product_id, 'product_unit_id' => $item->product_unit_id, 'quantity' => $item->quantity, 'conversion_factor' => $item->conversion_factor, 'original_unit_price' => $item->original_unit_price, 'applied_unit_price' => $item->applied_unit_price, 'discount_amount' => $item->discount_amount, 'cost_snapshot' => $baseCost, 'line_total' => $item->line_total, 'price_overridden_by' => $overridden ? $actor->id : null, 'price_override_reason' => $overridden ? 'Converted from '.$locked->quotation_number : null]);
            }
            $locked->update(['status' => 'converted', 'converted_sale_id' => $sale->id]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'action' => 'quotation.converted', 'subject_type' => Quotation::class, 'subject_id' => $locked->id, 'new_values' => ['sale_id' => $sale->id, 'sale_number' => $number]]);

            return $sale->load('items.product', 'customer');
        });
    }
}
