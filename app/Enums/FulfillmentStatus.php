<?php

namespace App\Enums;

enum FulfillmentStatus: string
{
    case OnHold = 'on_hold';
    case PartiallyReleased = 'partially_released';
    case Released = 'released';
}
