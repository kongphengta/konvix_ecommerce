<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WishlistController extends AbstractController
{
    #[Route('/wishlist/add/{id}', name: 'wishlist_add', methods: ['POST'])]
    /**
     * @param Product $product
     * @param EntityManagerInterface $em
     * @return RedirectResponse
     */
    public function add(Product $product, EntityManagerInterface $em): RedirectResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }
        $user->addWishlistedProduct($product);
        $em->flush();
        $this->addFlash('success', 'Produit ajouté à vos favoris.');
        return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
    }

    #[Route('/wishlist/remove/{id}', name: 'wishlist_remove', methods: ['POST'])]
    /**
     * @param Product $product
     * @param EntityManagerInterface $em
     * @return RedirectResponse
     */
    public function remove(Product $product, EntityManagerInterface $em): RedirectResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }
        $user->removeWishlistedProduct($product);
        $em->flush();
        $this->addFlash('success', 'Produit retiré de vos favoris.');
        return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
    }

    #[Route('/wishlist', name: 'wishlist_index')]
    /**
     * @return Response
     */
    public function index(): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }
        $products = $user->getWishlistedProducts();
        return $this->render('wishlist/index.html.twig', [
            'products' => $products,
        ]);
    }
}
