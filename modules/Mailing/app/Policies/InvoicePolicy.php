<?php

namespace Modules\Mailing\Policies;

use Modules\Mailing\Models\Invoice;
use Modules\Mailing\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function delete(User $user, Invoice $invoice, $role)
    {
        switch ($role) {
            case 'admin':
                $can = $invoice->isNew();
                break;
            case 'customer':
                $can = $invoice->isNew() && $invoice->customer_id == $user->customer->id;
                break;
        }

        return $can;
    }
}
