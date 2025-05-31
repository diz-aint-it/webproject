<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/products')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_products')]
    public function index(ProductRepository $productRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $request->query->get('q');
        $category = $request->query->get('category');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');

        if ($query) {
            $products = $productRepository->search($query);
        } elseif ($category) {
            $products = $productRepository->findByCategory($category);
        } elseif ($minPrice && $maxPrice) {
            $products = $productRepository->findByPriceRange($minPrice, $maxPrice);
        } else {
            $products = $productRepository->findAll();
        }

        // Fetch all categories
        $categories = $entityManager->getRepository(\App\Entity\Category::class)->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show')]
    public function show(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'app_product_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Product updated successfully.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/admin/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product deleted successfully.');
        }

        return $this->redirectToRoute('app_products');
    }

    #[Route('/{id}/subscribe-alert', name: 'app_product_subscribe_alert', methods: ['POST'])]
    public function subscribeAlert(int $id, ProductRepository $productRepository, EntityManagerInterface $entityManager): Response
    {
        $product = $productRepository->find($id);
        $user = $this->getUser();
        if ($product && $user) {
            $product->addAlertSubscriber($user);
            $entityManager->flush();
            $this->addFlash('success', 'You will be notified when this book is back in stock.');
        }
        return $this->redirectToRoute('app_product_show', ['id' => $id]);
    }

    #[Route('/{id}/unsubscribe-alert', name: 'app_product_unsubscribe_alert', methods: ['POST'])]
    public function unsubscribeAlert(int $id, ProductRepository $productRepository, EntityManagerInterface $entityManager): Response
    {
        $product = $productRepository->find($id);
        $user = $this->getUser();
        if ($product && $user) {
            $product->removeAlertSubscriber($user);
            $entityManager->flush();
            $this->addFlash('info', 'You will no longer receive alerts for this book.');
        }
        return $this->redirectToRoute('app_product_show', ['id' => $id]);
    }
} 