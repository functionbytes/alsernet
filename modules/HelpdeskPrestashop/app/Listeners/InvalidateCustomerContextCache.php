<?php

namespace Modules\HelpdeskPrestashop\Listeners;

use Modules\HelpdeskPrestashop\Events\PsCustomerUpdated;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * A customer updated their profile in PrestaShop (name, email, address,
 * newsletter, etc.) — the cached helpdesk context for their email is now
 * stale. Forgets it so the next agent that opens the conversation gets a
 * fresh pull instead of waiting out the cache TTL (up to 5 minutes).
 */
class InvalidateCustomerContextCache
{
    public function __construct(
        private readonly PrestashopContextService $prestashop
    ) {}

    public function handle(PsCustomerUpdated $event): void
    {
        $email = $event->email();

        if ($email === null || $email === '') {
            return;
        }

        $this->prestashop->forgetCache($email);
    }
}
