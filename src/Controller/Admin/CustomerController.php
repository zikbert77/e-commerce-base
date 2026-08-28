<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\User;
use App\Form\Admin\CustomerType;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Store\StoreContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/customers', host: '%admin_host%')]
class CustomerController extends BaseController
{
    private const PER_PAGE = 20;

    public function __construct(
        StoreContext $storeContext,
        private readonly UserRepository $userRepository,
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
        parent::__construct($storeContext);
    }

    #[Route('', name: 'admin_customers', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = (string) $request->query->get('q', '');
        $page = max(1, $request->query->getInt('page', 1));

        $result = $this->userRepository->search($query, $page, self::PER_PAGE);
        $orderCounts = $this->orderRepository->countsByCustomers(array_map(
            static fn (User $u) => $u->getId(),
            $result['items'],
        ));

        return $this->render('admin/customer/index.html.twig', [
            'customers' => $result['items'],
            'orderCounts' => $orderCounts,
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'query' => $query,
        ]);
    }

    #[Route('/new', name: 'admin_customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new User(), isNew: true);
    }

    #[Route('/{id}/edit', name: 'admin_customer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        return $this->handleForm($request, $user, isNew: false);
    }

    private function handleForm(Request $request, User $user, bool $isNew): Response
    {
        $form = $this->createForm(CustomerType::class, $user);
        $form->get('isVerified')->setData($user->isVerified() ?? false);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setIsVerified((bool) $form->get('isVerified')->getData());

            if ($isNew) {
                $user->setPassword(bin2hex(random_bytes(32)));
                $user->setRoles([]);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', $isNew ? 'Customer created.' : 'Customer updated.');

            return $this->redirectToRoute('admin_customers');
        }

        return $this->render('admin/customer/form.html.twig', [
            'form' => $form,
            'customer' => $user,
            'isNew' => $isNew,
        ]);
    }
}
