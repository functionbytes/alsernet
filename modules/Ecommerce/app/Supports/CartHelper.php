<?php

namespace Modules\Ecommerce\Supports;

use Illuminate\Support\Facades\Session;
use Modules\Ecommerce\Models\Product;

class CartHelper
{
    protected string $sessionKey = 'ecommerce_cart';

    public function add(Product $product, int $qty = 1, array $options = []): void
    {
        $cart = $this->getCart();
        $key = $this->generateKey($product->id, $options);

        if (isset($cart[$key])) {
            $cart[$key]['qty'] += $qty;
        } else {
            $cart[$key] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->final_price,
                'qty' => $qty,
                'options' => $options,
            ];
        }

        $this->saveCart($cart);
    }

    public function remove(string $key): void
    {
        $cart = $this->getCart();
        unset($cart[$key]);
        $this->saveCart($cart);
    }

    public function update(string $key, int $qty): void
    {
        $cart = $this->getCart();
        if (isset($cart[$key])) {
            $cart[$key]['qty'] = max(1, $qty);
            $this->saveCart($cart);
        }
    }

    public function getCart(): array
    {
        return Session::get($this->sessionKey, []);
    }

    public function saveCart(array $cart): void
    {
        Session::put($this->sessionKey, $cart);
    }

    public function clear(): void
    {
        Session::forget($this->sessionKey);
    }

    public function count(): int
    {
        return collect($this->getCart())->sum('qty');
    }

    public function total(): float
    {
        return collect($this->getCart())->sum(fn ($item) => $item['price'] * $item['qty']);
    }

    protected function generateKey(int $productId, array $options): string
    {
        return md5($productId.serialize($options));
    }
}
