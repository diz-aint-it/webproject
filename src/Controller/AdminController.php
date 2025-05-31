<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(UserRepository $userRepository, ProductRepository $productRepository, OrderRepository $orderRepository): Response
    {
        $userCount = count($userRepository->findAll());
        $productCount = count($productRepository->findAll());
        $orderCount = count($orderRepository->findAll());
        $recentOrders = $orderRepository->findRecentOrders(5);

        return $this->render('admin/dashboard.html.twig', [
            'userCount' => $userCount,
            'productCount' => $productCount,
            'orderCount' => $orderCount,
            'recentOrders' => $recentOrders,
        ]);
    }
} 