<?php

namespace Modules\Ecommerce\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Shipment;

class ShippingStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Shipment $shipment,
        public string $oldStatus,
        public string $newStatus
    ) {}
}
