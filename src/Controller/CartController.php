<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class CartController extends AbstractController
{
    #[Route('/cart/checkout', name: 'cart_checkout', methods: ['POST'])]
    public function checkout(Request $request): RedirectResponse
    {
        $session = $request->getSession();
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
        $cart = $cartService->getDetailedCart();
        $session = $request->getSession();
        $selectedTransporteur = $session->get('cart_transporteur', '');
        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
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

    #[Route('/cart/clear', name: 'cart_clear')]
    public function clear(CartService $cartService): RedirectResponse
    {
        $cartService->clear();
        $this->addFlash('warning', 'Le panier a été vidé.');
        return $this->redirectToRoute('cart_index');
    }
}
