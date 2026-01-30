<?php

namespace App\Controller;

use App\Service\CartService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\CodePromoRepository;
use App\Repository\CodePromoUsageRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CartController extends AbstractController
{
    #[Route('/cart/checkout', name: 'cart_checkout', methods: ['POST'])]
    public function checkout(Request $request, CodePromoRepository $codePromoRepository, CodePromoUsageRepository $codePromoUsageRepository, TokenStorageInterface $tokenStorage): RedirectResponse
    {
        $session = $request->getSession();
        // Gestion du code promo
        $codePromo = $request->request->get('code_promo', '');
        if ($request->request->get('apply_code_promo')) {
            if ($codePromo) {
                $promo = $codePromoRepository->findValidByCode($codePromo);
                if ($promo) {
                    $user = $tokenStorage->getToken() ? $tokenStorage->getToken()->getUser() : null;
                    if ($user && is_object($user) && method_exists($user, 'getId')) {
                        if ($codePromoUsageRepository->hasUserUsedCodePromo($user, $promo)) {
                            $session->remove('cart_code_promo');
                            $this->addFlash('danger', 'Ce code promo a déjà été utilisé lors d’une précédente commande. Il n’est valable qu’une seule fois par client.');
                            return $this->redirectToRoute('cart_index');
                        }
                    }
                    $session->set('cart_code_promo', $promo->getCode());
                    $this->addFlash('success', 'Code promo appliqué !');
                } else {
                    $session->remove('cart_code_promo');
                    $this->addFlash('warning', 'Code promo invalide ou expiré.');
                }
            } else {
                $session->remove('cart_code_promo');
                $this->addFlash('warning', 'Veuillez saisir un code promo.');
            }
            return $this->redirectToRoute('cart_index');
        }
        // Gestion du transporteur
        $selected = $request->request->get('transporteur', '');
        if ($selected == '') {
            $this->addFlash('warning', 'Veuillez sélectionner un mode de livraison avant de passer la commande.');
            return $this->redirectToRoute('cart_index');
        }
        $session->set('cart_transporteur', $selected);
        return $this->redirectToRoute('checkout_index', ['transporteur' => $selected]);
    }
    #[Route('/cart', name: 'cart_index')]
    public function index(CartService $cartService, Request $request): Response
    {
        $cartData = $cartService->getDetailedCart();
        $session = $request->getSession();
        $selectedTransporteur = $session->get('cart_transporteur', '');
        return $this->render('cart/index.html.twig', [
            'cart' => $cartData['items'],
            'total' => $cartData['total'],
            'reduction' => $cartData['reduction'],
            'codePromo' => $cartData['codePromo'],
            'totalAvecPromo' => $cartData['totalAvecPromo'],
            'selectedTransporteur' => $selectedTransporteur
        ]);
    }

    #[Route('/cart/add/{id}', name: 'cart_add')]
    public function add($id, CartService $cartService, Request $request): RedirectResponse
    {
        $quantity = $request->query->get('quantity', 1);
        $cartService->add($id, $quantity);
        $this->addFlash('success', 'Produit ajouté au panier !');
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove')]
    public function remove($id, CartService $cartService): RedirectResponse
    {
        $cartService->remove($id);
        $this->addFlash('info', 'Produit retiré du panier.');
        return $this->redirectToRoute('cart_index');
    }
    #[Route('/cart/apply-code-promo/{id}', name: 'cart_apply_code_promo', methods: ['POST'])]
    public function applyCodePromo($id, Request $request, CodePromoRepository $codePromoRepository, CodePromoUsageRepository $codePromoUsageRepository, TokenStorageInterface $tokenStorage): RedirectResponse
    {
        $session = $request->getSession();
        $codePromo = $request->request->get('code_promo', '');
        if ($codePromo) {
            $promo = $codePromoRepository->findValidByCode($codePromo);
            if ($promo) {
                $user = $tokenStorage->getToken() ? $tokenStorage->getToken()->getUser() : null;
                if ($user && is_object($user) && method_exists($user, 'getId')) {
                    if ($codePromoUsageRepository->hasUserUsedCodePromo($user, $promo)) {
                        $session->remove('cart_code_promo');
                        $this->addFlash('danger', 'Ce code promo a déjà été utilisé lors d’une précédente commande. Il n’est valable qu’une seule fois par client.');
                        return $this->redirectToRoute('product_show', ['id' => $id]);
                    }
                }
                $session->set('cart_code_promo', $promo->getCode());
                $this->addFlash('success', 'Code promo appliqué !');
            } else {
                $session->remove('cart_code_promo');
                $this->addFlash('warning', 'Code promo invalide ou expiré.');
            }
        } else {
            $session->remove('cart_code_promo');
            $this->addFlash('warning', 'Veuillez saisir un code promo.');
        }
        return $this->redirectToRoute('product_show', ['id' => $id]);
    }

    #[Route('/cart/clear', name: 'cart_clear')]
    public function clear(CartService $cartService): RedirectResponse
    {
        $cartService->clear();
        $this->addFlash('warning', 'Le panier a été vidé.');
        return $this->redirectToRoute('cart_index');
    }
}
