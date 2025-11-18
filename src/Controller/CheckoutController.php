<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends AbstractController
{
    /**
     * @Route("/checkout", name="checkout_index")
     */
    public function index(CartService $cartService, Request $request): Response
    {
        $cart = $cartService->getDetailedCart();
        $session = $request->getSession();

        if ($request->isMethod('POST')) {
            $address = [
                'address' => $request->request->get('address'),
                'city' => $request->request->get('city'),
                'zip' => $request->request->get('zip'),
                'country' => $request->request->get('country'),
            ];
            $session->set('checkout_address', $address);
            return $this->redirectToRoute('checkout_payment');
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart
        ]);
    }

    /**
     * @Route("/checkout/payment", name="checkout_payment")
     */
    public function payment(CartService $cartService, Request $request): Response
    {
        $cart = $cartService->getDetailedCart();
        $session = $request->getSession();
        $address = $session->get('checkout_address');
        if (!$address) {
            return $this->redirectToRoute('checkout_index');
        }
        return $this->render('checkout/payment.html.twig', [
            'cart' => $cart,
            'address' => $address
        ]);
    }
    /**
     * @Route("/checkout/pay/stripe", name="checkout_pay_stripe")
     */
    public function payStripe(CartService $cartService, Request $request): Response
    {
        // Ici, on prépare la logique Stripe (mode test)
        // Pour l'instant, on affiche une page de confirmation fictive
        return $this->render('checkout/stripe_test.html.twig');
    }

    /**
     * @Route("/checkout/pay/paypal", name="checkout_pay_paypal")
     */
    public function payPaypal(CartService $cartService, Request $request): Response
    {
        // Ici, on prépare la logique PayPal (mode test)
        // Pour l'instant, on affiche une page de confirmation fictive
        return $this->render('checkout/paypal_test.html.twig');
    }
}
