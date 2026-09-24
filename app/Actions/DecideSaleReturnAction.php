<?php

namespace App\Actions;

use App\Enums\ApprovalStatus;
use App\Enums\PermissionName;
use App\Enums\SaleReturnStatus;
use App\Enums\StockMovementType;
use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\SaleReturn;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecideSaleReturnAction
{
    public function __construct(
        private ApplyStockMovementAction $movements,
        private PostCustomerLedgerEntryAction $customerLedger,
    ) {}

    public function approve(User $actor, SaleReturn $return, string $reason): SaleReturn
    {
        return $this->decide($actor, $return, SaleReturnStatus::Approved, $reason);
    }

    public function reject(User $actor, SaleReturn $return, string $reason): SaleReturn
    {
        return $this->decide($actor, $return, SaleReturnStatus::Rejected, $reason);
    }

    private function decide(User $actor, SaleReturn $return, SaleReturnStatus $decision, string $reason): SaleReturn
    {
        $business = $return->branch->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::SalesReturnApprove, $business) || $return->business_id !== app(TenantContext::class)->businessId() || $return->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }
        if ($return->requested_by === $actor->id) {
            throw ValidationException::withMessages(['return' => 'The requester cannot approve or reject their own customer return.']);
        }

        return DB::transaction(function () use ($actor, $return, $decision, $reason) {
            $locked = SaleReturn::query()->with(['items.saleItem.product', 'branch', 'sale.customer'])->lockForUpdate()->findOrFail($return->id);
            if ($locked->status !== SaleReturnStatus::Pending) {
                throw ValidationException::withMessages(['return' => 'This customer return has already been decided.']);
            }

            if ($decision === SaleReturnStatus::Approved) {
                foreach ($locked->items as $item) {
                    $baseQuantity = bcmul((string) $item->quantity, (string) $item->saleItem->conversion_factor, 4);
                    $this->movements->execute($actor, $locked->branch, $item->saleItem->product, $item->stock_status, StockMovementType::CustomerReturn, $baseQuantity, 'Approved '.$locked->return_number, $locked, (string) $item->saleItem->cost_snapshot);
                }
                if ($locked->sale->customer) {
                    $this->customerLedger->execute($actor, $locked->sale->customer, 'sale_return', '0', (string) $locked->total_amount, $locked->branch_id, $locked, $locked->return_number, 'Approved customer return');
                }
            }

            $locked->update(['status' => $decision, 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_reason' => trim($reason)]);
            ApprovalRequest::query()->where('subject_type', SaleReturn::class)->where('subject_id', $locked->id)->where('status', ApprovalStatus::Pending)->update(['status' => $decision === SaleReturnStatus::Approved ? ApprovalStatus::Approved : ApprovalStatus::Rejected, 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_reason' => trim($reason)]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'action' => 'sale_return.'.$decision->value, 'subject_type' => SaleReturn::class, 'subject_id' => $locked->id, 'new_values' => ['status' => $decision->value], 'reason' => trim($reason)]);

            return $locked->refresh()->load('items.saleItem.product', 'sale');
        });
    }
}
