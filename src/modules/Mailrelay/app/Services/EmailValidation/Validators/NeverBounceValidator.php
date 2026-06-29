<?php

namespace Modules\Mailrelay\Services\EmailValidation\Validators;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Services\EmailValidation\ValidatorInterface;

class NeverBounceValidator implements ValidatorInterface
{
    private ?string $apiKey;

    private string $apiUrl;

    private int $timeout;

    private int $cacheTtl;

    public function __construct()
    {
        $config = config('email-validator.providers.neverbounce', []);

        $this->apiKey = $config['api_key'] ?? null;
        $this->apiUrl = $config['api_url'] ?? 'https://api.neverbounce.com/v4';
        $this->timeout = $config['timeout'] ?? 10;
        $this->cacheTtl = config('email-validator.cache_ttl', 86400);
    }

    /**
     * Validate an email address using NeverBounce API v4 single/check endpoint
     *
     * @return array ['valid' => bool, 'score' => int, 'details' => array, 'message' => string]
     */
    public function validate(string $email): array
    {
        // Check if API key is configured
        if (! $this->apiKey) {
            return $this->errorResponse('NeverBounce API key not configured');
        }

        // Check cache first
        $cacheKey = config('email-validator.cache_prefix', 'email_validation:')."neverbounce:{$email}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($email) {
            return $this->performValidation($email);
        });
    }

    /**
     * Get the validator name
     */
    public function getName(): string
    {
        return 'neverbounce';
    }

    /**
     * Perform the actual validation request to NeverBounce API v4 single/check endpoint
     */
    private function performValidation(string $email): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->apiUrl}/single/check", [
                    'key' => $this->apiKey,
                    'email' => $email,
                    'address_info' => 1, // Request additional address information
                    'credits_info' => 0, // Don't include credits info (optional)
                    'timeout' => $this->timeout,
                ]);

            if (! $response->successful()) {
                Log::warning('NeverBounce API request failed', [
                    'status' => $response->status(),
                    'email' => $email,
                ]);

                return $this->errorResponse('API request failed with status: '.$response->status());
            }

            $data = $response->json();

            return $this->parseResponse($data, $email);

        } catch (\Exception $e) {
            Log::error('NeverBounce validation error', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Validation error: '.$e->getMessage());
        }
    }

    /**
     * Parse NeverBounce API response and map to standard format
     */
    private function parseResponse(array $data, string $email): array
    {
        // Check for API errors
        if (isset($data['status']) && $data['status'] === 'failed') {
            $errorMessage = $data['message'] ?? 'Unknown API error';

            return $this->errorResponse($errorMessage);
        }

        // Check for general errors in response
        if (isset($data['error'])) {
            return $this->errorResponse($data['error']);
        }

        // Get the result code from response
        $result = $data['result'] ?? 'unknown';
        $flags = $data['flags'] ?? [];
        $suggestedCorrection = $data['suggested_correction'] ?? null;
        $addressInfo = $data['address_info'] ?? [];
        $executionTime = $data['execution_time'] ?? null;

        // Map NeverBounce result to our validation result
        $validationResult = $this->mapResult($result, $flags);

        return [
            'valid' => $validationResult['valid'],
            'score' => $validationResult['score'],
            'details' => [
                'result' => $result,
                'flags' => $flags,
                'suggested_correction' => $suggestedCorrection,
                'has_dns' => $flags['has_dns'] ?? null,
                'has_dns_mx' => $flags['has_dns_mx'] ?? null,
                'free_email_host' => $flags['free_email_host'] ?? null,
                'smtp_connectable' => $flags['smtp_connectable'] ?? null,
                'role_account' => $flags['role_account'] ?? null,
                'disposable_email' => $flags['disposable_email'] ?? null,
                'government_host' => $flags['government_host'] ?? null,
                'academic_host' => $flags['academic_host'] ?? null,
                'military_host' => $flags['military_host'] ?? null,
                'international_host' => $flags['international_host'] ?? null,
                'address_info' => $addressInfo,
                'execution_time' => $executionTime,
                'original_email' => $email,
            ],
            'message' => $validationResult['message'],
        ];
    }

    /**
     * Map NeverBounce result to validation result
     *
     * NeverBounce result codes (v4 API):
     * - valid: Email is valid and safe to send
     * - invalid: Email is invalid and should not be sent
     * - disposable: Email is from a disposable email provider
     * - catchall: Domain accepts all emails (risky)
     * - unknown: Cannot determine validity (risky)
     */
    private function mapResult(string $result, array $flags): array
    {
        // Check for disposable email flag
        $isDisposable = $flags['disposable_email'] ?? false;

        // Check for role-based account
        $isRoleAccount = $flags['role_account'] ?? false;

        // Check for free email provider
        $isFreeEmail = $flags['free_email_host'] ?? false;

        return match (strtolower($result)) {
            'valid' => [
                'valid' => true,
                'score' => $this->calculateValidScore($flags),
                'message' => $this->getValidMessage($flags),
            ],
            'invalid' => [
                'valid' => false,
                'score' => 0,
                'message' => 'Email is invalid and undeliverable',
            ],
            'disposable' => [
                'valid' => false,
                'score' => 0,
                'message' => 'Email is from a disposable email provider',
            ],
            'catchall' => [
                'valid' => false,
                'score' => 50,
                'message' => 'Domain accepts all emails (catch-all), verification uncertain',
            ],
            'unknown' => [
                'valid' => false,
                'score' => 40,
                'message' => 'Cannot determine email validity',
            ],
            default => [
                'valid' => false,
                'score' => 0,
                'message' => "Unknown result: {$result}",
            ],
        };
    }

    /**
     * Calculate score for valid emails based on flags
     */
    private function calculateValidScore(array $flags): int
    {
        $score = 100;

        // Reduce score for role accounts
        if ($flags['role_account'] ?? false) {
            $score -= 10;
        }

        // Reduce score for free email providers
        if ($flags['free_email_host'] ?? false) {
            $score -= 5;
        }

        // Bonus for having MX records
        if (! ($flags['has_dns_mx'] ?? false)) {
            $score -= 15;
        }

        // Bonus for SMTP connectivity
        if (! ($flags['smtp_connectable'] ?? false)) {
            $score -= 10;
        }

        return max(0, min(100, $score));
    }

    /**
     * Get detailed message for valid emails based on flags
     */
    private function getValidMessage(array $flags): string
    {
        $notes = [];

        if ($flags['role_account'] ?? false) {
            $notes[] = 'role-based account';
        }

        if ($flags['free_email_host'] ?? false) {
            $notes[] = 'free email provider';
        }

        if ($flags['government_host'] ?? false) {
            $notes[] = 'government domain';
        }

        if ($flags['academic_host'] ?? false) {
            $notes[] = 'academic domain';
        }

        if ($flags['military_host'] ?? false) {
            $notes[] = 'military domain';
        }

        $baseMessage = 'Email is valid and deliverable';

        if (! empty($notes)) {
            return $baseMessage.' ('.implode(', ', $notes).')';
        }

        return $baseMessage;
    }

    /**
     * Return error response in standard format
     */
    private function errorResponse(string $message): array
    {
        return [
            'valid' => false,
            'score' => 0,
            'details' => [
                'error' => $message,
            ],
            'message' => $message,
        ];
    }
}
