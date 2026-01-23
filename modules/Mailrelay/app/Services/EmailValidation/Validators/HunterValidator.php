<?php

namespace Modules\Mailrelay\Services\EmailValidation\Validators;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Services\EmailValidation\ValidatorInterface;

class HunterValidator implements ValidatorInterface
{
    private ?string $apiKey;

    private string $apiUrl;

    private int $timeout;

    private int $cacheTtl;

    public function __construct()
    {
        $config = config('email-validator.providers.hunter', []);

        $this->apiKey = $config['api_key'] ?? null;
        $this->apiUrl = $config['api_url'] ?? 'https://api.hunter.io/v2';
        $this->timeout = $config['timeout'] ?? 10;
        $this->cacheTtl = config('email-validator.cache_ttl', 86400);
    }

    /**
     * Validate an email address using Hunter.io API v2
     *
     * @return array ['valid' => bool, 'score' => int, 'details' => array, 'message' => string]
     */
    public function validate(string $email): array
    {
        // Check if API key is configured
        if (! $this->apiKey) {
            return $this->errorResponse('Hunter API key not configured');
        }

        // Check cache first
        $cacheKey = config('email-validator.cache_prefix', 'email_validation:')."hunter:{$email}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($email) {
            return $this->performValidation($email);
        });
    }

    /**
     * Get the validator name
     */
    public function getName(): string
    {
        return 'hunter';
    }

    /**
     * Perform the actual validation request to Hunter.io API
     */
    private function performValidation(string $email): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->apiUrl}/email-verifier", [
                    'email' => $email,
                    'api_key' => $this->apiKey,
                ]);

            if (! $response->successful()) {
                Log::warning('Hunter API request failed', [
                    'status' => $response->status(),
                    'email' => $email,
                ]);

                return $this->errorResponse('API request failed with status: '.$response->status());
            }

            $data = $response->json();

            return $this->parseResponse($data);

        } catch (\Exception $e) {
            Log::error('Hunter validation error', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Validation error: '.$e->getMessage());
        }
    }

    /**
     * Parse Hunter.io API response and map to standard format
     */
    private function parseResponse(array $data): array
    {
        // Check for API errors
        if (isset($data['errors'])) {
            $errorMessage = is_array($data['errors'])
                ? implode(', ', array_map(fn ($e) => $e['details'] ?? 'Unknown error', $data['errors']))
                : 'API error occurred';

            return $this->errorResponse($errorMessage);
        }

        // Extract the data object from response
        $emailData = $data['data'] ?? [];

        if (empty($emailData)) {
            return $this->errorResponse('No data returned from API');
        }

        $result = $emailData['result'] ?? 'unknown';
        $hunterScore = $emailData['score'] ?? 0;

        // Map Hunter result to our validation result
        $validationResult = $this->mapResult($result, $hunterScore);

        return [
            'valid' => $validationResult['valid'],
            'score' => $validationResult['score'],
            'details' => [
                'result' => $result,
                'hunter_score' => $hunterScore,
                'email' => $emailData['email'] ?? $email,
                'status' => $emailData['status'] ?? null,
                'regexp' => $emailData['regexp'] ?? null,
                'gibberish' => $emailData['gibberish'] ?? null,
                'disposable' => $emailData['disposable'] ?? null,
                'webmail' => $emailData['webmail'] ?? null,
                'mx_records' => $emailData['mx_records'] ?? null,
                'smtp_server' => $emailData['smtp_server'] ?? null,
                'smtp_check' => $emailData['smtp_check'] ?? null,
                'accept_all' => $emailData['accept_all'] ?? null,
                'block' => $emailData['block'] ?? null,
                'sources' => $emailData['sources'] ?? [],
                'domain' => $emailData['domain'] ?? null,
            ],
            'message' => $validationResult['message'],
        ];
    }

    /**
     * Map Hunter result to validation result
     *
     * Hunter result values:
     * - deliverable: Email is deliverable and safe to send
     * - undeliverable: Email is undeliverable and should not be sent
     * - risky: Email is risky (catch-all, disposable, or other issues)
     * - unknown: Cannot determine validity
     */
    private function mapResult(string $result, int $hunterScore): array
    {
        return match (strtolower($result)) {
            'deliverable' => [
                'valid' => true,
                'score' => max(80, $hunterScore),
                'message' => 'Email is deliverable',
            ],
            'undeliverable' => [
                'valid' => false,
                'score' => 0,
                'message' => 'Email is undeliverable',
            ],
            'risky' => [
                'valid' => false,
                'score' => 50,
                'message' => 'Email is risky (may be catch-all, disposable, or have other issues)',
            ],
            'unknown' => [
                'valid' => false,
                'score' => 30,
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
