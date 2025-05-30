<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

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
    public function add(Request $request, Product $product, SessionInterface $session): Response
    {
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
    public function update(Request $request, Product $product, SessionInterface $session): Response
    {
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
    public function remove(Product $product, SessionInterface $session): Response
    {
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
        $this->addFlash('success', 'Cart cleared.');

        return $this->redirectToRoute('app_cart');
    }
} 