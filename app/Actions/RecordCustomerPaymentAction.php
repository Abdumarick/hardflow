<?php

namespace App\Actions;

use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\PermissionName;
use App\Enums\SaleStatus;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Sale;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordCustomerPaymentAction
{
    public function __construct(private PostCustomerLedgerEntryAction $ledger) {}

    public function execute(User $actor, Customer $customer, PaymentAccount $account, array $data): Payment
    {
        $business = app(TenantContext::class)->businessOrFail();
        $branchId = app(TenantContext::class)->branchId();
        if (! $actor->hasPermissionInBusiness(PermissionName::PaymentsCreate, $business) || $customer->business_id !== $business->id || $account->business_id !== $business->id || ($account->branch_id !== null && $account->branch_id !== $branchId)) {
            throw new AuthorizationException;
        }
        if ($account->method->requires_reference && blank($data['external_reference'] ?? null)) {
            throw ValidationException::withMessages(['external_reference' => 'This payment method requires an external transaction reference.']);
        }

        return DB::transaction(function () use ($actor, $customer, $account, $data, $business, $branchId) {
            $total = collect($data['allocations'])->reduce(fn ($sum, $line) => bcadd($sum, (string) $line['amount'], 2), '0');
            if (bccomp($total, (string) $data['amount'], 2) !== 0 || bccomp($total, '0', 2) <= 0) {
                throw ValidationException::withMessages(['allocations' => 'Payment allocations must be positive and equal the payment amount.']);
            }

            $payment = Payment::query()->create(['business_id' => $business->id, 'branch_id' => $branchId, 'customer_id' => $customer->id, 'payment_account_id' => $account->id, 'payment_number' => 'PAY-'.now()->format('Ymd').'-'.str_pad((string) (Payment::query()->where('business_id', $business->id)->count() + 1), 5, '0', STR_PAD_LEFT), 'status' => PaymentRecordStatus::Confirmed, 'payment_date' => $data['payment_date'] ?? now()->toDateString(), 'amount' => $total, 'external_reference' => $data['external_reference'] ?? null, 'notes' => $data['notes'] ?? null, 'received_by' => $actor->id]);

            foreach ($data['allocations'] as $index => $line) {
                $sale = Sale::query()->where('business_id', $business->id)->where('branch_id', $branchId)->where('customer_id', $customer->id)->lockForUpdate()->find($line['sale_id']);
                if (! $sale || $sale->status !== SaleStatus::Confirmed) {
                    throw ValidationException::withMessages(["allocations.$index.sale.sale_id" => 'Select a confirmed sale belonging to this customer and branch.']);
                }
                $paid = (string) $sale->paymentAllocations()->whereHas('payment', fn ($query) => $query->where('status', PaymentRecordStatus::Confirmed))->sum('payment_allocations.amount');
                $balance = bcsub((string) $sale->total_amount, $paid, 2);
                if (bccomp((string) $line['amount'], '0', 2) <= 0 || bccomp((string) $line['amount'], $balance, 2) > 0) {
                    throw ValidationException::withMessages(["allocations.$index.amount" => 'Allocation exceeds the outstanding sale balance.']);
                }
                $payment->allocations()->create(['business_id' => $business->id, 'sale_id' => $sale->id, 'amount' => $line['amount']]);
                $newPaid = bcadd($paid, (string) $line['amount'], 2);
                $sale->update(['payment_status' => bccomp($newPaid, (string) $sale->total_amount, 2) >= 0 ? PaymentStatus::Paid : PaymentStatus::PartiallyPaid]);
            }

            $this->ledger->execute($actor, $customer, 'payment', '0', $total, $branchId, $payment, $payment->payment_number, 'Customer payment received');
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $business->id, 'branch_id' => $branchId, 'action' => 'payment.recorded', 'subject_type' => Payment::class, 'subject_id' => $payment->id, 'new_values' => ['amount' => $total, 'account_id' => $account->id]]);

            return $payment->load('allocations.sale', 'account.method', 'customer');
        });
    }
}
