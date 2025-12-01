<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;


final class ProductController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $query = $em->getRepository(Product::class)->createQueryBuilder('p')->getQuery();
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );
        return $this->render('product/index.html.twig', [
            'products' => $pagination,
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
            $em->persist($review);
            $em->flush();
            $this->addFlash('success', 'Merci pour votre avis !');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        $reviews = $product->getReviews();

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
        // Ajout d'images de test pour vérifier l'affichage des miniatures
        $thumbnails[] = '/konvix_ecommerce/images/placeholder.png';
        $thumbnails[] = '/konvix_ecommerce/images/placeholder.png';
        $thumbnails[] = '/konvix_ecommerce/images/placeholder.png';

        $img = '/uploads/product_images/' . $product->getImageFolder() . '/' . $product->getMainImage();
        return $this->render('product/show.html.twig', [
            'img' => $img,
            'product'    => $product,
            'reviews'    => $reviews,
            'thumbnails' => $thumbnails,
        ]);
        return $this->render('product/show.html.twig', [
            'product' => $product,
            'reviews' => $reviews,
            'reviewForm' => $form->createView(),
            'alreadyReviewed' => $alreadyReviewed,
            'thumbnails' => $thumbnails,      // ← cette ligne doit exister !
        ]);
    }
}
