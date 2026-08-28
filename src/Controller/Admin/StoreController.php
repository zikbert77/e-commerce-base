<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Store;
use App\Entity\StoreDomain;
use App\Entity\StoreTemplateConfig;
use App\Enum\BaseStatus;
use App\Form\Admin\StoreDomainType;
use App\Form\Admin\StoreTemplateConfigType;
use App\Form\Admin\StoreType;
use App\Repository\StoreDomainRepository;
use App\Repository\StoreRepository;
use App\Repository\StoreTemplateConfigRepository;
use App\Store\EventSubsriber\AdminStoreScopeSubscriber;
use App\Store\StoreContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/stores', host: '%admin_host%')]
class StoreController extends BaseController
{
    public function __construct(
        StoreContext $storeContext,
        private readonly StoreRepository $storeRepository,
        private readonly StoreDomainRepository $storeDomainRepository,
        private readonly StoreTemplateConfigRepository $storeTemplateConfigRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
        parent::__construct($storeContext);
    }

    #[Route('', name: 'admin_stores', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/store/index.html.twig', [
            'stores' => $this->storeRepository->findAllWithDomains(),
        ]);
    }

    #[Route('/new', name: 'admin_store_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $store = new Store();
        $store->setStatus(BaseStatus::ACTIVE);

        $form = $this->createForm(StoreType::class, $store);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($store);
            $this->entityManager->flush();

            $this->addFlash('success', 'Store created. Add a domain so it can receive requests.');

            return $this->redirectToRoute('admin_store_edit', ['id' => $store->getId()]);
        }

        return $this->render('admin/store/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_store_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Store $store): Response
    {
        $storeForm = $this->createForm(StoreType::class, $store);
        $storeForm->add('save', SubmitType::class);
        $storeForm->handleRequest($request);

        if ($storeForm->isSubmitted() && $storeForm->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Store settings saved.');

            return $this->redirectToRoute('admin_store_edit', ['id' => $store->getId()]);
        }

        // StoreTemplateConfig is store-scoped: the store_filter Doctrine
        // filter restricts it to whichever single store the admin switcher
        // currently has selected, so it can only be read/edited for that
        // store (never in aggregate "All stores" mode).
        $isCurrentStore = $this->storeContext->isInitialized() && $store->getId() === $this->storeContext->getId();

        $configForm = null;
        if ($isCurrentStore && $store->getTemplate() !== null) {
            $config = $this->storeTemplateConfigRepository->findOneByStoreAndTemplate($store, $store->getTemplate())
                ?? (new StoreTemplateConfig())->setStore($store)->setTemplate($store->getTemplate());

            $configForm = $this->createForm(StoreTemplateConfigType::class, $config);
            $configForm->add('save', SubmitType::class);
            $configForm->handleRequest($request);

            if ($configForm->isSubmitted() && $configForm->isValid()) {
                $this->entityManager->persist($config);
                $this->entityManager->flush();
                $this->addFlash('success', 'Template config saved.');

                return $this->redirectToRoute('admin_store_edit', ['id' => $store->getId()]);
            }
        }

        $domainForm = $this->createForm(StoreDomainType::class, new StoreDomain());

        return $this->render('admin/store/edit.html.twig', [
            'store' => $store,
            'storeForm' => $storeForm,
            'configForm' => $configForm,
            'domainForm' => $domainForm,
            'isCurrentStore' => $isCurrentStore,
        ]);
    }

    #[Route('/{id}/domains', name: 'admin_store_domain_add', methods: ['POST'])]
    public function addDomain(Request $request, Store $store): Response
    {
        $domain = new StoreDomain();
        $domain->setStore($store);

        $form = $this->createForm(StoreDomainType::class, $domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($domain);
            $this->entityManager->flush();
            $this->addFlash('success', 'Domain added.');
        } else {
            $this->addFlash('error', 'Could not add domain.');
        }

        return $this->redirectToRoute('admin_store_edit', ['id' => $store->getId()]);
    }

    #[Route('/domains/{id}/delete', name: 'admin_store_domain_delete', methods: ['POST'])]
    public function deleteDomain(Request $request, StoreDomain $domain): Response
    {
        $storeId = $domain->getStore()?->getId();

        if ($this->isCsrfTokenValid('delete-domain-'.$domain->getId(), (string) $request->request->get('_token'))) {
            $this->entityManager->remove($domain);
            $this->entityManager->flush();
            $this->addFlash('success', 'Domain removed.');
        }

        return $this->redirectToRoute('admin_store_edit', ['id' => $storeId]);
    }

    #[Route('/switch', name: 'admin_store_switch', methods: ['POST'])]
    public function switchStore(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_store_switch', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('admin_dashboard');
        }

        $storeId = $request->request->get('store_id');
        $session = $request->getSession();

        if ($storeId === null || $storeId === '') {
            $session->remove(AdminStoreScopeSubscriber::SESSION_KEY);
            $this->addFlash('success', 'Switched to all stores.');
        } else {
            $storeId = (int) $storeId;
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $linkedIds = array_map(static fn ($s) => $s->getId(), $user->getStores()->toArray());

            if (!in_array($storeId, $linkedIds, true)) {
                $this->addFlash('error', 'You do not have access to that store.');

                return $this->redirectToRoute('admin_dashboard');
            }

            $session->set(AdminStoreScopeSubscriber::SESSION_KEY, $storeId);
            $this->addFlash('success', 'Store switched.');
        }

        $referer = $request->headers->get('referer');
        if ($referer && str_starts_with($referer, $request->getSchemeAndHttpHost())) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('admin_dashboard');
    }
}
