<?php

namespace Modules\HelpdeskSocial\Services\Channels;

use Illuminate\Http\Request;
use Modules\HelpdeskSocial\Contracts\WebhookVerifierInterface;

class MetaWebhookVerifier implements WebhookVerifierInterface
{
    public function verify(Request $request, string $appSecret): bool
    {
        $signature = $request->header('X-Hub-Signature-256', '');

        if (! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $provided = substr($signature, 7);
        $expected = hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $provided);
    }

    public function getChallengeResponse(Request $request, string $verifyToken): string|false
    {
        $mode = $request->query('hub_mode', '');
        $challenge = $request->query('hub_challenge', '');
        $token = $request->query('hub_verify_token', '');

        if ($mode === 'subscribe' && filled($verifyToken) && hash_equals($verifyToken, $token)) {
            return $challenge;
        }

        return false;
    }
}
