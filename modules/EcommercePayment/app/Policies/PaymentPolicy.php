<?php

namespace Modules\EcommercePayment\Policies;

use App\Models\User;
use Modules\EcommercePayment\Models\Payment;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.payment.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('ecommerce.payment.view');
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('ecommerce.payment.refund');
    }
}
