<?php

namespace Modules\EcommercePayment\Listeners;

use Illuminate\Support\Facades\Mail;
use Modules\EcommercePayment\Events\PaymentCompleted;
use Modules\EcommercePayment\Mail\PaymentConfirmedMail;

class SendPaymentConfirmationEmail
{
    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;
        $order = $payment->order;

        if (! $order || ! $order->customer || ! $order->customer->email) {
            return;
        }

        Mail::to($order->customer->email)->send(new PaymentConfirmedMail($payment));
    }
}
