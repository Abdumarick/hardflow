<?php

namespace App\Enums;

enum SupplierLedgerType: string
{
    case Charge = 'charge';
    case Payment = 'payment';
    case ReturnCredit = 'return_credit';
    case Adjustment = 'adjustment';
}
