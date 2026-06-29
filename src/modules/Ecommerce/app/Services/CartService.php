<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Ecommerce\Models\Cart;

class CartService
{
    public function getCartItems(): Collection
    {
        $customerId = auth('ecommerce')->id();
        $sessionId = session()->getId();

        return Cart::query()
            ->with('product')
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when(! $customerId, fn ($q) => $q->where('session_id', $sessionId))
            ->get();
    }

    public function clearCart(): void
    {
        $customerId = auth('ecommerce')->id();
        $sessionId = session()->getId();

        Cart::query()
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when(! $customerId, fn ($q) => $q->where('session_id', $sessionId))
            ->delete();
    }

    public function count(): int
    {
        return $this->getCartItems()->sum('qty');
    }
}
