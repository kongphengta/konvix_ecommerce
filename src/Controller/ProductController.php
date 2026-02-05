<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\PermissionChecker;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;


final class ProductController extends AbstractController
{
    #[Route('/seller/product/create', name: 'seller_product_create')]
    public function createProduct(PermissionChecker $permissionChecker, EntityManagerInterface $em): Response
    {

        $user = $this->getUser();
        if (!$user || !$permissionChecker->hasPermission($user, 'product.create')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas la permission de créer un produit.');
        }
        // ... logique de création de produit ...
        return $this->render('product/create.html.twig');
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Page d'accueil simple, à personnaliser selon tes besoins
        return $this->redirectToRoute('product_list');
    }

    #[Route('/products', name: 'product_list')]
    public function productList(EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        $query = $request->query->get('q', '');
        $page = $request->query->getInt('page', 1);
        $limit = 12;

        if ($query) {
            $qb = $em->getRepository(Product::class)->createQueryBuilder('p');
            $qb->where('p.name LIKE :q OR p.description LIKE :q')
                ->setParameter('q', '%' . $query . '%');
            $allProducts = $qb->getQuery()->getResult();
        } else {
            $allProducts = $em->getRepository(Product::class)->findAll();
        }

        $total = count($allProducts);
        $offset = ($page - 1) * $limit;
        $productsPage = array_slice($allProducts, $offset, $limit);

        $pagination = [
            'products' => $productsPage,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit),
        ];

        return $this->render('product/index.html.twig', [
            'products' => $pagination['products'],
            'pagination' => $pagination,
            'query' => $query,
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_show')]
    public function show(Product $product, EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request): Response
    {

        $review = new \App\Entity\Review();
        $form = $this->createForm(\App\Form\ReviewType::class, $review);
        $form->handleRequest($request);

        $user = $this->getUser();
        $alreadyReviewed = false;
        if ($user) {
            $alreadyReviewed = $em->getRepository(\App\Entity\Review::class)->findOneBy([
                'product' => $product,
                'user' => $user
            ]) !== null;
        }

        if ($form->isSubmitted() && $form->isValid() && $user && !$alreadyReviewed) {
            $review->setProduct($product);
            $review->setUser($user);
            $review->setCreatedAt(new \DateTimeImmutable());
            $review->setIsValidated(false);
            $em->persist($review);
            $em->flush();
            $this->addFlash('success', 'Merci pour votre avis !');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        $review = new \App\Entity\Review();
        $form = $this->createForm(\App\Form\ReviewType::class, $review);
        $form->handleRequest($request);

        $user = $this->getUser();
        $alreadyReviewed = false;
        if ($user) {
            $alreadyReviewed = $em->getRepository(\App\Entity\Review::class)->findOneBy([
                'product' => $product,
                'user' => $user
            ]) !== null;
        }

        if ($form->isSubmitted() && $form->isValid() && $user && !$alreadyReviewed) {
            $review->setProduct($product);
            $review->setUser($user);
            $review->setCreatedAt(new \DateTimeImmutable());
            $em->persist($review);
            $em->flush();
            $this->addFlash('success', 'Merci pour votre avis !');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }
        // Récupérer les avis valides du produit
        $validatedReviews = $em->getRepository(\App\Entity\Review::class)->findBy([
            'product' => $product,
            'isValidated' => true
        ]);
        $reviews = $validatedReviews;

        // Récupérer les miniatures du dossier du produit
        $imageFolder = $product->getImageFolder();
        $imageDir = $this->getParameter('kernel.project_dir') . '/public/uploads/product_images/' . $imageFolder;
        $thumbnails = [];
        if (is_dir($imageDir)) {
            foreach (scandir($imageDir) as $file) {
                if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'])) {
                    $thumbnails[] = '/uploads/product_images/' . $imageFolder . '/' . $file;
                }
            }
        }

        // Définir l'image principale
        $img = '/uploads/product_images/' . $product->getImageFolder() . '/' . $product->getMainImage();

        // Calcul de la note moyenne
        $averageRating = null;
        if (count($validatedReviews) > 0) {
            $sum = 0;
            foreach ($validatedReviews as $review) {
                $sum += $review->getRating();
            }
            $averageRating = $sum / count($validatedReviews);
        }

        return $this->render('product/show.html.twig', [
            'img' => $img,
            'product'    => $product,
            'reviews'    => $reviews,
            'thumbnails' => $thumbnails,
            'reviewForm' => $form->createView(),
            'alreadyReviewed' => $alreadyReviewed,
            'averageRating' => $averageRating,
        ]);
    }
}
