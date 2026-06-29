<?php

namespace Modules\Campaign\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de verificación de emails vía API externa (NeverBounce o ZeroBounce).
 * Fallback a validación sintáctica si no hay API key configurada.
 */
class EmailVerifier
{
    /**
     * Resultados posibles de verificación.
     */
    public const VALID = 'valid';

    public const INVALID = 'invalid';

    public const CATCH_ALL = 'catch_all';

    public const UNKNOWN = 'unknown';

    public const DISPOSABLE = 'disposable';

    /**
     * Verifica un email.
     */
    public function verify(string $email): array
    {
        $provider = config('campaign.email_verification.provider');
        $apiKey = config("campaign.email_verification.{$provider}_api_key");

        if (! $provider || ! $apiKey) {
            return $this->syntaxCheck($email);
        }

        try {
            return match ($provider) {
                'neverbounce' => $this->verifyNeverBounce($email, $apiKey),
                'zerobounce' => $this->verifyZeroBounce($email, $apiKey),
                default => $this->syntaxCheck($email),
            };
        } catch (\Throwable $e) {
            Log::warning('Email verification API error', ['email' => $email, 'provider' => $provider, 'error' => $e->getMessage()]);

            return $this->syntaxCheck($email);
        }
    }

    /**
     * Verifica múltiples emails en batch (si la API lo soporta).
     */
    public function verifyMany(array $emails): array
    {
        return array_map(fn (string $email): array => $this->verify($email), $emails);
    }

    private function syntaxCheck(string $email): array
    {
        $valid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        return [
            'email' => $email,
            'status' => $valid ? self::VALID : self::INVALID,
            'score' => $valid ? 1.0 : 0.0,
            'provider' => 'syntax',
        ];
    }

    private function verifyNeverBounce(string $email, string $apiKey): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
        ])->post('https://api.neverbounce.com/v4/single/check', [
            'email' => $email,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('NeverBounce API error: '.$response->body());
        }

        $data = $response->json();
        $status = match ($data['result'] ?? 'unknown') {
            'valid' => self::VALID,
            'invalid' => self::INVALID,
            'catchall' => self::CATCH_ALL,
            'disposable' => self::DISPOSABLE,
            default => self::UNKNOWN,
        };

        return [
            'email' => $email,
            'status' => $status,
            'score' => $data['result'] === 'valid' ? 1.0 : 0.0,
            'provider' => 'neverbounce',
            'raw' => $data,
        ];
    }

    private function verifyZeroBounce(string $email, string $apiKey): array
    {
        $response = Http::get('https://api.zerobounce.net/v2/validate', [
            'api_key' => $apiKey,
            'email' => $email,
            'ip_address' => '',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('ZeroBounce API error: '.$response->body());
        }

        $data = $response->json();
        $status = match ($data['status'] ?? 'unknown') {
            'valid' => self::VALID,
            'invalid' => self::INVALID,
            'catch-all' => self::CATCH_ALL,
            'unknown' => self::UNKNOWN,
            'disposable' => self::DISPOSABLE,
            default => self::UNKNOWN,
        };

        return [
            'email' => $email,
            'status' => $status,
            'score' => $data['sub_status'] === 'mailbox_not_found' ? 0.0 : ($status === self::VALID ? 1.0 : 0.5),
            'provider' => 'zerobounce',
            'raw' => $data,
        ];
    }
}
