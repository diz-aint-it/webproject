<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Form\OrderType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/checkout')]
class CheckoutController extends AbstractController
{
    #[Route('/', name: 'app_checkout')]
    public function index(Request $request, SessionInterface $session, ProductRepository $productRepository): Response
    {
        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart');
        }

        $cartItems = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->getPrice() * $quantity
                ];
                $total += $product->getPrice() * $quantity;
            }
        }

        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('app_checkout_process');
        }

        return $this->render('checkout/index.html.twig', [
            'form' => $form->createView(),
            'cartItems' => $cartItems,
            'total' => $total
        ]);
    }

    #[Route('/process', name: 'app_checkout_process', methods: ['POST'])]
    public function process(
        Request $request,
        SessionInterface $session,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart');
        }

        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Please fill in all required fields correctly.');
            return $this->redirectToRoute('app_checkout');
        }

        $order->setUser($this->getUser());
        $order->setStatus('pending');
        $order->setOrderDate(new \DateTime());
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if (!$product || $product->getStock() < $quantity) {
                $this->addFlash('error', 'Some items in your cart are no longer available.');
                return $this->redirectToRoute('app_cart');
            }

            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setProduct($product);
            $orderItem->setQuantity($quantity);
            $orderItem->setPrice($product->getPrice());
            $entityManager->persist($orderItem);

            $product->setStock($product->getStock() - $quantity);
            $total += $product->getPrice() * $quantity;
        }

        $order->setTotal($total);
        $entityManager->persist($order);
        $entityManager->flush();

        $session->remove('cart');
        $this->addFlash('success', 'Your order has been placed successfully!');

        return $this->redirectToRoute('app_order_success', ['id' => $order->getId()]);
    }

    #[Route('/success/{id}', name: 'app_order_success')]
    public function success(Order $order): Response
    {
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot access this order.');
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order
        ]);
    }
} 