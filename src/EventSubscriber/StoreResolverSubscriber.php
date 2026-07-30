<?php

namespace App\EventSubscriber;

use App\Service\Store\StoreContext;
use App\Service\Store\StoreResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class StoreResolverSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private StoreResolver          $resolver,
        private StoreContext           $context,
        private EntityManagerInterface $em,
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

        $host = $event->getRequest()->getHost();
        $store = $this->resolver->resolveByHost($host);

        if ($store === null) {
            throw new NotFoundHttpException('Store not found for host: ' . $host);
        }

        $this->context->set($store);

        $this->em->getFilters()
            ->enable('store_filter')
            ->setParameter('storeId', $store->getId());
    }
}
