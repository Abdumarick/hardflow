<?php

namespace App\Enums;

enum SaleReturnStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
