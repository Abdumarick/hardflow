<?php

namespace App\Enums;

enum ExpenseStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Posted = 'posted';
    case Reversed = 'reversed';
}
