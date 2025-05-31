<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Category;

#[Route('/admin/products')]
#[IsGranted('ROLE_ADMIN')]
class AdminProductController extends AbstractController
{
    #[Route('/', name: 'admin_products')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        return $this->render('admin/products/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'admin_product_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('product_images_directory'),
                    $newFilename
                );
                $product->setImage($newFilename);
            }
            $entityManager->persist($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product created successfully.');
            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/products/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_product_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('product_images_directory'),
                    $newFilename
                );
                $product->setImage($newFilename);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Product updated successfully.');
            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/products/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_product_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product deleted successfully.');
        }
        return $this->redirectToRoute('admin_products');
    }

    #[Route('/import', name: 'admin_product_import')]
    public function import(Request $request, EntityManagerInterface $entityManager, ProductRepository $productRepository): Response
    {
        if ($request->isMethod('POST')) {
            $file = $request->files->get('csv_file');
            if ($file && $file->isValid()) {
                $handle = fopen($file->getPathname(), 'r');
                $header = fgetcsv($handle);
                while (($row = fgetcsv($handle)) !== false) {
                    $data = array_combine($header, $row);
                    $product = $productRepository->findOneBy(['isbn' => $data['isbn']]) ?? new Product();
                    $image = $data['image'] ?? '';
                    if (!$image) {
                        $image = 'default.jpg';
                    }
                    $categoryName = $data['category'] ?? null;
                    $category = null;
                    if ($categoryName) {
                        $category = $entityManager->getRepository(Category::class)->findOneBy(['name' => $categoryName]);
                    }
                    if (!$category) {
                        $category = $entityManager->getRepository(Category::class)->findOneBy([]); // fallback: first category
                    }
                    $product->setTitle($data['title'] ?? '')
                        ->setAuthor($data['author'] ?? '')
                        ->setPrice((float)($data['price'] ?? 0))
                        ->setStock((int)($data['stock'] ?? 0))
                        ->setIsbn($data['isbn'] ?? '')
                        ->setPublicationYear((int)($data['publicationYear'] ?? 0))
                        ->setBookCondition($data['bookCondition'] ?? '')
                        ->setDescription($data['description'] ?? '')
                        ->setImage($image)
                        ->setCategory($category);
                    $entityManager->persist($product);
                }
                $entityManager->flush();
                fclose($handle);
                $this->addFlash('success', 'Products imported successfully.');
                return $this->redirectToRoute('admin_products');
            }
            $this->addFlash('error', 'Invalid file.');
        }
        return $this->render('admin/products/import.html.twig');
    }

    #[Route('/export', name: 'admin_product_export')]
    public function export(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['title', 'author', 'price', 'stock', 'isbn', 'publicationYear', 'bookCondition', 'description']);
        foreach ($products as $product) {
            fputcsv($handle, [
                $product->getTitle(),
                $product->getAuthor(),
                $product->getPrice(),
                $product->getStock(),
                $product->getIsbn(),
                $product->getPublicationYear(),
                $product->getBookCondition(),
                $product->getDescription(),
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return new Response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products.csv"',
        ]);
    }

    #[Route('/bulk-delete', name: 'admin_product_bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request, ProductRepository $productRepository, EntityManagerInterface $entityManager): Response
    {
        $ids = $request->request->all('ids');
        if (!is_array($ids)) {
            $ids = [];
        }
        if (!empty($ids)) {
            foreach ($ids as $id) {
                $product = $productRepository->find($id);
                if ($product) {
                    $entityManager->remove($product);
                }
            }
            $entityManager->flush();
            $this->addFlash('success', 'Selected products deleted successfully.');
        } else {
            $this->addFlash('warning', 'No products selected.');
        }
        return $this->redirectToRoute('admin_products');
    }
} 