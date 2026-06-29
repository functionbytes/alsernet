<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\Shipment;

class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ecommerce.shipments.index');
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return $user->can('ecommerce.shipments.show');
    }

    public function create(User $user): bool
    {
        return $user->can('ecommerce.shipments.store');
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return $user->can('ecommerce.shipments.update');
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->can('ecommerce.shipments.destroy');
    }
}
