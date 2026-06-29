<?php

namespace Modules\EcommercePayment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\EcommercePayment\Models\Payment;

class PaymentCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Payment $payment) {}
}
