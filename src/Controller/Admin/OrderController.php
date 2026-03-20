<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Form\Admin\OrderStatusType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class OrderController extends AbstractController
{
    #[Route('/admin/orders', name: 'admin_order_index')]
    public function index(OrderRepository $repo): Response
    {
        return $this->render('admin/order/index.html.twig', [
            'orders' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/admin/orders/{id}', name: 'admin_order_show')]
    public function show(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(OrderStatusType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Order status updated.');

            return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
        }

        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
            'statusForm' => $form,
        ]);
    }
}
