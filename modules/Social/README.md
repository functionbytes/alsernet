# Social Media Management Module

Enterprise-grade social media management system for Laravel with AI-powered content generation, multi-channel publishing, and advanced analytics.

## 📋 Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Testing](#testing)
- [Advanced Features](#advanced-features)
- [API Reference](#api-reference)
- [Troubleshooting](#troubleshooting)

## ✨ Features

### Core Features
- **Multi-Channel Publishing** - Publish to Facebook, Instagram, Twitter, LinkedIn, and TikTok from a single dashboard
- **Content Scheduling** - Schedule posts with calendar view and timezone support
- **Campaign Management** - Organize posts into campaigns with color-coding and tracking
- **Media Library** - Centralized media management with automatic optimization for each network
- **Templates** - Reusable content templates with variable placeholders
- **Labels** - Tag and categorize posts for better organization
- **Bulk Import** - Import posts from CSV/Excel files
- **RSS Auto-Publishing** - Automatically create posts from RSS feeds

### AI-Powered Features
- **AI Content Generation** - Generate engaging content using OpenAI GPT-4
- **Smart Hashtags** - AI-suggested hashtags based on content
- **Content Improvement** - Enhance existing content with AI
- **Content Variations** - Generate multiple variations for A/B testing
- **Auto-Translation** - Translate posts to 11 languages (Google Translate API)

### Analytics & Reporting
- **Real-time Analytics** - Track likes, comments, shares, and engagement
- **Performance Dashboard** - Visual analytics with charts and graphs
- **Export Reports** - Export to PDF and Excel
- **A/B Testing** - Compare post variations and determine winners
- **Engagement Tracking** - Monitor post performance across networks

### Advanced Features
- **Search Integration** - Full-text search powered by Laravel Scout
- **Activity Logging** - Track all changes with Spatie Activity Log
- **Granular Permissions** - 40+ permissions with role-based access
- **Video Processing** - Automatic video optimization and thumbnail generation
- **Watermarking** - Add watermarks to images automatically
- **QR Codes** - Generate trackable QR codes for campaigns
- **URL Shortening** - Built-in URL shortener with click tracking
- **Multi-language** - Translate posts to multiple languages
- **Queue Processing** - Background jobs for heavy operations
- **Telegram Notifications** - Get notified about post approvals

## 🚀 Installation

### 1. Requirements

- PHP 8.4+
- Laravel 12.x
- MySQL/MariaDB or PostgreSQL
- Redis (for queues)
- FFmpeg (for video processing)
- Node.js & NPM

### 2. Install Module

The module is already installed via `nwidart/laravel-modules`.

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Seed Permissions

```bash
php artisan db:seed --class=Modules\\Social\\Database\\Seeders\\SocialPermissionsSeeder
```

### 5. (Optional) Seed Demo Data

```bash
php artisan db:seed --class=Modules\\Social\\Database\\Seeders\\SocialDemoDataSeeder
```

### 6. Compile Assets

```bash
npm run build
```

## ⚙️ Configuration

### 1. Environment Variables

Add these to your `.env` file:

```env
# AI Content Generation
OPENAI_API_KEY=your_openai_api_key
OPENAI_ORGANIZATION=your_org_id

# Translation
GOOGLE_TRANSLATE_API_KEY=your_google_api_key

# Search
SCOUT_DRIVER=database

# Facebook/Instagram
FACEBOOK_APP_ID=your_app_id
FACEBOOK_APP_SECRET=your_app_secret

# Twitter/X
TWITTER_API_KEY=your_api_key
TWITTER_API_SECRET=your_api_secret
TWITTER_ACCESS_TOKEN=your_access_token
TWITTER_ACCESS_SECRET=your_access_secret

# LinkedIn
LINKEDIN_CLIENT_ID=your_client_id
LINKEDIN_CLIENT_SECRET=your_client_secret

# Notifications
TELEGRAM_BOT_TOKEN=your_bot_token
```

### 2. Configuration File

Customize settings in `config/social.php`:

```php
return [
    'ai' => [
        'enabled' => true,
        'provider' => 'openai',
        'default_tone' => 'professional',
    ],
    'media' => [
        'max_size' => 20480, // KB
        'disk' => 'public',
    ],
    // ... more options
];
```

### 3. Storage Setup

Create required storage directories:

```bash
php artisan storage:link
```

### 4. Queue Configuration

Make sure your queue worker is running:

```bash
php artisan queue:work --queue=default,social
```

## 📖 Usage

### Publishing a Post

```php
use Modules\Social\Models\Post;
use Modules\Social\Enums\PostStatus;

$post = Post::create([
    'account_id' => auth()->user()->account_id,
    'social_account_id' => $socialAccount->id,
    'content' => 'Your post content here',
    'status' => PostStatus::DRAFT,
    'scheduled_at' => now()->addHours(2),
    'created_by' => auth()->id(),
]);

// Add media
$post->addMedia($imagePath)->toMediaCollection('images');

// Add labels
$post->labels()->attach([1, 2, 3]);

// Publish
$post->publish();
```

### Using AI Content Generator

```php
use Modules\Social\Services\AIContentGenerator;

$generator = app(AIContentGenerator::class);

// Generate content
$content = $generator->generateContent(
    topic: 'Laravel development tips',
    tone: 'professional',
    maxLength: 280
);

// Suggest hashtags
$hashtags = $generator->suggestHashtags($content);

// Improve content
$improved = $generator->improveContent($content, 'casual');

// Generate variations
$variations = $generator->generateVariations($content, 5);
```

### Translating Posts

```php
use Modules\Social\Services\TranslationService;
use Modules\Social\Jobs\TranslatePostJob;

$translator = app(TranslationService::class);

// Translate to multiple languages
$languages = ['es', 'fr', 'de'];

// Sync translation
$translations = $translator->translatePost($post, $languages);

// Or dispatch to queue
TranslatePostJob::dispatch($post, $languages);
```

### Exporting Data

```php
use Modules\Social\Exports\PostsExport;
use Maatwebsite\Excel\Facades\Excel;

// Export to Excel
return Excel::download(
    new PostsExport(auth()->user()->account_id),
    'posts.xlsx'
);

// Export to PDF
$pdf = PDF::loadView('social::exports.posts-pdf', compact('posts'));
return $pdf->download('posts.pdf');
```

## 🧪 Testing

Run the test suite:

```bash
# All tests
php artisan test

# Social module tests only
php artisan test --filter=Social

# Specific test
php artisan test tests/Unit/Modules/Social/Tests/Unit/AIContentGeneratorTest.php
```

## 🎯 Advanced Features

### A/B Testing

```php
use Modules\Social\Models\AbTest;

$test = AbTest::create([
    'account_id' => auth()->user()->account_id,
    'variant_a_id' => $postA->id,
    'variant_b_id' => $postB->id,
    'duration_days' => 7,
    'status' => 'running',
]);

// After duration, determine winner
$winner = $test->determineWinner();
```

### Bulk Import

```bash
# Via UI
1. Navigate to /admin/social/bulk-import/create
2. Upload CSV/Excel file with columns: content, network, scheduled_at, campaign_id
3. Review and import

# Via Code
use Modules\Social\Jobs\ProcessBulkImportJob;

ProcessBulkImportJob::dispatch($import);
```

### RSS Auto-Publishing

```php
use Modules\Social\Models\RssFeed;

$feed = RssFeed::create([
    'account_id' => auth()->user()->account_id,
    'social_account_id' => $socialAccount->id,
    'url' => 'https://example.com/feed.xml',
    'auto_post' => true,
    'post_frequency' => 'daily',
]);

// Manually fetch
$feed->fetchAndCreate();
```

### QR Code Generation

```php
use Modules\Social\Services\QRCodeService;

$qrService = app(QRCodeService::class);

$qrCode = $qrService->generateForCampaign($campaign);

// Access QR code
echo $qrCode->qr_image_url; // Public URL to QR code image
echo $qrCode->public_url;    // Tracking URL

// Track scans
$analytics = $qrService->getAnalytics($qrCode);
```

## 📚 API Reference

### Routes

All routes are prefixed with `/admin/social` and require authentication:

```php
GET    /admin/social                          # Dashboard
GET    /admin/social/publishing               # Posts list
GET    /admin/social/publishing/create        # Create post
GET    /admin/social/publishing/calendar      # Calendar view
POST   /admin/social/ai/generate              # AI content generation
POST   /admin/social/ai/hashtags              # Hashtag suggestions
GET    /admin/social/analytics                # Analytics dashboard
GET    /admin/social/export/posts/excel       # Export posts to Excel
GET    /admin/social/export/posts/pdf         # Export posts to PDF
```

### Permissions

Required permissions for common actions:

- `view-posts` - View posts list
- `create-posts` - Create new posts
- `edit-posts` - Edit existing posts
- `delete-posts` - Delete posts
- `publish-posts` - Publish posts to social networks
- `approve-posts` - Approve pending posts
- `use-ai-generator` - Access AI content features
- `translate-posts` - Translate posts
- `view-analytics` - View analytics dashboard
- `export-analytics` - Export reports

### Events

The module fires these events:

- `PostCreated` - When a post is created
- `PostPublished` - When a post is published
- `PostScheduled` - When a post is scheduled
- `PostFailed` - When publishing fails
- `CampaignCreated` - When a campaign is created

## 🐛 Troubleshooting

### AI Content Generation Not Working

**Problem:** AI features return errors

**Solution:**
1. Verify `OPENAI_API_KEY` is set in `.env`
2. Check API quota and billing
3. Review logs: `php artisan tail`

### Posts Not Publishing

**Problem:** Scheduled posts stay in "scheduled" status

**Solution:**
1. Ensure queue worker is running: `php artisan queue:work`
2. Check social account tokens are valid
3. Verify network credentials in database

### Translation Fails

**Problem:** Translation service returns errors

**Solution:**
1. Check `GOOGLE_TRANSLATE_API_KEY` is configured
2. Verify API is enabled in Google Cloud Console
3. Check API quotas and billing

### Video Processing Issues

**Problem:** Videos not processing or no thumbnails

**Solution:**
1. Verify FFmpeg is installed: `ffmpeg -version`
2. Check storage permissions
3. Increase PHP `max_execution_time` and `memory_limit`

### Search Not Working

**Problem:** Search returns no results

**Solution:**
1. Index posts: `php artisan scout:import "Modules\Social\Models\Post"`
2. Check Scout driver configuration
3. Clear Scout index: `php artisan scout:flush "Modules\Social\Models\Post"`

## 📝 License

This module is part of the Channels application and follows the same license.

## 🤝 Support

For issues, please contact support or create an issue in the project repository.

---

Made with ❤️ by the Channels Team
