<?php

namespace App\Store\EventSubsriber;

use App\Store\StoreConfigProvider;
use App\Store\StoreContext;
use App\Store\StoreResolver;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Store\Exception\StoreNotFoundException;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class StoreResolverSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private StoreResolver          $resolver,
        private StoreContext           $context,
        private StoreConfigProvider    $configProvider,
        private EntityManagerInterface $em,
        private string                 $adminHost,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 30],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        // dev toolbar, profiler, fragment-рендери
        if (str_starts_with($request->getPathInfo(), '/_')) {
            return;
        }

        // The admin module lives on its own fixed host and resolves its
        // store from a session-backed switcher instead (see
        // AdminStoreScopeSubscriber) — that host has no StoreDomain row by
        // design, so resolving it here would always throw.
        if ($this->resolver->normalizeHost($request->getHost()) === $this->resolver->normalizeHost($this->adminHost)) {
            return;
        }

        $host = $request->getHost();
        $storeId = $this->resolver->resolveStoreIdByHost($host);
        if (empty($storeId)) {
            throw new StoreNotFoundException('Store not found for host: ' . $host);
        }

        $storeDto = $this->configProvider->getConfig($storeId);

        $this->context->set($storeDto);

        $this->em->getFilters()
            ->enable('store_filter')
            ->setParameterList('storeIds', [$storeDto->getId()], Types::INTEGER);
    }
}
