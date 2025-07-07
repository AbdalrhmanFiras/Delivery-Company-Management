<?php

namespace App\Enums;

enum OrderStatus: int
{
    case Pending = 0; //done
    case AtWarehouse = 1; //done
    case AssignedDeliveryCompany = 2; //done
    case AssignedDriver = 3;
    case OutForDelivery = 4;
    case Delivered = 5;
    case Cancelled = 6;
}
