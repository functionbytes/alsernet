<?php

namespace Modules\Mailrelay\Services\EmailValidation\Validators;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Services\EmailValidation\ValidatorInterface;

class ZeroBounceValidator implements ValidatorInterface
{
    private ?string $apiKey;

    private string $apiUrl;

    private int $timeout;

    private int $cacheTtl;

    public function __construct()
    {
        $config = config('email-validator.providers.zerobounce', []);

        $this->apiKey = $config['api_key'] ?? null;
        $this->apiUrl = $config['api_url'] ?? 'https://api.zerobounce.net/v2';
        $this->timeout = $config['timeout'] ?? 10;
        $this->cacheTtl = config('email-validator.cache_ttl', 86400);
    }

    /**
     * Validate an email address using ZeroBounce API v2
     *
     * @return array ['valid' => bool, 'score' => int, 'details' => array, 'message' => string]
     */
    public function validate(string $email): array
    {
        // Check if API key is configured
        if (! $this->apiKey) {
            return $this->errorResponse('ZeroBounce API key not configured');
        }

        // Check cache first
        $cacheKey = config('email-validator.cache_prefix', 'email_validation:')."zerobounce:{$email}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($email) {
            return $this->performValidation($email);
        });
    }

    /**
     * Get the validator name
     */
    public function getName(): string
    {
        return 'zerobounce';
    }

    /**
     * Perform the actual validation request to ZeroBounce API
     */
    private function performValidation(string $email): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->apiUrl}/validate", [
                    'api_key' => $this->apiKey,
                    'email' => $email,
                    'ip_address' => '', // Optional IP address
                ]);

            if (! $response->successful()) {
                Log::warning('ZeroBounce API request failed', [
                    'status' => $response->status(),
                    'email' => $email,
                ]);

                return $this->errorResponse('API request failed with status: '.$response->status());
            }

            $data = $response->json();

            return $this->parseResponse($data);

        } catch (\Exception $e) {
            Log::error('ZeroBounce validation error', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Validation error: '.$e->getMessage());
        }
    }

    /**
     * Parse ZeroBounce API response and map to standard format
     */
    private function parseResponse(array $data): array
    {
        // Check for API errors
        if (isset($data['error'])) {
            return $this->errorResponse($data['error']);
        }

        $status = $data['status'] ?? 'unknown';
        $subStatus = $data['sub_status'] ?? null;

        // Map ZeroBounce status to our validation result
        $result = $this->mapStatus($status, $subStatus);

        return [
            'valid' => $result['valid'],
            'score' => $result['score'],
            'details' => [
                'status' => $status,
                'sub_status' => $subStatus,
                'account' => $data['account'] ?? null,
                'domain' => $data['domain'] ?? null,
                'did_you_mean' => $data['did_you_mean'] ?? null,
                'domain_age_days' => $data['domain_age_days'] ?? null,
                'free_email' => $data['free_email'] ?? false,
                'mx_found' => $data['mx_found'] ?? false,
                'mx_record' => $data['mx_record'] ?? null,
                'smtp_provider' => $data['smtp_provider'] ?? null,
                'firstname' => $data['firstname'] ?? null,
                'lastname' => $data['lastname'] ?? null,
                'gender' => $data['gender'] ?? null,
                'country' => $data['country'] ?? null,
                'region' => $data['region'] ?? null,
                'city' => $data['city'] ?? null,
                'zipcode' => $data['zipcode'] ?? null,
                'processed_at' => $data['processed_at'] ?? now()->toIso8601String(),
            ],
            'message' => $result['message'],
        ];
    }

    /**
     * Map ZeroBounce status to validation result
     *
     * ZeroBounce status values:
     * - valid: Email is valid and safe to send
     * - invalid: Email is invalid and should not be sent
     * - catch-all: Domain accepts all emails (risky)
     * - unknown: Cannot determine validity (risky)
     * - spamtrap: Email is a spam trap (invalid)
     * - abuse: Email is known for abuse complaints (invalid)
     * - do_not_mail: Email should not be mailed (invalid)
     */
    private function mapStatus(string $status, ?string $subStatus): array
    {
        return match (strtolower($status)) {
            'valid' => [
                'valid' => true,
                'score' => 100,
                'message' => 'Email is valid and deliverable',
            ],
            'invalid' => [
                'valid' => false,
                'score' => 0,
                'message' => $this->getInvalidMessage($subStatus),
            ],
            'catch-all' => [
                'valid' => false,
                'score' => 50,
                'message' => 'Catch-all domain (risky)',
            ],
            'unknown' => [
                'valid' => false,
                'score' => 40,
                'message' => 'Cannot determine validity (risky)',
            ],
            'spamtrap' => [
                'valid' => false,
                'score' => 0,
                'message' => 'Email is a spam trap',
            ],
            'abuse' => [
                'valid' => false,
                'score' => 0,
                'message' => 'Email is known for abuse complaints',
            ],
            'do_not_mail' => [
                'valid' => false,
                'score' => 0,
                'message' => 'Email is on do-not-mail list',
            ],
            default => [
                'valid' => false,
                'score' => 0,
                'message' => "Unknown status: {$status}",
            ],
        };
    }

    /**
     * Get specific message for invalid emails based on sub-status
     */
    private function getInvalidMessage(?string $subStatus): string
    {
        if (! $subStatus) {
            return 'Email is invalid';
        }

        return match (strtolower($subStatus)) {
            'mailbox_not_found' => 'Mailbox does not exist',
            'mailbox_quota_exceeded' => 'Mailbox quota exceeded',
            'mailbox_disabled' => 'Mailbox is disabled',
            'domain_not_found' => 'Domain does not exist',
            'no_dns_entries' => 'No DNS entries found for domain',
            'failed_smtp_connection' => 'Failed to connect to mail server',
            'failed_syntax_check' => 'Email has syntax errors',
            'possible_typo' => 'Possible typo in email address',
            'unroutable_ip_address' => 'Mail server IP is unroutable',
            'leading_period_removed' => 'Leading period was removed',
            'does_not_accept_mail' => 'Domain does not accept mail',
            'alias_address' => 'Email is an alias address',
            'role_based' => 'Role-based email address',
            'disposable' => 'Disposable email address',
            'toxic' => 'Toxic email address',
            default => "Invalid: {$subStatus}",
        };
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
