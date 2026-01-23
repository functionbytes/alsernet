# SourceConfigurationService Documentation

## Overview

The `SourceConfigurationService` is a comprehensive service for managing supplier source configurations, credentials, health monitoring, and connection testing in the Supplier Automation System.

## Features

- **Configuration Management**: Store, retrieve, and validate configurations for supplier sources
- **Template Processing**: Create configurations from reusable templates with variable substitution
- **Connection Testing**: Test connections to websites, FTP/SFTP servers, and APIs
- **Credential Management**: Securely store, retrieve, and rotate encrypted credentials
- **Health Monitoring**: Track source availability, response times, and uptime
- **Data Transformation**: Apply transformation pipelines to extracted data

## Architecture

### DTOs

#### ConnectionTestResult
```php
use App\DTOs\ConnectionTestResult;

$result = ConnectionTestResult::success(
    message: 'Connection successful',
    responseTime: 150,
    metadata: ['status_code' => 200]
);

// Check result
if ($result->isSuccessful()) {
    echo "Response time: {$result->responseTime}ms";
    echo "Status: {$result->getMetadata('status_code')}";
}
```

#### ValidationResult
```php
use App\DTOs\ValidationResult;

$result = ValidationResult::success();
$result->addWarning('Configuration may need optimization');

// Or create with errors
$result = ValidationResult::failure([
    'URL is required',
    'Timeout must be positive'
]);

if ($result->isInvalid()) {
    foreach ($result->errors as $error) {
        echo "Error: {$error}";
    }
}
```

## Usage Examples

### 1. Configuration Management

#### Set Configuration
```php
use App\Services\Supplier\SourceConfigurationService;

$service = new SourceConfigurationService();

// Set connection configuration
$config = $service->setConfiguration(
    source: $supplierSource,
    type: 'connection',
    config: [
        'url' => 'https://supplier.example.com/products',
        'timeout' => 30,
        'user_agent' => 'SupplierBot/1.0',
    ],
    userId: auth()->id()
);

// Set authentication configuration
$authConfig = $service->setConfiguration(
    source: $supplierSource,
    type: 'authentication',
    config: [
        'username' => 'api_user',
        'password' => 'credential:abc123', // Reference to stored credential
    ],
    userId: auth()->id()
);
```

#### Get Effective Configuration
```php
// Get merged configuration from all types
$effectiveConfig = $service->getEffectiveConfig($supplierSource);

// Access specific config types
$connectionConfig = $effectiveConfig['connection'];
$authConfig = $effectiveConfig['authentication'];
$extractionConfig = $effectiveConfig['extraction'];
```

#### Validate Configuration
```php
$result = $service->validateConfiguration('connection', [
    'url' => 'https://example.com',
    'timeout' => 30,
]);

if ($result->isValid()) {
    // Configuration is valid
} else {
    foreach ($result->errors as $error) {
        Log::error('Validation error: ' . $error);
    }
}
```

### 2. Template-Based Configuration

#### Create from Template
```php
// Define variables for template
$variables = [
    'api_key' => 'sk_live_abc123',
    'supplier_id' => '12345',
    'base_url' => 'https://api.supplier.com',
];

// Check for missing variables
$missingVars = $service->getMissingVariables($template, $variables);
if (!empty($missingVars)) {
    throw new Exception('Missing: ' . implode(', ', $missingVars));
}

// Create all configurations from template
$configurations = $service->createFromTemplate(
    source: $supplierSource,
    template: $template,
    variables: $variables,
    userId: auth()->id()
);
```

### 3. Connection Testing

#### Test Any Source Type
```php
// Automatically detects source type and tests appropriately
$result = $service->testConnection($supplierSource);

if ($result->isSuccessful()) {
    echo "✓ Connection successful";
    echo "Response time: {$result->responseTime}ms";
} else {
    echo "✗ Connection failed: {$result->message}";
}
```

#### Test Specific Connection Types

**Website/HTTP:**
```php
$result = $service->testWebConnection(
    config: [
        'url' => 'https://supplier.example.com',
        'timeout' => 30,
        'user_agent' => 'Bot/1.0',
    ],
    authConfig: [
        'username' => 'user',
        'password' => 'pass',
    ]
);
```

**FTP/SFTP:**
```php
$result = $service->testFtpConnection(
    config: [
        'host' => 'ftp.supplier.com',
        'port' => 21,
        'root' => '/inventaries',
        'passive' => true,
        'ssl' => false, // true for SFTP
    ],
    authConfig: [
        'username' => 'ftp_user',
        'password' => 'ftp_pass',
    ]
);
```

**API:**
```php
$result = $service->testApiConnection(
    config: [
        'base_url' => 'https://api.supplier.com',
        'health_endpoint' => '/health',
        'version' => 'v2',
        'timeout' => 20,
    ],
    authConfig: [
        'api_key' => 'abc123',
        'header_name' => 'X-API-Key',
    ]
);
```

### 4. Credential Management

#### Store Credentials
```php
// Store FTP credentials
$credential = $service->storeCredential(
    source: $supplierSource,
    type: 'ftp',
    credentials: [
        'username' => 'ftp_user',
        'password' => 'secure_password',
        'host' => 'ftp.supplier.com',
    ],
    name: 'Supplier FTP Access',
    userId: auth()->id()
);

// Store API credentials
$apiCredential = $service->storeCredential(
    source: $supplierSource,
    type: 'api_key',
    credentials: [
        'api_key' => 'sk_live_abc123',
        'api_secret' => 'secret_xyz',
    ],
    name: 'Supplier API Key'
);
```

#### Resolve Credential References
```php
// In configuration, reference credential by UID
$config = [
    'username' => 'credential:01JFABCD1234567890',
    'password' => 'credential:01JFABCD1234567891',
];

// Service automatically resolves references
$resolvedValue = $service->resolveCredential('credential:01JFABCD1234567890');
```

#### Rotate Credentials
```php
// Update credentials (e.g., when API key expires)
$rotatedCredential = $service->rotateCredential(
    credential: $existingCredential,
    newCredentials: [
        'api_key' => 'sk_live_new_key',
        'api_secret' => 'new_secret',
    ]
);
```

### 5. Health Monitoring

#### Perform Health Check
```php
// Manually trigger health check
$healthRecord = $service->performHealthCheck($supplierSource);

echo "Check type: {$healthRecord->check_type}";
echo "Success: " . ($healthRecord->is_success ? 'Yes' : 'No');
echo "Response time: {$healthRecord->response_time_ms}ms";
```

#### Get Health Summary
```php
$summary = $service->getHealthSummary($supplierSource);

echo "Status: {$summary['status']}";
echo "Uptime: {$summary['uptime_percentage']}%";
echo "Avg Response: {$summary['avg_response_time_ms']}ms";
echo "Consecutive Failures: {$summary['consecutive_failures']}";
echo "Recent Success Rate: {$summary['recent_success_rate']}%";
```

#### Update Monitor Status
```php
// After successful operation
$service->updateMonitorStatus($supplierSource, success: true);

// After failed operation
$service->updateMonitorStatus(
    $supplierSource,
    success: false,
    error: 'Connection timeout after 30s'
);
```

### 6. Data Transformations

#### Apply Transformations
```php
$rawData = [
    'product_name' => 'AMAZING PRODUCT!!!',
    'price' => '€ 99,99',
    'description' => '<p>Great product</p>',
];

// Apply all enabled transformations for the source
$transformedData = $service->applyTransformations($rawData, $supplierSource);

// Result:
// [
//     'product_name' => 'Amazing Product',
//     'price' => 99.99,
//     'description' => 'Great product',
// ]
```

#### Apply Single Transformation
```php
$transformation = SupplierSourceTransformation::find(1);

$value = '€ 99,99';
$result = $service->applyTransformation($value, $transformation);
// Result: 99.99
```

## Configuration Schemas

### Connection Configuration

**Website:**
```php
[
    'url' => 'https://supplier.com/products',
    'timeout' => 30,
    'user_agent' => 'Bot/1.0',
    'verify_ssl' => true,
]
```

**FTP/SFTP:**
```php
[
    'host' => 'ftp.supplier.com',
    'port' => 21, // or 22 for SFTP
    'root' => '/inventaries',
    'passive' => true,
    'ssl' => false, // true for SFTP
    'timeout' => 90,
]
```

**API:**
```php
[
    'base_url' => 'https://api.supplier.com',
    'version' => 'v2',
    'timeout' => 30,
    'retry_attempts' => 3,
    'health_endpoint' => '/health',
]
```

### Authentication Configuration

**Basic Auth:**
```php
[
    'username' => 'user',
    'password' => 'pass', // or 'credential:uid'
]
```

**API Key:**
```php
[
    'api_key' => 'abc123',
    'header_name' => 'X-API-Key',
]
```

**Bearer Token:**
```php
[
    'token' => 'bearer_token_here',
]
```

**OAuth:**
```php
[
    'client_id' => 'client_id',
    'client_secret' => 'credential:uid',
    'token_url' => 'https://auth.supplier.com/token',
    'scope' => 'read write',
]
```

### Extraction Configuration

```php
[
    'selectors' => [
        'product_name' => '.product-title',
        'price' => '.price',
        'image' => 'img.product-image@src',
    ],
    'xpath' => [
        'description' => '//div[@class="description"]/text()',
    ],
    'regex' => [
        'sku' => '/SKU:\s*([A-Z0-9]+)/',
    ],
]
```

### Schedule Configuration

```php
[
    'enabled' => true,
    'cron' => '0 */6 * * *', // Every 6 hours
    'timezone' => 'UTC',
    'interval_minutes' => null, // Alternative to cron
]
```

### Retry Configuration

```php
[
    'max_attempts' => 3,
    'initial_delay_seconds' => 5,
    'backoff_multiplier' => 2,
    'max_delay_seconds' => 60,
    'retry_on_status_codes' => [408, 429, 500, 502, 503, 504],
]
```

## Error Handling

```php
use App\Services\Supplier\SourceConfigurationService;
use Exception;

try {
    $result = $service->testConnection($source);

    if ($result->isFailed()) {
        // Handle connection failure
        Log::warning('Connection failed', [
            'source_id' => $source->id,
            'message' => $result->message,
            'metadata' => $result->metadata,
        ]);

        // Send alert if needed
        if ($monitor->shouldSendAlert()) {
            // Send notification
        }
    }
} catch (Exception $e) {
    Log::error('Service error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}
```

## Best Practices

1. **Use Templates**: Create reusable templates for common supplier types
2. **Store Credentials Separately**: Never hardcode credentials in configurations
3. **Reference Credentials**: Use `credential:uid` format in configs
4. **Monitor Health**: Regularly check source health and set up alerts
5. **Validate Before Saving**: Always validate configurations before storing
6. **Use Transformations**: Define transformations for data normalization
7. **Test Connections**: Test connections before enabling automatic extraction
8. **Log Everything**: Use structured logging for debugging
9. **Handle Failures Gracefully**: Implement retry logic and fallbacks
10. **Rotate Credentials**: Regularly rotate API keys and passwords

## Integration Example

### Complete Supplier Setup Workflow

```php
use App\Services\Supplier\SourceConfigurationService;
use App\Models\Supplier\SupplierSource;
use App\Models\Supplier\SupplierSourceTemplate;

class SupplierSetupController extends Controller
{
    public function __construct(
        protected SourceConfigurationService $configService
    ) {}

    public function setupSupplierSource(Request $request)
    {
        // 1. Create source
        $source = SupplierSource::create([
            'supplier_id' => $request->supplier_id,
            'source_type' => 'api',
            'label' => 'Main API',
            'trust_level' => 'high',
            'is_active' => false, // Start inactive
        ]);

        // 2. Store credentials
        $credential = $this->configService->storeCredential(
            source: $source,
            type: 'api_key',
            credentials: [
                'api_key' => $request->api_key,
                'api_secret' => $request->api_secret,
            ],
            name: 'API Credentials',
            userId: auth()->id()
        );

        // 3. Set connection config
        $this->configService->setConfiguration(
            source: $source,
            type: 'connection',
            config: [
                'base_url' => $request->base_url,
                'timeout' => 30,
            ],
            userId: auth()->id()
        );

        // 4. Set auth config with credential reference
        $this->configService->setConfiguration(
            source: $source,
            type: 'authentication',
            config: [
                'api_key' => "credential:{$credential->uid}",
                'header_name' => 'X-API-Key',
            ],
            userId: auth()->id()
        );

        // 5. Test connection
        $testResult = $this->configService->testConnection($source);

        if ($testResult->isSuccessful()) {
            // 6. Activate source
            $source->update(['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Source configured and tested successfully',
                'response_time' => $testResult->responseTime,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Connection test failed: ' . $testResult->message,
        ], 422);
    }
}
```

## Testing

```php
use App\Services\Supplier\SourceConfigurationService;
use Tests\TestCase;

class SourceConfigurationServiceTest extends TestCase
{
    protected SourceConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SourceConfigurationService();
    }

    public function test_validates_connection_config()
    {
        $result = $this->service->validateConfiguration('connection', [
            'url' => 'https://example.com',
            'timeout' => 30,
        ]);

        $this->assertTrue($result->isValid());
    }

    public function test_connection_test_succeeds()
    {
        $source = SupplierSource::factory()->create([
            'source_type' => 'website',
        ]);

        $this->service->setConfiguration($source, 'connection', [
            'url' => 'https://httpbin.org/status/200',
            'timeout' => 10,
        ]);

        $result = $this->service->testConnection($source);

        $this->assertTrue($result->isSuccessful());
        $this->assertNotNull($result->responseTime);
    }
}
```
