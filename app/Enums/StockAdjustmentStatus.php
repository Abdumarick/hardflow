<?php

namespace App\Enums;

enum StockAdjustmentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
