<?php

namespace App\Enums;

enum DefaultRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Cashier = 'cashier';
    case Storekeeper = 'storekeeper';
    case Accountant = 'accountant';
    case Salesperson = 'salesperson';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Shop Owner',
            self::Manager => 'Manager',
            self::Cashier => 'Cashier',
            self::Storekeeper => 'Storekeeper',
            self::Accountant => 'Accountant',
            self::Salesperson => 'Salesperson',
        };
    }
}
