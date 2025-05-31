<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/cart')]
class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart')]
    public function index(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $cart = $session->get('cart', []);
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

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Request $request, int $id, ProductRepository $productRepository, SessionInterface $session): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            $this->addFlash('error', 'Product not found.');
            return $this->redirectToRoute('app_cart');
        }
        $quantity = (int) $request->request->get('quantity', 1);
        $cart = $session->get('cart', []);
        if ($quantity <= 0) {
            $this->addFlash('error', 'Invalid quantity.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }
        if ($quantity > $product->getStock()) {
            $this->addFlash('error', 'Not enough stock available.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }
        if (isset($cart[$product->getId()])) {
            $cart[$product->getId()] += $quantity;
        } else {
            $cart[$product->getId()] = $quantity;
        }
        $session->set('cart', $cart);
        $this->addFlash('success', 'Product added to cart.');
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(Request $request, int $id, ProductRepository $productRepository, SessionInterface $session): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            $this->addFlash('error', 'Product not found.');
            return $this->redirectToRoute('app_cart');
        }
        $quantity = (int) $request->request->get('quantity', 1);
        $cart = $session->get('cart', []);
        if ($quantity <= 0) {
            unset($cart[$product->getId()]);
        } else {
            if ($quantity > $product->getStock()) {
                $this->addFlash('error', 'Not enough stock available.');
                return $this->redirectToRoute('app_cart');
            }
            $cart[$product->getId()] = $quantity;
        }
        $session->set('cart', $cart);
        $this->addFlash('success', 'Cart updated.');
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $id, ProductRepository $productRepository, SessionInterface $session): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            $this->addFlash('error', 'Product not found.');
            return $this->redirectToRoute('app_cart');
        }
        $cart = $session->get('cart', []);
        if (isset($cart[$product->getId()])) {
            unset($cart[$product->getId()]);
            $session->set('cart', $cart);
            $this->addFlash('success', 'Product removed from cart.');
        }
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(SessionInterface $session): Response
    {
        $session->remove('cart');
        $this->addFlash('success', 'Your cart has been cleared.');
         return $this->redirectToRoute('app_cart');
    }

    #[Route('/purchase', name: 'app_cart_purchase', methods: ['POST'])]
    public function purchase(Request $request, SessionInterface $session, ProductRepository $productRepository, EntityManagerInterface $entityManager): Response
    {
        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart');
        }

        // Iterate over cart items (using session cart) and update product stock (decrement by quantity)
        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if (!$product) {
                $this->addFlash('error', 'Product (id: ' . $id . ') not found.');
                 continue;
            }
            $newStock = $product->getStock() - $quantity;
            if ($newStock < 0) {
                $this->addFlash('error', 'Insufficient stock for product: ' . $product->getTitle());
                 return $this->redirectToRoute('app_cart');
            }
            $product->setStock($newStock);
        }

        // (Optional) Record purchase (e.g. create an Order record) – for example, if you have an Order entity:
        // $order = new Order();
        // $order->setUser($this->getUser());
        // $order->setTotal($this->cartService->getTotal());
        // $order->setStatus('purchased');
        // $entityManager->persist($order);

        $entityManager->flush();

        // Clear the cart (remove from session) and redirect (or flash a success message)
        $session->remove('cart');
        $this->addFlash('success', 'Purchase completed successfully!');
         return $this->redirectToRoute('app_cart');
    }
} 