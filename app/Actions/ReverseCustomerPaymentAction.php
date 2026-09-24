<?php

namespace App\Actions;

use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReverseCustomerPaymentAction
{
    public function __construct(private PostCustomerLedgerEntryAction $ledger) {}

    public function execute(User $actor, Payment $payment, string $reason): Payment
    {
        $business = app(TenantContext::class)->businessOrFail();
        if (! $actor->hasPermissionInBusiness(PermissionName::PaymentsReverse, $business) || $payment->business_id !== $business->id || $payment->branch_id !== app(TenantContext::class)->branchId()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $payment, $reason) {
            $locked = Payment::query()->with(['customer', 'allocations.sale'])->lockForUpdate()->findOrFail($payment->id);
            if ($locked->status !== PaymentRecordStatus::Confirmed) {
                throw ValidationException::withMessages(['payment' => 'This payment has already been reversed.']);
            }
            $locked->update(['status' => PaymentRecordStatus::Reversed, 'reversed_by' => $actor->id, 'reversed_at' => now(), 'reversal_reason' => trim($reason)]);
            foreach ($locked->allocations as $allocation) {
                $sale = $allocation->sale()->lockForUpdate()->firstOrFail();
                $paid = (string) $sale->paymentAllocations()->whereHas('payment', fn ($query) => $query->where('status', PaymentRecordStatus::Confirmed))->sum('payment_allocations.amount');
                $sale->update(['payment_status' => bccomp($paid, '0', 2) === 0 ? PaymentStatus::Unpaid : (bccomp($paid, (string) $sale->total_amount, 2) >= 0 ? PaymentStatus::Paid : PaymentStatus::PartiallyPaid)]);
            }
            if ($locked->customer) {
                $this->ledger->execute($actor, $locked->customer, 'payment_reversal', (string) $locked->amount, '0', $locked->branch_id, $locked, $locked->payment_number.'-REV', 'Reversal: '.trim($reason));
            }
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $locked->business_id, 'branch_id' => $locked->branch_id, 'action' => 'payment.reversed', 'subject_type' => Payment::class, 'subject_id' => $locked->id, 'new_values' => ['status' => PaymentRecordStatus::Reversed->value], 'reason' => trim($reason)]);

            return $locked->refresh();
        });
    }
}
