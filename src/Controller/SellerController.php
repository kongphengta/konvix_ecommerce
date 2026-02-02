<?php

namespace App\Controller;

use App\Entity\Seller;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Coupon;
use App\Form\CouponType;
use App\Repository\CouponRepository;

class SellerController extends AbstractController
{
    #[Route('/seller', name: 'seller_dashboard')]
    #[IsGranted('ROLE_SELLER')]
    public function dashboard(): Response
    {
        return $this->render('seller/dashboard.html.twig');
    }

    #[Route('/seller/products', name: 'seller_products')]
    #[IsGranted('ROLE_SELLER')]
    public function listProducts(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        /** @var \App\Entity\User $user */
        $products = $em->getRepository(\App\Entity\Product::class)->findBy(['seller' => $user->getSeller()]);
        return $this->render('seller/products.html.twig', [
            'products' => $products
        ]);
    }

    #[Route('/seller/product/{id}/edit', name: 'seller_product_edit')]
    #[IsGranted('ROLE_SELLER')]
    public function editProduct(\App\Entity\Product $product, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        /** @var \App\Entity\User $user */
        if ($product->getSeller() !== $user->getSeller()) {
            throw $this->createAccessDeniedException('Vous ne pouvez éditer que vos propres produits.');
        }
        $form = $this->createForm(\App\Form\ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Produit modifié avec succès.');
            return $this->redirectToRoute('seller_products');
        }

        return $this->render('seller/edit_product.html.twig', [
            'form' => $form->createView(),
            'product' => $product
        ]);
    }

    #[Route('/seller/product/{id}/delete', name: 'seller_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SELLER')]
    public function deleteProduct(\App\Entity\Product $product, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();
        /** @var \App\Entity\User $user */
        if ($product->getSeller() !== $user->getSeller()) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres produits.');
        }
        $em->remove($product);
        $em->flush();
        $this->addFlash('success', 'Produit supprimé avec succès.');
        return $this->redirectToRoute('seller_products');
    }
    #[Route('/become-seller', name: 'app_become_seller')]
    #[IsGranted('ROLE_USER')]
    public function becomeSeller(EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->getUser();
        /** @var \App\Entity\User $user */
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour devenir vendeur.');
            return $this->redirectToRoute('app_login');
        }
        if (!in_array('ROLE_SELLER', $user->getRoles())) {
            // Ajout du slug dans la méthode getRoles (ManyToMany + slug)
            // Ici, on ne touche pas à la collection ManyToMany, juste au tableau de slugs pour Symfony
            // Créer l'entité Seller si elle n'existe pas déjà
            if ($user->getSeller() === null) {
                $seller = new Seller();
                $seller->setUser($user);
                // Définir un nom par défaut pour le vendeur (ex: nom d'utilisateur ou email)
                $defaultName = $user->getUserIdentifier() ?? $user->getEmail() ?? 'Vendeur';
                $seller->setName($defaultName);
                $entityManager->persist($seller);
            }
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Vous êtes maintenant vendeur !');
        }
        return $this->redirectToRoute('app_home');
    }

    #[Route('/seller/orders', name: 'seller_orders')]
    #[IsGranted('ROLE_SELLER')]
    public function listOrders(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        /** @var \App\Entity\User $user */
        $seller = $user->getSeller();
        // Récupérer tous les OrderItems liés à ce vendeur
        $orderItems = $em->getRepository(\App\Entity\OrderItem::class)
            ->createQueryBuilder('oi')
            ->join('oi.product', 'p')
            ->join('oi.order', 'o')
            ->where('p.seller = :seller')
            ->setParameter('seller', $seller)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('seller/orders.html.twig', [
            'orderItems' => $orderItems
        ]);
    }
    #[Route('/seller/order/{id}', name: 'seller_order_detail')]
    #[IsGranted('ROLE_SELLER')]
    public function orderDetail(\App\Entity\Order $order, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        /** @var \App\Entity\User $user */
        $seller = $user->getSeller();
        // Vérifier que le vendeur a au moins un produit dans cette commande
        $hasProduct = false;
        foreach ($order->getOrderItems() as $item) {
            if ($item->getProduct()->getSeller() === $seller) {
                $hasProduct = true;
                break;
            }
        }
        if (!$hasProduct) {
            throw $this->createAccessDeniedException('Vous ne pouvez voir que les commandes contenant vos produits.');
        }
        return $this->render('seller/order_detail.html.twig', [
            'order' => $order
        ]);
    }

    #[Route('/seller/order/{id}/status', name: 'seller_order_status', methods: ['POST'])]
    #[IsGranted('ROLE_SELLER')]
    public function changeOrderStatus(\App\Entity\Order $order, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        /** @var \App\Entity\User $user */
        $seller = $user->getSeller();
        // Vérifier que le vendeur a au moins un produit dans cette commande
        $hasProduct = false;
        foreach ($order->getOrderItems() as $item) {
            if ($item->getProduct()->getSeller() === $seller) {
                $hasProduct = true;
                break;
            }
        }
        if (!$hasProduct) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que les commandes contenant vos produits.');
        }
        $status = $_POST['status'] ?? null;
        $allowed = ['En préparation', 'Expédiée', 'Livrée'];
        if ($status && in_array($status, $allowed, true)) {
            $order->setStatus($status);
            $em->flush();
            $this->addFlash('success', 'Statut de la commande mis à jour.');
        }
        return $this->redirectToRoute('seller_order_detail', ['id' => $order->getId()]);
    }

    #[Route('/seller/product/new', name: 'seller_product_new')]
    #[IsGranted('ROLE_SELLER')]
    public function newProduct(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        /** @var \App\Entity\User $user */
        $seller = $user->getSeller();
        // Création automatique du Seller si besoin
        if (!$seller && in_array('ROLE_SELLER', $user->getRoles(), true)) {
            $seller = new \App\Entity\Seller();
            $seller->setUser($user);
            $seller->setName($user->getUserIdentifier() ?? $user->getEmail() ?? 'Vendeur');
            $seller->setCreatedAt(new \DateTimeImmutable());
            $em->persist($seller);
            $em->flush();
            // Rafraîchir l'utilisateur pour avoir le seller
            $em->refresh($user);
            $seller = $user->getSeller();
        }
        $product = new \App\Entity\Product();
        $form = $this->createForm(\App\Form\ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setSeller($seller); // S'assure que le vendeur est bien associé
            $product->setCreatedAt(new \DateTimeImmutable());
            $em->persist($product);
            $em->flush();
            $this->addFlash('success', 'Produit ajouté avec succès.');
            return $this->redirectToRoute('seller_products');
        }

        return $this->render('seller/new_product.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    // ========== GESTION DES CODES PROMO ==========

    #[Route('/seller/coupons', name: 'seller_coupons_list')]
    public function listCoupons(CouponRepository $couponRepository): Response
    {
        $seller = $this->getUser();

        if (!$this->isGranted('ROLE_SELLER')) {
            $this->addFlash('error', 'Accès réservé aux vendeurs');
            return $this->redirectToRoute('app_home');
        }

        $coupons = $couponRepository->findBy(
            ['seller' => $seller],
            ['createdAt' => 'DESC']
        );

        return $this->render('seller/coupons/list.html.twig', [
            'coupons' => $coupons,
        ]);
    }

    #[Route('/seller/coupon/new', name: 'seller_coupon_new')]
    public function newCoupon(Request $request, EntityManagerInterface $em): Response
    {
        $seller = $this->getUser();

        if (!$this->isGranted('ROLE_SELLER')) {
            $this->addFlash('error', 'Accès réservé aux vendeurs');
            return $this->redirectToRoute('app_home');
        }

        $coupon = new Coupon();
        $coupon->setSeller($seller);

        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que le code n'existe pas déjà
            $existingCoupon = $em->getRepository(Coupon::class)->findOneBy(['code' => $coupon->getCode()]);

            if ($existingCoupon) {
                $this->addFlash('error', 'Ce code promo existe déjà.  Veuillez en choisir un autre.');
            } else {
                // Validation du montant selon le type
                if ($coupon->getType() === 'percentage' && $coupon->getDiscount() > 100) {
                    $this->addFlash('error', 'Le pourcentage ne peut pas dépasser 100%');
                } else {
                    $em->persist($coupon);
                    $em->flush();

                    $this->addFlash('success', 'Code promo créé avec succès !');
                    return $this->redirectToRoute('seller_coupons_list');
                }
            }
        }

        return $this->render('seller/coupons/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/seller/coupon/{id}/edit', name: 'seller_coupon_edit')]
    public function editCoupon(Coupon $coupon, Request $request, EntityManagerInterface $em): Response
    {
        $seller = $this->getUser();

        if (!$this->isGranted('ROLE_SELLER') || $coupon->getSeller() !== $seller) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce code promo');
            return $this->redirectToRoute('seller_coupons_list');
        }

        $originalCode = $coupon->getCode();

        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si le code a été modifié, vérifier qu'il n'existe pas déjà
            if ($coupon->getCode() !== $originalCode) {
                $existingCoupon = $em->getRepository(Coupon::class)->findOneBy(['code' => $coupon->getCode()]);

                if ($existingCoupon) {
                    $this->addFlash('error', 'Ce code promo existe déjà. Veuillez en choisir un autre.');
                    return $this->render('seller/coupons/edit.html.twig', [
                        'form' => $form->createView(),
                        'coupon' => $coupon,
                    ]);
                }
            }

            // Validation du montant selon le type
            if ($coupon->getType() === 'percentage' && $coupon->getDiscount() > 100) {
                $this->addFlash('error', 'Le pourcentage ne peut pas dépasser 100%');
            } else {
                $em->flush();
                $this->addFlash('success', 'Code promo modifié avec succès !');
                return $this->redirectToRoute('seller_coupons_list');
            }
        }

        return $this->render('seller/coupons/edit.html.twig', [
            'form' => $form->createView(),
            'coupon' => $coupon,
        ]);
    }

    #[Route('/seller/coupon/{id}/delete', name: 'seller_coupon_delete', methods: ['POST'])]
    public function deleteCoupon(Coupon $coupon, Request $request, EntityManagerInterface $em): Response
    {
        $seller = $this->getUser();

        if (!$this->isGranted('ROLE_SELLER') || $coupon->getSeller() !== $seller) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce code promo');
            return $this->redirectToRoute('seller_coupons_list');
        }

        if ($this->isCsrfTokenValid('delete' . $coupon->getId(), $request->request->get('_token'))) {
            $em->remove($coupon);
            $em->flush();
            $this->addFlash('success', 'Code promo supprimé avec succès');
        }

        return $this->redirectToRoute('seller_coupons_list');
    }


}
