<?php

namespace App\Enums;

enum OrderStatus: int
{
    case AtWarehouse = 1; //done
    case AssignedDeliveryCompany = 2; //done
    case AssignedDriver = 3; //done
    case OutForDelivery = 4; //done
    case Delivered = 5; //done
    case Cancelled = 6;




    public function labelForCustomer(): string
    {
        return match ($this) {
            self::AtWarehouse,
            self::AssignedDeliveryCompany,
            self::AssignedDriver => 'Preparing your order',

            self::OutForDelivery => 'Out for delivery',
            self::Delivered      => 'Delivered',
            self::Cancelled      => 'Cancelled',

            default => 'Processing',
        };
    }
}
