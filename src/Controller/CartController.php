<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Component\Mime\Email;
use App\Repository\ProductRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
            'selectedTransporteur' => $selectedTransporteur,
        ]);
    }
    #[Route('/cart/add/{id}', name: 'cart_add')]
    public function add($id, CartService $cartService, Request $request, ProductRepository $productRepository, MailerInterface $mailer): RedirectResponse
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        if ($product->getStock() <= 0) {
            $this->addFlash('danger', 'Ce produit est en rupture de stock.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        $quantity = $request->query->get('quantity', 1);
        $cartService->add($id, $quantity);
        $this->addFlash('success', 'Produit ajouté au panier !');

        // 🔔 Alerte admin si stock critique
        $newStock = $product->getStock() - $quantity;

        if ($newStock <= 2) {
            $user = $this->getUser();
            $email = 'inconnu';

            if ($user instanceof \App\Entity\User) {
                $email = $user->getEmail();
            }

            $adminEmail = 'admin@konvix.com';
            $adminAlert = (new Email())
                ->from('no-reply@konvix.fr')
                ->to($adminEmail)
                ->subject('⚠️ Stock critique détecté')
                ->html("
                <p><strong>Produit :</strong> {$product->getName()}</p>
                <p><strong>Stock restant :</strong> {$newStock}</p>
                <p><strong>Commande passée par :</strong> {$email}</p>
            ");

            $mailer->send($adminAlert);
        }

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
