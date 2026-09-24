<?php

namespace App\Actions;

use App\Models\PaymentAccount;

class PaymentAccountBalanceAction
{
    public function execute(PaymentAccount $account): string
    {
        $receipts = (string) $account->payments()->where('status', 'confirmed')->sum('amount');
        $incoming = (string) $account->incomingTransfers()->where('status', 'approved')->sum('amount');
        $outgoing = (string) $account->outgoingTransfers()->where('status', 'approved')->sum('amount');
        $expenses = (string) $account->expenses()->where('status', 'posted')->sum('amount');

        return bcsub(bcsub(bcadd($receipts, $incoming, 2), $outgoing, 2), $expenses, 2);
    }
}
