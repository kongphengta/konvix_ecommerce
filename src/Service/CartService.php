<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\ProductRepository;

class CartService
{
    private $session;
    private $productRepository;
    const CART_KEY = 'cart';

    public function __construct(SessionInterface $session, ProductRepository $productRepository)
    {
        $this->session = $session;
        $this->productRepository = $productRepository;
    }

    public function add(int $productId, int $quantity = 1): void
    {
        $cart = $this->session->get(self::CART_KEY, []);
        if (isset($cart[$productId])) {
            $cart[$productId] += $quantity;
        } else {
            $cart[$productId] = $quantity;
        }
        $this->session->set(self::CART_KEY, $cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->session->get(self::CART_KEY, []);
        unset($cart[$productId]);
        $this->session->set(self::CART_KEY, $cart);
    }

    public function getCart(): array
    {
        return $this->session->get(self::CART_KEY, []);
    }

    public function clear(): void
    {
        $this->session->remove(self::CART_KEY);
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
