<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\NumberSequence;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\Quotation;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateQuotationAction
{
    public function execute(User $actor, Business $business, array $data): Quotation
    {
        if (! $actor->hasPermissionInBusiness(PermissionName::QuotationsCreate, $business) || $business->id !== app(TenantContext::class)->businessId()) {
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
            throw ValidationException::withMessages(['items' => 'Add at least one quotation item.']);
        }
        if (! in_array($data['document_type'] ?? 'quotation', ['quotation', 'proforma'], true)) {
            throw ValidationException::withMessages(['document_type' => 'Select quotation or proforma.']);
        }

        return DB::transaction(function () use ($actor, $business, $branch, $data) {
            $sequence = NumberSequence::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->where('type', 'quotation')->lockForUpdate()->firstOrFail();
            $number = $sequence->prefix.str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT);
            $sequence->increment('next_number');
            $quotation = Quotation::query()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'customer_id' => $data['customer_id'] ?? null, 'quotation_number' => $number, 'document_type' => $data['document_type'] ?? 'quotation', 'status' => 'draft', 'walk_in_name' => $data['walk_in_name'] ?? null, 'walk_in_phone' => $data['walk_in_phone'] ?? null, 'walk_in_location' => $data['walk_in_location'] ?? null, 'quotation_date' => $data['quotation_date'], 'valid_until' => $data['valid_until'] ?? null, 'created_by' => $actor->id]);
            $subtotal = '0';
            $discountTotal = '0';
            foreach ($data['items'] as $index => $line) {
                $unit = ProductUnit::query()->with('product')->where('business_id', $business->id)->where('can_sell', true)->find($line['product_unit_id']);
                $price = $unit ? ProductPrice::query()->where('business_id', $business->id)->where('product_unit_id', $unit->id)->where('price_level_id', $line['price_level_id'])->where('is_active', true)->where('current_slot', true)->first() : null;
                if (! $unit || ! $price || ! is_numeric($line['quantity']) || bccomp((string) $line['quantity'], '0', 4) <= 0) {
                    throw ValidationException::withMessages(["items.$index" => 'Select a valid product price and quantity.']);
                }
                $original = (string) $price->amount;
                $applied = (string) ($line['applied_unit_price'] ?? $original);
                if (bccomp($original, $applied, 2) !== 0 && (! $actor->hasPermissionInBusiness(PermissionName::SalesOverridePrice, $business) || blank($line['override_reason'] ?? null))) {
                    throw ValidationException::withMessages(["items.$index.applied_unit_price" => 'A quoted price override requires permission and a reason.']);
                }
                $gross = bcmul((string) $line['quantity'], $applied, 2);
                $discount = (string) ($line['discount_amount'] ?? '0');
                if (! is_numeric($discount) || bccomp($discount, '0', 2) < 0 || bccomp($discount, $gross, 2) > 0) {
                    throw ValidationException::withMessages(["items.$index.discount_amount" => 'The discount is invalid.']);
                }
                $quotation->items()->create(['business_id' => $business->id, 'product_id' => $unit->product_id, 'product_unit_id' => $unit->id, 'quantity' => $line['quantity'], 'conversion_factor' => $unit->conversion_factor, 'original_unit_price' => $original, 'applied_unit_price' => $applied, 'discount_amount' => $discount, 'line_total' => bcsub($gross, $discount, 2)]);
                $subtotal = bcadd($subtotal, $gross, 2);
                $discountTotal = bcadd($discountTotal, $discount, 2);
            }
            $quotation->update(['subtotal' => $subtotal, 'discount_amount' => $discountTotal, 'total_amount' => bcsub($subtotal, $discountTotal, 2)]);

            return $quotation->load('items.product', 'customer');
        });
    }
}
