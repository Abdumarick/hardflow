<?php

namespace App\Actions;

use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\PermissionName;
use App\Enums\SaleStatus;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Sale;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordSalePaymentAction
{
    public function __construct(private PostCustomerLedgerEntryAction $ledger) {}

    public function execute(User $actor, Sale $sale, PaymentAccount $account, string $amount, ?string $reference = null, ?string $notes = null): Payment
    {
        $business = app(TenantContext::class)->businessOrFail();
        $branchId = app(TenantContext::class)->branchId();
        if (! $actor->hasPermissionInBusiness(PermissionName::PaymentsCreate, $business)
            || $sale->business_id !== $business->id
            || $sale->branch_id !== $branchId
            || $account->business_id !== $business->id
            || ($account->branch_id !== null && $account->branch_id !== $branchId)) {
            throw new AuthorizationException;
        }
        if ($account->method->requires_reference && blank($reference)) {
            throw ValidationException::withMessages(['external_reference' => 'This payment method requires an external transaction reference.']);
        }

        return DB::transaction(function () use ($actor, $sale, $account, $amount, $reference, $notes, $business) {
            $locked = Sale::query()->with('customer')->lockForUpdate()->findOrFail($sale->id);
            if ($locked->status !== SaleStatus::Confirmed) {
                throw ValidationException::withMessages(['sale' => 'Only confirmed sales can receive payments.']);
            }
            $paid = (string) $locked->paymentAllocations()->whereHas('payment', fn ($query) => $query->where('status', PaymentRecordStatus::Confirmed))->sum('payment_allocations.amount');
            $balance = bcsub((string) $locked->total_amount, $paid, 2);
            if (bccomp($amount, '0', 2) <= 0 || bccomp($amount, $balance, 2) > 0) {
                throw ValidationException::withMessages(['amount' => 'Payment amount exceeds the outstanding sale balance.']);
            }
            $payment = Payment::query()->create(['business_id' => $business->id, 'branch_id' => $locked->branch_id, 'customer_id' => $locked->customer_id, 'payment_account_id' => $account->id, 'payment_number' => 'PAY-'.now()->format('Ymd').'-'.str_pad((string) (Payment::query()->where('business_id', $business->id)->count() + 1), 5, '0', STR_PAD_LEFT), 'status' => PaymentRecordStatus::Confirmed, 'payment_date' => now()->toDateString(), 'amount' => $amount, 'external_reference' => $reference, 'notes' => $notes, 'received_by' => $actor->id]);
            $payment->allocations()->create(['business_id' => $business->id, 'sale_id' => $locked->id, 'amount' => $amount]);
            $newPaid = bcadd($paid, $amount, 2);
            $locked->update(['payment_status' => bccomp($newPaid, (string) $locked->total_amount, 2) >= 0 ? PaymentStatus::Paid : PaymentStatus::PartiallyPaid]);
            if ($locked->customer) {
                $this->ledger->execute($actor, $locked->customer, 'payment', '0', $amount, $locked->branch_id, $payment, $payment->payment_number, 'Customer payment received');
            }
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $business->id, 'branch_id' => $locked->branch_id, 'action' => 'payment.recorded', 'subject_type' => Payment::class, 'subject_id' => $payment->id, 'new_values' => ['amount' => $amount, 'sale_id' => $locked->id, 'account_id' => $account->id]]);

            return $payment->load('allocations.sale', 'account.method', 'customer');
        });
    }
}
