<?php

namespace Modules\Mailrelay\Services\EmailValidation\Validators;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Services\EmailValidation\ValidatorInterface;

class AbstractApiValidator implements ValidatorInterface
{
    private ?string $apiKey;

    private string $apiUrl;

    private int $timeout;

    private int $cacheTtl;

    public function __construct()
    {
        $config = config('email-validator.providers.abstract', []);

        $this->apiKey = $config['api_key'] ?? null;
        $this->apiUrl = $config['api_url'] ?? 'https://emailvalidation.abstractapi.com/v1';
        $this->timeout = $config['timeout'] ?? 5;
        $this->cacheTtl = config('email-validator.cache_ttl', 86400);
    }

    /**
     * Validate an email address using Abstract API
     *
     * @return array ['valid' => bool, 'score' => int, 'details' => array, 'message' => string]
     */
    public function validate(string $email): array
    {
        // Check if API key is configured
        if (! $this->apiKey) {
            return $this->errorResponse('Abstract API key not configured');
        }

        // Check cache first
        $cacheKey = config('email-validator.cache_prefix', 'email_validation:')."abstract:{$email}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($email) {
            return $this->performValidation($email);
        });
    }

    /**
     * Get the validator name
     */
    public function getName(): string
    {
        return 'abstract';
    }

    /**
     * Perform the actual validation request to Abstract API
     */
    private function performValidation(string $email): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->apiUrl, [
                    'api_key' => $this->apiKey,
                    'email' => $email,
                ]);

            if (! $response->successful()) {
                Log::warning('Abstract API request failed', [
                    'status' => $response->status(),
                    'email' => $email,
                ]);

                return $this->errorResponse('API request failed with status: '.$response->status());
            }

            $data = $response->json();

            return $this->parseResponse($data);

        } catch (\Exception $e) {
            Log::error('Abstract API validation error', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Validation error: '.$e->getMessage());
        }
    }

    /**
     * Parse Abstract API response and map to standard format
     */
    private function parseResponse(array $data): array
    {
        // Check for API errors
        if (isset($data['error'])) {
            return $this->errorResponse($data['error']['message'] ?? 'Unknown API error');
        }

        // Extract key validation results
        $deliverability = $data['deliverability'] ?? 'UNKNOWN';
        $quality = $data['quality_score'] ?? 0;
        $isValidFormat = $data['is_valid_format']['value'] ?? false;
        $isFreeEmail = $data['is_free_email']['value'] ?? false;
        $isDisposableEmail = $data['is_disposable_email']['value'] ?? false;
        $isCatchallEmail = $data['is_catchall_email']['value'] ?? null;
        $isMxFound = $data['is_mx_found']['value'] ?? false;
        $isSmtpValid = $data['is_smtp_valid']['value'] ?? null;

        // Calculate validity and score
        $result = $this->calculateResult(
            $deliverability,
            $quality,
            $isValidFormat,
            $isFreeEmail,
            $isDisposableEmail,
            $isCatchallEmail,
            $isMxFound,
            $isSmtpValid
        );

        return [
            'valid' => $result['valid'],
            'score' => $result['score'],
            'details' => [
                'email' => $data['email'] ?? null,
                'autocorrect' => $data['autocorrect'] ?? null,
                'deliverability' => $deliverability,
                'quality_score' => $quality,
                'is_valid_format' => $isValidFormat,
                'is_free_email' => $isFreeEmail,
                'is_disposable_email' => $isDisposableEmail,
                'is_role_email' => $data['is_role_email']['value'] ?? false,
                'is_catchall_email' => $isCatchallEmail,
                'is_mx_found' => $isMxFound,
                'is_smtp_valid' => $isSmtpValid,
            ],
            'message' => $result['message'],
        ];
    }

    /**
     * Calculate validation result based on Abstract API data
     *
     * Abstract API deliverability values:
     * - DELIVERABLE: Email is valid and deliverable
     * - UNDELIVERABLE: Email is invalid or undeliverable
     * - RISKY: Email may be valid but has risk factors
     * - UNKNOWN: Cannot determine deliverability
     */
    private function calculateResult(
        string $deliverability,
        float $quality,
        bool $isValidFormat,
        bool $isFreeEmail,
        bool $isDisposableEmail,
        ?bool $isCatchallEmail,
        bool $isMxFound,
        ?bool $isSmtpValid
    ): array {
        // If format is invalid, immediately return invalid
        if (! $isValidFormat) {
            return [
                'valid' => false,
                'score' => 0,
                'message' => 'Invalid email format',
            ];
        }

        // If disposable email, mark as invalid
        if ($isDisposableEmail) {
            return [
                'valid' => false,
                'score' => 10,
                'message' => 'Disposable email address detected',
            ];
        }

        // Use Abstract API's deliverability assessment
        switch (strtoupper($deliverability)) {
            case 'DELIVERABLE':
                // Base score on quality score (0-1 range, convert to 0-100)
                $score = max(80, min(100, intval($quality * 100)));

                return [
                    'valid' => true,
                    'score' => $score,
                    'message' => 'Email is valid and deliverable',
                ];

            case 'UNDELIVERABLE':
                return [
                    'valid' => false,
                    'score' => 0,
                    'message' => $this->getUndeliverableReason($isMxFound, $isSmtpValid),
                ];

            case 'RISKY':
                // Calculate score based on risk factors
                $score = 50;
                if ($isCatchallEmail) {
                    $score = 40;
                }
                if (! $isMxFound) {
                    $score = min($score, 30);
                }
                if ($isFreeEmail) {
                    $score = max(45, $score); // Free emails are risky but not necessarily invalid
                }

                return [
                    'valid' => false,
                    'score' => $score,
                    'message' => $this->getRiskyReason($isCatchallEmail, $isFreeEmail, $isMxFound),
                ];

            case 'UNKNOWN':
            default:
                return [
                    'valid' => false,
                    'score' => 30,
                    'message' => 'Cannot determine email deliverability',
                ];
        }
    }

    /**
     * Get specific reason for undeliverable email
     */
    private function getUndeliverableReason(bool $isMxFound, ?bool $isSmtpValid): string
    {
        if (! $isMxFound) {
            return 'No mail server found for domain';
        }

        if ($isSmtpValid === false) {
            return 'SMTP validation failed - mailbox does not exist';
        }

        return 'Email is undeliverable';
    }

    /**
     * Get specific reason for risky email
     */
    private function getRiskyReason(?bool $isCatchallEmail, bool $isFreeEmail, bool $isMxFound): string
    {
        if ($isCatchallEmail) {
            return 'Catch-all domain (accepts all addresses, risky)';
        }

        if ($isFreeEmail) {
            return 'Free email provider (may be less reliable)';
        }

        if (! $isMxFound) {
            return 'Mail server validation inconclusive (risky)';
        }

        return 'Email has risk factors';
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
