<?php

namespace Modules\HelpdeskSocial\Contracts;

/**
 * Contract for webhook payload parsers.
 */
interface WebhookParserInterface
{
    /**
     * Parse a webhook payload into normalized events.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(array $payload): array;

    /**
     * Check if this parser can handle the given payload.
     */
    public function supports(array $payload): bool;
}
