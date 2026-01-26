<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Repository\CodePromoRepository;
use App\Repository\CodePromoUsageRepository;
use App\Entity\CodePromoUsage;

class CheckoutController extends AbstractController
{

    #[Route('/checkout/cancel', name: 'checkout_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('danger', 'Le paiement a été annulé ou rejeté. Votre commande n’a pas été validée.');
        return $this->render('checkout/cancel.html.twig');
    }

    #[Route('/checkout', name: 'checkout_index')]
    public function index(CartService $cartService, Request $request): Response
    {
        $cart = $cartService->getDetailedCart();
        $session = $request->getSession();
        $addressDelivery = null;
        $addressBilling = null;
        $payment_choice = false;
        if ($request->isMethod('POST')) {
            // Adresse de livraison
            if ($request->request->has('address_delivery')) {
                $addressDelivery = [
                    'address' => $request->request->get('address_delivery'),
                    'city' => $request->request->get('city_delivery'),
                    'zip' => $request->request->get('zip_delivery'),
                    'country' => $request->request->get('country_delivery'),
                    'phone' => $request->request->get('phone_delivery'),
                ];
                $session->set('checkout_address_delivery', $addressDelivery);
                $payment_choice = true;
            }
            // Adresse de facturation
            if ($request->request->has('address_billing')) {
                $addressBilling = [
                    'address' => $request->request->get('address_billing'),
                    'city' => $request->request->get('city_billing'),
                    'zip' => $request->request->get('zip_billing'),
                    'country' => $request->request->get('country_billing'),
                    'phone' => $request->request->get('phone_billing'),
                ];
                $session->set('checkout_address_billing', $addressBilling);
            }
            // Vérification du choix du transporteur
            $selected = $request->request->get('transporteur', $session->get('cart_transporteur', ''));
            $transporteurs = [
                'colissimo' => ['name' => 'Colissimo', 'price' => 5.90],
                'mondial' => ['name' => 'Mondial Relay', 'price' => 4.50],
                'chrono' => ['name' => 'Chronopost', 'price' => 12.00],
            ];
            if ($selected == '' || !isset($transporteurs[$selected])) {
                $this->addFlash('warning', 'Avant de passer la commande il faut sélectionner votre transporteur.');
                return $this->redirectToRoute('cart_index');
            }
            $transporteur = $transporteurs[$selected];
            $session->set('checkout_transporteur', $transporteur);
            $session->set('cart_transporteur', $selected);
        } else {
            $addressDelivery = $session->get('checkout_address_delivery');
            $addressBilling = $session->get('checkout_address_billing');
        }

        // Centralisation du transporteur : priorité à la session checkout, sinon à la session cart
        // Récupère le transporteur depuis la requête GET si présent (redirection depuis le panier)
        $selected = $request->query->get('transporteur', $session->get('cart_transporteur', ''));
        $transporteurs = [
            'colissimo' => ['name' => 'Colissimo', 'price' => 5.90],
            'mondial' => ['name' => 'Mondial Relay', 'price' => 4.50],
            'chrono' => ['name' => 'Chronopost', 'price' => 12.00],
        ];
        // Si aucun transporteur n'est sélectionné, on bloque l'accès à la page checkout
        if ($selected == '' || !isset($transporteurs[$selected])) {
            $this->addFlash('warning', 'Veuillez sélectionner un mode de livraison avant de passer la commande.');
            return $this->redirectToRoute('cart_index');
        }
        $transporteur = $transporteurs[$selected];
        $session->set('checkout_transporteur', $transporteur);
        $session->set('cart_transporteur', $selected);
        $transporteurs = [
            'colissimo' => ['name' => 'Colissimo', 'price' => 5.90],
            'mondial' => ['name' => 'Mondial Relay', 'price' => 4.50],
            'chrono' => ['name' => 'Chronopost', 'price' => 12.00],
        ];
        $transporteur = $session->get('checkout_transporteur');
        if (!$transporteur || !isset($transporteur['name'])) {
            $transporteur = $selected && isset($transporteurs[$selected]) ? $transporteurs[$selected] : ['name' => 'Non renseigné', 'price' => 0.00];
            $session->set('checkout_transporteur', $transporteur);
        }
        return $this->render('checkout/index.html.twig', [
            'cart' => isset($cart['items']) ? $cart['items'] : $cart,
            'addressDelivery' => $addressDelivery,
            'addressBilling' => $addressBilling,
            'payment_choice' => $payment_choice,
            'transporteur' => $transporteur,
        ]);
    }
    #[Route('/checkout/recap', name: 'checkout_recap')]
    public function recap(CartService $cartService, Request $request): Response
    {
        $cart = $cartService->getDetailedCart();
        $session = $request->getSession();
        $user = $this->getUser();
        $address = $session->get('checkout_address');
        if (!$address || !$address['address']) {
            $address = [
                'address' => $user->getAddress(),
                'city' => $user->getCity(),
                'zip' => $user->getZip(),
                'country' => $user->getCountry(),
                'phone' => $user->getPhone(),
            ];
            $session->set('checkout_address', $address);
        }
        $selected = $session->get('cart_transporteur', '');
        $transporteurs = [
            'colissimo' => ['name' => 'Colissimo', 'price' => 5.90],
            'mondial' => ['name' => 'Mondial Relay', 'price' => 4.50],
            'chrono' => ['name' => 'Chronopost', 'price' => 12.00],
        ];
        $transporteur = $session->get('checkout_transporteur');
        if (!$transporteur || !isset($transporteur['name'])) {
            $transporteur = $selected && isset($transporteurs[$selected]) ? $transporteurs[$selected] : ['name' => 'Non renseigné', 'price' => 0.00];
            $session->set('checkout_transporteur', $transporteur);
        }
        return $this->render('checkout/recap.html.twig', [
            'cart' => isset($cart['items']) ? $cart['items'] : $cart,
            'address' => $address,
            'transporteur' => $transporteur,
        ]);
    }
    #[Route('/checkout/success', name: 'checkout_success')]
    public function success(
        CartService $cartService,
        Request $request,
        MailerInterface $mailer,
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        CodePromoRepository $codePromoRepository,
        CodePromoUsageRepository $codePromoUsageRepository
    ): Response {
        $cart = $cartService->getDetailedCart();
        $user = $this->getUser();
        $session = $request->getSession();
        $address = $session->get('checkout_address');
        $selected = $session->get('cart_transporteur', '');
        $transporteurs = [
            'colissimo' => ['name' => 'Colissimo', 'price' => 5.90],
            'mondial' => ['name' => 'Mondial Relay', 'price' => 4.50],
            'chrono' => ['name' => 'Chronopost', 'price' => 12.00],
        ];
        $transporteur = $session->get('checkout_transporteur');
        if (!$transporteur || !isset($transporteur['name'])) {
            $transporteur = $selected && isset($transporteurs[$selected]) ? $transporteurs[$selected] : ['name' => 'Non renseigné', 'price' => 0.00];
            $session->set('checkout_transporteur', $transporteur);
        }
        // Création et sauvegarde de la commande

        // Vérification et enregistrement de l'utilisation du code promo par client
        $sessionCode = $session->get('cart_code_promo', '');
        $codePromoEntity = null;
        if ($sessionCode) {
            $codePromoEntity = $codePromoRepository->findValidByCode($sessionCode);
            if ($codePromoEntity) {
                // Vérifier si l'utilisateur a déjà utilisé ce code
                if ($codePromoUsageRepository->hasUserUsedCodePromo($user, $codePromoEntity)) {
                    $this->addFlash('danger', 'Ce code promo a déjà été utilisé lors d’une précédente commande. Il n’est valable qu’une seule fois par client.');
                    $session->remove('cart_code_promo');
                    return $this->redirectToRoute('cart_index');
                }
            }
        }

        $order = new \App\Entity\Order();
        $order->setUser($user);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setTotal(array_reduce($cart['items'], function ($sum, $item) {
            return $sum + $item['product']->getPrice() * $item['quantity'];
        }, 0));
        $order->setFraisLivraison($transporteur['price'] ?? 0.0);
        $order->setTransporteur($transporteur['name'] ?? 'Non renseigné');
        $order->setStatus('payé');
        $user->addOrder($order);
        $entityManager->persist($order);
        $entityManager->persist($user);

        // Création des OrderItem pour chaque produit du panier
        foreach ($cart['items'] as $item) {
            $orderItem = new \App\Entity\OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setProduct($item['product']);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setPrice($item['product']->getPrice());
            $entityManager->persist($orderItem);
            $order->addOrderItem($orderItem);
        }

        $entityManager->flush();

        // Enregistrer l'utilisation du code promo si applicable
        if ($codePromoEntity) {
            $usage = new CodePromoUsage();
            $usage->setUser($user);
            $usage->setCodePromo($codePromoEntity);
            $usage->setUsedAt(new \DateTime());
            $entityManager->persist($usage);
            $entityManager->flush();
        }
        // Préparation du contenu HTML de l'email
        $html = $this->renderView('email/order_confirmation.html.twig', [
            'user' => $user,
            'cart' => isset($cart['items']) ? $cart['items'] : $cart,
            'address' => $address,
            'transporteur' => $transporteur,
            'order' => $order,
        ]);
        $email = (new Email())
            ->from('no-reply@konvix.fr')
            ->to($user ? $user->getEmail() : '')
            ->subject('Confirmation de votre commande Konvix')
            ->html($html);
        $mailer->send($email);
        // Vider le panier après paiement validé
        $cartService->clear();
        $this->addFlash('success', 'Votre paiement a été validé, merci pour votre commande ! Un email de confirmation vous a été envoyé.');
        return $this->render('checkout/success.html.twig', [
            'cart' => isset($cart['items']) ? $cart['items'] : $cart,
            'user' => $user,
            'order' => $order,
            'address' => $address,
            'transporteur' => $transporteur,
        ]);
    }

    #[Route('/checkout/pay/stripe', name: 'checkout_pay_stripe')]
    public function payStripe(CartService $cartService, Request $request): Response
    {
        $cart = $cartService->getDetailedCart();
        if (empty($cart['items'])) {
            $this->addFlash('danger', 'Votre panier est vide, impossible de procéder au paiement.');
            return $this->redirectToRoute('cart_index');
        }
        $stripeSecret = $_ENV['STRIPE_SECRET_KEY'] ?? $this->getParameter('STRIPE_SECRET_KEY');
        \Stripe\Stripe::setApiKey($stripeSecret);

        $lineItems = [];
        $total = 0;
        foreach ($cart['items'] as $item) {
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
            $total += $item['product']->getPrice() * $item['quantity'];
        }
        // Ajout des frais de livraison comme un item Stripe
        $session = $request->getSession();
        $transporteur = $session->get('checkout_transporteur');
        if ($transporteur && isset($transporteur['price']) && $transporteur['price'] > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Frais de livraison - ' . $transporteur['name'],
                    ],
                    'unit_amount' => (int)($transporteur['price'] * 100),
                ],
                'quantity' => 1,
            ];
            $total += $transporteur['price'];
        }

        if (empty($lineItems)) {
            $this->addFlash('danger', 'Votre panier est vide, impossible de procéder au paiement.');
            return $this->redirectToRoute('cart_index');
        }

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $this->generateUrl('checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('checkout_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),

        ]);

        return $this->redirect($session->url);
    }

    #[Route('/checkout/pay/paypal', name: 'checkout_pay_paypal')]
    public function payPaypal(CartService $cartService, Request $request, CodePromoRepository $codePromoRepository, CodePromoUsageRepository $codePromoUsageRepository, TokenStorageInterface $tokenStorage): Response
    {
        $cart = $cartService->getDetailedCart();
        $session = $request->getSession();
        $codePromo = $session->get('cart_code_promo', '');
        if ($codePromo) {
            $promo = $codePromoRepository->findValidByCode($codePromo);
            $user = $tokenStorage->getToken() ? $tokenStorage->getToken()->getUser() : null;
            if ($promo && $user && is_object($user) && method_exists($user, 'getId')) {
                if ($codePromoUsageRepository->hasUserUsedCodePromo($user, $promo)) {
                    $session->remove('cart_code_promo');
                    $this->addFlash('danger', 'Ce code promo a déjà été utilisé lors d’une précédente commande. Il n’est valable qu’une seule fois par client.');
                    return $this->redirectToRoute('cart_index');
                }
            }
        }
        $clientId = $_ENV['PAYPAL_CLIENT_ID'] ?? $this->getParameter('PAYPAL_CLIENT_ID');
        $clientSecret = $_ENV['PAYPAL_CLIENT_SECRET'] ?? $this->getParameter('PAYPAL_CLIENT_SECRET');

        $environment = new \PayPalCheckoutSdk\Core\SandboxEnvironment($clientId, $clientSecret);
        $client = new \PayPalCheckoutSdk\Core\PayPalHttpClient($environment);

        $items = [];
        $total = 0;
        foreach ($cart['items'] as $item) {
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
        // Ajouter le frais de livraison (transporteur)
        $session = $request->getSession();
        $selected = $session->get('cart_transporteur', '');
        $transporteurs = [
            'colissimo' => ['name' => 'Colissimo', 'price' => 5.90],
            'mondial' => ['name' => 'Mondial Relay', 'price' => 4.50],
            'chrono' => ['name' => 'Chronopost', 'price' => 12.00],
        ];
        $transporteur = $session->get('checkout_transporteur');
        if (!$transporteur || !isset($transporteur['name'])) {
            $transporteur = $selected && isset($transporteurs[$selected]) ? $transporteurs[$selected] : ['name' => 'Non renseigné', 'price' => 0.00];
            $session->set('checkout_transporteur', $transporteur);
        }
        $fraisLivraison = $transporteur['price'] ?? 0.0;
        $totalWithShipping = $total + $fraisLivraison;


        $order = new \PayPalCheckoutSdk\Orders\OrdersCreateRequest();
        $order->prefer('return=representation');
        $order->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => number_format($totalWithShipping, 2, '.', ''),
                    'breakdown' => [
                        'item_total' => [
                            'currency_code' => 'EUR',
                            'value' => number_format($total, 2, '.', ''),
                        ],
                        'shipping' => [
                            'currency_code' => 'EUR',
                            'value' => number_format($fraisLivraison, 2, '.', ''),
                        ]
                    ]
                ],
                'items' => $items,
            ]],
            'application_context' => [
                'return_url' => $this->generateUrl('checkout_success_paypal', [], 0),
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
            // Si on est en sandbox, afficher la page de test PayPal
            // Suppression de la page de test PayPal : on ne l'affiche plus dans le parcours pro
            // Sinon, comportement normal : message d'erreur et retour panier
            $this->addFlash('danger', 'Erreur PayPal : ' . $e->getMessage());
            return $this->redirectToRoute('cart_index');
        }

        return $this->redirectToRoute('cart_index');
    }
    #[Route('/checkout/success/paypal', name: 'checkout_success_paypal')]
    public function successPaypal(
        Request $request,
        CartService $cartService,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        TokenStorageInterface $tokenStorage,
        CodePromoRepository $codePromoRepository,
        CodePromoUsageRepository $codePromoUsageRepository
    ): Response {

        // 1. Récupérer et valider la commande (simulation ici)
        $cart = $cartService->getDetailedCart();
        $user = $tokenStorage->getToken()->getUser();
        $session = $request->getSession();
        $address = $session->get('checkout_address');
        $selected = $session->get('cart_transporteur', '');
        $transporteurs = [
            'colissimo' => ['name' => 'Colissimo', 'price' => 5.90],
            'mondial' => ['name' => 'Mondial Relay', 'price' => 4.50],
            'chrono' => ['name' => 'Chronopost', 'price' => 12.00],
        ];
        $transporteur = $session->get('checkout_transporteur');
        if (!$transporteur || !isset($transporteur['name'])) {
            $transporteur = $selected && isset($transporteurs[$selected]) ? $transporteurs[$selected] : ['name' => 'Non renseigné', 'price' => 0.00];
            $session->set('checkout_transporteur', $transporteur);
        }

        // 2. Créer et sauvegarder la commande (à adapter selon ton entité Order)
        // $order = new Order(); ... $em->persist($order); $em->flush();

        // Enregistrer l'utilisation du code promo si applicable
        $codePromo = $session->get('cart_code_promo', '');
        if ($codePromo) {
            $promoEntity = $codePromoRepository->findValidByCode($codePromo);
            if ($promoEntity) {
                $usage = new \App\Entity\CodePromoUsage();
                $usage->setUser($user);
                $usage->setCodePromo($promoEntity);
                $usage->setUsedAt(new \DateTime());
                $em->persist($usage);
                $em->flush();
            }
            $session->remove('cart_code_promo');
        }

        // 3. Envoyer l’email de confirmation de commande
        $html = $this->renderView('email/order_confirmation.html.twig', [
            'user' => $user,
            'cart' => isset($cart['items']) ? $cart['items'] : $cart,
            'address' => $address,
            'transporteur' => $transporteur,
        ]);

        $email = (new Email())
            ->from('no-reply@konvix.com')
            ->to($user->getEmail())
            ->subject('Confirmation de votre commande Konvix')
            ->html($html);

        $mailer->send($email);

        // Vider le panier après paiement validé
        $cartService->clear();

        // 4. Afficher la page de confirmation
        return $this->render('checkout/success_paypal.html.twig', [
            'cart' => isset($cart['items']) ? $cart['items'] : $cart,
            'user' => $user,
            'address' => $address,
            'transporteur' => $transporteur,
        ]);
    }
}
