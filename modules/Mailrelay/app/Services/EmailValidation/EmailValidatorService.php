<?php

namespace Modules\Mailrelay\Services\EmailValidation;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Entities\EmailValidation;
use Modules\Mailrelay\Enums\EmailValidationStatus;
use Modules\Mailrelay\Exceptions\EmailValidationException;
use Modules\Mailrelay\Services\EmailValidation\Validators\DnsValidator;
use Modules\Mailrelay\Services\EmailValidation\Validators\EmailUtilitiesValidator;
use Modules\Mailrelay\Services\EmailValidation\Validators\ExternalApiValidator;
use Modules\Mailrelay\Services\EmailValidation\Validators\SmtpValidator;
use Modules\Mailrelay\Services\EmailValidation\Validators\SyntaxValidator;

/**
 * EmailValidatorService
 *
 * Main orchestrator for email validation with multi-level validation pipeline.
 * Implements early exit optimization, result aggregation, caching, and cost tracking.
 *
 * Validation Pipeline (in order):
 * 1. Syntax Validation (free, instant)
 * 2. Email Utilities (free, instant) - disposable/role account check
 * 3. DNS Validation (free, cached)
 * 4. SMTP Validation (free, slower, cached)
 * 5. External API Validation (paid, cached)
 *
 * Features:
 * - Early exit on critical failures (syntax, DNS)
 * - Result caching to minimize API costs
 * - Cost tracking per validation
 * - Comprehensive result aggregation
 * - Configurable validation levels
 *
 * Reference: FASE 5 - Section 5.8
 */
class EmailValidatorService
{
    private SyntaxValidator $syntaxValidator;

    private EmailUtilitiesValidator $utilitiesValidator;

    private DnsValidator $dnsValidator;

    private SmtpValidator $smtpValidator;

    private ExternalApiValidator $externalValidator;

    private int $cacheTtl;

    private string $cachePrefix;

    private array $thresholds;

    private bool $enableEarlyExit;

    private array $defaultValidators;

    /**
     * Cost tracking (per validation in credits/USD cents)
     */
    private const COSTS = [
        'syntax' => 0,
        'email_utilities' => 0,
        'dns' => 0,
        'smtp' => 0,
        'external' => 1, // Approximate cost for external APIs
    ];

    public function __construct(
        SyntaxValidator $syntaxValidator,
        EmailUtilitiesValidator $utilitiesValidator,
        DnsValidator $dnsValidator,
        SmtpValidator $smtpValidator,
        ExternalApiValidator $externalValidator
    ) {
        $this->syntaxValidator = $syntaxValidator;
        $this->utilitiesValidator = $utilitiesValidator;
        $this->dnsValidator = $dnsValidator;
        $this->smtpValidator = $smtpValidator;
        $this->externalValidator = $externalValidator;

        $this->cacheTtl = config('email-validator.cache_ttl', 86400);
        $this->cachePrefix = config('email-validator.cache_prefix', 'email_validation:');
        $this->thresholds = config('email-validator.thresholds', [
            'valid' => 80,
            'risky' => 50,
            'invalid' => 30,
        ]);
        $this->enableEarlyExit = config('email-validator.enable_early_exit', true);
        $this->defaultValidators = config('email-validator.default_validators', [
            'syntax',
            'dns',
            'smtp',
            'external',
        ]);
    }

    /**
     * Validate an email address using the complete validation pipeline
     *
     * @param  string  $email  The email address to validate
     * @param  array  $options  Validation options
     *                          - 'validators' => array: Specific validators to use (defaults to all)
     *                          - 'skip_cache' => bool: Skip cache and force fresh validation
     *                          - 'level' => string: Validation level (quick|standard|thorough)
     *                          - 'store_result' => bool: Store result in database (default: true)
     * @return array Complete validation result with aggregated data
     *
     * @throws EmailValidationException
     */
    public function validate(string $email, array $options = []): array
    {
        $email = strtolower(trim($email));

        // Validate email format first
        if (empty($email) || ! str_contains($email, '@')) {
            throw EmailValidationException::invalidEmail($email);
        }

        // Check cache first (unless skip_cache is true)
        $skipCache = $options['skip_cache'] ?? false;

        if (! $skipCache) {
            $cachedResult = $this->getCachedResult($email);
            if ($cachedResult) {
                Log::info("EmailValidatorService: Cache hit for {$email}");

                return $cachedResult;
            }
        }

        Log::info("EmailValidatorService: Starting validation for {$email}");

        // Determine validators to use
        $validators = $this->determineValidators($options);

        // Execute validation pipeline
        $results = $this->executeValidationPipeline($email, $validators);

        // Aggregate results
        $aggregatedResult = $this->aggregateResults($email, $results);

        // Store result in database if requested
        if ($options['store_result'] ?? true) {
            $this->storeResult($email, $aggregatedResult);
        }

        // Cache the result
        $this->cacheResult($email, $aggregatedResult);

        Log::info("EmailValidatorService: Validation complete for {$email}", [
            'status' => $aggregatedResult['status'],
            'score' => $aggregatedResult['score'],
            'cost' => $aggregatedResult['cost'],
        ]);

        return $aggregatedResult;
    }

    /**
     * Execute the validation pipeline with early exit optimization
     *
     * @param  array  $validators  List of validator names to execute
     * @return array Results from each validator
     */
    private function executeValidationPipeline(string $email, array $validators): array
    {
        $results = [];
        $shouldContinue = true;

        foreach ($validators as $validatorName) {
            if (! $shouldContinue && $this->enableEarlyExit) {
                Log::info("EmailValidatorService: Early exit triggered, skipping {$validatorName}");
                break;
            }

            $validator = $this->getValidator($validatorName);

            if (! $validator) {
                Log::warning("EmailValidatorService: Unknown validator '{$validatorName}'");

                continue;
            }

            try {
                Log::debug("EmailValidatorService: Running {$validatorName} validator");

                $result = $validator->validate($email);
                $results[$validatorName] = $result;

                Log::debug("EmailValidatorService: {$validatorName} result", [
                    'valid' => $result['valid'],
                    'score' => $result['score'],
                ]);

                // Early exit logic: Stop if critical validators fail
                if ($this->shouldExitEarly($validatorName, $result)) {
                    $shouldContinue = false;
                    Log::info("EmailValidatorService: Critical failure in {$validatorName}, triggering early exit");
                }

            } catch (\Exception $e) {
                Log::error("EmailValidatorService: Error in {$validatorName} validator", [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);

                $results[$validatorName] = [
                    'valid' => null,
                    'score' => 0,
                    'details' => ['error' => $e->getMessage()],
                    'message' => 'Validator error',
                ];
            }
        }

        return $results;
    }

    /**
     * Determine which validators to use based on options
     *
     * @return array List of validator names
     */
    private function determineValidators(array $options): array
    {
        // Custom validators specified
        if (! empty($options['validators'])) {
            return $options['validators'];
        }

        // Validation level specified
        if (! empty($options['level'])) {
            return match ($options['level']) {
                'quick' => ['syntax', 'email_utilities', 'dns'],
                'standard' => ['syntax', 'email_utilities', 'dns', 'smtp'],
                'thorough' => ['syntax', 'email_utilities', 'dns', 'smtp', 'external'],
                default => $this->defaultValidators,
            };
        }

        return $this->defaultValidators;
    }

    /**
     * Get validator instance by name
     */
    private function getValidator(string $name): ?ValidatorInterface
    {
        return match ($name) {
            'syntax' => $this->syntaxValidator,
            'email_utilities' => $this->utilitiesValidator,
            'dns' => $this->dnsValidator,
            'smtp' => $this->smtpValidator,
            'external', 'zerobounce' => $this->externalValidator,
            default => null,
        };
    }

    /**
     * Determine if validation should exit early based on critical failures
     */
    private function shouldExitEarly(string $validatorName, array $result): bool
    {
        if (! $this->enableEarlyExit) {
            return false;
        }

        // Critical validators that should trigger early exit if they fail
        $criticalValidators = ['syntax', 'dns'];

        if (! in_array($validatorName, $criticalValidators)) {
            return false;
        }

        // Exit early if the result is definitively invalid (not just uncertain)
        return $result['valid'] === false && $result['score'] === 0;
    }

    /**
     * Aggregate results from all validators into a final result
     *
     * @param  array  $results  Results from each validator
     * @return array Aggregated validation result
     */
    private function aggregateResults(string $email, array $results): array
    {
        // Calculate weighted average score
        $totalScore = 0;
        $totalWeight = 0;
        $validatorResults = [];
        $totalCost = 0;

        // Weights for each validator (can be configured)
        $weights = [
            'syntax' => 1.0,
            'email_utilities' => 0.8,
            'dns' => 1.2,
            'smtp' => 1.5,
            'external' => 2.0,
        ];

        foreach ($results as $validatorName => $result) {
            $weight = $weights[$validatorName] ?? 1.0;
            $score = $result['score'] ?? 0;

            $totalScore += $score * $weight;
            $totalWeight += $weight;

            $validatorResults[$validatorName] = [
                'valid' => $result['valid'],
                'score' => $score,
                'message' => $result['message'] ?? '',
                'details' => $result['details'] ?? [],
            ];

            // Track cost
            $totalCost += self::COSTS[$validatorName] ?? 0;
        }

        // Calculate final score
        $finalScore = $totalWeight > 0 ? (int) round($totalScore / $totalWeight) : 0;

        // Determine status based on score and individual results
        $status = $this->determineStatus($finalScore, $results);

        // Extract key information
        $isDisposable = $results['email_utilities']['details']['is_disposable'] ?? false;
        $isRoleAccount = $results['email_utilities']['details']['is_role_account'] ?? false;
        $domain = $this->extractDomain($email);

        // Build issues list
        $issues = $this->collectIssues($results);

        return [
            'email' => $email,
            'valid' => $status === EmailValidationStatus::VALID,
            'status' => $status->value,
            'score' => $finalScore,
            'domain' => $domain,
            'is_disposable' => $isDisposable,
            'is_role_account' => $isRoleAccount,
            'validators' => $validatorResults,
            'issues' => $issues,
            'cost' => $totalCost,
            'validated_at' => now()->toIso8601String(),
            'summary' => $this->generateSummary($status, $finalScore, $issues),
        ];
    }

    /**
     * Determine final validation status based on score and validator results
     */
    private function determineStatus(int $score, array $results): EmailValidationStatus
    {
        // Check for disposable email
        if (isset($results['email_utilities']['details']['is_disposable']) &&
            $results['email_utilities']['details']['is_disposable'] === true) {
            return EmailValidationStatus::DISPOSABLE;
        }

        // Check for suspicious patterns
        if (isset($results['external']['details']['status']) &&
            in_array($results['external']['details']['status'], ['spamtrap', 'abuse', 'toxic'])) {
            return EmailValidationStatus::SUSPICIOUS;
        }

        // Score-based determination
        if ($score >= $this->thresholds['valid']) {
            return EmailValidationStatus::VALID;
        }

        if ($score >= $this->thresholds['risky']) {
            return EmailValidationStatus::RISKY;
        }

        return EmailValidationStatus::INVALID;
    }

    /**
     * Collect all issues from validator results
     */
    private function collectIssues(array $results): array
    {
        $issues = [];

        foreach ($results as $validatorName => $result) {
            if ($result['valid'] === false && isset($result['message'])) {
                $issues[] = [
                    'validator' => $validatorName,
                    'issue' => $result['message'],
                ];
            }
        }

        return $issues;
    }

    /**
     * Generate human-readable summary
     */
    private function generateSummary(EmailValidationStatus $status, int $score, array $issues): string
    {
        $summary = match ($status) {
            EmailValidationStatus::VALID => "Email is valid and safe to use (score: {$score}/100)",
            EmailValidationStatus::RISKY => "Email is risky and may cause delivery issues (score: {$score}/100)",
            EmailValidationStatus::INVALID => "Email is invalid and should not be used (score: {$score}/100)",
            EmailValidationStatus::DISPOSABLE => 'Email is from a disposable domain',
            EmailValidationStatus::SUSPICIOUS => 'Email shows suspicious patterns or is known for abuse',
            default => 'Email validation status unknown',
        };

        if (! empty($issues)) {
            $issueCount = count($issues);
            $summary .= ". Found {$issueCount} issue(s).";
        }

        return $summary;
    }

    /**
     * Extract domain from email address
     */
    private function extractDomain(string $email): ?string
    {
        if (! str_contains($email, '@')) {
            return null;
        }

        $parts = explode('@', $email);

        return end($parts);
    }

    /**
     * Get cached validation result
     */
    private function getCachedResult(string $email): ?array
    {
        $cacheKey = $this->cachePrefix.'complete:'.md5($email);

        return Cache::get($cacheKey);
    }

    /**
     * Cache validation result
     */
    private function cacheResult(string $email, array $result): void
    {
        $cacheKey = $this->cachePrefix.'complete:'.md5($email);
        Cache::put($cacheKey, $result, $this->cacheTtl);
    }

    /**
     * Store validation result in database
     */
    private function storeResult(string $email, array $result): void
    {
        try {
            EmailValidation::create([
                'email' => $email,
                'validation_type' => 'complete',
                'status' => $result['status'],
                'score' => $result['score'],
                'details' => [
                    'validators' => $result['validators'],
                    'issues' => $result['issues'],
                    'cost' => $result['cost'],
                    'is_disposable' => $result['is_disposable'],
                    'is_role_account' => $result['is_role_account'],
                ],
                'validated_at' => now(),
                'expires_at' => now()->addSeconds($this->cacheTtl),
            ]);
        } catch (\Exception $e) {
            Log::error('EmailValidatorService: Failed to store validation result', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate multiple emails in batch
     *
     * @return array Array of validation results keyed by email
     */
    public function validateBatch(array $emails, array $options = []): array
    {
        $results = [];

        foreach ($emails as $email) {
            try {
                $results[$email] = $this->validate($email, $options);
            } catch (\Exception $e) {
                Log::error("EmailValidatorService: Batch validation error for {$email}", [
                    'error' => $e->getMessage(),
                ]);

                $results[$email] = [
                    'email' => $email,
                    'valid' => false,
                    'status' => EmailValidationStatus::FAILED->value,
                    'score' => 0,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get validation statistics for an email from cache/database
     */
    public function getValidationHistory(string $email): ?array
    {
        $email = strtolower(trim($email));

        // Try cache first
        $cached = $this->getCachedResult($email);
        if ($cached) {
            return $cached;
        }

        // Try database
        $validation = EmailValidation::forEmail($email)
            ->where('validation_type', 'complete')
            ->notExpired()
            ->latest('validated_at')
            ->first();

        if (! $validation) {
            return null;
        }

        return [
            'email' => $validation->email,
            'valid' => $validation->status === EmailValidationStatus::VALID,
            'status' => $validation->status->value,
            'score' => $validation->score,
            'validators' => $validation->details['validators'] ?? [],
            'issues' => $validation->details['issues'] ?? [],
            'cost' => $validation->details['cost'] ?? 0,
            'validated_at' => $validation->validated_at->toIso8601String(),
        ];
    }

    /**
     * Clear validation cache for an email
     */
    public function clearCache(string $email): void
    {
        $email = strtolower(trim($email));
        $cacheKey = $this->cachePrefix.'complete:'.md5($email);
        Cache::forget($cacheKey);
    }

    /**
     * Get cost estimate for validation based on options
     *
     * @return int Cost in credits/cents
     */
    public function estimateCost(array $options = []): int
    {
        $validators = $this->determineValidators($options);
        $totalCost = 0;

        foreach ($validators as $validatorName) {
            $totalCost += self::COSTS[$validatorName] ?? 0;
        }

        return $totalCost;
    }
}
