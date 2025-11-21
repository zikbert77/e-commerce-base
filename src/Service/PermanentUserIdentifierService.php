<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;

class PermanentUserIdentifierService
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getUserSessionId(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $userId = $request->attributes->get(User::COOKIE_SESSION_ID_NAME);
        if (!$userId) {
            $userId = $request->cookies->get(User::COOKIE_SESSION_ID_NAME);
        }

        return $userId;
    }
}
