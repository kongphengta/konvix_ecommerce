<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\ProductRepository;
use App\Repository\CodePromoRepository;

class CartService
{
    private const BUBBLE_ENVELOPE_WEIGHT_GRAMS = 20.0;
    private const DEFAULT_PRODUCT_WEIGHT_GRAMS = 100.0;
    private const SMALL_CART_MAX_TOTAL = 8.00;
    private const SMALL_CART_LETTER_CAP = 2.90;

    private $requestStack;
    private $productRepository;
    private $codePromoRepository;
    const CART_KEY = 'cart';

    public function __construct(RequestStack $requestStack, ProductRepository $productRepository, CodePromoRepository $codePromoRepository)
    {
        $this->requestStack = $requestStack;
        $this->productRepository = $productRepository;
        $this->codePromoRepository = $codePromoRepository;
    }

    public function add(int $productId, int $quantity = 1): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get(self::CART_KEY, []);
        if (isset($cart[$productId])) {
            $cart[$productId] += $quantity;
        } else {
            $cart[$productId] = $quantity;
        }
        $session->set(self::CART_KEY, $cart);
    }

    public function remove(int $productId): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get(self::CART_KEY, []);
        unset($cart[$productId]);
        $session->set(self::CART_KEY, $cart);
    }

    public function getCart(): array
    {
        $session = $this->requestStack->getSession();
        return $session->get(self::CART_KEY, []);
    }

    public function clear(): void
    {
        $session = $this->requestStack->getSession();
        $session->remove(self::CART_KEY);
    }

    public function getCartWeightGrams(): float
    {
        $cart = $this->getCart();
        $weight = 0.0;

        foreach ($cart as $productId => $quantity) {
            $product = $this->productRepository->find($productId);
            if (!$product) {
                continue;
            }

            $productWeight = $product->getWeight();
            if ($productWeight === null || $productWeight <= 0) {
                $productWeight = self::DEFAULT_PRODUCT_WEIGHT_GRAMS;
            }
            $weight += max(0.0, $productWeight) * (int) $quantity;
        }

        return $weight;
    }

    public function getShippingWeightGrams(): float
    {
        $productsWeight = $this->getCartWeightGrams();
        if ($productsWeight <= 0) {
            return 0.0;
        }

        return $productsWeight + self::BUBBLE_ENVELOPE_WEIGHT_GRAMS;
    }

    public function getTransporteurs(): array
    {
        $shippingWeight = $this->getShippingWeightGrams();
        $cartData = $this->getDetailedCart();
        $cartTotal = (float) ($cartData['totalAvecPromo'] ?? 0.0);
        $lettrePrice = $this->calculateLettreSuiviePrice($shippingWeight, $cartTotal);

        return [
            'colissimo' => [
                'id' => 'colissimo',
                'name' => 'Colissimo',
                'price' => 5.90,
                'desc' => 'Livraison a domicile sous 2 jours ouvres.',
                'available' => true,
            ],
            'mondial' => [
                'id' => 'mondial',
                'name' => 'Mondial Relay',
                'price' => 4.50,
                'desc' => 'Livraison en point relais sous 3 a 5 jours.',
                'available' => true,
            ],
            'chrono' => [
                'id' => 'chrono',
                'name' => 'Chronopost',
                'price' => 12.00,
                'desc' => 'Express a domicile sous 24h.',
                'available' => true,
            ],
            'lettre_suivie' => [
                'id' => 'lettre_suivie',
                'name' => 'Lettre suivie La Poste',
                'price' => $lettrePrice ?? 0.0,
                'desc' => $lettrePrice !== null
                    ? 'Tarif calcule selon le poids total (produits + enveloppe a bulles).'
                    : 'Indisponible au-dela de 2 kg (poids panier trop eleve).',
                'available' => $lettrePrice !== null,
            ],
        ];
    }

    private function calculateLettreSuiviePrice(float $shippingWeight, float $cartTotal): ?float
    {
        if ($shippingWeight <= 0) {
            return 0.0;
        }

        // Barème Lettre suivie plus adapté aux petits paniers.
        if ($shippingWeight <= 20) {
            $basePrice = 2.20;
        } elseif ($shippingWeight <= 100) {
            $basePrice = 2.90;
        } elseif ($shippingWeight <= 250) {
            $basePrice = 3.70;
        } elseif ($shippingWeight <= 500) {
            $basePrice = 4.90;
        } elseif ($shippingWeight <= 1000) {
            $basePrice = 6.70;
        } elseif ($shippingWeight <= 2000) {
            $basePrice = 8.90;
        } else {
            return null;
        }

        // Evite qu'un petit article soit pénalisé par des frais trop élevés.
        if ($cartTotal > 0 && $cartTotal <= self::SMALL_CART_MAX_TOTAL) {
            return min($basePrice, self::SMALL_CART_LETTER_CAP);
        }

        return $basePrice;
    }

    public function getDetailedCart(): array
    {
        $cart = $this->getCart();
        $detailedCart = [];
        $total = 0;
        foreach ($cart as $productId => $quantity) {
            $product = $this->productRepository->find($productId);
            if ($product) {
                $lineTotal = $product->getPrice() * $quantity;
                $detailedCart[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'lineTotal' => $lineTotal
                ];
                $total += $lineTotal;
            }
        }
        // Gestion du code promo
        $session = $this->requestStack->getSession();
        $codePromo = $session->get('cart_code_promo', '');
        $reduction = 0;
        $promoEntity = null;
        if ($codePromo) {
            $promoEntity = $this->codePromoRepository->findValidByCode($codePromo);
            if ($promoEntity) {
                if ($promoEntity->getType() === 'pourcentage') {
                    $reduction = round($total * ($promoEntity->getValeur() / 100), 2);
                } elseif ($promoEntity->getType() === 'montant') {
                    $reduction = min($promoEntity->getValeur(), $total);
                }
            }
        }
        return [
            'items' => $detailedCart,
            'total' => $total,
            'reduction' => $reduction,
            'codePromo' => $promoEntity,
            'totalAvecPromo' => $total - $reduction
        ];
    }
}
