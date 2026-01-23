<?php

namespace Modules\Mailrelay\Services\EmailValidation\Validators;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Services\EmailValidation\ValidatorInterface;

/**
 * PythonMlValidator - Machine Learning based email validation
 *
 * Uses a Python script to perform ML-based validation to detect:
 * - Suspicious patterns
 * - Disposable email addresses
 * - Temporary email providers
 * - Other anomalies using trained models
 *
 * FASE 11 - Section 11.5: Python ML Integration
 */
class PythonMlValidator implements ValidatorInterface
{
    private bool $enabled;

    private string $pythonExecutable;

    private string $scriptPath;

    private int $timeout;

    private int $cacheTtl;

    public function __construct()
    {
        $config = config('email-validator.python_ml', []);

        $this->enabled = $config['enabled'] ?? false;
        $this->pythonExecutable = $config['python_executable'] ?? '/usr/bin/python3';
        $this->scriptPath = $config['script_path'] ?? base_path('python/ml_validator/validator.py');
        $this->timeout = $config['timeout'] ?? 30;
        $this->cacheTtl = $config['cache_ttl'] ?? 86400;
    }

    /**
     * Validate an email address using Python ML script
     *
     * @return array ['valid' => bool, 'score' => int, 'details' => array, 'message' => string]
     */
    public function validate(string $email): array
    {
        // Check if ML validator is enabled
        if (! $this->enabled) {
            return $this->errorResponse('Python ML validator is not enabled');
        }

        // Check if Python executable exists
        if (! file_exists($this->pythonExecutable) && ! $this->isExecutableInPath($this->pythonExecutable)) {
            Log::warning('Python executable not found', [
                'path' => $this->pythonExecutable,
            ]);

            return $this->errorResponse('Python executable not found');
        }

        // Check if Python script exists
        if (! file_exists($this->scriptPath)) {
            Log::warning('Python ML script not found', [
                'path' => $this->scriptPath,
            ]);

            return $this->errorResponse('Python ML script not found');
        }

        // Check cache first
        $cacheKey = config('email-validator.cache_prefix', 'email_validation:')."python_ml:{$email}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($email) {
            return $this->performValidation($email);
        });
    }

    /**
     * Get the validator name
     */
    public function getName(): string
    {
        return 'python_ml';
    }

    /**
     * Perform the actual validation by executing Python script
     */
    private function performValidation(string $email): array
    {
        try {
            // Escape email for shell execution
            $escapedEmail = escapeshellarg($email);
            $escapedScript = escapeshellarg($this->scriptPath);

            // Build the command
            $command = sprintf(
                '%s %s %s 2>&1',
                escapeshellarg($this->pythonExecutable),
                $escapedScript,
                $escapedEmail
            );

            // Log the execution attempt
            Log::info('Executing Python ML validator', [
                'email' => $email,
                'command' => $command,
            ]);

            // Execute the Python script with timeout
            $output = $this->executeWithTimeout($command, $this->timeout);

            if ($output === null) {
                Log::error('Python ML script execution timeout', [
                    'email' => $email,
                    'timeout' => $this->timeout,
                ]);

                return $this->errorResponse('Python script execution timeout');
            }

            // Parse JSON output from Python script
            return $this->parseOutput($output, $email);

        } catch (\Exception $e) {
            Log::error('Python ML validation error', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('ML validation error: '.$e->getMessage());
        }
    }

    /**
     * Execute command with timeout using shell_exec
     */
    private function executeWithTimeout(string $command, int $timeout): ?string
    {
        // Add timeout to the command if on Unix-like system
        if (DIRECTORY_SEPARATOR === '/') {
            $command = sprintf('timeout %d %s', $timeout, $command);
        }

        // Execute the command
        $output = shell_exec($command);

        // Check if execution failed
        if ($output === null || $output === false) {
            return null;
        }

        return trim($output);
    }

    /**
     * Parse the JSON output from Python script
     *
     * Expected JSON format:
     * {
     *   "valid": true|false,
     *   "score": 0-100,
     *   "confidence": 0.0-1.0,
     *   "is_disposable": true|false,
     *   "is_suspicious": true|false,
     *   "patterns_detected": ["pattern1", "pattern2"],
     *   "provider_type": "temporary|permanent|unknown",
     *   "risk_level": "low|medium|high",
     *   "ml_model_version": "1.0.0",
     *   "message": "Validation message"
     * }
     */
    private function parseOutput(string $output, string $email): array
    {
        // Remove any non-JSON content (warnings, info messages, etc.)
        $output = $this->extractJson($output);

        if (empty($output)) {
            Log::error('Python ML script returned empty output', [
                'email' => $email,
            ]);

            return $this->errorResponse('Python script returned empty output');
        }

        // Decode JSON output
        $data = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse Python ML script JSON output', [
                'email' => $email,
                'output' => $output,
                'json_error' => json_last_error_msg(),
            ]);

            return $this->errorResponse('Failed to parse ML script output: '.json_last_error_msg());
        }

        // Check for error in response
        if (isset($data['error'])) {
            return $this->errorResponse($data['error']);
        }

        // Validate required fields
        if (! isset($data['valid']) || ! isset($data['score'])) {
            Log::error('Python ML script output missing required fields', [
                'email' => $email,
                'data' => $data,
            ]);

            return $this->errorResponse('ML script output missing required fields');
        }

        // Build standardized response
        return [
            'valid' => (bool) $data['valid'],
            'score' => (int) $data['score'],
            'details' => [
                'confidence' => $data['confidence'] ?? null,
                'is_disposable' => $data['is_disposable'] ?? false,
                'is_suspicious' => $data['is_suspicious'] ?? false,
                'patterns_detected' => $data['patterns_detected'] ?? [],
                'provider_type' => $data['provider_type'] ?? 'unknown',
                'risk_level' => $data['risk_level'] ?? 'unknown',
                'ml_model_version' => $data['ml_model_version'] ?? null,
                'processed_at' => now()->toIso8601String(),
            ],
            'message' => $data['message'] ?? $this->generateMessage($data),
        ];
    }

    /**
     * Extract JSON content from output (removes stderr, warnings, etc.)
     */
    private function extractJson(string $output): string
    {
        // Try to find JSON object in output
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and lines that don't start with {
            if (empty($line) || $line[0] !== '{') {
                continue;
            }

            // Try to decode this line as JSON
            $decoded = json_decode($line, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $line;
            }
        }

        // If no valid JSON line found, try the entire output
        $trimmed = trim($output);
        if (! empty($trimmed) && $trimmed[0] === '{') {
            return $trimmed;
        }

        return '';
    }

    /**
     * Generate a message based on the validation data
     */
    private function generateMessage(array $data): string
    {
        $valid = $data['valid'] ?? false;
        $isDisposable = $data['is_disposable'] ?? false;
        $isSuspicious = $data['is_suspicious'] ?? false;
        $riskLevel = $data['risk_level'] ?? 'unknown';

        if (! $valid) {
            if ($isDisposable) {
                return 'Email appears to be from a disposable provider';
            }

            if ($isSuspicious) {
                return 'Email contains suspicious patterns';
            }

            return 'Email failed ML validation';
        }

        if ($riskLevel === 'high') {
            return 'Email is valid but has high risk level';
        }

        if ($riskLevel === 'medium') {
            return 'Email is valid but has medium risk level';
        }

        return 'Email passed ML validation';
    }

    /**
     * Check if executable is available in system PATH
     */
    private function isExecutableInPath(string $executable): bool
    {
        // Remove any path components, keep only executable name
        $execName = basename($executable);

        // Try to locate the executable using 'which' on Unix or 'where' on Windows
        if (DIRECTORY_SEPARATOR === '/') {
            $result = shell_exec("which {$execName} 2>/dev/null");
        } else {
            $result = shell_exec("where {$execName} 2>nul");
        }

        return ! empty($result);
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
