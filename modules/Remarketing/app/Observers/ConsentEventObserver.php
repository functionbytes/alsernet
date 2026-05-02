<?php

namespace Modules\Remarketing\Observers;

use Modules\Remarketing\Models\Automation;
use Modules\Remarketing\Models\ConsentEvent;
use Modules\Remarketing\Services\AutomationService;

class ConsentEventObserver
{
    public function __construct(
        protected AutomationService $automations
    ) {}

    public function created(ConsentEvent $event): void
    {
        if ($event->event_type !== 'confirmed') {
            return;
        }

        $customer = $event->customer;

        if (! $customer) {
            return;
        }

        Automation::where('store_id', $event->store_id)
            ->where('trigger', 'welcome')
            ->where('status', 'active')
            ->each(fn (Automation $a) => $this->automations->trigger($a, $customer));
    }
}
