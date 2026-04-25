<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\OrderPlaced;

class SendWebhookWhenOrderPlaced
{
    public function handle(OrderPlaced $event): void
    {
        // TODO: Send webhook notification
    }
}
