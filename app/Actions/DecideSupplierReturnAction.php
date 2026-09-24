<?php

namespace App\Actions;

use App\Enums\ApprovalStatus;
use App\Enums\PermissionName;
use App\Enums\StockMovementType;
use App\Enums\SupplierLedgerType;
use App\Enums\SupplierReturnStatus;
use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\StockBalance;
use App\Models\SupplierReturn;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecideSupplierReturnAction
{
    public function __construct(private ApplyStockMovementAction $movements, private PostSupplierLedgerEntryAction $ledger) {}

    public function approve(User $actor, SupplierReturn $return, string $reason): SupplierReturn
    {
        return $this->decide($actor, $return, SupplierReturnStatus::Approved, $reason);
    }

    public function reject(User $actor, SupplierReturn $return, string $reason): SupplierReturn
    {
        return $this->decide($actor, $return, SupplierReturnStatus::Rejected, $reason);
    }

    private function decide(User $actor, SupplierReturn $return, SupplierReturnStatus $decision, string $reason): SupplierReturn
    {
        $business = $return->branch->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::PurchasesReturnApprove, $business) || $return->business_id !== app(TenantContext::class)->businessId() || $return->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }
        if ($return->requested_by === $actor->id) {
            throw ValidationException::withMessages(['return' => 'The requester cannot approve or reject their own supplier return.']);
        }

        return DB::transaction(function () use ($actor, $return, $decision, $reason) {
            $locked = SupplierReturn::query()->with(['items.product', 'branch', 'supplier'])->lockForUpdate()->findOrFail($return->id);
            if ($locked->status !== SupplierReturnStatus::Pending) {
                throw ValidationException::withMessages(['return' => 'This supplier return has already been decided.']);
            }
            if ($decision === SupplierReturnStatus::Approved) {
                foreach ($locked->items as $item) {
                    $balance = StockBalance::query()->where('branch_id', $locked->branch_id)->where('product_id', $item->product_id)->where('stock_status', $item->stock_status->value)->lockForUpdate()->first();
                    if (! $balance || bccomp((string) $balance->quantity, (string) $item->quantity, 4) < 0) {
                        throw ValidationException::withMessages(['return' => "Stock changed after the request; {$item->product->name} no longer has enough {$item->stock_status->label()} stock."]);
                    }
                    $this->movements->execute($actor, $locked->branch, $item->product, $item->stock_status, StockMovementType::SupplierReturn, bcmul((string) $item->quantity, '-1', 4), $reason, $locked, (string) $item->unit_cost);
                }
                if (bccomp((string) $locked->total_amount, '0', 2) > 0) {
                    $this->ledger->execute($actor, $locked->supplier, SupplierLedgerType::ReturnCredit, '0', (string) $locked->total_amount, $locked->branch_id, $locked, 'Approved '.$locked->return_number);
                }
            }
            $locked->update(['status' => $decision, 'decided_by' => $actor->id, 'decision_reason' => trim($reason), 'decided_at' => now()]);
            ApprovalRequest::query()->where('subject_type', SupplierReturn::class)->where('subject_id', $locked->id)->where('status', ApprovalStatus::Pending)->update(['status' => $decision === SupplierReturnStatus::Approved ? ApprovalStatus::Approved : ApprovalStatus::Rejected, 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_reason' => trim($reason)]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'action' => 'supplier_return.'.$decision->value, 'subject_type' => SupplierReturn::class, 'subject_id' => $locked->id, 'new_values' => ['status' => $decision->value], 'reason' => $reason]);

            return $locked->refresh()->load('items.product', 'supplier');
        });
    }
}
