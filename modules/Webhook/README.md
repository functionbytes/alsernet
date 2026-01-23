# Webhook Module Documentation

## Overview

The Webhook module is a comprehensive, production-ready system for managing webhook integrations, event subscriptions, and secure delivery mechanisms. It provides a robust framework for:

- **Event-Driven Architecture**: Trigger webhooks based on application events (order.created, order.updated, etc.)
- **Multi-Integration Support**: Manage multiple webhook endpoints per integration
- **Secure Delivery**: HMAC signatures, API key authentication, and multiple auth types
- **Reliable Processing**: Automatic retry logic with exponential backoff and dead letter queue handling
- **Payload Transformation**: Rule-based filtering and payload mapping
- **Comprehensive Logging**: Full audit trail of events and deliveries

### Module Statistics

| Component | Count |
|-----------|-------|
| Eloquent Models | 8 |
| Database Tables | 9 |
| Job Classes | 3 |
| Service Classes | 3+ |
| API Endpoints | RESTful |
| Manager Routes | CRUD |

---

## Features

### 1. Webhook Integrations

Create and manage webhook integrations with flexible configuration:

- **Unique Integration IDs**: ULID-based unique identifiers
- **Plan-Based Limits**: Free, Pro, and Enterprise plans with configurable daily limits
- **Status Management**: Active, suspended, or disabled integrations
- **IP Whitelisting**: Optional IP and domain restrictions
- **Rate Limiting**: Per-integration rate limiting (configurable per minute)

### 2. Event Subscriptions

Subscribe to application events with granular control:

- **Event Filtering**: Subscribe to specific events (e.g., "order.created", "order.updated")
- **Multiple Endpoints**: Multiple subscriptions per integration
- **Custom URLs**: Direct webhook payloads to different endpoints
- **Event Versioning**: Support for versioned events (v1, v2, etc.)

### 3. Delivery Mechanisms

Flexible delivery configuration for each subscription:

- **Multiple Auth Types**:
  - None (no authentication)
  - Bearer Token
  - Basic Authentication (username:password)
  - API Key Header
  - Custom Headers

- **Timeout Configuration**: Per-subscription timeout (milliseconds)
- **Custom Headers**: Add arbitrary headers to webhook requests
- **Retry Strategy**: Configurable backoff policies per subscription

### 4. Security Features

Enterprise-grade security built-in:

- **HMAC-SHA256 Signatures**: Cryptographically signed payloads
- **API Key Management**: Rotate keys with unique secrets
- **Signature Verification**: Validate incoming webhook payloads
- **Permission-Based API Keys**: Granular permissions (inbound, outbound, admin)
- **Key Revocation**: Instantly revoke compromised keys
- **Rate Limiting**: Per-API-key rate limits with configurable thresholds

### 5. Reliability & Resilience

Production-ready failure handling:

- **Automatic Retries**: 3+ configurable retry attempts
- **Exponential Backoff**: Configurable backoff policies (1m, 5m, 15m, 1h, 6h, 24h)
- **Dead Letter Queue**: Failed deliveries automatically marked as "dead"
- **Idempotency Keys**: Prevent duplicate processing
- **Event Deduplication**: Content hash-based duplicate detection
- **Delivery Status Tracking**: Detailed status and error logging
- **Next Retry Scheduling**: Smart scheduling with timestamps

---

## Architecture

### Multi-Tenant Design

The Webhook module is fully designed to support multi-tenancy:

```
Integration (Company A)
  ├── API Keys
  ├── Webhook Events
  ├── Subscriptions
  │   ├── Subscription 1 (endpoint A)
  │   └── Subscription 2 (endpoint B)
  └── Deliveries (all)

Integration (Company B)
  ├── API Keys
  ├── Webhook Events
  ├── Subscriptions
  │   └── Subscription 1 (endpoint C)
  └── Deliveries (all)
```

### Event Flow

```
Application Event
    ↓
ProcessWebhookEventJob (Queue: events)
    ↓
Rule Engine (Filter & Transform)
    ↓
WebhookDelivery Created
    ↓
DeliverWebhookJob (Queue: deliveries)
    ↓
HTTP Request to Endpoint
    ↓
Success/Failure Handling
    ↓
Retry (if failed) OR Dead Letter Queue
```

### Retry Strategy

```
Attempt 1: Immediate
           ↓ (fail)
Attempt 2: +1 minute (configurable)
           ↓ (fail)
Attempt 3: +5 minutes (configurable)
           ↓ (fail)
Attempt 4: +15 minutes (configurable)
           ↓ (fail)
Attempt 5: +1 hour (configurable)
           ↓ (fail)
Attempt 6: +6 hours (configurable)
           ↓ (fail)
Dead Letter Queue (manual review required)
```

### Database Schema Relationships

```
WebhookIntegration
├── webhook_api_keys (1:M)
├── webhook_subscriptions (1:M)
├── webhook_events (1:M)
└── webhook_deliveries (1:M)

WebhookSubscription
├── webhook_delivery_logs (1:M)
└── webhook_subscription_rules (1:M)

WebhookEvent
├── webhook_deliveries (1:M)
└── webhook_event_catalog (reference)
```

---

## Models

### 1. WebhookIntegration

Primary model for managing integrations.

**Attributes:**
- `id` (int) - Primary key
- `uid` (string) - ULID unique identifier
- `name` (string) - Integration name
- `status` (enum) - active|suspended|disabled
- `plan` (string) - free|pro|enterprise
- `daily_limit` (int) - Max webhooks per day
- `allowed_ips` (json) - Whitelisted IP addresses
- `allowed_domains` (json) - Whitelisted domains
- `notes` (text) - Admin notes
- `timestamps`

**Key Methods:**
```php
$integration->apiKeys();      // HasMany relationship
$integration->subscriptions(); // HasMany relationship
$integration->events();        // HasMany relationship
$integration->deliveries();    // HasMany relationship

// Check if integration is active
$integration->isActive(); // bool

// Get today's delivery count
$integration->todayDeliveryCount(); // int
```

### 2. WebhookSubscription

Represents a specific webhook endpoint subscription.

**Attributes:**
- `id` (int) - Primary key
- `uid` (string) - ULID unique identifier
- `integration_id` (fk) - Parent integration
- `name` (string) - Subscription name
- `url` (string) - Webhook endpoint URL
- `is_active` (bool) - Active/inactive status
- `subscribed_events` (json) - ["order.created", "order.updated"]
- `auth_type` (enum) - none|bearer|basic|apikey|custom
- `auth_config` (json) - Authentication details
- `signing_secret` (string) - HMAC signing key
- `timeout_ms` (int) - HTTP request timeout
- `max_attempts` (int) - Retry attempts
- `backoff_policy` (json) - Retry backoff schedule
- `timestamps`

**Key Methods:**
```php
$subscription->integration();      // BelongsTo
$subscription->deliveries();       // HasMany
$subscription->deliveryLogs();     // HasMany
$subscription->subscriptionRules(); // HasMany

// Check if subscribed to event
$subscription->isSubscribedTo('order.created'); // bool

// Get next retry timestamp
$subscription->getNextRetryAt($attemptCount); // Carbon

// Sign payload for delivery
$subscription->signPayload($payload); // string (HMAC)
```

### 3. WebhookEvent

Immutable record of a webhook event triggered in the application.

**Attributes:**
- `id` (int) - Primary key
- `uid` (string) - ULID unique identifier
- `integration_id` (fk) - Parent integration
- `event_key` (string) - Event identifier (e.g., "order.created")
- `event_version` (string) - Event version (e.g., "v1")
- `external_event_id` (string) - External system event ID
- `idempotency_key` (string) - Unique key for deduplication
- `payload` (json) - Full event payload
- `payload_hash` (string) - SHA256 hash of payload
- `received_at` (timestamp) - When event was received
- `processed_at` (timestamp) - When event was processed
- `timestamps`

**Key Methods:**
```php
$event->integration();  // BelongsTo
$event->deliveries();   // HasMany

// Check if already processed
$event->isProcessed(); // bool

// Mark as processed
$event->markAsProcessed(); // void

// Get subscriptions for this event
$event->getMatchingSubscriptions(); // Collection
```

### 4. WebhookDelivery

Represents a single webhook delivery attempt/status.

**Attributes:**
- `id` (int) - Primary key
- `uid` (string) - ULID unique identifier
- `integration_id` (fk) - Parent integration
- `subscription_id` (fk) - Target subscription
- `event_id` (fk) - Triggering event
- `status` (enum) - pending|sending|success|failed|dead
- `attempt_count` (int) - Number of attempts made
- `next_retry_at` (timestamp) - Scheduled retry time
- `last_error` (text) - Last error message
- `last_http_status` (int) - Last HTTP response code
- `last_latency_ms` (int) - Last request latency
- `timestamps`

**Key Methods:**
```php
$delivery->integration();   // BelongsTo
$delivery->subscription();  // BelongsTo
$delivery->event();         // BelongsTo
$delivery->logs();          // HasMany

// Increment attempt counter
$delivery->incrementAttempt(); // int (new count)

// Mark as successful delivery
$delivery->markAsSuccess($httpStatus, $latencyMs); // void

// Mark as failed (schedules retry)
$delivery->markAsFailed($errorMsg, $httpStatus, $latencyMs); // void

// Get retry delay for next attempt
$delivery->getRetryDelay(); // int (seconds)

// Check if delivery is stale (dead letter candidate)
$delivery->isStale($maxAgeHours = 72); // bool
```

### 5. WebhookApiKey

API key management for programmatic integration.

**Attributes:**
- `id` (int) - Primary key
- `integration_id` (fk) - Parent integration
- `key` (string) - Public key (API_KEY_xxx format)
- `secret` (string) - Hashed secret (used for HMAC)
- `name` (string) - Key label (e.g., "Production", "Testing")
- `permissions` (json) - ["inbound", "outbound", "admin"]
- `rate_limit_per_minute` (int) - Rate limit threshold
- `revoked_at` (timestamp) - Revocation timestamp
- `last_used_at` (timestamp) - Last usage timestamp
- `timestamps`

**Key Methods:**
```php
$apiKey->integration(); // BelongsTo

// Check if key is active/valid
$apiKey->isActive(); // bool

// Check if key has permission
$apiKey->hasPermission('inbound'); // bool

// Verify API key secret
$apiKey->verifySecret($providedSecret); // bool

// Mark key as last used (update timestamp)
$apiKey->recordUsage(); // void

// Revoke the key
$apiKey->revoke(); // void
```

### 6. WebhookDeliveryLog

Detailed audit trail for each delivery attempt.

**Attributes:**
- `id` (int) - Primary key
- `delivery_id` (fk) - Parent delivery
- `attempt_number` (int) - Which attempt this was
- `http_status` (int) - HTTP response code
- `request_headers` (json) - Headers sent
- `request_body_hash` (string) - SHA256 of payload sent
- `response_body` (text) - Response body (truncated)
- `latency_ms` (int) - Request duration
- `error_message` (text) - Error details
- `created_at` (timestamp)

**Key Methods:**
```php
$log->delivery(); // BelongsTo

// Get full request headers
$log->getRequestHeaders(); // array

// Get response details
$log->wasSuccessful(); // bool
```

### 7. WebhookSubscriptionRule

Conditional rules for payload filtering and transformation.

**Attributes:**
- `id` (int) - Primary key
- `subscription_id` (fk) - Parent subscription
- `rule_type` (enum) - filter|transform
- `event_key` (string) - Event this rule applies to
- `condition` (json) - Filter condition logic
- `action` (json) - Transform action logic
- `priority` (int) - Execution order
- `is_active` (bool) - Enable/disable rule
- `timestamps`

**Key Methods:**
```php
$rule->subscription(); // BelongsTo

// Evaluate if event matches condition
$rule->matches($eventPayload); // bool

// Apply transformation to payload
$rule->apply($payload); // array
```

### 8. WebhookEventCatalog

Reference table for available events.

**Attributes:**
- `id` (int) - Primary key
- `event_key` (string) - Event identifier (e.g., "order.created")
- `event_name` (string) - Human-readable name
- `description` (text) - Event description
- `version` (string) - Event version
- `payload_schema` (json) - JSON schema for validation
- `example_payload` (json) - Example webhook data
- `is_active` (bool) - Event is available for subscription
- `timestamps`

**Key Methods:**
```php
// Get all active events
WebhookEventCatalog::active()->get(); // Collection
```

---

## Controllers

### Manager Controllers

Manager routes (`/webhooks`) handle admin panel operations.

#### IntegrationController

**Routes:**
- `GET /webhooks` - List integrations
- `GET /webhooks/create` - Create form
- `POST /webhooks` - Store integration
- `GET /webhooks/{integration}/edit` - Edit form
- `PUT /webhooks/{integration}` - Update integration
- `DELETE /webhooks/{integration}` - Delete integration

**Methods:**
```php
index()              // List all integrations
create()             // Show creation form
store(StoreIntegrationRequest $request)        // Create integration
edit(WebhookIntegration $integration)          // Show edit form
update(UpdateIntegrationRequest $request, $id) // Update integration
destroy($id)         // Delete integration
```

#### SubscriptionController

**Routes:**
- `GET /webhooks/{integration}/subscriptions` - List subscriptions
- `GET /webhooks/{integration}/subscriptions/create` - Create form
- `POST /webhooks/{integration}/subscriptions` - Store subscription
- `GET /webhooks/{integration}/subscriptions/{subscription}/edit` - Edit form
- `PUT /webhooks/{integration}/subscriptions/{subscription}` - Update
- `DELETE /webhooks/{integration}/subscriptions/{subscription}` - Delete

**Methods:**
```php
index(WebhookIntegration $integration)
create(WebhookIntegration $integration)
store(StoreSubscriptionRequest $request, $id)
edit(WebhookIntegration $integration, $subscriptionId)
update(UpdateSubscriptionRequest $request, $integrationId, $subscriptionId)
destroy($integrationId, $subscriptionId)
```

#### DeliveryController

**Routes:**
- `GET /webhooks/deliveries` - List recent deliveries
- `GET /webhooks/deliveries/{delivery}` - View delivery details
- `POST /webhooks/deliveries/{delivery}/retry` - Retry failed delivery
- `GET /webhooks/deliveries/dead-letter` - View dead letter queue

**Methods:**
```php
index()                          // List deliveries with pagination
show(WebhookDelivery $delivery)  // Show delivery details + logs
retry($deliveryId)               // Retry failed delivery
deadLetter()                     // List dead letter queue
```

### API Controllers

API routes (`/api/webhooks`) handle webhook receiving and programmatic access.

#### WebhookEventController

**Routes:**
- `POST /api/webhooks/events` - Receive inbound webhook event
- `GET /api/webhooks/events/{eventId}` - Get event details
- `GET /api/webhooks/events` - List recent events

**Methods:**
```php
store(Request $request)     // Receive and queue webhook event
show($eventId)              // Get event details
index()                     // List events for API key's integration
```

#### IntegrationApiController

**Routes:**
- `GET /api/webhooks/integrations` - List integrations (for API key)
- `GET /api/webhooks/integrations/{id}` - Get integration details
- `GET /api/webhooks/integrations/{id}/subscriptions` - List subscriptions

**Methods:**
```php
index()                              // List integrations
show($integrationId)                 // Get integration details
subscriptions($integrationId)        // List active subscriptions
```

---

## Jobs

### 1. ProcessWebhookEventJob

Processes incoming webhook events and creates delivery records.

**Queue:** `events`

**Configuration:**
- Max tries: 3
- Timeout: 120 seconds

**Responsibilities:**
1. Load webhook event by ID
2. Check if event already processed (idempotency)
3. Find all matching subscriptions
4. Apply rule engine (filter and transform)
5. Create WebhookDelivery records
6. Dispatch DeliverWebhookJob for each delivery

**Usage:**
```php
ProcessWebhookEventJob::dispatch($eventId);
// Or with delay
ProcessWebhookEventJob::dispatch($eventId)
    ->delay(now()->addSeconds(60));
```

### 2. DeliverWebhookJob

Executes HTTP request to webhook endpoint and handles response.

**Queue:** `deliveries`

**Configuration:**
- Max tries: 1 (retry handled by model)
- Timeout: 30 seconds

**Responsibilities:**
1. Load WebhookDelivery record
2. Increment attempt counter
3. Send HTTP request with:
   - Authentication headers
   - HMAC signature
   - Custom headers
   - Timeout configuration
4. Record response:
   - HTTP status code
   - Latency
   - Response body
5. Handle success/failure:
   - Success: Mark delivery as complete
   - Failure: Check if retries remain, schedule next attempt

**Usage:**
```php
DeliverWebhookJob::dispatch($deliveryId, $payload);
// Or with custom queue
DeliverWebhookJob::dispatch($deliveryId, $payload)
    ->onQueue('deliveries');
```

### 3. ProcessWebhookPayloadJob

Specialized job for supplier webhook payloads (Supplier module integration).

**Queue:** `default`

**Configuration:**
- Max tries: 3
- Timeout: 120 seconds

**Responsibilities:**
1. Validate webhook signature
2. Load supplier source configuration
3. Map payload to internal format
4. Extract items from batch/single payload
5. Create or update SupplierExtractionResult records
6. Evaluate extraction quality
7. Dispatch AI content generation jobs
8. Track processing statistics

**Usage:**
```php
ProcessWebhookPayloadJob::dispatch($webhookData, $sourceId, $signature);
```

---

## Services

### 1. WebhookDeliveryService

Core service for executing HTTP delivery to webhook endpoints.

**Location:** `app/Services/Webhook/WebhookDeliveryService.php`

**Key Methods:**
```php
/**
 * Deliver webhook to endpoint
 * @return ['success' => bool, 'http_status' => ?int, 'latency_ms' => int, 'error' => ?string]
 */
public function deliver(WebhookDelivery $delivery, array $payload): array

/**
 * Build HTTP request with auth headers
 */
private function buildRequest(WebhookDelivery $delivery, array $payload): Request

/**
 * Sign payload with subscription's secret
 */
private function signPayload(string $payload, string $secret): string

/**
 * Add authentication headers based on type
 */
private function addAuthHeaders(Request $request, WebhookSubscription $subscription): void

/**
 * Log delivery attempt
 */
private function logDelivery(WebhookDelivery $delivery, array $result): void
```

**Features:**
- Configurable timeout per subscription
- Multiple authentication methods (Bearer, Basic, API Key, Custom)
- HMAC-SHA256 signing of payload
- Automatic retry scheduling
- Full audit logging

### 2. WebhookRuleEngineService

Evaluates rules and transforms payloads before delivery.

**Location:** `app/Services/Webhook/WebhookRuleEngineService.php`

**Key Methods:**
```php
/**
 * Check if subscription rules allow delivery
 */
public function shouldDeliver(WebhookSubscription $subscription, array $payload): bool

/**
 * Transform payload according to subscription rules
 */
public function transformPayload(WebhookSubscription $subscription, array $payload): array

/**
 * Evaluate filter conditions
 */
private function evaluateFilterRules(WebhookSubscription $subscription, array $payload): bool

/**
 * Apply transformation rules
 */
private function applyTransformRules(WebhookSubscription $subscription, array $payload): array
```

**Filter Conditions:**
```php
// Example filter rule (JSON stored)
{
    "type": "filter",
    "operator": "and",
    "conditions": [
        {"field": "order.status", "operator": "equals", "value": "completed"},
        {"field": "order.total", "operator": "greater_than", "value": 100}
    ]
}
```

**Transform Actions:**
```php
// Example transform rule (JSON stored)
{
    "type": "transform",
    "mappings": {
        "order_id": "data.id",
        "customer_name": "data.customer.full_name",
        "total_amount": "data.total"
    }
}
```

### 3. EventStoreService

Manages webhook event creation and idempotency.

**Location:** `app/Services/Webhook/EventStoreService.php`

**Key Methods:**
```php
/**
 * Store incoming webhook event with idempotency check
 */
public function store(
    int $integrationId,
    string $eventKey,
    array $payload,
    ?string $idempotencyKey = null,
    string $version = 'v1'
): WebhookEvent|false

/**
 * Check if event already processed
 */
public function isDuplicate(string $idempotencyKey): bool

/**
 * Get event by idempotency key
 */
public function getByIdempotencyKey(string $idempotencyKey): ?WebhookEvent

/**
 * Calculate payload hash for deduplication
 */
public function getPayloadHash(array $payload): string
```

---

## Routes

### Manager Routes

Located in `/routes/managers.php` - Requires `web` and `auth` middleware.

```php
Route::prefix('webhooks')
    ->middleware(['web', 'auth'])
    ->name('webhooks.')
    ->group(function () {
        // Integration management
        Route::get('/', [IntegrationController::class, 'index'])->name('index');
        Route::get('/create', [IntegrationController::class, 'create'])->name('create');
        Route::post('/', [IntegrationController::class, 'store'])->name('store');
        Route::get('/{integration}/edit', [IntegrationController::class, 'edit'])->name('edit');
        Route::put('/{integration}', [IntegrationController::class, 'update'])->name('update');
        Route::delete('/{integration}', [IntegrationController::class, 'destroy'])->name('destroy');

        // Subscription management (nested)
        Route::prefix('{integration}/subscriptions')
            ->name('subscriptions.')
            ->group(function () {
                Route::get('/', [SubscriptionController::class, 'index'])->name('index');
                Route::get('/create', [SubscriptionController::class, 'create'])->name('create');
                Route::post('/', [SubscriptionController::class, 'store'])->name('store');
                Route::get('/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('edit');
                Route::put('/{subscription}', [SubscriptionController::class, 'update'])->name('update');
                Route::delete('/{subscription}', [SubscriptionController::class, 'destroy'])->name('destroy');
            });

        // Delivery management
        Route::prefix('deliveries')
            ->name('deliveries.')
            ->group(function () {
                Route::get('/', [DeliveryController::class, 'index'])->name('index');
                Route::get('/{delivery}', [DeliveryController::class, 'show'])->name('show');
                Route::post('/{delivery}/retry', [DeliveryController::class, 'retry'])->name('retry');
                Route::get('/dead-letter/queue', [DeliveryController::class, 'deadLetter'])->name('dead-letter');
            });
    });
```

### API Routes

Located in `/routes/api.php` - Requires `api` and `auth:sanctum` middleware.

```php
Route::prefix('webhooks')
    ->middleware(['api', 'auth:sanctum'])
    ->name('webhooks.')
    ->group(function () {
        // Event endpoints
        Route::post('/events', [WebhookEventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}', [WebhookEventController::class, 'show'])->name('events.show');
        Route::get('/events', [WebhookEventController::class, 'index'])->name('events.index');

        // Integration endpoints
        Route::get('/integrations', [IntegrationApiController::class, 'index'])->name('integrations.index');
        Route::get('/integrations/{integration}', [IntegrationApiController::class, 'show'])->name('integrations.show');
        Route::get('/integrations/{integration}/subscriptions', [IntegrationApiController::class, 'subscriptions'])
            ->name('integrations.subscriptions');
    });
```

### Route Names Reference

| Route | Name | Method | Auth |
|-------|------|--------|------|
| `/webhooks` | webhooks.index | GET | auth |
| `/webhooks/create` | webhooks.create | GET | auth |
| `/webhooks` | webhooks.store | POST | auth |
| `/webhooks/{id}/edit` | webhooks.edit | GET | auth |
| `/webhooks/{id}` | webhooks.update | PUT | auth |
| `/webhooks/{id}` | webhooks.destroy | DELETE | auth |
| `/webhooks/{id}/subscriptions` | webhooks.subscriptions.index | GET | auth |
| `/api/webhooks/events` | api.webhooks.events.store | POST | sanctum |
| `/api/webhooks/integrations` | api.webhooks.integrations.index | GET | sanctum |

---

## Configuration

Located in `/config/config.php` - Publish with: `php artisan vendor:publish --tag=webhook-config`

```php
return [
    'name' => 'Webhook',
    'description' => 'Webhook module for managing integrations and subscriptions',

    // Retry configuration
    'max_retries' => env('WEBHOOK_MAX_RETRIES', 3),
    'retry_delay' => env('WEBHOOK_RETRY_DELAY', 300), // seconds

    // HTTP configuration
    'timeout' => env('WEBHOOK_TIMEOUT', 30), // seconds

    // Queue names
    'queues' => [
        'events' => env('WEBHOOK_QUEUE_EVENTS', 'default'),
        'deliveries' => env('WEBHOOK_QUEUE_DELIVERIES', 'default'),
    ],

    // Event catalog
    'events' => [
        // Pre-configured events
        'order.created',
        'order.updated',
        'order.deleted',
        'customer.created',
        'customer.updated',
        'product.created',
        'product.updated',
        'product.deleted',
    ],
];
```

### Environment Variables

```bash
# Webhook retry configuration
WEBHOOK_MAX_RETRIES=3              # Max delivery attempts
WEBHOOK_RETRY_DELAY=300            # Delay between retries (seconds)
WEBHOOK_TIMEOUT=30                 # HTTP request timeout (seconds)

# Queue configuration
WEBHOOK_QUEUE_EVENTS=default       # Queue for event processing
WEBHOOK_QUEUE_DELIVERIES=default   # Queue for webhook delivery
```

---

## Security

### HMAC Signature Generation

Each webhook subscription has a unique `signing_secret`. Payloads are signed using HMAC-SHA256:

**Signing Process:**
```php
$payload = json_encode($data);
$signature = hash_hmac('sha256', $payload, $subscription->signing_secret);
```

**Verification (Recipient Side):**
```php
$provided_signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? null;
$payload = file_get_contents('php://input');
$expected_signature = hash_hmac('sha256', $payload, $webhook_secret);

if (!hash_equals($expected_signature, $provided_signature)) {
    http_response_code(401);
    exit('Invalid signature');
}
```

### API Key Authentication

**Key Format:**
- Public key: `API_KEY_` + 32-character random string
- Secret: 255-character hashed secret (HMAC key)

**Usage:**
```bash
curl -X POST https://api.example.com/api/webhooks/events \
  -H "Authorization: Bearer API_KEY_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{"event_key": "order.created", "payload": {...}}'
```

**Verification:**
```php
$apiKey = WebhookApiKey::where('key', $providedKey)->first();

if (!$apiKey || !$apiKey->isActive()) {
    return response()->json(['error' => 'Invalid API key'], 401);
}

if (!$apiKey->hasPermission('inbound')) {
    return response()->json(['error' => 'Insufficient permissions'], 403);
}
```

### Authentication Types

**1. Bearer Token**
```php
'auth_type' => 'bearer',
'auth_config' => ['token' => 'your-bearer-token']
```

**Header Sent:**
```
Authorization: Bearer your-bearer-token
```

**2. Basic Authentication**
```php
'auth_type' => 'basic',
'auth_config' => ['username' => 'user', 'password' => 'pass']
```

**Header Sent:**
```
Authorization: Basic base64(user:pass)
```

**3. API Key Header**
```php
'auth_type' => 'apikey',
'auth_config' => ['header' => 'X-API-Key', 'value' => 'secret-key']
```

**Header Sent:**
```
X-API-Key: secret-key
```

**4. Custom Headers**
```php
'auth_type' => 'custom',
'auth_config' => [
    'headers' => [
        'X-Custom-Header' => 'value',
        'X-Another-Header' => 'another-value'
    ]
]
```

### Rate Limiting

**Per Integration:**
- `daily_limit`: Maximum webhooks per 24 hours
- Checked before processing events

**Per API Key:**
- `rate_limit_per_minute`: Requests per minute limit
- Enforced at API endpoint

**Implementation:**
```php
// Check integration daily limit
if ($integration->todayDeliveryCount() >= $integration->daily_limit) {
    Log::warning('Daily webhook limit exceeded', [
        'integration_id' => $integration->id,
        'today_count' => $integration->todayDeliveryCount(),
        'daily_limit' => $integration->daily_limit,
    ]);
    return false;
}

// Check API key rate limit (via middleware)
$rateLimitMiddleware->handleApiKeyRateLimit($apiKey);
```

---

## Reliability

### Retry Logic

**Exponential Backoff Configuration:**

Default backoff policy (configurable per subscription):
```json
{
    "backoff_policy": [
        "1m",   // After 1st failure: wait 1 minute
        "5m",   // After 2nd failure: wait 5 minutes
        "15m",  // After 3rd failure: wait 15 minutes
        "1h",   // After 4th failure: wait 1 hour
        "6h",   // After 5th failure: wait 6 hours
        "24h"   // After 6th failure: wait 24 hours, then dead letter
    ]
}
```

**Status Transitions:**
```
pending → sending → success (complete)
       ↓        ↓
       failed (reschedule) → pending
       ↓
       dead (manual intervention required)
```

### Idempotency

Events are deduplicated using `idempotency_key`:

```php
// Generate idempotency key (usually from external system)
$idempotencyKey = $request->header('X-Idempotency-Key')
    ?? md5($externalEventId . $payload);

// Check for duplicate
$event = WebhookEvent::where('idempotency_key', $idempotencyKey)->first();
if ($event) {
    Log::info('Duplicate event detected, skipping', ['idempotency_key' => $idempotencyKey]);
    return response()->json(['status' => 'duplicate'], 409);
}

// Store new event
$event = WebhookEvent::create([
    'integration_id' => $integration->id,
    'event_key' => $eventKey,
    'idempotency_key' => $idempotencyKey,
    'payload' => $payload,
    'payload_hash' => hash('sha256', json_encode($payload)),
]);
```

### Dead Letter Queue

Failed deliveries after maximum retries are marked as `dead`:

```php
// In DeliverWebhookJob
if ($delivery->attempt_count >= $delivery->subscription->max_attempts) {
    $delivery->update(['status' => 'dead']);

    Log::error('Webhook delivery dead lettered', [
        'delivery_id' => $delivery->id,
        'subscription_id' => $delivery->subscription_id,
        'attempts' => $delivery->attempt_count,
    ]);

    // Optional: Send alert to admin
    // Notification::route('mail', config('app.admin_email'))
    //     ->notify(new DeadLetterQueueAlert($delivery));
}
```

**Manual Intervention:**
- Review dead letter queue in admin panel
- Fix endpoint configuration or credentials
- Retry manually or through API

### Delivery Logging

Each delivery attempt is logged:

```php
WebhookDeliveryLog::create([
    'delivery_id' => $delivery->id,
    'attempt_number' => $delivery->attempt_count,
    'http_status' => $result['http_status'],
    'request_headers' => $requestHeaders,
    'request_body_hash' => hash('sha256', $payload),
    'response_body' => substr($response->body(), 0, 1000), // Truncated
    'latency_ms' => $latency,
    'error_message' => $result['error'],
]);
```

---

## Testing

### Unit Tests

Test individual services:

```php
// Test WebhookRuleEngineService
public function test_should_deliver_respects_filter_rules()
{
    $subscription = WebhookSubscription::factory()
        ->has(WebhookSubscriptionRule::factory(['condition' => [
            'field' => 'order.status',
            'operator' => 'equals',
            'value' => 'completed'
        ]]))
        ->create();

    $payload = ['order' => ['status' => 'pending']];

    $this->assertFalse(
        app(WebhookRuleEngineService::class)->shouldDeliver($subscription, $payload)
    );
}

// Test WebhookDeliveryService
public function test_deliver_sends_hmac_signature()
{
    $delivery = WebhookDelivery::factory()->create();

    Http::fake(['*' => Http::response()]);

    app(WebhookDeliveryService::class)->deliver($delivery, ['test' => 'data']);

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('X-Webhook-Signature');
    });
}
```

### Feature Tests

Test complete workflows:

```php
// Test webhook event processing
public function test_webhook_event_triggers_deliveries()
{
    Queue::fake();

    $integration = WebhookIntegration::factory()
        ->has(WebhookSubscription::factory())
        ->create();

    $event = WebhookEvent::create([
        'integration_id' => $integration->id,
        'event_key' => 'order.created',
        'idempotency_key' => 'test-' . now()->timestamp,
        'payload' => ['order_id' => 123],
        'payload_hash' => hash('sha256', json_encode(['order_id' => 123])),
    ]);

    ProcessWebhookEventJob::dispatch($event->id);

    Queue::assertPushed(DeliverWebhookJob::class);
}

// Test API endpoint
public function test_post_webhook_event_via_api()
{
    $integration = WebhookIntegration::factory()->create();
    $apiKey = WebhookApiKey::factory()->for($integration)->create();

    $response = $this->actingAs($apiKey)
        ->postJson('/api/webhooks/events', [
            'event_key' => 'order.created',
            'payload' => ['order_id' => 123],
            'idempotency_key' => 'test-' . now()->timestamp,
        ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('webhook_events', [
        'integration_id' => $integration->id,
        'event_key' => 'order.created',
    ]);
}
```

### Integration Tests

Test with real HTTP:

```php
// Test endpoint with mock server
public function test_webhook_delivery_to_endpoint()
{
    Http::fake([
        'https://api.example.com/webhook' => Http::response(['status' => 'ok'], 200)
    ]);

    $delivery = WebhookDelivery::factory()
        ->for(WebhookSubscription::factory(['url' => 'https://api.example.com/webhook']))
        ->create();

    $result = app(WebhookDeliveryService::class)->deliver($delivery, ['test' => 'data']);

    $this->assertTrue($result['success']);
    $this->assertEquals(200, $result['http_status']);
}
```

### Running Tests

```bash
# Run all webhook tests
php artisan test --filter=Webhook

# Run specific test class
php artisan test tests/Unit/Services/WebhookRuleEngineServiceTest.php

# Run with coverage
php artisan test --coverage tests/

# Run only failed tests
php artisan test --only-failures
```

---

## Troubleshooting

### Common Issues

#### Issue: Webhook deliveries stuck in "pending" status

**Symptoms:**
- Deliveries never move to "sending" or "success"
- Queue worker not processing jobs

**Solutions:**
```bash
# Check if queue worker is running
ps aux | grep "queue:work"

# Start queue worker
php artisan queue:work --queue=deliveries

# Monitor queue
php artisan queue:monitor

# Check failed jobs table
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

#### Issue: "Invalid webhook signature" errors

**Symptoms:**
- Deliveries marked as "failed" with signature validation errors
- Endpoint rejects all requests

**Solutions:**
```php
// Verify signing secret is correct
$subscription = WebhookSubscription::find($subscriptionId);
$secret = $subscription->signing_secret;

// Ensure endpoint is using same secret
// Example endpoint verification:
$payload = file_get_contents('php://input');
$signature = hash_hmac('sha256', $payload, $secret);
$providedSig = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? null;

if (!hash_equals($signature, $providedSig)) {
    // Log for debugging
    error_log("Expected: $signature");
    error_log("Received: $providedSig");
}
```

#### Issue: Endpoints receiving duplicate events

**Symptoms:**
- Same event delivered multiple times
- Duplicate records created on endpoint

**Solutions:**
```php
// Ensure idempotency_key is used
// On inbound webhook:
$idempotencyKey = $request->header('X-Idempotency-Key')
    ?? md5($externalId . date('Y-m-d-H'));

// On outbound endpoint:
// Store received event by idempotency key
// Return 200 OK even if already processed
if (EventLog::where('idempotency_key', $idempotencyKey)->exists()) {
    return response()->json(['status' => 'received']);
}
```

#### Issue: High database growth from delivery logs

**Symptoms:**
- `webhook_delivery_logs` table growing rapidly
- Slow queries on logs table

**Solutions:**
```php
// Archive old logs (add to migration)
Schema::table('webhook_delivery_logs', function (Blueprint $table) {
    $table->softDeletes(); // Enable soft deletes
});

// Create archive job
class ArchiveOldDeliveryLogsJob implements ShouldQueue
{
    public function handle(): void
    {
        WebhookDeliveryLog::where('created_at', '<', now()->subMonths(3))
            ->delete(); // Or move to archive table
    }
}

// Schedule in kernel
$schedule->job(ArchiveOldDeliveryLogsJob::class)
    ->daily()
    ->at('02:00');
```

#### Issue: API rate limit errors

**Symptoms:**
- `429 Too Many Requests` errors
- High-frequency event sends blocked

**Solutions:**
```php
// Check API key rate limit
$apiKey = WebhookApiKey::find($keyId);
echo "Rate limit: {$apiKey->rate_limit_per_minute} per minute";

// Increase if needed
$apiKey->update(['rate_limit_per_minute' => 300]);

// Check current usage
$usageThisMinute = WebhookEvent::where('api_key_id', $keyId)
    ->where('created_at', '>', now()->subMinute())
    ->count();
```

#### Issue: Webhooks not being triggered for certain events

**Symptoms:**
- Events created but no deliveries generated
- Rule engine filtering events

**Solutions:**
```php
// Check subscription is active
$subscription = WebhookSubscription::find($subId);
echo "Active: " . ($subscription->is_active ? 'Yes' : 'No');

// Check subscribed events
echo "Subscribed to: " . json_encode($subscription->subscribed_events);

// Check rule engine conditions
$ruleEngine = app(WebhookRuleEngineService::class);
$shouldDeliver = $ruleEngine->shouldDeliver($subscription, $payload);
echo "Should deliver: " . ($shouldDeliver ? 'Yes' : 'No');

// Temporarily disable rules to test
$subscription->subscriptionRules()->update(['is_active' => false]);
```

### Debug Mode

Enable verbose logging:

```php
// In ProcessWebhookEventJob
Log::channel('webhooks')->debug('Event processing', [
    'event_id' => $event->id,
    'subscriptions' => $subscriptions->count(),
    'payload' => $event->payload,
]);

// In DeliverWebhookJob
Log::channel('webhooks')->debug('Delivery attempt', [
    'delivery_id' => $delivery->id,
    'url' => $delivery->subscription->url,
    'attempt' => $delivery->attempt_count,
]);
```

**Environment Variable:**
```bash
WEBHOOK_DEBUG=true
LOG_CHANNEL=webhooks
```

### Monitoring & Metrics

**Query delivery statistics:**
```php
// Success rate
$successRate = WebhookDelivery::where('status', 'success')->count()
    / WebhookDelivery::count();

// Average latency
$avgLatency = WebhookDeliveryLog::avg('latency_ms');

// Dead letter count
$deadCount = WebhookDelivery::where('status', 'dead')->count();

// Retry percentage
$retryRate = WebhookDelivery::where('attempt_count', '>', 1)->count()
    / WebhookDelivery::count();
```

**Create a dashboard command:**
```php
// app/Console/Commands/WebhookStats.php
class WebhookStats extends Command
{
    public function handle()
    {
        $this->info('Webhook Statistics');
        $this->info('==================');
        $this->line('Total Events: ' . WebhookEvent::count());
        $this->line('Successful: ' . WebhookDelivery::where('status', 'success')->count());
        $this->line('Failed: ' . WebhookDelivery::where('status', 'failed')->count());
        $this->line('Dead: ' . WebhookDelivery::where('status', 'dead')->count());
    }
}
```

---

## Best Practices

### 1. Use Idempotency Keys

Always provide unique idempotency keys to prevent duplicate processing:

```php
// On inbound webhook
$idempotencyKey = $externalEventId . '-' . date('YmdHi');

ProcessWebhookEventJob::dispatch(
    eventKey: 'order.created',
    payload: $data,
    idempotencyKey: $idempotencyKey
);
```

### 2. Implement Exponential Backoff

Configure reasonable retry delays based on endpoint characteristics:

```php
$subscription->update([
    'backoff_policy' => [
        '30s',  // Quick retry for transient failures
        '5m',   // Wait before second attempt
        '1h',   // Longer wait
        '24h',  // Give up after day
    ],
    'max_attempts' => 4,
]);
```

### 3. Monitor Dead Letter Queue

Regularly review and handle dead-lettered webhooks:

```php
// Schedule daily dead letter check
$schedule->call(function () {
    $deadCount = WebhookDelivery::where('status', 'dead')
        ->where('updated_at', '>', now()->subDay())
        ->count();

    if ($deadCount > 0) {
        Notification::route('mail', 'admin@example.com')
            ->notify(new DeadLetterAlert($deadCount));
    }
})->daily()->at('09:00');
```

### 4. Use Rule Engine for Filtering

Reduce unnecessary webhook deliveries with subscription rules:

```php
$subscription->subscriptionRules()->create([
    'rule_type' => 'filter',
    'condition' => [
        'operator' => 'and',
        'conditions' => [
            ['field' => 'order.total', 'operator' => '>', 'value' => 100],
            ['field' => 'order.status', 'operator' => '=', 'value' => 'completed'],
        ]
    ],
    'priority' => 1,
    'is_active' => true,
]);
```

### 5. Document Event Schema

Keep the WebhookEventCatalog updated with example payloads:

```php
WebhookEventCatalog::create([
    'event_key' => 'order.created',
    'event_name' => 'Order Created',
    'description' => 'Triggered when a new order is created',
    'version' => 'v1',
    'example_payload' => [
        'order_id' => 12345,
        'customer_id' => 67890,
        'total' => 199.99,
        'status' => 'pending',
        'created_at' => now()->toIso8601String(),
    ],
    'is_active' => true,
]);
```

### 6. Secure Your Endpoints

Always validate signatures on the receiving end:

```php
// Example endpoint implementation
Route::post('/webhook', function (Request $request) {
    $signature = $request->header('X-Webhook-Signature');
    $payload = $request->getContent();
    $secret = config('app.webhook_secret');

    $expectedSignature = hash_hmac('sha256', $payload, $secret);

    if (!hash_equals($expectedSignature, $signature)) {
        return response()->json(['error' => 'Invalid signature'], 401);
    }

    // Process webhook...
    return response()->json(['status' => 'received'], 200);
});
```

### 7. Use Circuit Breaker Pattern

Temporarily suspend failing endpoints to avoid cascading failures:

```php
// Extended subscription model with circuit breaker
if ($delivery->wasSuccessful()) {
    $subscription->recordSuccess();
} else {
    $subscription->recordFailure();

    // If failure rate exceeds 50% in last hour, suspend
    if ($subscription->getFailureRate('1h') > 0.5) {
        $subscription->update(['is_active' => false]);
        Log::error('Subscription suspended due to high failure rate', [
            'subscription_id' => $subscription->id,
        ]);
    }
}
```

---

## Migration & Seeders

### Run Migrations

```bash
# Auto-loaded via service provider
php artisan migrate

# Or specific migration
php artisan migrate --path=modules/Webhook/database/migrations
```

### Run Seeders

```bash
# Seed event catalog
php artisan db:seed --class="Modules\\Webhook\\Database\\Seeders\\WebhookEventCatalogSeeder"

# Or via DatabaseSeeder
php artisan db:seed
```

**Event Catalog Seeder includes:**
- order.created
- order.updated
- order.deleted
- customer.created
- customer.updated
- product.created
- product.updated
- product.deleted

---

## API Examples

### Send Webhook Event

```bash
curl -X POST http://localhost:8000/api/webhooks/events \
  -H "Authorization: Bearer API_KEY_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -H "X-Idempotency-Key: order-12345-2025-12-29" \
  -d '{
    "event_key": "order.created",
    "payload": {
      "order_id": 12345,
      "customer_id": 67890,
      "total": 199.99,
      "status": "pending",
      "items": [
        {
          "sku": "PROD-001",
          "quantity": 2,
          "price": 99.99
        }
      ],
      "created_at": "2025-12-29T10:30:00Z"
    }
  }'
```

### Receive Webhook (Endpoint Example)

```php
<?php

Route::post('/webhook/orders', function (Request $request) {
    // Validate signature
    $signature = $request->header('X-Webhook-Signature');
    $payload = $request->getContent();
    $secret = env('WEBHOOK_SECRET');

    $expectedSignature = hash_hmac('sha256', $payload, $secret);

    if (!hash_equals($expectedSignature, $signature ?? '')) {
        return response()->json(['error' => 'Invalid signature'], 401);
    }

    // Parse event
    $event = $request->json();

    // Process order
    $order = Order::create([
        'external_id' => $event['payload']['order_id'],
        'customer_id' => $event['payload']['customer_id'],
        'total' => $event['payload']['total'],
        'status' => $event['payload']['status'],
    ]);

    // Create line items
    foreach ($event['payload']['items'] as $item) {
        $order->items()->create([
            'sku' => $item['sku'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
        ]);
    }

    // Always return 200 OK for idempotency
    return response()->json(['status' => 'received'], 200);
});
```

---

## Publishing & Customization

### Publish Configuration

```bash
php artisan vendor:publish --tag=webhook-config
```

Creates `/config/webhook.php` in your project root.

### Publish Migrations

```bash
php artisan vendor:publish --tag=webhook-migrations
```

Copies migrations to `/database/migrations/` for customization.

### Extend Models

Create your own model traits:

```php
// app/Models/Traits/HasWebhookEvents.php
namespace App\Models\Traits;

trait HasWebhookEvents
{
    public function triggerWebhookEvent(string $eventKey, array $data): void
    {
        $event = WebhookEvent::create([
            'integration_id' => $this->integration_id,
            'event_key' => $eventKey,
            'idempotency_key' => $this->generateIdempotencyKey(),
            'payload' => $data,
            'payload_hash' => hash('sha256', json_encode($data)),
        ]);

        ProcessWebhookEventJob::dispatch($event->id);
    }
}
```

---

## Performance Optimization

### Database Indexes

Ensure all indexes are in place for optimal query performance:

```bash
# Check index usage
ANALYZE TABLE webhook_events;
ANALYZE TABLE webhook_deliveries;
ANALYZE TABLE webhook_subscriptions;
ANALYZE TABLE webhook_api_keys;
```

### Query Optimization

Use eager loading to prevent N+1 queries:

```php
// Bad - N+1 queries
$deliveries = WebhookDelivery::all();
foreach ($deliveries as $delivery) {
    $delivery->subscription->url; // Query per delivery
}

// Good - Eager loading
$deliveries = WebhookDelivery::with('subscription', 'event')->get();
```

### Caching

Cache frequently accessed data:

```php
// Cache active subscriptions
$subscriptions = Cache::remember(
    'webhook-subscriptions-' . $integrationId,
    3600,
    fn () => WebhookSubscription::where('integration_id', $integrationId)
        ->where('is_active', true)
        ->get()
);
```

### Archiving Old Data

Keep tables performant by archiving historical data:

```bash
# Archive deliveries older than 90 days
php artisan webhook:archive-deliveries --days=90
```

---

## Support & Contributing

For issues, questions, or contributions:

1. Check the troubleshooting section above
2. Review test cases for implementation examples
3. Check Laravel and module documentation
4. Contact the development team

---

## Changelog

### Version 1.0.0 (2025-12-23)

Initial release featuring:

- Core webhook integration system
- Multi-tenant support
- Event-driven architecture
- Secure delivery with HMAC signatures
- Automatic retry logic with exponential backoff
- Rule engine for payload transformation
- Dead letter queue handling
- Comprehensive audit logging
- Full test coverage
- Admin panel for management
- RESTful API endpoints

---

## License

This module is part of the Alsernet application. All rights reserved.

---

## Related Documentation

- [Laravel 12 Documentation](https://laravel.com/docs/12)
- [Laravel Queues](https://laravel.com/docs/12/queues)
- [Laravel Testing](https://laravel.com/docs/12/testing)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [HMAC Signatures](https://www.rfc-editor.org/rfc/rfc2104.html)
