<?php

namespace Modules\Mailrelay\Services\EmailValidation\Validators;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Services\EmailValidation\ValidatorInterface;

/**
 * ExternalApiValidator
 *
 * Orchestrates multiple external email validation providers with fallback support.
 * Primary provider: ZeroBounce
 * Fallback providers: NeverBounce, Abstract API
 *
 * This validator tries the primary provider first, then falls back to secondary
 * providers if the primary fails or is unavailable. Results are cached to minimize
 * API calls and improve performance.
 *
 * Reference: FASE 5 - Section 5.7
 */
class ExternalApiValidator implements ValidatorInterface
{
    private string $primaryProvider;

    private array $fallbackProviders;

    private array $providersConfig;

    private int $cacheTtl;

    public function __construct()
    {
        $this->primaryProvider = config('email-validator.primary_provider', 'zerobounce');
        $this->fallbackProviders = config('email-validator.fallback_providers', []);
        $this->providersConfig = config('email-validator.providers', []);
        $this->cacheTtl = config('email-validator.cache_ttl', 86400);
    }

    /**
     * Validate an email address using external API providers
     *
     * @return array ['valid' => bool, 'score' => int, 'details' => array]
     */
    public function validate(string $email): array
    {
        $cacheKey = config('email-validator.cache_prefix', 'email_validation:')."external:{$email}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($email) {
            // Try primary provider first
            if ($this->isProviderEnabled($this->primaryProvider)) {
                Log::info("ExternalApiValidator: Trying primary provider '{$this->primaryProvider}' for {$email}");

                $result = $this->validateWithProvider($this->primaryProvider, $email);

                if ($result['valid'] !== null) {
                    Log::info("ExternalApiValidator: Primary provider '{$this->primaryProvider}' succeeded");

                    return $this->enrichResult($result, $this->primaryProvider, true);
                }

                Log::warning("ExternalApiValidator: Primary provider '{$this->primaryProvider}' returned null, trying fallbacks");
            } else {
                Log::warning("ExternalApiValidator: Primary provider '{$this->primaryProvider}' is disabled");
            }

            // Try fallback providers in order
            foreach ($this->fallbackProviders as $provider) {
                if (! $this->isProviderEnabled($provider)) {
                    Log::info("ExternalApiValidator: Fallback provider '{$provider}' is disabled, skipping");

                    continue;
                }

                Log::info("ExternalApiValidator: Trying fallback provider '{$provider}' for {$email}");

                $result = $this->validateWithProvider($provider, $email);

                if ($result['valid'] !== null) {
                    Log::info("ExternalApiValidator: Fallback provider '{$provider}' succeeded");

                    return $this->enrichResult($result, $provider, false);
                }

                Log::warning("ExternalApiValidator: Fallback provider '{$provider}' failed, continuing to next");
            }

            // All providers failed
            Log::error("ExternalApiValidator: All providers failed for {$email}");

            return [
                'valid' => null,
                'score' => 0,
                'details' => [
                    'error' => 'All validation providers failed or are unavailable',
                    'providers_tried' => array_merge([$this->primaryProvider], $this->fallbackProviders),
                ],
                'message' => 'External validation unavailable',
                'provider' => null,
                'is_primary' => false,
            ];
        });
    }

    /**
     * Get the validator name
     */
    public function getName(): string
    {
        return 'external';
    }

    /**
     * Check if a provider is enabled
     */
    private function isProviderEnabled(string $provider): bool
    {
        $config = $this->providersConfig[$provider] ?? null;

        if (! $config) {
            return false;
        }

        return ($config['enabled'] ?? false) && ! empty($config['api_key']);
    }

    /**
     * Validate email with a specific provider
     */
    private function validateWithProvider(string $provider, string $email): array
    {
        return match ($provider) {
            'zerobounce' => $this->validateWithZeroBounce($email),
            'neverbounce' => $this->validateWithNeverBounce($email),
            'abstract' => $this->validateWithAbstract($email),
            'hunter' => $this->validateWithHunter($email),
            default => [
                'valid' => null,
                'score' => 0,
                'details' => ['error' => "Unknown provider: {$provider}"],
                'message' => 'Unknown provider',
            ],
        };
    }

    /**
     * Validate email with ZeroBounce API
     */
    private function validateWithZeroBounce(string $email): array
    {
        $config = $this->providersConfig['zerobounce'] ?? [];

        try {
            $response = Http::timeout($config['timeout'] ?? 10)
                ->get($config['api_url'].'/validate', [
                    'api_key' => $config['api_key'],
                    'email' => $email,
                ]);

            if (! $response->successful()) {
                return [
                    'valid' => null,
                    'score' => 0,
                    'details' => ['error' => 'API request failed', 'status' => $response->status()],
                    'message' => 'ZeroBounce API request failed',
                ];
            }

            $data = $response->json();

            // ZeroBounce status: valid, invalid, catch-all, unknown, spamtrap, abuse, do_not_mail
            $status = $data['status'] ?? 'unknown';

            $isValid = match ($status) {
                'valid' => true,
                'catch-all' => null, // Uncertain
                'invalid', 'spamtrap', 'abuse', 'do_not_mail' => false,
                default => null,
            };

            $score = match ($status) {
                'valid' => 100,
                'catch-all' => 70,
                'unknown' => 50,
                'invalid' => 0,
                'spamtrap', 'abuse', 'do_not_mail' => 0,
                default => 0,
            };

            return [
                'valid' => $isValid,
                'score' => $score,
                'details' => [
                    'status' => $status,
                    'sub_status' => $data['sub_status'] ?? null,
                    'free_email' => $data['free_email'] ?? false,
                    'did_you_mean' => $data['did_you_mean'] ?? null,
                    'account' => $data['account'] ?? null,
                    'domain' => $data['domain'] ?? null,
                    'domain_age_days' => $data['domain_age_days'] ?? null,
                    'smtp_provider' => $data['smtp_provider'] ?? null,
                    'mx_found' => $data['mx_found'] ?? null,
                    'mx_record' => $data['mx_record'] ?? null,
                ],
                'message' => $this->getZeroBounceMessage($status),
            ];
        } catch (\Exception $e) {
            Log::error('ZeroBounce validation error: '.$e->getMessage());

            return [
                'valid' => null,
                'score' => 0,
                'details' => ['error' => $e->getMessage()],
                'message' => 'ZeroBounce API error',
            ];
        }
    }

    /**
     * Validate email with NeverBounce API
     */
    private function validateWithNeverBounce(string $email): array
    {
        $config = $this->providersConfig['neverbounce'] ?? [];

        try {
            $response = Http::timeout($config['timeout'] ?? 10)
                ->get($config['api_url'].'/single/check', [
                    'key' => $config['api_key'],
                    'email' => $email,
                ]);

            if (! $response->successful()) {
                return [
                    'valid' => null,
                    'score' => 0,
                    'details' => ['error' => 'API request failed', 'status' => $response->status()],
                    'message' => 'NeverBounce API request failed',
                ];
            }

            $data = $response->json();

            // NeverBounce result: valid, invalid, disposable, catchall, unknown
            $result = $data['result'] ?? 'unknown';

            $isValid = match ($result) {
                'valid' => true,
                'catchall' => null, // Uncertain
                'invalid', 'disposable' => false,
                default => null,
            };

            $score = match ($result) {
                'valid' => 100,
                'catchall' => 70,
                'unknown' => 50,
                'disposable' => 0,
                'invalid' => 0,
                default => 0,
            };

            return [
                'valid' => $isValid,
                'score' => $score,
                'details' => [
                    'result' => $result,
                    'flags' => $data['flags'] ?? [],
                    'suggested_correction' => $data['suggested_correction'] ?? null,
                    'execution_time' => $data['execution_time'] ?? null,
                ],
                'message' => $this->getNeverBounceMessage($result),
            ];
        } catch (\Exception $e) {
            Log::error('NeverBounce validation error: '.$e->getMessage());

            return [
                'valid' => null,
                'score' => 0,
                'details' => ['error' => $e->getMessage()],
                'message' => 'NeverBounce API error',
            ];
        }
    }

    /**
     * Validate email with Abstract API
     */
    private function validateWithAbstract(string $email): array
    {
        $config = $this->providersConfig['abstract'] ?? [];

        try {
            $response = Http::timeout($config['timeout'] ?? 5)
                ->get($config['api_url'], [
                    'api_key' => $config['api_key'],
                    'email' => $email,
                ]);

            if (! $response->successful()) {
                return [
                    'valid' => null,
                    'score' => 0,
                    'details' => ['error' => 'API request failed', 'status' => $response->status()],
                    'message' => 'Abstract API request failed',
                ];
            }

            $data = $response->json();

            $isDisposable = $data['is_disposable_email']['value'] ?? false;
            $isValidFormat = $data['is_valid_format']['value'] ?? false;
            $isMxFound = $data['is_mx_found']['value'] ?? false;
            $isSmtpValid = $data['is_smtp_valid']['value'] ?? false;

            // Determine validity based on multiple factors
            $isValid = $isValidFormat && ! $isDisposable && $isMxFound;

            // Calculate score based on quality
            $qualityScore = $data['quality_score'] ?? 0;
            $score = $isValid ? min(100, max(70, (int) ($qualityScore * 100))) : 0;

            return [
                'valid' => $isValid,
                'score' => $score,
                'details' => [
                    'is_valid_format' => $isValidFormat,
                    'is_disposable' => $isDisposable,
                    'is_free_email' => $data['is_free_email']['value'] ?? false,
                    'is_mx_found' => $isMxFound,
                    'is_smtp_valid' => $isSmtpValid,
                    'quality_score' => $qualityScore,
                    'autocorrect' => $data['autocorrect'] ?? null,
                ],
                'message' => $this->getAbstractMessage($isValid, $isDisposable, $isValidFormat),
            ];
        } catch (\Exception $e) {
            Log::error('Abstract API validation error: '.$e->getMessage());

            return [
                'valid' => null,
                'score' => 0,
                'details' => ['error' => $e->getMessage()],
                'message' => 'Abstract API error',
            ];
        }
    }

    /**
     * Validate email with Hunter API
     */
    private function validateWithHunter(string $email): array
    {
        $config = $this->providersConfig['hunter'] ?? [];

        try {
            $response = Http::timeout($config['timeout'] ?? 10)
                ->get($config['api_url'].'/email-verifier', [
                    'email' => $email,
                    'api_key' => $config['api_key'],
                ]);

            if (! $response->successful()) {
                return [
                    'valid' => null,
                    'score' => 0,
                    'details' => ['error' => 'API request failed', 'status' => $response->status()],
                    'message' => 'Hunter API request failed',
                ];
            }

            $data = $response->json()['data'] ?? [];

            // Hunter result: deliverable, undeliverable, risky, unknown
            $result = $data['result'] ?? 'unknown';

            $isValid = match ($result) {
                'deliverable' => true,
                'risky' => null, // Uncertain
                'undeliverable', 'unknown' => false,
                default => null,
            };

            $hunterScore = $data['score'] ?? 0;
            $score = match ($result) {
                'deliverable' => max(80, $hunterScore),
                'risky' => 50,
                'unknown' => 30,
                'undeliverable' => 0,
                default => 0,
            };

            return [
                'valid' => $isValid,
                'score' => $score,
                'details' => [
                    'result' => $result,
                    'score' => $hunterScore,
                    'regexp' => $data['regexp'] ?? false,
                    'gibberish' => $data['gibberish'] ?? false,
                    'disposable' => $data['disposable'] ?? false,
                    'webmail' => $data['webmail'] ?? false,
                    'mx_records' => $data['mx_records'] ?? false,
                    'smtp_server' => $data['smtp_server'] ?? false,
                    'smtp_check' => $data['smtp_check'] ?? false,
                    'accept_all' => $data['accept_all'] ?? false,
                    'block' => $data['block'] ?? false,
                ],
                'message' => ucfirst($result),
            ];
        } catch (\Exception $e) {
            Log::error('Hunter API validation error: '.$e->getMessage());

            return [
                'valid' => null,
                'score' => 0,
                'details' => ['error' => $e->getMessage()],
                'message' => 'Hunter API error',
            ];
        }
    }

    /**
     * Enrich result with provider information
     */
    private function enrichResult(array $result, string $provider, bool $isPrimary): array
    {
        $result['provider'] = $provider;
        $result['is_primary'] = $isPrimary;

        return $result;
    }

    /**
     * Get human-readable message for ZeroBounce status
     */
    private function getZeroBounceMessage(string $status): string
    {
        return match ($status) {
            'valid' => 'Valid email address',
            'invalid' => 'Invalid email address',
            'catch-all' => 'Catch-all domain (accepts all emails)',
            'unknown' => 'Unable to verify',
            'spamtrap' => 'Known spam trap',
            'abuse' => 'Known abuse email',
            'do_not_mail' => 'Do not mail',
            default => 'Unknown status',
        };
    }

    /**
     * Get human-readable message for NeverBounce result
     */
    private function getNeverBounceMessage(string $result): string
    {
        return match ($result) {
            'valid' => 'Valid email address',
            'invalid' => 'Invalid email address',
            'disposable' => 'Disposable email address',
            'catchall' => 'Catch-all domain',
            'unknown' => 'Unable to verify',
            default => 'Unknown result',
        };
    }

    /**
     * Get human-readable message for Abstract API result
     */
    private function getAbstractMessage(bool $isValid, bool $isDisposable, bool $isValidFormat): string
    {
        if ($isDisposable) {
            return 'Disposable email detected';
        }

        if (! $isValidFormat) {
            return 'Invalid email format';
        }

        if ($isValid) {
            return 'Valid email address';
        }

        return 'Email validation failed';
    }
}
