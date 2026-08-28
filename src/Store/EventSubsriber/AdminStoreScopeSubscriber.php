<?php

namespace App\Store\EventSubsriber;

use App\Entity\Store;
use App\Entity\User;
use App\Store\StoreConfigProvider;
use App\Store\StoreContext;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Scopes /admin requests to a store chosen via the sidebar switcher instead
 * of the request host (see StoreResolverSubscriber, which skips the admin
 * host entirely). Default (no session key) is aggregate mode: every store
 * the logged-in admin is linked to.
 */
final readonly class AdminStoreScopeSubscriber implements EventSubscriberInterface
{
    public const SESSION_KEY = 'admin_selected_store_id';

    public function __construct(
        private Security               $security,
        private StoreContext           $storeContext,
        private StoreConfigProvider    $configProvider,
        private EntityManagerInterface $em,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Must run after FirewallListener (priority 8), which attaches
            // the lazy firewall context that Security::getUser() needs.
            KernelEvents::REQUEST => ['onKernelRequest', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/admin')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $linkedIds = array_map(
            static fn (Store $store) => $store->getId(),
            $user->getStores()->toArray(),
        );

        $session = $request->getSession();
        $selectedId = $session->get(self::SESSION_KEY);
        if ($selectedId !== null && !in_array($selectedId, $linkedIds, true)) {
            // Store no longer linked to this user (e.g. unlinked since it
            // was chosen) — reset to aggregate rather than crash.
            $session->remove(self::SESSION_KEY);
            $selectedId = null;
        }

        if ($selectedId !== null) {
            $storeDto = $this->configProvider->getConfig($selectedId);
            $this->storeContext->set($storeDto);

            $this->em->getFilters()
                ->enable('store_filter')
                ->setParameterList('storeIds', [$selectedId], Types::INTEGER);
        } else {
            // Aggregate mode. $linkedIds may be empty — StoreFilter turns
            // that into "1 = 0" rather than an invalid empty IN(). StoreContext
            // stays uninitialized on purpose: every existing
            // `$storeContext->isInitialized()` check already means "exactly
            // one concrete store in scope" and keeps working unchanged.
            $this->em->getFilters()
                ->enable('store_filter')
                ->setParameterList('storeIds', $linkedIds, Types::INTEGER);
        }
    }
}
