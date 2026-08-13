<?php

namespace App\Controller;

use App\Store\StoreContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BaseController extends AbstractController
{
    public function __construct(
        protected readonly StoreContext $storeContext
    )
    {
    }
}
