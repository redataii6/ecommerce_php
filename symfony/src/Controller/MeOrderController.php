<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/symfony/me')]
#[IsGranted('ROLE_USER')]
class MeOrderController extends AbstractController
{
    /**
     * List orders for the current logged-in user
     * Route: /symfony/me/orders
     */
    #[Route('/orders', name: 'app_me_orders', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        $orders = $orderRepository->findByUser($user);

        return $this->render('me/orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    /**
     * View a single order (with owner-check)
     * Route: /symfony/me/orders/{id}
     */
    #[Route('/orders/{id}', name: 'app_me_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        // OWNER-CHECK: Verify the current user owns this order
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You are not allowed to view this order.');
        }

        return $this->render('me/order_show.html.twig', [
            'order' => $order,
        ]);
    }
}
