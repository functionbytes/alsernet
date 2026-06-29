# 🏗️ TECHNICAL ARCHITECTURE - Social Media Module

**Version**: 1.0
**Date**: 2025-12-27
**Framework**: Laravel 12.x
**Pattern**: Publisher + Job + Webhook

---

## 📐 SYSTEM ARCHITECTURE

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER INTERFACE                          │
│  (Admin Panel - Livewire/Blade Components)                     │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│                    HTTP CONTROLLERS                             │
│  - PublishingController                                         │
│  - AccountController (OAuth)                                    │
│  - WebhookControllers (4 networks)                             │
└────────────────────────┬────────────────────────────────────────┘
                         │
        ┌────────────────┼────────────────┐
        │                │                │
        ▼                ▼                ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│   JOBS      │  │  COMMANDS   │  │  WEBHOOKS   │
│ (Queue)     │  │ (Scheduler) │  │ (Events)    │
└──────┬──────┘  └──────┬──────┘  └──────┬──────┘
       │                │                │
       │                │                │
       ▼                ▼                ▼
┌─────────────────────────────────────────────────────────────────┐
│                    PUBLISHERS / SERVICES                        │
│  - FacebookPublisher                                            │
│  - InstagramPublisher                                           │
│  - TwitterPublisher                                             │
│  - LinkedInPublisher                                            │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│              EXTERNAL APIs (Social Networks)                    │
│  - Facebook Graph API v21.0                                     │
│  - Instagram Graph API                                          │
│  - Twitter API v2                                               │
│  - LinkedIn API v2                                              │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🗄️ DATABASE SCHEMA

### Core Tables

#### `social_accounts`

Stores connected social media accounts.

```sql
CREATE TABLE social_accounts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    account_id BIGINT NOT NULL,  -- FK to helpdesk_accounts
    network VARCHAR(255),         -- ENUM: facebook, instagram, twitter, linkedin
    username VARCHAR(255),
    network_id VARCHAR(255),      -- Page ID, Business Account ID, etc.
    access_token TEXT,            -- ENCRYPTED
    access_token_expires_at TIMESTAMP NULL,
    refresh_token TEXT NULL,      -- ENCRYPTED
    metadata JSON NULL,           -- Extra info (followers, page name, etc.)
    status TINYINT DEFAULT 1,     -- 1=active, 0=inactive (token expired)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

#### `social_posts`

Stores posts to be published or already published.

```sql
CREATE TABLE social_posts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    account_id BIGINT NOT NULL,
    social_account_id BIGINT NULL,
    campaign_id BIGINT NULL,
    created_by BIGINT NOT NULL,

    -- Content
    type VARCHAR(255),            -- ENUM: text, image, video, link, carousel
    content TEXT NULL,
    media JSON NULL,              -- ['image1.jpg', 'image2.jpg']
    link_url VARCHAR(255) NULL,
    link_preview JSON NULL,

    -- Scheduling
    scheduled_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,

    -- Status
    status VARCHAR(255),          -- ENUM: draft, scheduled, publishing, published, failed
    external_id VARCHAR(255) NULL, -- Post ID from network (e.g., '123456789_987654321')
    external_url VARCHAR(255) NULL, -- Direct URL to post

    -- Approval workflow
    approval_status VARCHAR(255) DEFAULT 'draft',
    reviewed_by BIGINT NULL,
    review_notes TEXT NULL,
    submitted_at TIMESTAMP NULL,
    reviewed_at TIMESTAMP NULL,

    -- Error handling
    error_message TEXT NULL,
    publish_results JSON NULL,
    retry_count INT DEFAULT 0,

    -- Metrics
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    shares_count INT DEFAULT 0,
    reach INT NULL,
    impressions INT NULL,
    views_count INT DEFAULT 0,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

---

## 🔄 PUBLISHING FLOW

### Step-by-Step Process

```
┌──────────────────────────────────────────────────────────────┐
│ 1. USER CREATES POST                                         │
│    - Select social account(s)                                │
│    - Choose type (text/image/video/link)                     │
│    - Write content                                           │
│    - Upload media (optional)                                 │
│    - Schedule date/time OR publish now                       │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│ 2. POST SAVED TO DATABASE                                    │
│    - Status: DRAFT or SCHEDULED                              │
│    - scheduled_at: future timestamp or null                  │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│ 3a. IMMEDIATE PUBLISH (if "Publish Now")                     │
│    PublishingController dispatches PublishPostJob            │
│                                                              │
│ 3b. SCHEDULED PUBLISH (if future date)                      │
│    Laravel Scheduler runs every minute:                      │
│    social:publish-scheduled                                  │
│    - Finds posts with scheduled_at <= now()                  │
│    - Dispatches PublishPostJob for each                      │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│ 4. PublishPostJob EXECUTES (in queue)                        │
│    - Updates post.status = 'publishing'                      │
│    - Calls getPublisher() → returns correct publisher        │
│    - Publisher->publish($post)                               │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│ 5. PUBLISHER MAKES API CALL                                  │
│    - FacebookPublisher → Graph API                           │
│    - InstagramPublisher → Container + Publish                │
│    - TwitterPublisher → Chunked Upload + Tweet               │
│    - LinkedInPublisher → Register + Upload + UGC Post        │
└────────────────────────┬─────────────────────────────────────┘
                         │
                ┌────────┴────────┐
                │                 │
                ▼                 ▼
        ┌───────────┐     ┌───────────┐
        │  SUCCESS  │     │   FAILED  │
        └─────┬─────┘     └─────┬─────┘
              │                 │
              ▼                 ▼
┌──────────────────────────────────────────────────────────────┐
│ 6. UPDATE POST IN DATABASE                                   │
│                                                              │
│ SUCCESS:                        FAILED:                      │
│ - status = 'published'          - status = 'failed'          │
│ - external_id = '12345...'      - error_message = '...'      │
│ - external_url = 'https://...'  - retry_count++              │
│ - published_at = now()          - Job retries (max 3)        │
└──────────────────────────────────────────────────────────────┘
```

---

## 🎯 PUBLISHER PATTERN

### Base Publisher (Abstract Class)

```php
abstract class BasePublisher
{
    abstract public function publish(Post $post): array;

    protected function getMediaUrls(Post $post): array
    {
        return json_decode($post->media, true) ?? [];
    }

    protected function isImage(string $url): bool
    {
        return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $url);
    }

    protected function isVideo(string $url): bool
    {
        return preg_match('/\.(mp4|mov|avi|webm)$/i', $url);
    }

    protected function getFullMediaUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return url("storage/{$path}");
    }
}
```

### Network-Specific Publishers

Each publisher extends `BasePublisher` and implements network-specific logic:

#### FacebookPublisher

**Features**:
- TEXT posts → `/feed` endpoint
- SINGLE IMAGE → `/photos` endpoint
- MULTIPLE IMAGES → Upload unpublished + `/feed` with attached_media
- VIDEO → `/videos` endpoint
- LINK → `/feed` with link parameter
- CAROUSEL → Not natively supported, uses multiple images

**API Endpoint**:
```
POST https://graph.facebook.com/v21.0/{page_id}/feed
POST https://graph.facebook.com/v21.0/{page_id}/photos
POST https://graph.facebook.com/v21.0/{page_id}/videos
```

#### InstagramPublisher

**Features**:
- Two-step process: Create container → Publish container
- IMAGE → Create media container → Publish
- VIDEO → Create media container → Publish (requires video processing)
- CAROUSEL → Create carousel container with children → Publish
- REELS → Special video container with media_type=REELS

**API Flow**:
```
1. POST /{ig_account_id}/media  → Returns {id: container_id}
2. POST /{ig_account_id}/media_publish  → Returns {id: media_id}
```

#### TwitterPublisher

**Features**:
- TEXT posts → 280 char limit
- IMAGE → Chunked upload (INIT → APPEND → FINALIZE) → Tweet with media_ids
- VIDEO → Same chunked upload
- LINK → Auto-preview in text
- Maximum 4 images per tweet

**API Flow**:
```
1. POST /media/upload.json (INIT)  → {media_id}
2. POST /media/upload.json (APPEND)  → OK
3. POST /media/upload.json (FINALIZE)  → OK
4. POST /tweets  → {data: {id, text}}
```

#### LinkedInPublisher

**Features**:
- TEXT posts → UGC post
- IMAGE → Register upload → Upload binary → UGC post with media
- VIDEO → Same flow
- ARTICLE (LINK) → UGC post with article object

**API Flow**:
```
1. POST /assets?action=registerUpload  → {asset: urn}
2. PUT {uploadUrl}  → Upload binary
3. POST /ugcPosts  → {id: urn}
```

---

## ⚙️ JOB SYSTEM

### PublishPostJob

**Purpose**: Asynchronously publish post to social network

**Configuration**:
```php
public $tries = 3;  // Max attempts
public $backoff = [60, 300, 900];  // 1min, 5min, 15min between retries
```

**Flow**:
```php
public function handle(): void
{
    // 1. Refresh post from DB
    $this->post->refresh();

    // 2. Update status
    $this->post->update(['status' => PostStatus::PUBLISHING]);

    try {
        // 3. Get correct publisher
        $publisher = $this->getPublisher();

        // 4. Publish
        $result = $publisher->publish($this->post);

        // 5. Update post with success
        $this->post->update([
            'status' => PostStatus::PUBLISHED,
            'external_id' => $result['id'],
            'external_url' => $result['url'],
            'published_at' => now(),
        ]);

    } catch (Exception $e) {
        // 6. Handle error
        if ($this->isTokenExpiredError($e)) {
            // Mark account as inactive
            $this->post->socialAccount->update(['status' => 0]);
        }

        // Save error
        $this->post->update([
            'status' => PostStatus::FAILED,
            'error_message' => $e->getMessage(),
            'retry_count' => $this->post->retry_count + 1,
        ]);

        // Re-throw for retry logic
        throw $e;
    }
}
```

### ProcessWebhookJobs

Similar pattern for async webhook processing:

- `ProcessFacebookWebhookJob`
- `ProcessInstagramWebhookJob`
- `ProcessTwitterWebhookJob`
- `ProcessLinkedInWebhookJob`

---

## 🕐 AUTOMATION COMMANDS

### social:publish-scheduled

**Purpose**: Find and publish scheduled posts

**Execution**: Every minute via Laravel Scheduler

**Logic**:
```php
$posts = Post::where('status', PostStatus::SCHEDULED)
    ->where('scheduled_at', '<=', now())
    ->limit($limit)
    ->get();

foreach ($posts as $post) {
    PublishPostJob::dispatch($post);
}
```

**Scheduler Config**:
```php
Schedule::command('social:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping()  // Prevent concurrent executions
    ->onOneServer();        // Only run on one server (load balancer)
```

### social:sync-stats

**Purpose**: Sync engagement metrics from social networks

**Execution**: Every hour via Laravel Scheduler

**Logic**:
```php
$posts = Post::where('status', PostStatus::PUBLISHED)
    ->whereNotNull('external_id')
    ->where('published_at', '>=', now()->subDays($days))
    ->limit($limit)
    ->get();

foreach ($posts as $post) {
    $stats = $this->fetchStats($post);
    $post->update($stats);
}
```

**API Calls**:
- Facebook: `/post_id?fields=likes.summary(true),comments.summary(true),shares`
- Instagram: `/media_id?fields=like_count,comments_count,insights.metric(reach,impressions)`
- Twitter: `/tweets/{id}?tweet.fields=public_metrics`
- LinkedIn: `/socialActions/{share_id}`

---

## 🔐 SECURITY

### OAuth Flow

```
1. User clicks "Connect Account" → redirect to OAuth provider
2. User authorizes app
3. Provider redirects to callback with code
4. Callback exchanges code for access_token
5. System stores encrypted token in DB
6. System fetches account info (pages, username, etc.)
```

### Token Encryption

```php
// Encryption
$encrypted = encrypt($accessToken);

// Decryption
$decrypted = decrypt($account->access_token);
```

### Webhook Signature Verification

**Facebook/Instagram (HMAC-SHA256)**:
```php
$signature = $request->header('X-Hub-Signature-256');
$expectedSignature = 'sha256=' . hash_hmac(
    'sha256',
    $request->getContent(),
    config('services.facebook.webhook_secret')
);
return hash_equals($expectedSignature, $signature);
```

**Twitter (CRC Challenge)**:
```php
$crcToken = $request->query('crc_token');
$responseToken = 'sha256=' . base64_encode(
    hash_hmac('sha256', $crcToken, $consumerSecret, true)
);
return response()->json(['response_token' => $responseToken]);
```

---

## 🔄 ERROR HANDLING

### Retry Strategy

**Exponential Backoff**:
- Attempt 1: Immediate
- Attempt 2: After 60 seconds
- Attempt 3: After 300 seconds (5 min)
- Attempt 4: After 900 seconds (15 min)

**Token Expiration Detection**:
```php
protected function isTokenExpiredError(Exception $e): bool
{
    // Facebook
    if ($e->getCode() == 190) return true;

    // Twitter
    if ($e->getCode() == 401) return true;

    // Generic
    if (stripos($e->getMessage(), 'token expired') !== false) {
        return true;
    }

    return false;
}
```

### Failed Job Handling

```bash
# View failed jobs
php artisan queue:failed

# Retry specific job
php artisan queue:retry {id}

# Retry all
php artisan queue:retry all

# Flush all failed
php artisan queue:flush
```

---

## 📊 PERFORMANCE OPTIMIZATIONS

### Database Indexes

```sql
-- Scheduled posts lookup
INDEX (account_id, status, scheduled_at)

-- Published posts for sync
INDEX (status, external_id, published_at)

-- Account lookups
INDEX (network, status)
```

### Queue Configuration

```php
// config/queue.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 90,
    'block_for' => null,
],
```

### Caching

```php
// Cache account info (5 minutes)
Cache::remember("social_account_{$id}", 300, function () use ($id) {
    return SocialAccount::find($id);
});
```

---

## 🧪 TESTING STRATEGY

### Unit Tests (Recommended)

- Publisher methods
- Webhook signature verification
- Token expiration detection
- Media URL generation

### Feature Tests (Recommended)

- OAuth callback flow
- Post creation and publishing
- Command execution (social:publish-scheduled)
- Webhook event processing

### Integration Tests (Optional)

- End-to-end publishing flow
- Real API calls with test credentials
- Webhook delivery

---

## 📚 EXTENDING THE MODULE

### Adding a New Social Network

1. **Create Publisher**:
```php
class TikTokPublisher extends BasePublisher
{
    public function publish(Post $post): array
    {
        // Implementation
    }
}
```

2. **Update getPublisher() in Job**:
```php
protected function getPublisher()
{
    return match ($this->account->network->value) {
        // ...
        'tiktok' => new TikTokPublisher(),
        default => throw new Exception('Unsupported network'),
    };
}
```

3. **Create Webhook Controller**:
```php
class TikTokWebhookController extends BaseWebhookController
{
    protected function verifySignature(Request $request): bool
    {
        // Implementation
    }

    protected function handleEvent(Request $request): JsonResponse
    {
        // Implementation
    }
}
```

4. **Add Route**:
```php
Route::post('/webhooks/social/tiktok', [TikTokWebhookController::class, 'handle']);
```

---

## 🎯 BEST PRACTICES

### Code Organization

- **Publishers**: One file per network in `Services/Publishers/`
- **Jobs**: Separate jobs for publishing and webhook processing
- **Commands**: One file per scheduled task
- **Controllers**: Separate webhook controller per network

### Error Messages

- Always save `error_message` to posts
- Log errors with context: `Log::error('Message', ['post_id' => $post->id])`
- User-friendly messages in UI

### Token Management

- Always encrypt tokens before storing
- Check token expiration before API calls
- Auto-refresh tokens when possible
- Mark account inactive on persistent auth errors

---

*Generated: 2025-12-27*
*Module: Social Media Management*
*Version: 1.0.0*
