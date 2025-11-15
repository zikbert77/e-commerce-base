<?php

namespace App\Entity\Enum;

enum OrderStatus: int
{
    case NEW = 0;
    case CONFIRMED = 1;
    case PAID = 2;
    case SHIPPED = 3;
    case COMPLETED = 4;
    case CANCELLED = 5;
}
