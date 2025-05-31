<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/admin/stats')]
#[IsGranted('ROLE_ADMIN')]
class AdminStatsController extends AbstractController
{
    #[Route('/', name: 'admin_stats')]
    public function index(OrderRepository $orderRepository, ProductRepository $productRepository, UserRepository $userRepository): Response
    {
        $totalSales = 0;
        $pendingOrders = 0;
        $completedOrders = 0;
        $orders = $orderRepository->findAll();
        foreach ($orders as $order) {
            if ($order->getStatus() === 'completed') {
                $totalSales += $order->getTotal();
                $completedOrders++;
            } elseif ($order->getStatus() === 'pending') {
                $pendingOrders++;
            }
        }
        $userCount = count($userRepository->findAll());
        $productCount = count($productRepository->findAll());
        $orderCount = count($orders);

        // Prepare data for charts (e.g., sales per month)
        $salesByMonth = [];
        foreach ($orders as $order) {
            if ($order->getStatus() === 'completed') {
                $month = $order->getOrderDate()->format('Y-m');
                if (!isset($salesByMonth[$month])) {
                    $salesByMonth[$month] = 0;
                }
                $salesByMonth[$month] += $order->getTotal();
            }
        }
        ksort($salesByMonth);

        return $this->render('admin/stats.html.twig', [
            'totalSales' => $totalSales,
            'pendingOrders' => $pendingOrders,
            'completedOrders' => $completedOrders,
            'userCount' => $userCount,
            'productCount' => $productCount,
            'orderCount' => $orderCount,
            'salesByMonth' => $salesByMonth,
        ]);
    }
} 