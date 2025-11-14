<?php

namespace App\Entity\Enum;

enum CartStatus: int
{
    case Active = 1;
    case Inactive = 0;
}
