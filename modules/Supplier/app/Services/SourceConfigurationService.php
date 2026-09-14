<?php

namespace Modules\Supplier\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use Modules\Supplier\DTOs\ConnectionTestResult;
use Modules\Supplier\DTOs\ValidationResult;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Source\SourceConfiguration;
use Modules\Supplier\Models\Source\SourceHealthHistory;
use Modules\Supplier\Models\Source\SourceMonitor;
use Modules\Supplier\Models\Source\SourceTemplate;
use Modules\Supplier\Models\Source\SourceTransformation;
use Modules\Supplier\Models\SupplierCredential;
use Modules\Supplier\Traits\ValidatesPublicUrl;

/**
 * SourceConfigurationService
 *
 * Comprehensive service for managing supplier source configurations,
 * credentials, health monitoring, and connection testing.
 */
class SourceConfigurationService
{
    use ValidatesPublicUrl;

    protected Client $httpClient;

    protected array $configSchemas = [
        'connection' => [
            'website' => ['url', 'timeout', 'user_agent'],
            'ftp' => ['host', 'port', 'root', 'passive', 'ssl', 'timeout'],
            'api' => ['base_url', 'version', 'timeout', 'retry_attempts'],
        ],
        'authentication' => [
            'basic_auth' => ['username', 'password'],
            'api_key' => ['api_key', 'header_name'],
            'oauth' => ['client_id', 'client_secret', 'token_url', 'scope'],
            'bearer' => ['token'],
        ],
    ];

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => true,
            'http_errors' => false,
        ]);
    }

    /**
     * Get effective configuration by merging all config types
     */
    public function getEffectiveConfig(Source $source): array
    {
        $configurations = $source->configurations()->enabled()->valid()->ordered()->get();

        $effective = [
            'connection' => [],
            'authentication' => [],
            'extraction' => [],
            'schedule' => [],
            'retry' => [],
            'proxy' => [],
            'validation' => [],
        ];

        foreach ($configurations as $config) {
            $type = $config->config_type;
            if (isset($effective[$type])) {
                $effective[$type] = array_merge($effective[$type], $config->config_data ?? []);
            }
        }

        return $effective;
    }

    /**
     * Set configuration for a source
     */
    public function setConfiguration(
        Source $source,
        string $type,
        array $config,
        ?int $userId = null
    ): SourceConfiguration {
        $validation = $this->validateConfiguration($type, $config);

        $configuration = $source->configurations()->updateOrCreate(
            ['config_type' => $type],
            [
                'config_data' => $config,
                'config_schema_version' => '1.0',
                'is_valid' => $validation->isValid(),
                'validation_errors' => $validation->errors,
                'last_validated_at' => now(),
                'is_enabled' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );

        Log::info('Configuration set for source', [
            'source_id' => $source->id,
            'config_type' => $type,
            'is_valid' => $validation->isValid(),
        ]);

        return $configuration;
    }

    /**
     * Validate configuration against schema
     */
    public function validateConfiguration(string $type, array $config): ValidationResult
    {
        $result = ValidationResult::success();

        if (! in_array($type, ['connection', 'authentication', 'extraction', 'schedule', 'retry', 'proxy', 'validation'])) {
            return ValidationResult::failure(["Invalid configuration type: {$type}"]);
        }

        if ($type === 'connection') {
            $result = $this->validateConnectionConfig($config);
        } elseif ($type === 'authentication') {
            $result = $this->validateAuthenticationConfig($config);
        } elseif ($type === 'extraction') {
            $result = $this->validateExtractionConfig($config);
        } elseif ($type === 'schedule') {
            $result = $this->validateScheduleConfig($config);
        } elseif ($type === 'retry') {
            $result = $this->validateRetryConfig($config);
        }

        return $result;
    }

    /**
     * Create configuration from template
     */
    public function createFromTemplate(
        Source $source,
        SourceTemplate $template,
        array $variables,
        ?int $userId = null
    ): array {
        $missingVars = $this->getMissingVariables($template, $variables);

        if (! empty($missingVars)) {
            throw new Exception('Missing required variables: '.implode(', ', $missingVars));
        }

        $processed = $this->processTemplateVariables($template->toArray(), $variables);

        $configurations = [];

        $configTypes = ['connection', 'extraction', 'schedule', 'retry', 'validation'];
        foreach ($configTypes as $type) {
            $templateKey = "{$type}_template";
            if (! empty($processed[$templateKey])) {
                $configurations[$type] = $this->setConfiguration(
                    $source,
                    $type,
                    $processed[$templateKey],
                    $userId
                );
            }
        }

        $template->incrementUsage();

        Log::info('Configuration created from template', [
            'source_id' => $source->id,
            'template_id' => $template->id,
            'configurations_created' => count($configurations),
        ]);

        return $configurations;
    }

    /**
     * Get missing template variables
     */
    public function getMissingVariables(SourceTemplate $template, array $variables): array
    {
        return $template->getMissingVariables($variables);
    }

    /**
     * Process template variables
     */
    public function processTemplateVariables(array $config, array $variables): array
    {
        array_walk_recursive($config, function (&$value) use ($variables) {
            if (is_string($value)) {
                foreach ($variables as $key => $val) {
                    $value = str_replace("{{{$key}}}", $val, $value);
                }
            }
        });

        return $config;
    }

    /**
     * Test connection to source
     */
    public function testConnection(Source $source): ConnectionTestResult
    {
        $config = $this->getEffectiveConfig($source);

        try {
            $result = match ($source->source_type) {
                Source::SOURCE_TYPE_WEBSITE => $this->testWebConnection(
                    $config['connection'],
                    $config['authentication']
                ),
                Source::SOURCE_TYPE_FTP => $this->testFtpConnection(
                    $config['connection'],
                    $config['authentication']
                ),
                Source::SOURCE_TYPE_API => $this->testApiConnection(
                    $config['connection'],
                    $config['authentication']
                ),
                default => ConnectionTestResult::failure('Unsupported source type: '.$source->source_type),
            };

            $this->recordHealthCheck($source, $result);

            return $result;
        } catch (Exception $e) {
            Log::error('Connection test failed', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);

            $result = ConnectionTestResult::failure('Connection test failed: '.$e->getMessage());
            $this->recordHealthCheck($source, $result);

            return $result;
        }
    }

    /**
     * Test web/HTTP connection
     */
    public function testWebConnection(array $config, array $authConfig): ConnectionTestResult
    {
        if (empty($config['url'])) {
            return ConnectionTestResult::failure('URL is required for web connection');
        }

        try {
            $this->assertUrlIsPublic($config['url']);
        } catch (\InvalidArgumentException $e) {
            return ConnectionTestResult::failure('Connection target is not allowed: '.$e->getMessage());
        }

        $startTime = microtime(true);

        try {
            $options = [
                'timeout' => $config['timeout'] ?? 30,
                'headers' => [
                    'User-Agent' => $config['user_agent'] ?? 'SupplierAutomation/1.0',
                ],
            ];

            $authConfig = $this->resolveCredentials($authConfig);
            $options = $this->applyAuthentication($options, $authConfig);

            $response = $this->httpClient->get($config['url'], $options);
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            $statusCode = $response->getStatusCode();
            $contentLength = $response->getBody()->getSize();

            if ($statusCode >= 200 && $statusCode < 300) {
                return ConnectionTestResult::success(
                    "Connection successful (HTTP {$statusCode})",
                    $responseTime,
                    [
                        'status_code' => $statusCode,
                        'content_length' => $contentLength,
                        'headers' => $response->getHeaders(),
                    ]
                );
            }

            return ConnectionTestResult::failure(
                "HTTP error: {$statusCode}",
                [
                    'status_code' => $statusCode,
                    'response_time' => $responseTime,
                ]
            );
        } catch (GuzzleException $e) {
            return ConnectionTestResult::failure(
                'HTTP request failed: '.$e->getMessage(),
                ['exception' => get_class($e)]
            );
        }
    }

    /**
     * Test FTP/SFTP connection
     */
    public function testFtpConnection(array $config, array $authConfig): ConnectionTestResult
    {
        $requiredFields = ['host', 'username', 'password'];
        foreach ($requiredFields as $field) {
            if (empty($config[$field]) && empty($authConfig[$field])) {
                return ConnectionTestResult::failure("Missing required field: {$field}");
            }
        }

        try {
            $this->assertHostIsPublic((string) $config['host']);
        } catch (\InvalidArgumentException $e) {
            return ConnectionTestResult::failure('Connection target is not allowed: '.$e->getMessage());
        }

        $startTime = microtime(true);

        try {
            $authConfig = $this->resolveCredentials($authConfig);

            $isSftp = $config['ssl'] ?? false;

            if ($isSftp) {
                $filesystem = $this->createSftpFilesystem($config, $authConfig);
            } else {
                $filesystem = $this->createFtpFilesystem($config, $authConfig);
            }

            $files = $filesystem->listContents('/')->toArray();
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            return ConnectionTestResult::success(
                ($isSftp ? 'SFTP' : 'FTP').' connection successful',
                $responseTime,
                [
                    'protocol' => $isSftp ? 'sftp' : 'ftp',
                    'files_found' => count($files),
                    'root_directory' => $config['root'] ?? '/',
                ]
            );
        } catch (Exception $e) {
            return ConnectionTestResult::failure(
                'FTP connection failed: '.$e->getMessage(),
                ['exception' => get_class($e)]
            );
        }
    }

    /**
     * Test API connection
     */
    public function testApiConnection(array $config, array $authConfig): ConnectionTestResult
    {
        if (empty($config['base_url'])) {
            return ConnectionTestResult::failure('Base URL is required for API connection');
        }

        try {
            $this->assertUrlIsPublic($config['base_url']);
        } catch (\InvalidArgumentException $e) {
            return ConnectionTestResult::failure('Connection target is not allowed: '.$e->getMessage());
        }

        $startTime = microtime(true);

        try {
            $authConfig = $this->resolveCredentials($authConfig);

            $options = [
                'timeout' => $config['timeout'] ?? 30,
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'SupplierAutomation/1.0',
                ],
            ];

            $options = $this->applyAuthentication($options, $authConfig);

            $healthEndpoint = $config['health_endpoint'] ?? '/';
            $url = rtrim($config['base_url'], '/').'/'.$healthEndpoint;

            $response = $this->httpClient->get($url, $options);
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $body = json_decode($response->getBody()->getContents(), true);

                return ConnectionTestResult::success(
                    "API connection successful (HTTP {$statusCode})",
                    $responseTime,
                    [
                        'status_code' => $statusCode,
                        'api_version' => $config['version'] ?? 'unknown',
                        'response_data' => $body,
                    ]
                );
            }

            return ConnectionTestResult::failure(
                "API error: {$statusCode}",
                ['status_code' => $statusCode, 'response_time' => $responseTime]
            );
        } catch (GuzzleException $e) {
            return ConnectionTestResult::failure(
                'API request failed: '.$e->getMessage(),
                ['exception' => get_class($e)]
            );
        }
    }

    /**
     * Resolve credential references
     */
    public function resolveCredential(string $reference): string
    {
        if (! str_starts_with($reference, 'credential:')) {
            return $reference;
        }

        $credentialId = str_replace('credential:', '', $reference);

        $credential = SupplierCredential::where('uid', $credentialId)
            ->orWhere('id', $credentialId)
            ->valid()
            ->first();

        if (! $credential) {
            throw new Exception("Credential not found or invalid: {$credentialId}");
        }

        $credential->markAsUsed();

        return json_encode($credential->getCredentialsArray());
    }

    /**
     * Resolve all credential references in config
     */
    protected function resolveCredentials(array $config): array
    {
        array_walk_recursive($config, function (&$value) {
            if (is_string($value) && str_starts_with($value, 'credential:')) {
                $resolved = $this->resolveCredential($value);
                $decoded = json_decode($resolved, true);
                $value = $decoded ?? $resolved;
            }
        });

        return $config;
    }

    /**
     * Store credential for source
     */
    public function storeCredential(
        Source $source,
        string $type,
        array $credentials,
        ?string $name = null,
        ?int $userId = null
    ): SupplierCredential {
        $credential = SupplierCredential::create([
            'supplier_id' => $source->supplier_id,
            'source_id' => $source->id,
            'credential_type' => $type,
            'name' => $name ?? "{$source->label} {$type}",
            'credentials' => $credentials,
            'is_valid' => true,
            'created_by' => $userId,
        ]);

        Log::info('Credential stored', [
            'source_id' => $source->id,
            'credential_type' => $type,
            'credential_id' => $credential->id,
        ]);

        return $credential;
    }

    /**
     * Rotate credential
     */
    public function rotateCredential(
        SupplierCredential $credential,
        array $newCredentials
    ): SupplierCredential {
        $credential->update([
            'credentials' => $newCredentials,
            'is_valid' => true,
            'validation_error' => null,
            'last_used_at' => null,
        ]);

        Log::info('Credential rotated', [
            'credential_id' => $credential->id,
            'credential_type' => $credential->credential_type,
        ]);

        return $credential->fresh();
    }

    /**
     * Perform health check
     */
    public function performHealthCheck(Source $source): SourceHealthHistory
    {
        $connectionResult = $this->testConnection($source);

        return $this->recordHealthCheck($source, $connectionResult);
    }

    /**
     * Update monitor status
     */
    public function updateMonitorStatus(Source $source, bool $success, ?string $error = null): void
    {
        $monitor = SourceMonitor::firstOrCreate(
            ['source_id' => $source->id],
            [
                'status' => 'unknown',
                'check_interval_minutes' => 60,
            ]
        );

        if ($success) {
            $monitor->recordSuccessfulCheck($this->getLastResponseTime($source));
        } else {
            $monitor->recordFailedCheck(0, $error ?? 'Unknown error');
        }
    }

    /**
     * Get health summary
     */
    public function getHealthSummary(Source $source): array
    {
        $monitor = SourceMonitor::where('source_id', $source->id)->first();

        if (! $monitor) {
            return [
                'status' => 'unknown',
                'uptime_percentage' => null,
                'avg_response_time_ms' => null,
                'consecutive_failures' => 0,
                'last_check_at' => null,
            ];
        }

        $recentChecks = SourceHealthHistory::where('source_id', $source->id)
            ->recent(24)
            ->get();

        return [
            'status' => $monitor->status,
            'uptime_percentage' => $monitor->uptime_percentage,
            'avg_response_time_ms' => $monitor->avg_response_time_ms,
            'consecutive_failures' => $monitor->consecutive_failures,
            'consecutive_successes' => $monitor->consecutive_successes,
            'last_successful_check_at' => $monitor->last_successful_check_at,
            'last_failed_check_at' => $monitor->last_failed_check_at,
            'recent_checks_count' => $recentChecks->count(),
            'recent_success_rate' => $recentChecks->count() > 0
                ? ($recentChecks->where('is_success', true)->count() / $recentChecks->count()) * 100
                : 0,
        ];
    }

    /**
     * Apply transformations to data
     */
    public function applyTransformations(array $data, Source $source): array
    {
        $transformations = SourceTransformation::where('source_id', $source->id)
            ->enabled()
            ->ordered()
            ->get();

        foreach ($transformations as $transformation) {
            if (! $transformation->shouldApply($data)) {
                continue;
            }

            if ($transformation->field_name) {
                if (isset($data[$transformation->field_name])) {
                    $data[$transformation->field_name] = $this->applyTransformation(
                        $data[$transformation->field_name],
                        $transformation
                    );
                }
            } else {
                foreach ($data as $key => $value) {
                    $data[$key] = $this->applyTransformation($value, $transformation);
                }
            }
        }

        return $data;
    }

    /**
     * Apply single transformation
     */
    public function applyTransformation(mixed $value, SourceTransformation $transformation): mixed
    {
        try {
            return $transformation->apply($value);
        } catch (Exception $e) {
            Log::warning('Transformation failed', [
                'transformation_id' => $transformation->id,
                'error' => $e->getMessage(),
            ]);

            return $value;
        }
    }

    /**
     * Validate connection configuration
     */
    protected function validateConnectionConfig(array $config): ValidationResult
    {
        $result = ValidationResult::success();

        if (empty($config['url']) && empty($config['host']) && empty($config['base_url'])) {
            $result->addError('Connection endpoint (url/host/base_url) is required');
        }

        if (isset($config['timeout']) && (! is_numeric($config['timeout']) || $config['timeout'] <= 0)) {
            $result->addError('Timeout must be a positive number');
        }

        if (isset($config['port']) && (! is_numeric($config['port']) || $config['port'] <= 0)) {
            $result->addError('Port must be a positive number');
        }

        return $result;
    }

    /**
     * Validate authentication configuration
     */
    protected function validateAuthenticationConfig(array $config): ValidationResult
    {
        $result = ValidationResult::success();

        if (empty($config)) {
            $result->addWarning('No authentication configured - connection may fail if authentication is required');

            return $result;
        }

        if (isset($config['username']) && empty($config['password'])) {
            $result->addError('Password is required when username is provided');
        }

        if (isset($config['client_id']) && empty($config['client_secret'])) {
            $result->addError('Client secret is required when client ID is provided');
        }

        return $result;
    }

    /**
     * Validate extraction configuration
     */
    protected function validateExtractionConfig(array $config): ValidationResult
    {
        $result = ValidationResult::success();

        if (empty($config['selectors']) && empty($config['xpath']) && empty($config['regex'])) {
            $result->addWarning('No extraction rules defined - data extraction may not work');
        }

        return $result;
    }

    /**
     * Validate schedule configuration
     */
    protected function validateScheduleConfig(array $config): ValidationResult
    {
        $result = ValidationResult::success();

        if (isset($config['cron']) && ! $this->isValidCronExpression($config['cron'])) {
            $result->addError('Invalid cron expression');
        }

        if (isset($config['interval_minutes']) && (! is_numeric($config['interval_minutes']) || $config['interval_minutes'] <= 0)) {
            $result->addError('Interval must be a positive number');
        }

        return $result;
    }

    /**
     * Validate retry configuration
     */
    protected function validateRetryConfig(array $config): ValidationResult
    {
        $result = ValidationResult::success();

        if (isset($config['max_attempts']) && (! is_numeric($config['max_attempts']) || $config['max_attempts'] < 0)) {
            $result->addError('Max attempts must be a non-negative number');
        }

        if (isset($config['backoff_multiplier']) && (! is_numeric($config['backoff_multiplier']) || $config['backoff_multiplier'] < 1)) {
            $result->addError('Backoff multiplier must be >= 1');
        }

        return $result;
    }

    /**
     * Record health check
     */
    protected function recordHealthCheck(Source $source, ConnectionTestResult $result): SourceHealthHistory
    {
        $healthRecord = SourceHealthHistory::recordCheck(
            sourceId: $source->id,
            checkType: 'connectivity',
            isSuccess: $result->success,
            statusCode: $result->getMetadata('status_code'),
            responseTimeMs: $result->responseTime,
            errorType: $result->success ? null : 'connection_error',
            errorMessage: $result->success ? null : $result->message,
            pageSizeBytes: $result->getMetadata('content_length'),
            productsFound: null
        );

        $this->updateMonitorStatus($source, $result->success, $result->success ? null : $result->message);

        return $healthRecord;
    }

    /**
     * Apply authentication to HTTP options
     */
    protected function applyAuthentication(array $options, array $authConfig): array
    {
        if (empty($authConfig)) {
            return $options;
        }

        if (isset($authConfig['username']) && isset($authConfig['password'])) {
            $options['auth'] = [$authConfig['username'], $authConfig['password']];
        }

        if (isset($authConfig['api_key'])) {
            $headerName = $authConfig['header_name'] ?? 'X-API-Key';
            $options['headers'][$headerName] = $authConfig['api_key'];
        }

        if (isset($authConfig['token'])) {
            $options['headers']['Authorization'] = 'Bearer '.$authConfig['token'];
        }

        return $options;
    }

    /**
     * Create SFTP filesystem
     */
    protected function createSftpFilesystem(array $config, array $authConfig): Filesystem
    {
        $provider = new SftpConnectionProvider(
            host: $config['host'],
            username: $authConfig['username'] ?? $config['username'],
            password: $authConfig['password'] ?? $config['password'],
            privateKey: $authConfig['private_key'] ?? null,
            passphrase: $authConfig['passphrase'] ?? null,
            port: $config['port'] ?? 22,
            useAgent: false,
            timeout: $config['timeout'] ?? 10,
        );

        $adapter = new SftpAdapter($provider, $config['root'] ?? '/');

        return new Filesystem($adapter);
    }

    /**
     * Create FTP filesystem
     */
    protected function createFtpFilesystem(array $config, array $authConfig): Filesystem
    {
        $options = FtpConnectionOptions::fromArray([
            'host' => $config['host'],
            'port' => $config['port'] ?? 21,
            'username' => $authConfig['username'] ?? $config['username'],
            'password' => $authConfig['password'] ?? $config['password'],
            'root' => $config['root'] ?? '/',
            'passive' => $config['passive'] ?? true,
            'ssl' => $config['ssl'] ?? false,
            'timeout' => $config['timeout'] ?? 90,
        ]);

        $adapter = new FtpAdapter($options);

        return new Filesystem($adapter);
    }

    /**
     * Get last response time from health history
     */
    protected function getLastResponseTime(Source $source): int
    {
        $lastCheck = SourceHealthHistory::where('source_id', $source->id)
            ->whereNotNull('response_time_ms')
            ->orderBy('checked_at', 'desc')
            ->first();

        return $lastCheck?->response_time_ms ?? 0;
    }

    /**
     * Validate cron expression
     */
    protected function isValidCronExpression(string $cron): bool
    {
        $parts = explode(' ', $cron);

        if (count($parts) !== 5) {
            return false;
        }

        return true;
    }
}
