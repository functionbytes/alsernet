<?php

namespace Modules\CampaignSendingServers\Library\Everification;

use Illuminate\Support\Facades\Http;

/**
 * @see https://www.zerobounce.net/docs/email-validation-api-quickstart
 *
 * Endpoint: GET /v2/validate?api_key=<key>&email=<email>
 *
 * Resultado en `status`: valid | invalid | catch-all | spamtrap |
 *   abuse | do_not_mail | unknown.
 */
class ZeroBounceVerifier implements EmailVerifierContract
{
    public function __construct(protected string $apiKey) {}

    public function verify(string $email): string
    {
        $response = Http::timeout(15)->get('https://api.zerobounce.net/v2/validate', [
            'api_key' => $this->apiKey,
            'email' => $email,
        ]);

        if (! $response->successful()) {
            return self::RESULT_UNKNOWN;
        }

        $status = (string) $response->json('status');

        return match ($status) {
            'valid' => self::RESULT_VALID,
            'invalid', 'spamtrap', 'abuse', 'do_not_mail' => self::RESULT_INVALID,
            'catch-all' => self::RESULT_RISKY,
            default => self::RESULT_UNKNOWN,
        };
    }
}
