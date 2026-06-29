<?php

namespace Modules\Ecommerce\Supports;

use Modules\Ecommerce\Models\Invoice;
use Modules\Ecommerce\Models\InvoiceItem;
use Modules\Ecommerce\Models\Order;

class InvoiceHelper
{
    public static function store(Order $order): Invoice
    {
        $invoice = Invoice::query()->create([
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'code' => 'INV-'.strtoupper(uniqid()),
            'customer_name' => $order->customer?->name,
            'customer_email' => $order->customer?->email,
            'sub_total' => $order->sub_total,
            'tax_amount' => $order->tax_amount,
            'shipping_amount' => $order->shipping_amount,
            'discount_amount' => $order->discount_amount,
            'coupon_code' => $order->coupon_code,
            'amount' => $order->total,
            'status' => 'pending',
        ]);

        foreach ($order->items as $item) {
            InvoiceItem::query()->create([
                'invoice_id' => $invoice->id,
                'reference_type' => get_class($item),
                'reference_id' => $item->id,
                'name' => $item->product_name,
                'qty' => $item->qty,
                'sub_total' => $item->price * $item->qty,
                'amount' => $item->price * $item->qty,
            ]);
        }

        return $invoice;
    }
}
