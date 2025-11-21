<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Random\RandomException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Cookie;

class PermanentUserIdentifierSubscriber implements EventSubscriberInterface
{
    private ?string $userSessionId = null;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    /**
     * @throws RandomException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->cookies->has(User::COOKIE_SESSION_ID_NAME)) {
            $this->userSessionId = $this->generateUniqueId();
        } else {
            $this->userSessionId = $request->cookies->get(User::COOKIE_SESSION_ID_NAME);
        }

        $request->attributes->set(User::COOKIE_SESSION_ID_NAME, $this->userSessionId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        if (!$request->cookies->has(User::COOKIE_SESSION_ID_NAME) && $this->userSessionId) {
            $cookie = Cookie::create(User::COOKIE_SESSION_ID_NAME)
                ->withValue($this->userSessionId)
                ->withExpires(strtotime('+10 years'))
                ->withPath('/')
                ->withSecure(false) //@todo: Change on prod to true
                ->withHttpOnly()
                ->withSameSite(Cookie::SAMESITE_LAX);

            $response->headers->setCookie($cookie);
        }
    }

    /**
     * @throws RandomException
     */
    private function generateUniqueId(): string
    {
        return uniqid('user-session-', true) . '-' . bin2hex(random_bytes(11));
    }
}
