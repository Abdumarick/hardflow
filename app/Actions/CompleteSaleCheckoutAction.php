<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Enums\SaleStatus;
use App\Models\PaymentAccount;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteSaleCheckoutAction
{
    public function __construct(
        private ConfirmSaleAction $confirmSale,
        private RecordSalePaymentAction $recordPayment,
    ) {}

    /** @param array<int, array{payment_account_id:int,amount:string,external_reference?:?string}> $payments */
    public function execute(User $actor, Sale $sale, array $payments): Sale
    {
        $business = $sale->branch->business;
        if (! $actor->hasPermissionInBusiness(PermissionName::SalesConfirm, $business)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $sale, $payments, $business): Sale {
            $locked = Sale::query()->with('branch.business')->lockForUpdate()->findOrFail($sale->id);
            if ($locked->status !== SaleStatus::Draft) {
                throw ValidationException::withMessages(['sale' => 'Only a draft sale can be completed from checkout.']);
            }

            $accounts = PaymentAccount::query()
                ->with('method')
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->where(fn ($query) => $query->where('branch_id', $locked->branch_id)->orWhereNull('branch_id'))
                ->whereIn('id', collect($payments)->pluck('payment_account_id'))
                ->get()
                ->keyBy('id');

            foreach ($payments as $index => $payment) {
                $account = $accounts->get($payment['payment_account_id']);
                if (! $account || ! $account->method->is_active) {
                    throw ValidationException::withMessages(["payments.$index.payment_account_id" => 'Choose an active payment method for this branch.']);
                }
                if (! is_numeric($payment['amount']) || bccomp((string) $payment['amount'], '0', 2) <= 0) {
                    throw ValidationException::withMessages(["payments.$index.amount" => 'Enter a payment amount greater than zero.']);
                }
                if ($account->method->requires_reference && blank($payment['external_reference'] ?? null)) {
                    throw ValidationException::withMessages(["payments.$index.external_reference" => 'This payment method requires a transaction reference.']);
                }
            }

            $confirmed = $this->confirmSale->execute($actor, $locked);
            foreach ($payments as $payment) {
                $this->recordPayment->execute($actor, $confirmed, $accounts->get($payment['payment_account_id']), (string) $payment['amount'], $payment['external_reference'] ?? null);
            }

            return $confirmed->refresh();
        });
    }
}
