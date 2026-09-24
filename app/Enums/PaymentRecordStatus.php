<?php

namespace App\Enums;

enum PaymentRecordStatus: string
{
    case Confirmed = 'confirmed';
    case Reversed = 'reversed';
}
