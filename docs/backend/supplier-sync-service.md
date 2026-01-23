# Supplier SyncService Documentation

## Overview

The `SyncService` class (`/Users/functionbytes/Function/Coding/manager/app/Services/Supplier/SyncService.php`) is a comprehensive service for synchronizing AI-generated supplier content to PrestaShop and ERP systems.

## Features

### 1. PrestaShop Synchronization

#### Single Content Sync
```php
use App\Services\Supplier\SyncService;
use App\Models\Supplier\SupplierAiContent;

$syncService = new SyncService();
$content = SupplierAiContent::find(1);

$success = $syncService->syncToPrestaShop($content);
```

**Features:**
- Validates content before sync
- Supports both Web Service API and direct database connection
- Automatic image synchronization
- Transaction-safe operations
- Comprehensive logging and event dispatching
- Automatic retry with exponential backoff

#### Batch Synchronization
```php
$results = $syncService->syncBatchToPrestaShop([1, 2, 3, 4, 5]);

// Return:
[
    'total' => 5,
    'success' => [1, 2, 3],
    'failed' => [
        ['id' => 4, 'error' => 'Validation failed']
    ],
    'skipped' => [
        ['id' => 5, 'reason' => 'Content not validated']
    ]
]
```

#### Update Existing Product
```php
$syncService->updatePrestaShopProduct(123, [
    'name' => 'Updated Product Name',
    'description' => 'New description',
    'price' => 99.99
]);
```

### 2. Content Publishing

#### Publish Single Content
```php
$syncService->publishContent($content);
// Marks as published and auto-syncs if enabled
```

#### Publish Batch
```php
$results = $syncService->publishBatch([1, 2, 3]);
```

#### Schedule Publication
```php
use Carbon\Carbon;

$syncService->schedulePublication($content, Carbon::parse('2025-12-25 10:00:00'));
```

### 3. ERP Integration

#### Sync to ERP
```php
$success = $syncService->syncToErp($content);
```

#### Map to ERP Format
```php
$erpData = $syncService->mapContentToErpFormat($content);

// Return:
[
    'reference' => 'REF-12345',
    'model_id' => 'MODEL-789',
    'ean' => '1234567890123',
    'name' => 'Product Name',
    'short_description' => '...',
    'long_description' => '...',
    'supplier_id' => 42,
    'source_attributes' => [...],
    'metadata' => [
        'ai_generated' => true,
        'validated_at' => '2025-12-20T10:30:00Z',
        'prompt_id' => 5
    ]
]
```

### 4. Image Synchronization

#### Sync All Images for Content
```php
$results = $syncService->syncImages($content);

// Return:
[
    'success' => [
        [
            'url' => 'https://example.com/image1.jpg',
            'local_path' => 'suppliers/1/images/...',
            'product_id' => 123
        ]
    ],
    'failed' => [
        [
            'url' => 'https://example.com/image2.jpg',
            'error' => 'Download timeout'
        ]
    ]
]
```

#### Download Single Image
```php
$success = $syncService->downloadImage(
    'https://example.com/product.jpg',
    'suppliers/1/images/product_1.jpg'
);
```

#### Upload to PrestaShop
```php
$success = $syncService->uploadToPrestaShop(123, 'suppliers/1/images/product_1.jpg');
```

**Image Processing Features:**
- Automatic download with retry
- Resize images larger than 1200px
- Quality optimization (85% JPEG quality)
- Support for multiple image formats
- Fallback to original if processing fails

### 5. Conflict Detection & Resolution

#### Detect Conflicts
```php
$conflicts = $syncService->detectConflicts($content);

// Return array of conflicts:
[
    [
        'type' => 'name_mismatch',
        'field' => 'name',
        'current' => 'Old Product Name',
        'new' => 'New Product Name'
    ],
    [
        'type' => 'duplicate_ean',
        'field' => 'ean',
        'existing_product_id' => 456,
        'ean' => '1234567890123'
    ]
]
```

#### Resolve Conflicts
```php
// Strategy: Overwrite existing data
$syncService->resolveConflict($content, 'overwrite');

// Strategy: Merge with existing (keeps name, updates descriptions)
$syncService->resolveConflict($content, 'merge');

// Strategy: Skip sync
$syncService->resolveConflict($content, 'skip');

// Strategy: Create new product
$syncService->resolveConflict($content, 'create_new');
```

**Available Strategies:**
- `overwrite` - Replace all existing data
- `merge` - Intelligently merge (keeps name, updates descriptions/SEO)
- `skip` - Skip sync and log conflict
- `create_new` - Create new product instead of updating

### 6. Rollback & History

#### Rollback Publication
```php
$syncService->rollbackPublication($content);
// Reverts to previous state or removes from PrestaShop
```

#### Get Publication History
```php
$history = $syncService->getPublicationHistory($content);

// Return:
[
    [
        'action' => 'published',
        'user' => 'John Doe',
        'timestamp' => Carbon instance,
        'details' => [...]
    ],
    [
        'action' => 'publication_rolled_back',
        'user' => 'System',
        'timestamp' => Carbon instance,
        'details' => [...]
    ]
]
```

### 7. Status Tracking & Statistics

#### Update Sync Status
```php
$syncService->updateSyncStatus($content, 'success');
$syncService->updateSyncStatus($content, 'failed', 'Connection timeout');
```

**Available Statuses:**
- `pending` - Waiting to sync
- `in_progress` - Currently syncing
- `success` - Successfully synced
- `failed` - Sync failed
- `partial` - Partially synced (e.g., some images failed)

#### Get Sync Statistics
```php
use Carbon\Carbon;

$stats = $syncService->getSyncStatistics(
    $supplierId = 1,
    $from = Carbon::now()->subDays(30),
    $to = Carbon::now()
);

// Return:
[
    'total_content' => 150,
    'published' => 120,
    'synced_to_prestashop' => 115,
    'synced_to_erp' => 110,
    'publish_rate' => 80.00,      // Percentage
    'sync_rate' => 95.83,          // Percentage
    'avg_sync_time_seconds' => 45  // Average time from publish to sync
]
```

## Configuration

### PrestaShop Configuration

Required config keys in `config/prestashop.php`:

```php
return [
    'enabled' => true,
    'url' => env('PRESTASHOP_URL', 'https://www.a-alvarez.com'),
    'api_key' => env('PRESTASHOP_API_KEY', ''),
    'timeout' => 30,
    'sync_enabled' => false,
    'sync_products' => true,
];
```

### ERP Configuration

Required config keys in `config/erp.php` (create if needed):

```php
return [
    'api_url' => env('ERP_API_URL', 'https://erp.example.com/api'),
    'api_key' => env('ERP_API_KEY', ''),
];
```

## Events Dispatched

The service dispatches the following events:

1. **supplier.content.synced**
   - When: After successful PrestaShop sync
   - Data: `['content' => $content, 'platform' => 'prestashop']`

2. **supplier.content.published**
   - When: After content is published
   - Data: `['content' => $content]`

3. **supplier.content.scheduled**
   - When: Publication is scheduled
   - Data: `['content' => $content, 'scheduled_at' => $scheduledAt]`

4. **supplier.content.synced_to_erp**
   - When: After successful ERP sync
   - Data: `['content' => $content, 'erp_reference' => $reference]`

5. **supplier.content.rollback**
   - When: Publication is rolled back
   - Data: `['content' => $content]`

## Error Handling

### Automatic Retry
- All HTTP requests retry up to 3 times with 5-second delays
- Exponential backoff for failed operations
- Graceful degradation (e.g., returns original image if processing fails)

### Transaction Safety
- All database operations wrapped in transactions
- Automatic rollback on failure
- Consistent state guaranteed

### Logging
All operations are logged with full context:

```php
Log::error('PrestaShop sync failed', [
    'content_id' => $content->id,
    'exception' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

### Exception Handling
- All public methods catch and handle exceptions
- Returns boolean for success/failure
- Never throws exceptions to calling code
- Detailed error messages stored in content model

## Database Requirements

The service expects the following columns on `supplier_ai_contents` table:

```sql
- synced_to_prestashop_at (timestamp, nullable)
- synced_to_erp_at (timestamp, nullable)
- sync_status (string, nullable)
- sync_error (text, nullable)
- last_sync_attempt (timestamp, nullable)
- scheduled_publish_at (timestamp, nullable)
```

## Usage Examples

### Complete Workflow Example

```php
use App\Services\Supplier\SyncService;
use App\Models\Supplier\SupplierAiContent;

$syncService = new SyncService();
$content = SupplierAiContent::find(1);

// 1. Validate content
if (!$content->isValidated()) {
    $content->validate();
}

// 2. Check for conflicts
$conflicts = $syncService->detectConflicts($content);

if (!empty($conflicts)) {
    // Resolve using merge strategy
    $syncService->resolveConflict($content, 'merge');
} else {
    // 3. Publish content
    $syncService->publishContent($content);
}

// 4. Get sync statistics
$stats = $syncService->getSyncStatistics($content->supplier_id);

// 5. If needed, rollback
if ($needsRollback) {
    $syncService->rollbackPublication($content);
}
```

### Batch Processing Example

```php
$contentIds = SupplierAiContent::where('supplier_id', 1)
    ->validated()
    ->pluck('id')
    ->toArray();

// Publish all validated content
$publishResults = $syncService->publishBatch($contentIds);

// Then sync to PrestaShop
$syncResults = $syncService->syncBatchToPrestaShop($publishResults['published']);

Log::info('Batch processing completed', [
    'published' => count($publishResults['published']),
    'synced' => count($syncResults['success']),
    'failed' => count($syncResults['failed']),
]);
```

## Performance Considerations

1. **Batch Operations**: Use batch methods for processing multiple items
2. **Image Processing**: Large images are automatically resized
3. **HTTP Timeouts**: Configurable timeouts prevent hanging requests
4. **Database Queries**: Efficient use of eager loading and cloning
5. **Transaction Safety**: Minimal transaction scope for better concurrency

## Dependencies

- `intervention/image` - Image processing (GD driver)
- `guzzlehttp/guzzle` - HTTP client (via Laravel Http facade)
- PrestaShop database connection configured as `prestashop`
- Laravel events system
- Laravel queue system (for async processing - recommended)

## Testing

Create a test file at `/Users/functionbytes/Function/Coding/manager/tests/Feature/Services/Supplier/SyncServiceTest.php`:

```php
<?php

namespace Tests\Feature\Services\Supplier;

use App\Models\Supplier\SupplierAiContent;
use App\Services\Supplier\SyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SyncService $syncService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncService = new SyncService();
    }

    public function test_can_sync_content_to_prestashop(): void
    {
        $content = SupplierAiContent::factory()->validated()->create();

        $result = $this->syncService->syncToPrestaShop($content);

        $this->assertTrue($result);
        $this->assertNotNull($content->fresh()->synced_to_prestashop_at);
    }

    public function test_detects_conflicts(): void
    {
        $content = SupplierAiContent::factory()->create([
            'product_id' => 123,
            'ean' => '1234567890123',
        ]);

        $conflicts = $this->syncService->detectConflicts($content);

        $this->assertIsArray($conflicts);
    }

    // Add more tests...
}
```

## Future Enhancements

1. **Queue Support**: Make all sync operations async via queues
2. **Webhook Integration**: Real-time updates from PrestaShop
3. **Bulk Image Optimization**: Parallel image processing
4. **Advanced Conflict Resolution**: AI-powered merge strategies
5. **Multi-platform Support**: Extend to other e-commerce platforms
6. **Performance Metrics**: Detailed performance tracking and analytics

## Troubleshooting

### Images Not Uploading
- Check `Storage` disk configuration
- Verify PrestaShop API permissions for image uploads
- Check file size limits in PHP configuration

### Sync Failures
- Verify PrestaShop database connection (if using direct DB)
- Check API key validity (if using Web Service)
- Review Laravel logs for detailed error messages

### Performance Issues
- Consider using queue workers for batch operations
- Optimize image sizes before upload
- Use database connection pooling for PrestaShop

## Support

For issues or questions, check:
1. Laravel logs: `storage/logs/laravel.log`
2. Content logs: `$content->logs()->recent()->get()`
3. Database connection: Test with `php artisan manager.settings.prestashop.checkConnection`
