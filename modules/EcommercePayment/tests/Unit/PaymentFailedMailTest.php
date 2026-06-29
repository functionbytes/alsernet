<?php

namespace Modules\EcommercePayment\Tests\Unit;

use Modules\Ecommerce\Models\Order;
use Modules\EcommercePayment\Mail\PaymentFailedMail;
use Tests\TestCase;

class PaymentFailedMailTest extends TestCase
{
    public function test_mail_has_correct_subject(): void
    {
        $order = new Order(['code' => 'ORD-12345']);
        $mail = new PaymentFailedMail($order, 'DECLINED');

        $this->assertEquals('Pago fallido - Orden ORD-12345', $mail->envelope()->subject);
    }

    public function test_mail_uses_correct_view(): void
    {
        $order = new Order(['code' => 'ORD-12345']);
        $mail = new PaymentFailedMail($order, 'DECLINED');

        $this->assertEquals('ecommerce-payment::emails.payment_failed', $mail->content()->view);
    }
}
