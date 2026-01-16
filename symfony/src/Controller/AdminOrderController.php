<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/symfony/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminOrderController extends AbstractController
{
    /**
     * List all orders (admin view)
     * Route: /symfony/admin/orders
     */
    #[Route('/orders', name: 'app_admin_orders', methods: ['GET'])]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $status = $request->query->get('status');
        $search = $request->query->get('search');

        $orders = $orderRepository->findAllWithFilters($status, $search);

        // Get status counts
        $statusCounts = [];
        $allOrders = $orderRepository->findAll();
        foreach ($allOrders as $order) {
            $s = $order->getStatus();
            $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
        }

        return $this->render('admin/orders.html.twig', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
            'currentStatus' => $status,
            'search' => $search,
        ]);
    }

    /**
     * View and manage a single order
     * Route: /symfony/admin/orders/{id}
     */
    #[Route('/orders/{id}', name: 'app_admin_order_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        // Handle status update
        if ($request->isMethod('POST')) {
            $newStatus = $request->request->get('status');
            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

            if (in_array($newStatus, $validStatuses)) {
                $order->setStatus($newStatus);
                $order->setUpdatedAt(new \DateTime());
                $em->flush();

                $this->addFlash('success', 'Order status updated successfully.');
            }

            return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
        }

        return $this->render('admin/order_show.html.twig', [
            'order' => $order,
        ]);
    }
}
