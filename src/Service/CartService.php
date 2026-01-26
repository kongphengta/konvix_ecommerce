<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\ProductRepository;
use App\Repository\CodePromoRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
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
