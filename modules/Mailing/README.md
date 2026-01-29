# Mailing Module

[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue.svg)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)
[![Status](https://img.shields.io/badge/Status-Production-brightgreen.svg)]()

> Complete email marketing and automation module with multi-level email validation, campaign management, and newsletter capabilities for the Alsernet platform.

**Migrated from:** Acelle Mail - Professional email marketing platform

---

## Table of Contents

- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Architecture](#architecture)
- [API Reference](#api-reference)
- [Artisan Commands](#artisan-commands)
- [Queue System](#queue-system)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [Credits](#credits)
- [License](#license)

---

## Features

### Email Marketing & Campaigns

- **Campaign Management**: Full CRUD operations for email campaigns
- **Rich Text Editor**: Integrated Quill.js editor with customizable templates
- **A/B Testing**: Create and manage A/B tests for campaign optimization
- **RSS Campaigns**: Automatic campaign generation from RSS feeds
- **Campaign Analytics**: Detailed metrics (opens, clicks, bounces, unsubscribes)
- **Scheduled Sending**: Schedule campaigns for future delivery
- **Test Campaigns**: Send test emails before mass distribution

### Subscriber Management

- **Complete CRUD**: Full subscriber lifecycle management
- **Lists & Groups**: Organize subscribers by lists and segments
- **Custom Fields**: Support for custom subscriber attributes
- **Import/Export**: Bulk operations with Excel (.xlsx, .xls) and CSV formats
- **Status Management**: Active, Pending, Unsubscribed, Bounced, Banned states
- **Blacklist Management**: Prevent unwanted email addresses
- **Subscriber Segmentation**: Advanced filtering and targeting

### Multi-Level Email Validation

The module includes a sophisticated 5-level email validation system:

#### Level 1 - Syntax Validation (Free, Instant)
- RFC 5322 compliant validation
- Email format verification
- Character set validation

#### Level 2 - Email Utilities (Free)
- Disposable email detection
- Role-based email detection (admin@, info@, etc.)
- Common typo detection

#### Level 3 - DNS Verification (Free, Cached 24h)
- MX record validation
- A record validation
- Domain existence check

#### Level 4 - SMTP Verification (Free, Slower)
- Mailbox existence verification
- Real-time SMTP handshake
- Catch-all detection

#### Level 5 - External APIs (Paid, Cached)
- ZeroBounce integration
- NeverBounce integration
- Hunter.io integration
- Abstract API integration

**Features:**
- **Scoring System**: 0-100 score with configurable thresholds
- **Bulk Validation**: Asynchronous processing of thousands of emails
- **Cost Tracking**: Monitor API usage and costs
- **Result Caching**: Intelligent caching to reduce API calls
- **Early Exit Optimization**: Stop validation when threshold is met

### Newsletter & Subscriptions

- **Public API**: Endpoints for subscribe/unsubscribe operations
- **Web Forms**: Integration-ready subscription widgets
- **Double Opt-in**: Configurable email confirmation workflow
- **Spam Prevention**: Block disposable and suspicious emails
- **Subscription Tracking**: Complete audit trail
- **Unsubscribe Management**: One-click unsubscribe with tracking

### SMS Marketing

- **SMS Campaigns**: Mass SMS sending capabilities
- **Transactional SMS**: Individual programmable SMS messages
- **Message Tracking**: Delivery status monitoring
- **SMS Templates**: Reusable message templates

### Automation & Workflows

- **Automated Workflows**: Create sophisticated automation sequences
- **Triggers**: Behavior-based automation triggers
- **Auto Triggers**: Time-based and event-based triggers
- **Webhooks**: Integration with external systems
- **Conditional Logic**: Complex automation rules

### Sending Infrastructure

- **Multiple Sending Servers**: SMTP, SendGrid, Amazon SES, Mailgun, SparkPost, etc.
- **Server Rotation**: Round-robin and smart routing
- **Bounce Handling**: Automatic bounce detection and processing
- **Feedback Loop**: ISP complaint handling
- **DKIM/SPF/DMARC**: Email authentication support
- **Quota Management**: Per-server hourly and daily limits

### Analytics & Reporting

- **Real-time Dashboard**: Live metrics and KPIs
- **Campaign Reports**: Detailed performance analytics
- **Subscriber Reports**: Engagement tracking
- **Chart Visualizations**: Chart.js powered graphs
- **Export Reports**: Excel and PDF exports
- **Activity Logging**: Complete audit trail with Spatie Activity Log

### Email Templates & Layouts

- **Template Library**: Pre-built email templates
- **Custom Templates**: Create and manage custom designs
- **Layout System**: Reusable layout components
- **Template Variables**: Dynamic content insertion
- **Version Control**: Template versioning support

---

## System Requirements

### Required

- **PHP**: 8.4 or higher
- **Laravel**: 12.x
- **Database**: PostgreSQL 15+ (recommended) or MySQL 8+
- **Redis**: 7.0+ (for queues, cache, and sessions)
- **Supervisor**: For queue worker management

### Recommended

- **Memory**: 512 MB minimum, 2 GB recommended
- **Storage**: 10 GB minimum for logs and attachments
- **CPU**: 2 cores minimum for queue processing
- **SSL Certificate**: For secure email tracking

### PHP Extensions

```
- OpenSSL
- PDO
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- Redis
- GD or Imagick (for image processing)
- IMAP (for bounce handling)
```

---

## Installation

### Step 1: Clone or Install Module

The module should already be present in `modules/Mailing/`. If not:

```bash
cd modules/
git clone <repository-url> Mailing
```

### Step 2: Install Dependencies

```bash
# Navigate to project root
cd /path/to/your/project

# Install PHP dependencies
composer install

# Install module-specific dependencies
cd modules/Mailing
composer install
```

### Step 3: Run Migrations

```bash
# From project root
php artisan migrate --path=modules/Mailing/database/migrations

# Or run all migrations
php artisan migrate
```

### Step 4: Seed Database (Optional)

```bash
php artisan db:seed --class=Modules\\Mailing\\Database\\Seeders\\DatabaseSeeder
```

### Step 5: Compile Assets

```bash
# Install npm dependencies
npm install

# Build assets
npm run build

# Or for development
npm run dev
```

### Step 6: Configure Queue Workers

See [Queue System](#queue-system) section for detailed configuration.

---

## Configuration

### Environment Variables

Copy the configuration from `.env.example` in the Mailing module to your main `.env` file:

```bash
cat modules/Mailing/.env.example >> .env
```

### Core Configuration

```env
# Mailrelay API Configuration
MAILING_URL=https://your-account.mailrelay.com/api/v1
MAILING_API_KEY=your_api_key_here
MAILING_TIMEOUT=30
MAILING_CONNECT_TIMEOUT=10

# Retry Configuration
MAILING_RETRY_MAX_ATTEMPTS=3
MAILING_RETRY_DELAY=100
MAILING_RETRY_MULTIPLIER=2
```

### Sync Settings

```env
MAILING_AUTO_SYNC=true
MAILING_SYNC_BATCH_INTERVAL=60
MAILING_SYNC_BATCH_SIZE=100
MAILING_SYNC_USE_QUEUE=true
MAILING_SYNC_QUEUE_NAME=mailing
```

### Email Validation

```env
MAILING_VALIDATION_ENABLED=true
MAILING_VALIDATION_MIN_SCORE=70
MAILING_VALIDATION_BLOCK_DISPOSABLE=true
MAILING_VALIDATION_BLOCK_ROLE_BASED=false
```

### Cache Settings

```env
MAILING_CACHE_ENABLED=true
MAILING_CACHE_DRIVER=redis
MAILING_CACHE_TTL_SUBSCRIBERS=3600
MAILING_CACHE_TTL_GROUPS=3600
MAILING_CACHE_TTL_CAMPAIGNS=1800
```

### Webhook Configuration

```env
MAILING_WEBHOOK_ENABLED=true
MAILING_WEBHOOK_PATH=/api/webhooks/mailrelay
MAILING_WEBHOOK_SECRET=your_webhook_secret_here
MAILING_WEBHOOK_VERIFY_SIGNATURE=true
```

### Bounce Handler

```env
MAILING_BOUNCE_HANDLER_ENABLED=false
MAILING_BOUNCE_HANDLER_TYPE=imap
MAILING_BOUNCE_HANDLER_HOST=imap.example.com
MAILING_BOUNCE_HANDLER_PORT=993
MAILING_BOUNCE_HANDLER_USERNAME=bounces@example.com
MAILING_BOUNCE_HANDLER_PASSWORD=password
MAILING_BOUNCE_HANDLER_ENCRYPTION=ssl
```

### Sending Server

```env
MAILING_SENDING_SERVER_DEFAULT_TYPE=smtp
MAILING_SENDING_SERVER_ROTATION=round-robin
MAILING_SENDING_SERVER_QUOTA_HOUR=10000
MAILING_SENDING_SERVER_QUOTA_DAY=100000
```

### External Validation APIs

```env
# ZeroBounce
MAILING_VERIFICATION_SERVICE=zerobounce
MAILING_ZEROBOUNCE_API_KEY=your_key_here

# NeverBounce
MAILING_NEVERBOUNCE_API_KEY=your_key_here

# Hunter.io
MAILING_HUNTER_API_KEY=your_key_here
```

### Complete Configuration Reference

See `modules/Mailing/.env.example` for all 100+ available configuration options.

---

## Usage

### Web Interface

#### Admin Routes

```
/admin/mailing/dashboard              # Dashboard with metrics
/admin/mailing/campaigns              # Campaign management
/admin/mailing/subscribers            # Subscriber management
/admin/mailing/lists                  # Lists & segments
/admin/mailing/templates              # Template management
/admin/mailing/sending-servers        # Sending server configuration
/admin/mailing/bounce-handlers        # Bounce handler setup
/admin/mailing/settings               # Module settings
```

#### Public Routes

```
/newsletter/subscribe                 # Subscription form
/newsletter/unsubscribe               # Unsubscribe page
/newsletter/confirm/{token}           # Email confirmation
```

### Programmatic Usage

#### Create Campaign

```php
use Modules\Mailing\Models\Campaign;
use Modules\Mailing\Enums\CampaignStatus;

$campaign = Campaign::create([
    'name' => 'Newsletter January 2026',
    'subject' => 'Monthly Updates',
    'html_content' => '<html>...</html>',
    'plain_content' => 'Text version...',
    'status' => CampaignStatus::DRAFT,
    'from_name' => 'Company Name',
    'from_email' => 'newsletter@company.com',
]);
```

#### Validate Email

```php
use Modules\Mailing\Services\MailingService;

$mailingService = app(MailingService::class);

// Single validation
$result = $mailingService->validateEmail('user@example.com', [
    'syntax',
    'dns',
    'smtp',
    'external'
]);

if ($result['score'] >= 70) {
    // Email is valid
}

// Bulk validation
$emails = ['user1@example.com', 'user2@example.com', ...];
$mailingService->validateBulkEmails($emails);
```

#### Subscribe User

```php
use Modules\Mailing\Models\Subscriber;
use Modules\Mailing\Enums\SubscriberStatus;

$subscriber = Subscriber::create([
    'email' => 'user@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'status' => SubscriberStatus::ACTIVE,
]);

// Add to list
$subscriber->lists()->attach($listId);
```

#### Send Campaign

```php
use Modules\Mailing\Jobs\SendEmailCampaign;

$campaign = Campaign::find(1);

// Schedule immediate send
SendEmailCampaign::dispatch($campaign);

// Schedule for later
SendEmailCampaign::dispatch($campaign)->delay(now()->addHours(2));
```

#### Import Subscribers

```php
use Modules\Mailing\Jobs\ProcessEmailImportJob;

$file = request()->file('import_file');
$listId = 1;

ProcessEmailImportJob::dispatch($file, $listId, [
    'validate_emails' => true,
    'validation_level' => 3,
    'update_existing' => true,
]);
```

---

## Architecture

### Module Structure

```
modules/Mailing/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── SyncMailingCommand.php
│   ├── Contracts/
│   │   ├── CampaignRendererInterface.php
│   │   └── MailProviderInterface.php
│   ├── Enums/                          # 12 Status Enums
│   │   ├── BounceHandlerStatus.php
│   │   ├── CampaignStatus.php
│   │   ├── SendingServerStatus.php
│   │   └── SubscriberStatus.php
│   ├── Events/                         # 16 Domain Events
│   │   ├── CampaignCreated.php
│   │   ├── CampaignSent.php
│   │   ├── EmailBounced.php
│   │   ├── EmailOpened.php
│   │   ├── SubscriberCreated.php
│   │   └── SubscriberUnsubscribed.php
│   ├── Exceptions/
│   │   ├── MailingException.php
│   │   └── EmailValidationException.php
│   ├── Helpers/                        # 7 Helper Classes
│   │   ├── EmailContentProcessor.php
│   │   ├── LinkTracker.php
│   │   └── UnsubscribeUrlGenerator.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                  # Admin panel controllers
│   │   │   ├── Api/                    # API controllers
│   │   │   └── Public/                 # Public-facing controllers
│   │   ├── Middleware/
│   │   └── Requests/                   # Form validation
│   ├── Jobs/                           # 7 Asynchronous Jobs
│   │   ├── ProcessEmailImportJob.php
│   │   ├── SendEmailCampaign.php
│   │   ├── SyncMailingSubscriberJob.php
│   │   ├── ValidateBulkEmailsJob.php
│   │   └── ValidateEmailJob.php
│   ├── Library/                        # HTML Processing
│   │   └── HtmlHandler/
│   ├── Listeners/                      # 17 Event Listeners
│   │   ├── SendCampaignSentNotification.php
│   │   ├── UpdateSubscriberStatus.php
│   │   └── LogSubscriberActivity.php
│   ├── Mail/                          # 3 Mailable Classes
│   │   ├── RegistrationConfirmationMailer.php
│   │   ├── SubscriptionDoneMailer.php
│   │   └── SettingMailerTest.php
│   ├── Models/                        # 50+ Eloquent Models
│   │   ├── Campaign.php
│   │   ├── Subscriber.php
│   │   ├── Lists.php
│   │   ├── EmailTemplate.php
│   │   ├── SendingServer.php
│   │   ├── BounceHandler.php
│   │   └── ...
│   ├── Notifications/                 # 6 Notification Classes
│   │   ├── CampaignSentNotification.php
│   │   └── SubscriberConfirmationNotification.php
│   ├── Observers/                     # 5 Model Observers
│   │   ├── CampaignObserver.php
│   │   └── SubscriberObserver.php
│   ├── Policies/                      # 13 Authorization Policies
│   │   ├── CampaignPolicy.php
│   │   └── SubscriberPolicy.php
│   ├── Providers/
│   │   ├── MailingServiceProvider.php
│   │   └── EventServiceProvider.php
│   ├── Services/
│   │   └── MailingService.php         # Core service class
│   └── Traits/
│       └── HasUid.php
├── config/
│   ├── mailing.php                    # Main configuration
│   └── validation.php                 # Validation settings
├── database/
│   ├── factories/                     # Model factories
│   ├── migrations/                    # 50+ database migrations
│   └── seeders/
│       └── DatabaseSeeder.php
├── resources/
│   ├── views/                         # Blade templates
│   │   ├── admin/
│   │   ├── public/
│   │   └── emails/
│   ├── css/
│   └── js/
├── routes/
│   ├── admin.php                      # Admin routes
│   ├── api.php                        # API routes
│   ├── public.php                     # Public routes
│   └── web.php                        # Web routes
├── supervisor/                        # Queue worker configs
│   ├── linux/
│   └── mac/
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env.example                       # Configuration template
├── composer.json
├── module.json
└── README.md
```

### Key Models

#### Campaign
Main campaign model with relationships to analytics, links, and segments.

```php
Campaign
├── hasMany: CampaignAnalytics
├── hasMany: CampaignLink
├── hasMany: TrackingLog
├── belongsTo: Lists
├── belongsTo: EmailTemplate
└── belongsToMany: Segments
```

#### Subscriber
Subscriber model with custom fields and list relationships.

```php
Subscriber
├── hasMany: SubscriberField (custom fields)
├── belongsToMany: Lists
├── belongsToMany: Segments
├── hasMany: TrackingLog
└── hasMany: UnsubscribeLog
```

#### Lists
Email list model with subscribers and segments.

```php
Lists
├── hasMany: Subscriber
├── hasMany: Segment
├── hasMany: CustomField
└── belongsTo: User (owner)
```

#### SendingServer
Sending server configuration with quota management.

```php
SendingServer
├── hasMany: Campaign (sent through)
├── hasOne: SendingServerQuota
└── belongsTo: User
```

### Core Services

#### MailingService
Main orchestration service for all mailing operations.

**Key Methods:**
- `validateEmail(string $email, array $levels): array`
- `validateBulkEmails(array $emails): void`
- `createCampaign(array $data): Campaign`
- `sendCampaign(Campaign $campaign): void`
- `importSubscribers(File $file, int $listId): ImportJob`
- `syncWithMailrelay(): void`

### Database Schema

The module uses 50+ tables with the `mailing_` prefix:

**Core Tables:**
- `mailing_campaigns` - Email campaigns
- `mailing_subscribers` - Subscriber records
- `mailing_lists` - Email lists
- `mailing_segments` - Subscriber segments
- `mailing_email_templates` - Email templates
- `mailing_sending_servers` - SMTP/API server configs
- `mailing_bounce_handlers` - Bounce handling configs

**Analytics Tables:**
- `mailing_campaign_analytics` - Campaign metrics
- `mailing_tracking_logs` - Open/click tracking
- `mailing_bounces` - Bounce records
- `mailing_unsubscribe_events` - Unsubscribe tracking

**Automation Tables:**
- `mailing_automation2s` - Automation workflows
- `mailing_auto_triggers` - Trigger definitions
- `mailing_cronjob_settings` - Scheduled tasks

---

## API Reference

### Authentication

All API endpoints require authentication using Laravel Sanctum tokens.

```bash
# Include token in header
Authorization: Bearer {your-token}
```

### Newsletter API

#### Subscribe

```http
POST /api/newsletter/subscribe
Content-Type: application/json

{
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "list_id": 1,
    "custom_fields": {
        "company": "Acme Inc",
        "phone": "+1234567890"
    }
}
```

**Response:**
```json
{
    "success": true,
    "message": "Subscription successful",
    "data": {
        "subscriber_id": 123,
        "status": "pending",
        "confirmation_sent": true
    }
}
```

#### Unsubscribe

```http
POST /api/newsletter/unsubscribe
Content-Type: application/json

{
    "email": "user@example.com",
    "reason": "No longer interested"
}
```

#### Check Status

```http
GET /api/newsletter/status?email=user@example.com
```

### Campaign API

#### List Campaigns

```http
GET /api/campaigns?page=1&per_page=20
```

#### Create Campaign

```http
POST /api/campaigns
Content-Type: application/json

{
    "name": "Newsletter January 2026",
    "subject": "Monthly Updates",
    "from_name": "Company Name",
    "from_email": "news@company.com",
    "reply_to": "support@company.com",
    "html_content": "<html>...</html>",
    "plain_content": "Text version...",
    "list_id": 1,
    "template_id": 5,
    "scheduled_at": "2026-01-30 10:00:00"
}
```

#### Get Campaign

```http
GET /api/campaigns/{id}
```

#### Update Campaign

```http
PATCH /api/campaigns/{id}
Content-Type: application/json

{
    "subject": "Updated Subject",
    "scheduled_at": "2026-02-01 14:00:00"
}
```

#### Send Campaign

```http
POST /api/campaigns/{id}/send
```

#### Get Campaign Analytics

```http
GET /api/campaigns/{id}/analytics
```

**Response:**
```json
{
    "campaign_id": 1,
    "sent": 10000,
    "delivered": 9850,
    "opens": 3200,
    "unique_opens": 2950,
    "clicks": 850,
    "unique_clicks": 720,
    "bounces": 150,
    "unsubscribes": 45,
    "complaints": 5,
    "open_rate": 32.5,
    "click_rate": 8.85,
    "bounce_rate": 1.52
}
```

### Validation API

#### Validate Single Email

```http
POST /api/validation/validate
Content-Type: application/json

{
    "email": "user@example.com",
    "levels": ["syntax", "dns", "smtp", "external"]
}
```

**Response:**
```json
{
    "email": "user@example.com",
    "valid": true,
    "score": 95,
    "details": {
        "syntax": {"valid": true, "score": 100},
        "dns": {"valid": true, "mx_exists": true},
        "smtp": {"valid": true, "mailbox_exists": true},
        "external": {"valid": true, "disposable": false}
    }
}
```

#### Validate Bulk

```http
POST /api/validation/validate-bulk
Content-Type: application/json

{
    "emails": [
        "user1@example.com",
        "user2@example.com",
        "user3@example.com"
    ],
    "levels": ["syntax", "dns"]
}
```

### Import API

#### Upload File

```http
POST /api/imports/upload
Content-Type: multipart/form-data

file: <binary>
list_id: 1
validate_emails: true
validation_level: 3
```

#### Get Import Status

```http
GET /api/imports/{id}/status
```

#### Get Import Report

```http
GET /api/imports/{id}/report
```

---

## Artisan Commands

### Sync Command

Synchronize data with Mailrelay API.

```bash
# Basic sync
php artisan mailing:sync

# Force sync (ignore cache)
php artisan mailing:sync --force

# Dry run (preview changes)
php artisan mailing:sync --dry-run

# Sync specific entity
php artisan mailing:sync --entity=subscribers
php artisan mailing:sync --entity=campaigns
php artisan mailing:sync --entity=groups
```

**Options:**
- `--force` - Bypass cache and force synchronization
- `--dry-run` - Show what would be synced without making changes
- `--entity=` - Sync specific entity type (subscribers, campaigns, groups)

---

## Queue System

The Mailing module uses Laravel queues extensively for asynchronous processing.

### Queue Configuration

Add to your `.env`:

```env
QUEUE_CONNECTION=redis

MAILING_SYNC_QUEUE_NAME=mailing
MAILING_WEBHOOK_QUEUE_NAME=webhooks
MAILING_IMPORT_QUEUE=imports
MAILING_AUTOMATION_QUEUE=automation
MAILING_CLICK_TRACKING_QUEUE=tracking
```

### Supervisor Configuration

#### Linux

```bash
# Copy configuration
sudo cp modules/Mailing/supervisor/linux/mailing-queue.conf /etc/supervisor/conf.d/

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start mailing-queue:*

# Check status
sudo supervisorctl status mailing-queue:*
```

**Configuration file** (`mailing-queue.conf`):

```ini
[program:mailing-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --queue=mailing,webhooks,imports,automation,tracking --tries=3 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/mailing-queue.log
stopwaitsecs=3600
```

#### macOS

```bash
# Copy plist file
cp modules/Mailing/supervisor/mac/mailing-queue.plist ~/Library/LaunchAgents/

# Load service
launchctl load ~/Library/LaunchAgents/mailing-queue.plist

# Start service
launchctl start mailing-queue

# Check status
launchctl list | grep mailing
```

### Manual Queue Processing

For development or testing:

```bash
# Process mailing queue
php artisan queue:work redis --queue=mailing --tries=3

# Process all queues
php artisan queue:work redis --queue=mailing,webhooks,imports,automation,tracking

# Process once (for testing)
php artisan queue:work --once

# Listen for new jobs
php artisan queue:listen
```

### Queue Monitoring

Use Laravel Horizon (if installed):

```bash
php artisan horizon
```

Access dashboard at: `http://your-app.com/horizon`

---

## Testing

### Run All Tests

```bash
# From project root
php artisan test modules/Mailing/tests

# With coverage
php artisan test modules/Mailing/tests --coverage
```

### Run Specific Test Suites

```bash
# Feature tests
php artisan test modules/Mailing/tests/Feature

# Unit tests
php artisan test modules/Mailing/tests/Unit

# Specific test file
php artisan test modules/Mailing/tests/Feature/CampaignTest.php

# Specific test method
php artisan test --filter=test_can_create_campaign
```

### Test Categories

#### Feature Tests
- Campaign CRUD operations
- Subscriber management
- Email validation flows
- Import processing
- API endpoints
- Authentication & authorization

#### Unit Tests
- Email validation logic
- Helper functions
- Service methods
- Model relationships
- Enum behaviors

### Example Test

```php
namespace Modules\Mailing\Tests\Feature;

use Tests\TestCase;
use Modules\Mailing\Models\Campaign;
use Modules\Mailing\Enums\CampaignStatus;

class CampaignTest extends TestCase
{
    public function test_can_create_campaign()
    {
        $data = [
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'html_content' => '<p>Test content</p>',
            'status' => CampaignStatus::DRAFT,
        ];

        $campaign = Campaign::create($data);

        $this->assertDatabaseHas('mailing_campaigns', [
            'name' => 'Test Campaign',
        ]);
    }
}
```

### Test Database

Configure separate test database in `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="pgsql"/>
<env name="DB_DATABASE" value="mailing_test"/>
```

---

## Troubleshooting

### Common Issues

#### 1. Queue Workers Not Processing Jobs

**Symptoms:**
- Jobs stuck in queue
- Campaigns not sending
- Imports not processing

**Solutions:**

```bash
# Check if workers are running
sudo supervisorctl status mailing-queue:*

# Restart workers
sudo supervisorctl restart mailing-queue:*

# Check queue status
php artisan queue:monitor mailing,webhooks,imports

# Clear failed jobs
php artisan queue:flush
php artisan queue:retry all
```

#### 2. Email Validation Failing

**Symptoms:**
- All emails marked as invalid
- External API errors
- SMTP timeouts

**Solutions:**

```bash
# Check validation configuration
php artisan config:cache

# Test single email
php artisan tinker
>>> app(\Modules\Mailing\Services\MailingService::class)->validateEmail('test@example.com')

# Check API credentials
php artisan config:show mailing.validation

# Disable problematic levels
MAILING_VALIDATION_ENABLED=true
MAILING_VALIDATION_SMTP_ENABLED=false
```

#### 3. Campaigns Not Sending

**Symptoms:**
- Campaigns stuck in "sending" status
- No emails delivered

**Solutions:**

```bash
# Check sending server configuration
SELECT * FROM mailing_sending_servers WHERE status = 'active';

# Test sending server connection
php artisan mailing:test-server {server_id}

# Check quota limits
SELECT * FROM mailing_sending_server_quotas;

# Review logs
tail -f storage/logs/laravel.log | grep -i mailing
```

#### 4. Import Jobs Failing

**Symptoms:**
- Import status shows "failed"
- Partial imports
- Memory errors

**Solutions:**

```bash
# Increase PHP memory limit
php -d memory_limit=512M artisan queue:work

# Check file permissions
ls -la storage/app/imports/

# Reduce batch size
MAILING_IMPORT_BATCH_SIZE=500

# Check failed jobs table
SELECT * FROM failed_jobs WHERE queue = 'imports';
```

#### 5. Webhook Not Receiving Events

**Symptoms:**
- No webhook events logged
- Tracking not updating

**Solutions:**

```bash
# Verify webhook URL is accessible
curl -X POST http://your-app.com/api/webhooks/mailrelay

# Check webhook secret
MAILING_WEBHOOK_SECRET=your_secret

# Review webhook logs
tail -f storage/logs/webhook.log

# Test webhook endpoint
php artisan route:list | grep webhook
```

### Debug Mode

Enable debug mode for detailed logging:

```env
MAILING_DEBUG=true
MAILING_LOG_REQUESTS=true
MAILING_LOG_RESPONSES=true
```

### Clear Caches

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear specific cache
php artisan cache:forget mailing:*
```

### Database Issues

```bash
# Reset migrations
php artisan migrate:rollback --path=modules/Mailing/database/migrations
php artisan migrate --path=modules/Mailing/database/migrations

# Fresh install
php artisan migrate:fresh --path=modules/Mailing/database/migrations --seed
```

### Performance Optimization

```bash
# Optimize application
php artisan optimize

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Index database tables
php artisan db:index mailing_campaigns
php artisan db:index mailing_subscribers
```

---

## Credits

### Original Software

This module is migrated from **Acelle Mail** - a professional email marketing web application.

**Acelle Mail**
- Website: https://acelle.io
- License: Extended License
- Original Authors: Acelle Co., Ltd.

### Migration Credits

- **Alsernet Development Team**
- **Migration Date**: January 2026
- **Target Framework**: Laravel 12.x
- **Database**: PostgreSQL (converted from MySQL)

### Technologies Used

- **Laravel Framework** 12.x - PHP framework
- **PostgreSQL** 15+ - Primary database
- **Redis** 7+ - Cache and queue driver
- **Quill.js** - Rich text editor
- **Chart.js** - Analytics visualizations
- **Bootstrap** 5.3 - Frontend framework
- **Font Awesome** 6 - Icon library

### External Services

- **ZeroBounce** - Email validation API
- **NeverBounce** - Email validation API
- **Hunter.io** - Email validation API
- **Abstract API** - Email validation API

### Laravel Packages

- `spatie/laravel-activitylog` - Activity logging
- `maatwebsite/excel` - Excel import/export
- `barryvdh/laravel-dompdf` - PDF generation
- `guzzlehttp/guzzle` - HTTP client

---

## License

This module is licensed under the **MIT License**.

```
MIT License

Copyright (c) 2026 Alsernet

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## Support & Contributing

### Get Support

- **Email**: dev@alsernet.com
- **Documentation**: `/modules/Mailing/docs/`
- **Issue Tracker**: Internal project management

### Contributing

This is a private module for the Alsernet platform. For feature requests or bug reports, please contact the development team.

### Roadmap

Planned features for future releases:

- [ ] Integration with additional email service providers (SendGrid, Mailgun, Amazon SES)
- [ ] Drag-and-drop email template builder
- [ ] Advanced segmentation with machine learning
- [ ] Multi-language support (full i18n)
- [ ] Mobile app for campaign management
- [ ] Enhanced A/B testing with statistical analysis
- [ ] SMS integration with multiple providers
- [ ] Social media integration
- [ ] Advanced automation workflows (visual builder)
- [ ] Real-time collaboration features

---

**Last Updated**: January 29, 2026
**Version**: 1.0.0
**Module Status**: Production Ready
