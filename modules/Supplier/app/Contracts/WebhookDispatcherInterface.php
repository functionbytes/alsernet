<?php

namespace Modules\Supplier\Contracts;

/**
 * Outbound webhook dispatcher contract.
 *
 * Implemented by host modules (e.g. Helpdesk) that own webhook configuration.
 * The Supplier module resolves this contract defensively: when no binding is
 * registered, webhook dispatch is silently skipped.
 */
interface WebhookDispatcherInterface
{
    /**
     * Dispatch the given event with its payload to all active webhook listeners.
     *
     * @param  string  $event  Event name (e.g. "content.approved")
     * @param  array  $payload  Event data to deliver as JSON
     */
    public function dispatch(string $event, array $payload): void;
}
