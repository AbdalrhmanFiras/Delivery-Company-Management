<?php

namespace App\Enums;

enum OrderStatus:int
{
    case Pending = 0;
    case AtWarehouse = 1;
    case AssignedDeliveryCompany = 2;
    case AssignedDriver = 3;
    case OutForDelivery = 4;
    case Delivered = 5;
    case Cancelled = 6;

}
