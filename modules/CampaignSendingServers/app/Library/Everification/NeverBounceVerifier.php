<?php

namespace Modules\CampaignSendingServers\Library\Everification;

use Illuminate\Support\Facades\Http;

/**
 * @see https://developers.neverbounce.com/reference/single-check
 *
 * Endpoint: GET /v4.2/single/check?key=<key>&email=<email>
 *
 * Resultado en `result`: valid | invalid | catchall | unknown | disposable.
 *   - catchall, disposable → mapeamos a 'risky'
 *   - unknown → 'unknown'
 *   - invalid → 'invalid'
 *   - valid → 'valid'
 */
class NeverBounceVerifier implements EmailVerifierContract
{
    public function __construct(protected string $apiKey) {}

    public function verify(string $email): string
    {
        $response = Http::timeout(15)->get('https://api.neverbounce.com/v4.2/single/check', [
            'key' => $this->apiKey,
            'email' => $email,
        ]);

        if (! $response->successful()) {
            return self::RESULT_UNKNOWN;
        }

        $result = (string) $response->json('result');

        return match ($result) {
            'valid' => self::RESULT_VALID,
            'invalid' => self::RESULT_INVALID,
            'catchall', 'disposable' => self::RESULT_RISKY,
            default => self::RESULT_UNKNOWN,
        };
    }
}
