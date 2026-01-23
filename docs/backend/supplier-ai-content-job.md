# AI Content Generation Job

## Overview

The `GenerateAiContentJob` is a queued job responsible for generating AI-powered product content from supplier extraction results. It integrates with OpenAI and Anthropic APIs to create high-quality product descriptions, SEO content, and metadata.

**Location:** `/app/Jobs/Supplier/GenerateAiContentJob.php`

## Features

- **Single & Batch Processing**: Process one product or multiple products in a single job
- **AI API Rate Limiting**: Configurable rate limits per minute/hour/day to prevent API throttling
- **Budget Management**: Enforces daily and monthly budget limits for AI API costs
- **Cost Tracking**: Automatically tracks token usage and costs in `SupplierAiCost` model
- **Metrics Logging**: Records generation time, token counts, model used, and quality metrics
- **Automatic Retry**: 3 retry attempts with exponential backoff for transient failures
- **Status Management**: Automatically updates `SupplierAiContent` status throughout the process
- **Error Recovery**: Graceful error handling with detailed logging

## Usage

### Single Product Generation

```php
use App\Jobs\Supplier\GenerateAiContentJob;
use App\Models\Supplier\SupplierExtractionResult;

// Dispatch with a single result model
$result = SupplierExtractionResult::find(1);
GenerateAiContentJob::dispatch($result);

// Or with just the ID
GenerateAiContentJob::dispatch(1);

// With specific prompt
GenerateAiContentJob::dispatch($result, promptId: 5);

// With custom options
GenerateAiContentJob::dispatch($result, options: [
    'model' => 'gpt-4o',
    'temperature' => 0.8,
]);
```

### Batch Processing

```php
// Dispatch with multiple results
$results = SupplierExtractionResult::where('status', 'new')->get();
GenerateAiContentJob::dispatch($results);

// With array of IDs
$ids = [1, 2, 3, 4, 5];
GenerateAiContentJob::dispatch($ids);

// With custom batch ID for tracking
GenerateAiContentJob::dispatch($results, options: [
    'batch_id' => 'import_2024_01_15'
]);
```

### Delayed Execution

```php
// Delay for 5 minutes
GenerateAiContentJob::dispatch($result)->delay(now()->addMinutes(5));

// Schedule for specific time
GenerateAiContentJob::dispatch($result)->delay(now()->tomorrow()->setTime(2, 0));
```

### Queue Assignment

```php
// Dispatch to specific queue
GenerateAiContentJob::dispatch($result)->onQueue('high-priority');

// Chain with other jobs
GenerateAiContentJob::dispatch($result)
    ->chain([
        new ValidateContentJob($contentId),
        new PublishContentJob($contentId),
    ]);
```

## Configuration

Add to `config/supplier.php`:

```php
return [
    'queues' => [
        'ai_generation' => env('QUEUE_AI_GENERATION', 'ai-generation'),
    ],

    'ai' => [
        // Rate limits for AI API calls
        'rate_limits' => [
            'minute' => env('AI_RATE_LIMIT_MINUTE', 10),
            'hour' => env('AI_RATE_LIMIT_HOUR', 100),
            'day' => env('AI_RATE_LIMIT_DAY', 1000),
        ],

        // Budget limits in USD
        'daily_budget_limit' => env('AI_DAILY_BUDGET', 100.00),
        'monthly_budget_limit' => env('AI_MONTHLY_BUDGET', 2000.00),

        // Default AI model
        'default_model' => env('AI_DEFAULT_MODEL', 'gpt-4o-mini'),
    ],
];
```

Environment variables in `.env`:

```env
# Queue for AI generation jobs
QUEUE_AI_GENERATION=ai-generation

# Rate limiting
AI_RATE_LIMIT_MINUTE=10
AI_RATE_LIMIT_HOUR=100
AI_RATE_LIMIT_DAY=1000

# Budget limits
AI_DAILY_BUDGET=100.00
AI_MONTHLY_BUDGET=2000.00

# AI Model
AI_DEFAULT_MODEL=gpt-4o-mini
```

## Job Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `tries` | int | 3 | Number of retry attempts |
| `timeout` | int | 300/600 | Timeout in seconds (600 for batch) |
| `maxExceptions` | int | 3 | Max exceptions before failing |
| `failOnTimeout` | bool | true | Fail job on timeout |

## Process Flow

```
1. Job Dispatched
   ↓
2. Rate Limit Check
   ↓ (if exceeded)
   └→ Release job for 60 seconds OR Skip in batch
   ↓ (if ok)
3. Budget Limit Check
   ↓ (if exceeded)
   └→ Stop processing & log error
   ↓ (if ok)
4. For each extraction result:
   ├→ Create SupplierAiContent record (status: generating)
   ├→ Call ContentGenerationService
   ├→ Track AI API cost
   ├→ Update content with AI response
   ├→ Log generation metrics
   └→ Update status (pending_validation)
   ↓
5. Record batch metrics
   ↓
6. Complete
```

## Error Handling

### Rate Limiting

When rate limits are exceeded:

**Single Result:**
- Job is released back to queue for 60 seconds
- Retries automatically after delay

**Batch Processing:**
- Item is skipped
- Processing continues with next item
- Skipped items logged in metrics

### Budget Exceeded

- Processing stops immediately
- Remaining items are not processed
- Error logged with budget details
- Job completes with partial results

### Generation Failures

**Transient Errors** (network, timeout, 429, 503):
- Automatic retry with exponential backoff
- Wait time: 60s × attempt number
- Up to 3 attempts

**Permanent Errors** (invalid data, API key):
- Job fails immediately
- Content status set to error
- Detailed error logged

## Monitoring

### Logs

```php
// Job started
Log::info('AI content generation job started', [
    'batch_id' => 'ai_gen_01HQXYZ...',
    'result_count' => 10,
    'is_batch' => true,
]);

// Individual success
Log::info('AI content generated successfully', [
    'content_id' => 123,
    'cost' => 0.05,
    'tokens' => 300,
    'latency_ms' => 1500,
]);

// Rate limit hit
Log::warning('Rate limit exceeded, delaying generation', [
    'supplier_id' => 5,
    'window' => 'minute',
]);

// Job completed
Log::info('AI content generation job completed', [
    'metrics' => [
        'total' => 10,
        'successful' => 8,
        'failed' => 2,
        'total_cost' => 0.40,
        'avg_latency_ms' => 1750,
    ],
]);
```

### Metrics Table

Batch metrics are recorded in `supplier_ai_batch_metrics`:

```sql
SELECT
    batch_id,
    successful,
    failed,
    rate_limited,
    total_cost,
    avg_latency_ms,
    duration_seconds
FROM supplier_ai_batch_metrics
WHERE created_at >= NOW() - INTERVAL '24 hours'
ORDER BY created_at DESC;
```

### Laravel Horizon

Monitor jobs in Horizon dashboard:

- Queue: `ai-generation`
- Tags: `supplier:ai-generation`, `batch:{batch_id}`, `count:{n}`
- Metrics: throughput, runtime, failures

## Cost Tracking

Every AI API call is tracked in `supplier_ai_costs`:

```php
use App\Models\Supplier\SupplierAiCost;

// Get today's costs
$dailyCost = SupplierAiCost::getTotalCostForPeriod(
    today()->startOfDay(),
    today()->endOfDay()
);

// Get costs by model
$modelCosts = SupplierAiCost::getCostsByModel(
    now()->startOfMonth(),
    now()->endOfMonth()
);

// Check budget status
$isExceeded = SupplierAiCost::isDailyBudgetExceeded(100.00);
```

## Performance Optimization

### Queue Workers

Run dedicated workers for AI generation:

```bash
# Start worker for AI generation queue
php artisan queue:work --queue=ai-generation --sleep=3 --tries=3

# Multiple workers for parallel processing
php artisan queue:work --queue=ai-generation --sleep=3 --tries=3 &
php artisan queue:work --queue=ai-generation --sleep=3 --tries=3 &
php artisan queue:work --queue=ai-generation --sleep=3 --tries=3 &
```

### Batch Size Recommendations

| Scenario | Batch Size | Timeout | Workers |
|----------|------------|---------|---------|
| Real-time | 1-5 | 300s | 2-3 |
| Background | 10-50 | 600s | 3-5 |
| Bulk Import | 50-100 | 900s | 5-10 |

### Rate Limit Tuning

Adjust based on your AI API tier:

**OpenAI GPT-4o-mini (Tier 1):**
```php
'rate_limits' => [
    'minute' => 30,
    'hour' => 500,
    'day' => 10000,
],
```

**OpenAI GPT-4o (Tier 2):**
```php
'rate_limits' => [
    'minute' => 60,
    'hour' => 2000,
    'day' => 40000,
],
```

## Testing

Run the test suite:

```bash
# All tests
php artisan test --filter=GenerateAiContentJobTest

# Specific test
php artisan test --filter=test_job_processes_single_result_successfully

# With coverage
php artisan test --filter=GenerateAiContentJobTest --coverage
```

## Troubleshooting

### Job Stuck in Queue

**Symptom:** Jobs not processing

**Solutions:**
1. Check worker is running: `php artisan queue:work`
2. Verify queue name matches configuration
3. Check for rate limiting: look for "Rate limit exceeded" logs
4. Inspect failed jobs: `php artisan queue:failed`

### High API Costs

**Symptom:** Budget exceeded frequently

**Solutions:**
1. Review rate limits - reduce API calls
2. Use cheaper model (gpt-4o-mini instead of gpt-4o)
3. Reduce max_tokens in generation options
4. Implement caching for similar products
5. Monitor `supplier_ai_costs` table for patterns

### Slow Processing

**Symptom:** Jobs taking too long

**Solutions:**
1. Increase worker count
2. Reduce batch size
3. Check AI API latency in metrics
4. Consider using faster model
5. Process during off-peak hours

### Generation Failures

**Symptom:** Content status stuck in "generating"

**Solutions:**
1. Check AI API credentials are valid
2. Review error logs for specific errors
3. Verify extraction result has required fields
4. Check prompt template is valid
5. Ensure budget limits not exceeded

## Related Documentation

- [Content Generation Service](/docs/backend/supplier-content-generation-service.md)
- [AI Cost Tracking](/docs/backend/supplier-ai-cost-tracking.md)
- [Rate Limiting](/docs/backend/supplier-rate-limiting.md)
- [Queue Configuration](/docs/backend/queue-configuration.md)
