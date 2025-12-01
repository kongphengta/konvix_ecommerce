<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\ProductRepository;

class CartService
{
    private $requestStack;
    private $productRepository;
    const CART_KEY = 'cart';

    public function __construct(RequestStack $requestStack, ProductRepository $productRepository)
    {
        $this->requestStack = $requestStack;
        $this->productRepository = $productRepository;
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
        foreach ($cart as $productId => $quantity) {
            $product = $this->productRepository->find($productId);
            if ($product) {
                $detailedCart[] = [
                    'product' => $product,
                    'quantity' => $quantity
                ];
            }
        }
        return $detailedCart;
    }
}
