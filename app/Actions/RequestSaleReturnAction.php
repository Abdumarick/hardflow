<?php

namespace App\Actions;

use App\Enums\ApprovalStatus;
use App\Enums\PermissionName;
use App\Enums\SaleReturnStatus;
use App\Enums\SaleStatus;
use App\Enums\StockStatus;
use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestSaleReturnAction
{
    public function execute(User $actor, Sale $sale, array $data): SaleReturn
    {
        $business = $sale->branch->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::SalesReturnRequest, $business) || $sale->business_id !== app(TenantContext::class)->businessId() || $sale->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }
        if ($sale->status !== SaleStatus::Confirmed) {
            throw ValidationException::withMessages(['sale' => 'Only a confirmed sale can receive a customer return.']);
        }

        return DB::transaction(function () use ($actor, $sale, $data) {
            $lockedSale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            $number = 'SRT-'.now()->format('Ymd').'-'.str_pad((string) (SaleReturn::query()->where('business_id', $sale->business_id)->lockForUpdate()->count() + 1), 5, '0', STR_PAD_LEFT);
            $return = SaleReturn::query()->create(['business_id' => $sale->business_id, 'branch_id' => $sale->branch_id, 'sale_id' => $sale->id, 'return_number' => $number, 'status' => SaleReturnStatus::Pending, 'reason' => trim($data['reason']), 'requested_by' => $actor->id]);
            $total = '0';

            foreach ($data['items'] as $index => $line) {
                $item = SaleItem::query()->where('sale_id', $lockedSale->id)->lockForUpdate()->find($line['sale_item_id']);
                if (! $item) {
                    throw ValidationException::withMessages(["items.$index.sale_item_id" => 'The selected sale item does not belong to this sale.']);
                }
                $committed = DB::table('sale_return_items')->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')->where('sale_return_items.sale_item_id', $item->id)->where('sale_returns.status', '!=', SaleReturnStatus::Rejected->value)->sum('sale_return_items.quantity');
                $remaining = bcsub((string) $item->released_quantity, (string) $committed, 4);
                if (bccomp((string) $line['quantity'], '0', 4) <= 0 || bccomp((string) $line['quantity'], $remaining, 4) > 0) {
                    throw ValidationException::withMessages(["items.$index.quantity" => 'Return quantity exceeds the released quantity still eligible for return.']);
                }
                $status = StockStatus::from($line['stock_status']);
                $unitAmount = bcdiv((string) $item->line_total, (string) $item->quantity, 2);
                $lineTotal = bcmul($unitAmount, (string) $line['quantity'], 2);
                $return->items()->create(['business_id' => $sale->business_id, 'sale_item_id' => $item->id, 'stock_status' => $status, 'quantity' => $line['quantity'], 'unit_amount' => $unitAmount, 'line_total' => $lineTotal]);
                $total = bcadd($total, $lineTotal, 2);
            }

            $return->update(['total_amount' => $total]);
            ApprovalRequest::query()->create(['business_id' => $return->business_id, 'branch_id' => $return->branch_id, 'subject_type' => SaleReturn::class, 'subject_id' => $return->id, 'action_type' => 'sale_return.approve', 'status' => ApprovalStatus::Pending, 'amount' => $total, 'request_reason' => trim($data['reason']), 'requested_by' => $actor->id]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $sale->business_id, 'branch_id' => $sale->branch_id, 'action' => 'sale_return.requested', 'subject_type' => SaleReturn::class, 'subject_id' => $return->id, 'new_values' => ['return_number' => $number, 'total_amount' => $total], 'reason' => trim($data['reason'])]);

            return $return->load('items.saleItem.product', 'sale');
        });
    }
}
