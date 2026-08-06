<?php

namespace App\Enum;

enum CacheLifetime: int
{
    case STORE_RESOLVER = 300;
    case ONE_HOUR = 3600;
}
