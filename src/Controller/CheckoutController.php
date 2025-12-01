<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'checkout_index')]
    public function index(CartService $cartService, Request $request): Response
    {
        $cart = $cartService->getDetailedCart();
        $session = $request->getSession();
        $address = null;
        $payment_choice = false;
        if ($request->isMethod('POST')) {
                // Si on vient du formulaire d'adresse
                if ($request->request->has('address')) {
                    $address = [
                        'address' => $request->request->get('address'),
                        'city' => $request->request->get('city'),
                        'zip' => $request->request->get('zip'),
                        'country' => $request->request->get('country'),
                    ];
                    $session->set('checkout_address', $address);
                    $payment_choice = true;
                }
                // Vérification du choix du transporteur
                if (!$request->request->has('transporteur') || $request->request->get('transporteur') == '') {
                    $this->addFlash('warning', 'Avant de passer la commande il faut sélectionner votre transporteur.');
                    return $this->redirectToRoute('cart_index');
                }
                // Si le transporteur est bien sélectionné, on l’enregistre
                if ($request->request->has('transporteur')) {
                    $transporteurs = [
                        'colissimo' => ['name' => 'Colissimo', 'price' => 5.90],
                        'mondial' => ['name' => 'Mondial Relay', 'price' => 4.50],
                        'chrono' => ['name' => 'Chronopost', 'price' => 12.00],
                    ];
                    $selected = $request->request->get('transporteur');
                    $transporteur = $transporteurs[$selected] ?? ['name' => 'Non renseigné', 'price' => 0.00];
                    $session->set('checkout_transporteur', $transporteur);
                }
        } else {
            $address = $session->get('checkout_address');
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
            'address' => $address,
            'payment_choice' => $payment_choice,
        ]);
    }
    #[Route('/checkout/recap', name: 'checkout_recap')]
    public function recap(CartService $cartService, Request $request): Response
    {
        $cart = $cartService->getDetailedCart();
        $session = $request->getSession();
        $address = $session->get('checkout_address');
        $transporteur = $session->get('checkout_transporteur');
        if (!$address) {
            $this->addFlash('warning', 'Veuillez renseigner votre adresse avant de valider la commande.');
            return $this->redirectToRoute('cart_index');
        }
        if (!$transporteur) {
            $this->addFlash('warning', 'Veuillez sélectionner un mode de livraison avant de valider la commande.');
            return $this->redirectToRoute('cart_index');
        }
        return $this->render('checkout/recap.html.twig', [
            'cart' => $cart,
            'address' => $address,
            'transporteur' => $transporteur,
        ]);
    }
    #[Route('/checkout/pay/stripe', name: 'checkout_pay_stripe')]
    public function payStripe(CartService $cartService, Request $request): Response
    {
        $cart = $cartService->getDetailedCart();
        $stripeSecret = $_ENV['STRIPE_SECRET_KEY'] ?? $this->getParameter('STRIPE_SECRET_KEY');
        \Stripe\Stripe::setApiKey($stripeSecret);

        $lineItems = [];
        foreach ($cart as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item['product']->getName(),
                    ],
                    'unit_amount' => (int)($item['product']->getPrice() * 100),
                ],
                'quantity' => $item['quantity'],
            ];
        }

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $this->generateUrl('cart_index', [], 0) . '?success=1',
            'cancel_url' => $this->generateUrl('cart_index', [], 0) . '?canceled=1',
        ]);

        return $this->redirect($session->url);
    }

    #[Route('/checkout/pay/paypal', name: 'checkout_pay_paypal')]
    public function payPaypal(CartService $cartService, Request $request): Response
    {
        $cart = $cartService->getDetailedCart();
        $clientId = $_ENV['PAYPAL_CLIENT_ID'] ?? $this->getParameter('PAYPAL_CLIENT_ID');
        $clientSecret = $_ENV['PAYPAL_CLIENT_SECRET'] ?? $this->getParameter('PAYPAL_CLIENT_SECRET');

        $environment = new \PayPalCheckoutSdk\Core\SandboxEnvironment($clientId, $clientSecret);
        $client = new \PayPalCheckoutSdk\Core\PayPalHttpClient($environment);

        $items = [];
        $total = 0;
        foreach ($cart as $item) {
            $items[] = [
                'name' => $item['product']->getName(),
                'unit_amount' => [
                    'currency_code' => 'EUR',
                    'value' => number_format($item['product']->getPrice(), 2, '.', ''),
                ],
                'quantity' => $item['quantity'],
            ];
            $total += $item['product']->getPrice() * $item['quantity'];
        }

        $order = new \PayPalCheckoutSdk\Orders\OrdersCreateRequest();
        $order->prefer('return=representation');
        $order->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => number_format($total, 2, '.', ''),
                    'breakdown' => [
                        'item_total' => [
                            'currency_code' => 'EUR',
                            'value' => number_format($total, 2, '.', ''),
                        ]
                    ]
                ],
                'items' => $items,
            ]],
            'application_context' => [
                'return_url' => $this->generateUrl('cart_index', [], 0) . '?success=1',
                'cancel_url' => $this->generateUrl('cart_index', [], 0) . '?canceled=1',
            ],
        ];

        try {
            $response = $client->execute($order);
            foreach ($response->result->links as $link) {
                if ($link->rel === 'approve') {
                    return $this->redirect($link->href);
                }
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur PayPal : ' . $e->getMessage());
            return $this->redirectToRoute('cart_index');
        }

        return $this->redirectToRoute('cart_index');
    }
}
