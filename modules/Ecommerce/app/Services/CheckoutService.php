<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Order;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected ShippingService $shippingService,
        protected TaxService $taxService,
        protected DiscountService $discountService,
        protected OrderService $orderService,
    ) {}

    public function process(array $data): Order
    {
        $cartItems = $this->cartService->getCartItems();
        $subTotal = $this->cartService->getCartTotal();

        $shippingAmount = $this->shippingService->calculateShipping(
            $subTotal,
            $data['country'] ?? null,
            $data['state'] ?? null
        );

        $taxAmount = $this->taxService->calculateTax(
            $subTotal + $shippingAmount,
            $data['country'] ?? null,
            $data['state'] ?? null
        );

        $discountAmount = 0;
        if (! empty($data['coupon_code'])) {
            $discount = $this->discountService->applyCoupon($data['coupon_code'], $subTotal);
            $discountAmount = $discount['amount'] ?? 0;
        }

        $total = $subTotal + $shippingAmount + $taxAmount - $discountAmount;

        $items = [];
        foreach ($cartItems as $item) {
            $items[] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['name'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'total' => $item['price'] * $item['qty'],
            ];
        }

        $order = $this->orderService->createOrder([
            'customer_id' => $data['customer_id'] ?? null,
            'status' => 'pending',
            'sub_total' => $subTotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'total' => max(0, $total),
            'coupon_code' => $data['coupon_code'] ?? null,
            'shipping_method' => $data['shipping_method'] ?? 'default',
            'payment_method' => $data['payment_method'] ?? 'cash',
        ], $items);

        $this->cartService->clearCart();

        return $order;
    }
}
