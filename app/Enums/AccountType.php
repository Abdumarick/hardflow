<?php

namespace App\Enums;

enum AccountType: string
{
    case Cash = 'cash';
    case MobileMoney = 'mobile_money';
    case Bank = 'bank';
    case Card = 'card';
}
