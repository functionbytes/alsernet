<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\Invoice;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ecommerce.invoices.index');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('ecommerce.invoices.show');
    }

    public function create(User $user): bool
    {
        return $user->can('ecommerce.invoices.store');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('ecommerce.invoices.update');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can('ecommerce.invoices.destroy');
    }
}
