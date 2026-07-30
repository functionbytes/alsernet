<?php

namespace Modules\HelpdeskSocial\Contracts;

use Illuminate\Http\Request;

/**
 * Contract for webhook signature verification.
 */
interface WebhookVerifierInterface
{
    /**
     * Verify the webhook request signature.
     */
    public function verify(Request $request, string $appSecret): bool;

    /**
     * Get the challenge response for subscription verification.
     */
    public function getChallengeResponse(Request $request, string $verifyToken): string|false;
}
