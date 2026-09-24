<?php

namespace App\Enums;

enum SupplierReturnStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
