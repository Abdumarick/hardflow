<?php

namespace App\Enums;

enum StockStatus: string
{
    case Available = 'available';
    case Damaged = 'damaged';
    case Expired = 'expired';
    case Returned = 'returned';
    case UnderInspection = 'under_inspection';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->headline()->toString();
    }
}
