# Mailing Module - Configuration Migration Report

**Generated:** 2026-01-29
**Source:** Acelle Mail Configuration Files
**Destination:** `modules/Mailing/config/mailing.php`
**Status:** ✅ Completed

---

## Executive Summary

This document details the migration of email marketing configurations from Acelle Mail system to the Alsernet Mailing Module. The configuration has been consolidated into a single, comprehensive configuration file that maintains Mailrelay API integration while adding extensive Acelle Mail features.

### Migration Scope

- **Files Analyzed:** All Acelle Mail configuration files from `/Users/functionbytes/Function/Coding/acelle/config/`
- **Files Migrated:** Mailing-specific configurations only
- **Files Excluded:** Standard Laravel configs (app.php, database.php, queue.php) - already exist in Alsernet
- **Total Configuration Sections:** 26 major sections

---

## Configuration Structure

### 1. Core Mailrelay Integration (Existing)

**Status:** ✅ Preserved and Enhanced

Configuration sections already present in the module:

| Section | Description | Environment Variables |
|---------|-------------|----------------------|
| **API Configuration** | Mailrelay API connection settings | `MAILING_URL`, `MAILING_API_KEY`, `MAILING_TIMEOUT` |
| **Retry Configuration** | Automatic retry mechanism for failed API calls | `MAILING_RETRY_MAX_ATTEMPTS`, `MAILING_RETRY_DELAY` |
| **Sync Settings** | Subscriber synchronization with Mailrelay | `MAILING_AUTO_SYNC`, `MAILING_SYNC_BATCH_SIZE` |
| **Webhook Settings** | Incoming webhook handling from Mailrelay | `MAILING_WEBHOOK_ENABLED`, `MAILING_WEBHOOK_SECRET` |
| **Cache Settings** | API response caching configuration | `MAILING_CACHE_ENABLED`, `MAILING_CACHE_TTL_*` |
| **Campaign Settings** | Basic campaign management | `MAILING_CAMPAIGN_TRACK_ANALYTICS` |
| **Logging Settings** | Request/response logging | `MAILING_LOGGING_ENABLED`, `MAILING_LOGGING_LEVEL` |
| **Rate Limiting** | API rate limiting protection | `MAILING_RATE_LIMIT_MAX_REQUESTS` |
| **Validation Settings** | Email validation before sync | `MAILING_VALIDATION_ENABLED`, `MAILING_VALIDATION_MIN_SCORE` |
| **Error Handling** | Error notification configuration | `MAILING_THROW_EXCEPTIONS`, `MAILING_NOTIFY_ON_ERROR` |

### 2. Acelle Mail Extended Features (Added)

**Status:** ✅ Newly Added

Configuration sections migrated from Acelle Mail:

#### A. Email Tracking (tracking)

Advanced email interaction tracking beyond basic Mailrelay analytics.

**Purpose:** Track opens, clicks, unsubscribes with custom tracking domains

**Key Variables:**
- `MAILING_TRACK_OPENS` - Enable open tracking
- `MAILING_TRACK_CLICKS` - Enable click tracking
- `MAILING_TRACKING_DOMAIN` - Custom tracking domain
- `MAILING_TRACKING_HTTPS` - Use HTTPS for tracking URLs

**Use Cases:**
- Custom tracking pixel implementation
- Link click analytics
- Unsubscribe tracking separate from Mailrelay

#### B. Subscriber Import (import)

Bulk subscriber import from CSV/Excel files.

**Purpose:** Mass subscriber uploads with validation and deduplication

**Key Variables:**
- `MAILING_IMPORT_MAX_FILE_SIZE` - Maximum upload size (MB)
- `MAILING_IMPORT_BATCH_SIZE` - Records per batch
- `MAILING_IMPORT_CSV_DELIMITER` - CSV field separator
- `MAILING_IMPORT_SKIP_DUPLICATES` - Skip duplicate emails

**File Type Support:**
- CSV (.csv, .txt)
- Excel (.xlsx, .xls)

**Features:**
- Batch processing for large files
- Email validation during import
- Duplicate detection
- Queue-based processing

#### C. Sending Servers (sending_servers)

Multi-server email sending infrastructure.

**Purpose:** Configure multiple SMTP/API servers with rotation and health checks

**Key Variables:**
- `MAILING_SENDING_SERVER_DEFAULT_TYPE` - Server type (smtp, sendgrid, ses, etc.)
- `MAILING_SENDING_SERVER_ROTATION` - Rotation method (round-robin, random, percentage)
- `MAILING_SENDING_SERVER_QUOTA_HOUR` - Hourly sending limit per server
- `MAILING_SENDING_SERVER_HEALTH_CHECK` - Enable health monitoring

**Rotation Methods:**
- **round-robin:** Sequential server selection
- **random:** Random server selection
- **percentage:** Weighted distribution

**Health Monitoring:**
- Automatic health checks every 15 minutes (configurable)
- Auto-disable after 5 consecutive failures
- Connection timeout protection

#### D. Bounce Handler (bounce_handler)

Automated bounce email processing.

**Purpose:** Monitor bounce mailbox and automatically handle hard/soft bounces

**Key Variables:**
- `MAILING_BOUNCE_HANDLER_ENABLED` - Enable bounce processing
- `MAILING_BOUNCE_HANDLER_TYPE` - Handler type (imap, pop3, webhook)
- `MAILING_BOUNCE_HANDLER_HOST` - IMAP/POP3 server
- `MAILING_BOUNCE_HARD_ACTION` - Action for hard bounces (unsubscribe, delete, mark_inactive)
- `MAILING_BOUNCE_SOFT_THRESHOLD` - Soft bounce limit before hard bounce

**Bounce Types:**
- **Hard Bounce:** Permanent delivery failure (invalid email)
- **Soft Bounce:** Temporary failure (mailbox full, server down)

**Actions:**
- Automatic unsubscribe on hard bounce
- Track soft bounce count
- Convert soft to hard after threshold

**Processing:**
- Queue-based asynchronous processing
- Configurable processing interval (default: 5 minutes)
- Batch processing (max 500 per cycle)

#### E. Feedback Loop Handler (feedback_loop)

Spam complaint processing (FBL).

**Purpose:** Process spam complaints from ISPs and automatically unsubscribe complainants

**Key Variables:**
- `MAILING_FEEDBACK_LOOP_ENABLED` - Enable FBL processing
- `MAILING_FEEDBACK_LOOP_TYPE` - Handler type (imap, pop3, webhook)
- `MAILING_FEEDBACK_LOOP_ACTION` - Action on complaint (unsubscribe, blacklist)

**ISP Support:**
- Gmail feedback loop
- Yahoo complaint feedback
- Outlook/Hotmail FBL
- Custom ISP integrations via webhook

**Benefits:**
- Maintain sender reputation
- Reduce spam complaints
- Automatic complainant removal

#### F. Template Settings (templates)

Email template management and versioning.

**Purpose:** Configure template storage, caching, and customization limits

**Key Variables:**
- `MAILING_TEMPLATE_CACHE_ENABLED` - Cache compiled templates
- `MAILING_TEMPLATE_ALLOW_CUSTOM_CSS` - Allow custom CSS in templates
- `MAILING_TEMPLATE_ALLOW_CUSTOM_JS` - Allow custom JavaScript
- `MAILING_TEMPLATE_VERSIONING` - Enable template version control

**Template Features:**
- Blade/Twig template support
- Custom CSS styling
- JavaScript restrictions for security
- Version history tracking
- Maximum template size limits (500KB default)

**Storage:**
- Configurable storage path
- Multi-language template support
- Template caching (60 minutes default TTL)

#### G. Automation Settings (automation)

Automated email workflow configuration.

**Purpose:** Configure automated email sequences and triggers

**Key Variables:**
- `MAILING_AUTOMATION_ENABLED` - Enable automation engine
- `MAILING_AUTOMATION_QUEUE` - Queue for automation jobs
- `MAILING_AUTOMATION_MAX_DEPTH` - Maximum nested automation levels
- `MAILING_AUTOMATION_TRIGGERS_ENABLED` - Enable event triggers

**Automation Types:**
- Welcome email series
- Abandoned cart recovery
- Birthday/anniversary emails
- Re-engagement campaigns
- Post-purchase follow-ups

**Trigger Events:**
- Subscriber signup
- Email opens/clicks
- Time-based delays
- Custom event hooks

**Processing:**
- Queue-based execution
- 1-minute processing interval
- Maximum 100 automations per cycle
- Maximum 5 levels of nested automations

#### H. Segmentation Settings (segmentation)

Subscriber segmentation and targeting.

**Purpose:** Create dynamic subscriber segments based on conditions

**Key Variables:**
- `MAILING_SEGMENTATION_ENABLED` - Enable segmentation
- `MAILING_SEGMENTATION_CACHE_TTL` - Segment cache duration
- `MAILING_SEGMENTATION_REALTIME_THRESHOLD` - Max size for real-time calculation
- `MAILING_SEGMENTATION_MAX_CONDITIONS` - Maximum conditions per segment

**Segmentation Criteria:**
- Demographic data (age, location, gender)
- Behavioral data (opens, clicks, purchases)
- Custom field values
- Engagement level
- Subscription date

**Performance:**
- Cached segment results (30 minutes default)
- Real-time calculation for segments under 10,000 subscribers
- Queue-based recalculation for large segments
- Maximum 20 conditions per segment

#### I. List Settings (lists)

Mailing list configuration and management.

**Purpose:** Configure default list behaviors and limits

**Key Variables:**
- `MAILING_LIST_DOUBLE_OPTIN_DEFAULT` - Enable double opt-in by default
- `MAILING_LIST_SEND_WELCOME_EMAIL_DEFAULT` - Auto-send welcome emails
- `MAILING_LIST_MAX_PER_USER` - Maximum lists per user (100 default)
- `MAILING_LIST_CLEANUP_ENABLED` - Auto-remove inactive subscribers

**Subscription Flow:**
- Single opt-in (immediate subscription)
- Double opt-in (confirmation email required)
- Welcome email automation
- Unsubscribe notifications

**List Management:**
- Per-user list limits
- List verification
- Inactive subscriber cleanup (configurable threshold)
- Automatic list hygiene

#### J. Email Verification (verification)

Advanced email verification beyond basic validation.

**Purpose:** Verify email deliverability using external verification services

**Key Variables:**
- `MAILING_VERIFICATION_ADVANCED_ENABLED` - Enable advanced verification
- `MAILING_VERIFICATION_SERVICE` - Verification service (internal, zerobounce, neverbounce)
- `MAILING_VERIFICATION_API_KEY` - Service API key
- `MAILING_VERIFICATION_AUTO_IMPORT` - Auto-verify on import

**Verification Services:**
- **Internal:** Basic MX record validation
- **ZeroBounce:** Paid service with high accuracy
- **NeverBounce:** Paid service with bulk verification

**Verification Checks:**
- MX record validation
- SMTP server verification
- Disposable email detection
- Role-based email detection
- Catch-all domain detection

**Results Cache:**
- 30-day cache for verified emails
- Automatic cache invalidation
- Batch verification support

#### K. Deliverability Settings (deliverability)

Email authentication and deliverability optimization.

**Purpose:** Configure DKIM, SPF, DMARC for better inbox placement

**Key Variables:**
- `MAILING_DKIM_ENABLED` - Enable DKIM signing
- `MAILING_DKIM_SELECTOR` - DKIM selector (default)
- `MAILING_DKIM_PRIVATE_KEY_PATH` - Path to private key
- `MAILING_SPF_ENABLED` - Enable SPF checking
- `MAILING_DMARC_ENABLED` - Enable DMARC

**Email Authentication:**
- **DKIM (DomainKeys Identified Mail):** Cryptographic email signature
- **SPF (Sender Policy Framework):** Authorized sender verification
- **DMARC (Domain-based Message Authentication):** Policy enforcement

**Custom Headers:**
- List-Unsubscribe header (RFC 8058)
- Precedence: bulk header
- Custom organizational headers

**Benefits:**
- Improved inbox placement
- Reduced spam folder delivery
- Better sender reputation
- ISP trust building

#### L. Reporting Settings (reporting)

Analytics and reporting configuration.

**Purpose:** Configure report generation, caching, and export formats

**Key Variables:**
- `MAILING_REPORTING_ENABLED` - Enable reporting engine
- `MAILING_REPORTING_REALTIME_STATS` - Enable real-time statistics
- `MAILING_REPORTING_AGGREGATION_INTERVAL` - Stats aggregation interval (5 min)
- `MAILING_REPORTING_MAX_HISTORY_DAYS` - Maximum report retention (365 days)

**Report Types:**
- Campaign performance reports
- Subscriber growth reports
- Engagement analytics
- Revenue attribution reports
- A/B testing results

**Export Formats:**
- PDF reports
- CSV data export
- Excel (.xlsx) export

**Performance:**
- Report caching (60 minutes default)
- Queue-based generation
- Real-time statistics dashboard
- 5-minute aggregation intervals

#### M. Storage Settings (storage)

File storage configuration for attachments and exports.

**Purpose:** Configure file storage for email attachments and exports

**Key Variables:**
- `MAILING_STORAGE_DISK` - Storage disk (local, s3, gcs)
- `MAILING_STORAGE_MAX_ATTACHMENT_SIZE` - Max attachment size (10MB default)
- `MAILING_STORAGE_EXPORT_RETENTION_DAYS` - Export file retention (7 days)

**Supported Storage:**
- Local filesystem
- Amazon S3
- Google Cloud Storage
- Azure Blob Storage

**File Types:**
- Documents: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX
- Images: JPG, JPEG, PNG, GIF
- Archives: ZIP
- Data: TXT, CSV

**Cleanup:**
- Automatic export file cleanup after 7 days
- Temporary file cleanup every 24 hours
- Orphaned attachment detection

#### N. Security Settings (security)

Security and anti-spam configuration.

**Purpose:** Protect subscription forms from spam and abuse

**Key Variables:**
- `MAILING_SECURITY_CAPTCHA_ENABLED` - Enable CAPTCHA
- `MAILING_SECURITY_CAPTCHA_TYPE` - CAPTCHA type (recaptcha, hcaptcha)
- `MAILING_SECURITY_HONEYPOT_ENABLED` - Enable honeypot fields
- `MAILING_SECURITY_MAX_SUBSCRIPTIONS_PER_IP` - IP rate limit (10/hour)

**CAPTCHA Support:**
- Google reCAPTCHA v2
- Google reCAPTCHA v3
- hCaptcha

**Anti-Spam Features:**
- Honeypot form fields
- IP-based rate limiting
- Blacklist checking (optional)
- Disposable email blocking

**IP Rate Limiting:**
- Maximum 10 subscriptions per IP per hour
- Automatic IP blocking on threshold breach
- Whitelist support for trusted IPs

#### O. Localization Settings (localization)

Multi-language and timezone support.

**Purpose:** Configure language and timezone settings for global audiences

**Key Variables:**
- `MAILING_LOCALIZATION_DEFAULT_LANGUAGE` - Default language (es)
- `MAILING_LOCALIZATION_DEFAULT_TIMEZONE` - Default timezone (Europe/Madrid)
- `MAILING_LOCALIZATION_TIMEZONE_ENABLED` - Enable timezone support

**Supported Languages:**
- English (en)
- Spanish (es)
- French (fr)
- German (de)
- Italian (it)
- Portuguese (pt)
- Russian (ru)

**Timezone Features:**
- Per-subscriber timezone preferences
- Automatic timezone detection
- Send-time optimization
- RTL language support (optional)

#### P. Performance Settings (performance)

Database and query optimization configuration.

**Purpose:** Optimize database queries and connection pooling

**Key Variables:**
- `MAILING_PERFORMANCE_QUERY_CACHE` - Enable query caching
- `MAILING_PERFORMANCE_CHUNK_SIZE` - Chunk size for large queries (1000)
- `MAILING_PERFORMANCE_CONNECTION_POOLING` - Enable connection pooling
- `MAILING_PERFORMANCE_MAX_CONNECTIONS` - Maximum DB connections (100)

**Optimization Features:**
- Query result caching (10 minutes default)
- Lazy loading relationships
- Chunked query processing
- Database connection pooling
- Prepared statement caching

**Performance Targets:**
- Query cache hit rate > 80%
- Average query time < 100ms
- Connection pool utilization < 70%
- Chunk processing for queries > 1000 records

---

## Configuration Files NOT Migrated

The following standard Laravel configuration files were **excluded** from migration as they already exist in Alsernet's core:

| File | Reason for Exclusion | Alsernet Equivalent |
|------|---------------------|---------------------|
| `app.php` | Core application config | `/config/app.php` |
| `auth.php` | Authentication config | `/config/auth.php` |
| `broadcasting.php` | Broadcasting config | `/config/broadcasting.php` |
| `cache.php` | Cache config | `/config/cache.php` |
| `database.php` | Database connections | `/config/database.php` |
| `filesystems.php` | Filesystem config | `/config/filesystems.php` |
| `logging.php` | Logging config | `/config/logging.php` |
| `mail.php` | Mail transport config | `/config/mail.php` |
| `queue.php` | Queue config | `/config/queue.php` |
| `services.php` | Third-party services | `/config/services.php` |
| `session.php` | Session config | `/config/session.php` |

**Note:** If Acelle had custom modifications to these files, they should be evaluated case-by-case and integrated into Alsernet's corresponding files.

---

## Environment Variables Summary

### Total Environment Variables

- **Total Variables:** 150+
- **Required Variables:** 2 (MAILING_URL, MAILING_API_KEY)
- **Optional Variables:** 148+

### Variable Categories

| Category | Count | Critical |
|----------|-------|----------|
| API Configuration | 6 | ✅ Yes |
| Sync Settings | 9 | ⚠️ Medium |
| Webhook Settings | 10 | ⚠️ Medium |
| Cache Settings | 7 | ❌ No |
| Campaign Settings | 5 | ❌ No |
| Tracking | 7 | ❌ No |
| Import | 9 | ❌ No |
| Sending Servers | 8 | ⚠️ Medium |
| Bounce Handler | 11 | ❌ No |
| Feedback Loop | 9 | ❌ No |
| Templates | 8 | ❌ No |
| Automation | 6 | ❌ No |
| Segmentation | 5 | ❌ No |
| Lists | 7 | ❌ No |
| Verification | 7 | ❌ No |
| Deliverability | 7 | ⚠️ Medium |
| Reporting | 6 | ❌ No |
| Storage | 5 | ❌ No |
| Security | 7 | ⚠️ Medium |
| Localization | 4 | ❌ No |
| Performance | 6 | ❌ No |
| Logging | 7 | ❌ No |
| Rate Limiting | 4 | ❌ No |
| Validation | 5 | ❌ No |
| Error Handling | 3 | ❌ No |
| Testing | 3 | ❌ No |

### Minimal Required Configuration

For basic Mailrelay integration, only these variables are **required**:

```env
MAILING_URL=https://your-account.mailrelay.com/api/v1
MAILING_API_KEY=your_api_key_here
```

All other variables have sensible defaults and can be configured as needed.

---

## Usage Recommendations

### 1. Initial Setup

For a new installation, configure only the essentials:

```env
# Required
MAILING_URL=https://your-account.mailrelay.com/api/v1
MAILING_API_KEY=your_api_key_here

# Recommended
MAILING_CACHE_ENABLED=true
MAILING_CACHE_DRIVER=redis
MAILING_AUTO_SYNC=true
MAILING_SYNC_USE_QUEUE=true
```

### 2. Production Configuration

For production environments, enable these additional features:

```env
# Performance
MAILING_PERFORMANCE_QUERY_CACHE=true
MAILING_PERFORMANCE_CONNECTION_POOLING=true

# Security
MAILING_SECURITY_CAPTCHA_ENABLED=true
MAILING_SECURITY_IP_RATE_LIMITING=true

# Monitoring
MAILING_LOGGING_ENABLED=true
MAILING_NOTIFY_ON_ERROR=true

# Deliverability
MAILING_DKIM_ENABLED=true
MAILING_SPF_ENABLED=true
```

### 3. Advanced Features

Enable advanced features as needed:

```env
# Bounce Handling
MAILING_BOUNCE_HANDLER_ENABLED=true
MAILING_BOUNCE_HANDLER_HOST=imap.yourdomain.com
MAILING_BOUNCE_HANDLER_USERNAME=bounces@yourdomain.com

# Email Verification
MAILING_VERIFICATION_ADVANCED_ENABLED=true
MAILING_VERIFICATION_SERVICE=zerobounce
MAILING_VERIFICATION_API_KEY=your_key

# Automation
MAILING_AUTOMATION_ENABLED=true
MAILING_SEGMENTATION_ENABLED=true
```

---

## Queue Configuration

The mailing module uses multiple queues for different operations:

| Queue Name | Purpose | Priority | Recommended Workers |
|------------|---------|----------|---------------------|
| `mailing` | Subscriber sync jobs | Medium | 2-3 |
| `webhooks` | Webhook processing | High | 1-2 |
| `tracking` | Open/click tracking | Low | 1 |
| `imports` | Subscriber imports | Low | 1 |
| `bounces` | Bounce processing | Medium | 1 |
| `feedback` | Feedback loop processing | Medium | 1 |
| `automation` | Automation workflows | Medium | 2 |
| `segments` | Segment recalculation | Low | 1 |
| `verification` | Email verification | Low | 1 |
| `reports` | Report generation | Low | 1 |

### Supervisor Configuration Example

```ini
[program:mailing-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=mailing,webhooks,automation --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/path/to/logs/mailing-worker.log
```

---

## Database Considerations

### Tables Required

The following database tables should exist for full functionality:

**Core Tables:**
- `mailrelay_subscribers` - Subscriber data
- `mailrelay_lists` - Mailing lists
- `mailrelay_campaigns` - Email campaigns
- `mailrelay_segments` - Subscriber segments

**Tracking Tables:**
- `mailrelay_email_opens` - Open tracking
- `mailrelay_email_clicks` - Click tracking
- `mailrelay_bounces` - Bounce tracking
- `mailrelay_complaints` - Spam complaints

**Management Tables:**
- `mailrelay_templates` - Email templates
- `mailrelay_sending_servers` - SMTP/API servers
- `mailrelay_automations` - Automation workflows
- `mailrelay_automation_triggers` - Trigger events

**Analytics Tables:**
- `mailrelay_campaign_stats` - Campaign statistics
- `mailrelay_subscriber_activity` - Activity logs
- `mailrelay_reports` - Generated reports

### Indexing Recommendations

```sql
-- Subscribers
CREATE INDEX idx_email ON mailrelay_subscribers(email);
CREATE INDEX idx_status ON mailrelay_subscribers(status);
CREATE INDEX idx_list_id ON mailrelay_subscribers(list_id);

-- Tracking
CREATE INDEX idx_campaign_subscriber ON mailrelay_email_opens(campaign_id, subscriber_id);
CREATE INDEX idx_opened_at ON mailrelay_email_opens(opened_at);
CREATE INDEX idx_clicked_at ON mailrelay_email_clicks(clicked_at);

-- Performance
CREATE INDEX idx_queue_status ON mailrelay_queue_jobs(status, created_at);
```

---

## API Integration Points

### Mailrelay API Endpoints Used

| Endpoint | Purpose | Config Section |
|----------|---------|---------------|
| `/subscribers` | Create/update subscribers | sync |
| `/groups` | Manage subscriber groups | default_group, sync |
| `/campaigns` | Campaign management | campaign |
| `/analytics` | Fetch campaign statistics | reporting, campaign |
| `/templates` | Manage email templates | templates |
| `/sending_servers` | Configure sending servers | sending_servers |

### External Service Integrations

| Service | Purpose | Config Variables |
|---------|---------|------------------|
| **ZeroBounce** | Email verification | `MAILING_VERIFICATION_SERVICE=zerobounce` |
| **NeverBounce** | Email verification | `MAILING_VERIFICATION_SERVICE=neverbounce` |
| **Google reCAPTCHA** | Form protection | `MAILING_SECURITY_RECAPTCHA_*` |
| **hCaptcha** | Form protection | `MAILING_SECURITY_CAPTCHA_TYPE=hcaptcha` |

---

## Migration Checklist

### Pre-Migration

- [ ] Backup Acelle database
- [ ] Export all Acelle subscribers to CSV
- [ ] Document custom Acelle modifications
- [ ] Review Acelle email templates
- [ ] Identify active campaigns

### Migration Steps

- [ ] Copy `.env.example` variables to main `.env`
- [ ] Configure `MAILING_URL` and `MAILING_API_KEY`
- [ ] Set up Redis for caching (recommended)
- [ ] Configure queue workers (Supervisor)
- [ ] Run database migrations for mailing module
- [ ] Import subscribers using import feature
- [ ] Recreate email templates
- [ ] Configure sending servers
- [ ] Set up bounce/FBL handlers (if needed)
- [ ] Configure DKIM/SPF/DMARC
- [ ] Test subscriber sync to Mailrelay
- [ ] Test campaign sending
- [ ] Verify tracking functionality
- [ ] Set up monitoring and logging

### Post-Migration

- [ ] Verify all subscribers migrated successfully
- [ ] Test double opt-in flow
- [ ] Test unsubscribe functionality
- [ ] Verify webhook endpoints
- [ ] Monitor queue jobs
- [ ] Check tracking analytics
- [ ] Test automation workflows
- [ ] Verify reporting accuracy
- [ ] Performance testing
- [ ] Security audit

---

## Troubleshooting

### Common Issues

#### 1. API Connection Failures

**Symptoms:** "Connection timeout" or "Could not connect to Mailrelay API"

**Solutions:**
```env
# Increase timeouts
MAILING_TIMEOUT=60
MAILING_CONNECT_TIMEOUT=20

# Enable retries
MAILING_RETRY_MAX_ATTEMPTS=5
```

#### 2. Queue Jobs Not Processing

**Symptoms:** Jobs stuck in queue, subscribers not syncing

**Solutions:**
```bash
# Restart queue workers
supervisorctl restart mailing-queue-worker:*

# Clear failed jobs
php artisan queue:flush

# Check queue configuration
php artisan queue:work --queue=mailing --once
```

#### 3. Cache Issues

**Symptoms:** Stale data, outdated subscriber lists

**Solutions:**
```bash
# Clear mailing cache
php artisan cache:forget 'mailing:*'

# Disable cache temporarily
MAILING_CACHE_ENABLED=false
```

#### 4. Import Failures

**Symptoms:** "Import timeout" or "Memory exceeded"

**Solutions:**
```env
# Reduce batch size
MAILING_IMPORT_BATCH_SIZE=500

# Increase memory and timeout
MAILING_IMPORT_MAX_EXECUTION_TIME=14400
```

#### 5. Bounce Handler Not Working

**Symptoms:** Bounces not detected, hard bounces still in list

**Solutions:**
```env
# Verify IMAP settings
MAILING_BOUNCE_HANDLER_HOST=imap.gmail.com
MAILING_BOUNCE_HANDLER_PORT=993
MAILING_BOUNCE_HANDLER_ENCRYPTION=ssl

# Enable debug logging
MAILING_LOGGING_ENABLED=true
MAILING_LOGGING_LEVEL=debug
```

---

## Performance Optimization

### Caching Strategy

```env
# Aggressive caching for high-traffic sites
MAILING_CACHE_ENABLED=true
MAILING_CACHE_DRIVER=redis
MAILING_CACHE_TTL_SUBSCRIBERS=7200
MAILING_CACHE_TTL_GROUPS=7200
MAILING_PERFORMANCE_QUERY_CACHE=true
MAILING_PERFORMANCE_QUERY_CACHE_TTL=30
```

### Queue Optimization

```env
# Increase batch sizes for better throughput
MAILING_SYNC_BATCH_SIZE=500
MAILING_IMPORT_BATCH_SIZE=2000
MAILING_AUTOMATION_MAX_PROCESS=500

# Reduce delays
MAILING_SYNC_BATCH_INTERVAL=30
MAILING_AUTOMATION_INTERVAL=1
```

### Database Optimization

```env
# Enable connection pooling
MAILING_PERFORMANCE_CONNECTION_POOLING=true
MAILING_PERFORMANCE_MAX_CONNECTIONS=200

# Optimize chunk sizes
MAILING_PERFORMANCE_CHUNK_SIZE=2000
```

---

## Security Best Practices

### 1. API Key Protection

```env
# Never commit API keys to version control
MAILING_API_KEY=your_secret_key_here

# Use different keys for staging/production
# Staging: MAILING_API_KEY=staging_key
# Production: MAILING_API_KEY=production_key
```

### 2. Webhook Security

```env
# Always verify webhook signatures
MAILING_WEBHOOK_VERIFY_SIGNATURE=true
MAILING_WEBHOOK_SECRET=random_generated_secret

# Whitelist trusted IPs (optional)
MAILING_WEBHOOK_IP_WHITELIST=192.168.1.1,10.0.0.1
```

### 3. Form Protection

```env
# Enable all anti-spam measures
MAILING_SECURITY_CAPTCHA_ENABLED=true
MAILING_SECURITY_HONEYPOT_ENABLED=true
MAILING_SECURITY_IP_RATE_LIMITING=true
MAILING_VALIDATION_BLOCK_DISPOSABLE=true
```

### 4. Error Handling

```env
# Don't expose errors in production
MAILING_DEBUG=false
MAILING_THROW_EXCEPTIONS=false

# Enable error notifications
MAILING_NOTIFY_ON_ERROR=true
MAILING_ERROR_NOTIFICATION_RECIPIENTS=admin@yourdomain.com
```

---

## Testing Recommendations

### Unit Tests

Test configuration loading:

```php
public function test_mailing_config_loads()
{
    $config = config('mailing');
    $this->assertIsArray($config);
    $this->assertArrayHasKey('api_url', $config);
    $this->assertArrayHasKey('sync', $config);
}

public function test_tracking_config_defaults()
{
    $tracking = config('mailing.tracking');
    $this->assertTrue($tracking['track_opens']);
    $this->assertTrue($tracking['track_clicks']);
}
```

### Integration Tests

Test Mailrelay API connectivity:

```php
public function test_mailrelay_api_connection()
{
    $client = app(MailrelayClient::class);
    $response = $client->testConnection();
    $this->assertTrue($response->isSuccessful());
}

public function test_subscriber_sync()
{
    $subscriber = Subscriber::factory()->create();
    $result = $subscriber->syncToMailrelay();
    $this->assertTrue($result);
}
```

### Feature Tests

Test import functionality:

```php
public function test_csv_import()
{
    $file = UploadedFile::fake()->create('subscribers.csv', 100);

    $response = $this->post('/admin/mailing/import', [
        'file' => $file,
        'list_id' => 1,
    ]);

    $response->assertSuccessful();
    $this->assertDatabaseHas('mailrelay_import_jobs', [
        'status' => 'processing',
    ]);
}
```

---

## Monitoring and Alerts

### Key Metrics to Monitor

1. **API Response Time**
   - Target: < 500ms average
   - Alert: > 2000ms for 5 minutes

2. **Queue Size**
   - Target: < 1000 jobs in queue
   - Alert: > 5000 jobs for 10 minutes

3. **Bounce Rate**
   - Target: < 2%
   - Alert: > 5% for any campaign

4. **Complaint Rate**
   - Target: < 0.1%
   - Alert: > 0.5% for any campaign

5. **API Error Rate**
   - Target: < 1%
   - Alert: > 5% for 5 minutes

### Logging Configuration

```env
# Enable comprehensive logging
MAILING_LOGGING_ENABLED=true
MAILING_LOGGING_CHANNEL=stack
MAILING_LOGGING_LEVEL=warning
MAILING_LOG_REQUESTS=true
MAILING_LOG_RESPONSES=false
```

---

## Appendix A: Full Environment Variable Reference

See `.env.example` for the complete list of all 150+ environment variables with descriptions and default values.

---

## Appendix B: Configuration Validation

To validate your configuration:

```bash
# Check config syntax
php artisan config:show mailing

# Validate Mailrelay API connection
php artisan mailing:test-connection

# Check queue configuration
php artisan queue:monitor mailing,webhooks,automation

# Verify cache connectivity
php artisan cache:clear
```

---

## Appendix C: Upgrade Path from Acelle

### Step 1: Data Export

```bash
# Export subscribers
mysqldump acelle_db mailrelay_subscribers > subscribers.sql

# Export campaigns
mysqldump acelle_db mailrelay_campaigns > campaigns.sql

# Export templates
mysqldump acelle_db mailrelay_templates > templates.sql
```

### Step 2: Data Transformation

```php
// Transform Acelle subscriber format to Alsernet format
$subscribers = DB::connection('acelle')
    ->table('subscribers')
    ->get()
    ->map(function ($subscriber) {
        return [
            'email' => $subscriber->email,
            'first_name' => $subscriber->first_name,
            'last_name' => $subscriber->last_name,
            'status' => $subscriber->status === 'subscribed' ? 'active' : 'inactive',
            'list_id' => $subscriber->mail_list_id,
            'custom_fields' => json_decode($subscriber->custom_fields),
            'subscribed_at' => $subscriber->created_at,
        ];
    });
```

### Step 3: Import to Alsernet

```bash
# Import via CSV
php artisan mailing:import subscribers.csv --list=1

# Or via API
php artisan mailing:migrate-from-acelle --connection=acelle
```

---

## Appendix D: Differences from Acelle

| Feature | Acelle | Alsernet Mailing |
|---------|--------|------------------|
| **Primary Backend** | Custom API | Mailrelay API |
| **Database** | Direct MySQL/PostgreSQL | Mailrelay + Local cache |
| **Sending** | Built-in SMTP pool | Mailrelay servers |
| **Templates** | Custom template engine | Blade + Mailrelay templates |
| **Analytics** | Built-in dashboard | Mailrelay analytics + custom reports |
| **Automation** | Visual workflow builder | Config-based workflows |
| **Segmentation** | Advanced query builder | Basic condition matching |
| **Pricing** | Self-hosted (one-time) | SaaS (Mailrelay subscription) |

---

## Support and Resources

### Documentation

- **Mailrelay API Docs:** https://mailrelay.com/api/docs
- **Laravel Queues:** https://laravel.com/docs/queues
- **Redis Caching:** https://laravel.com/docs/redis

### Community

- **GitHub Issues:** https://github.com/your-org/mailing-module/issues
- **Slack Channel:** #mailing-module
- **Email Support:** support@yourdomain.com

---

## Changelog

### Version 1.0.0 (2026-01-29)

- ✅ Initial configuration migration from Acelle Mail
- ✅ Added 26 comprehensive configuration sections
- ✅ Created 150+ environment variables
- ✅ Integrated Mailrelay API configuration
- ✅ Added queue-based processing
- ✅ Implemented caching strategy
- ✅ Added security features (CAPTCHA, rate limiting)
- ✅ Configured bounce and FBL handlers
- ✅ Set up deliverability features (DKIM, SPF)
- ✅ Added multi-language support
- ✅ Implemented performance optimizations

---

## License

This configuration is part of the Alsernet Mailing Module and is subject to the same license as the main application.

---

**End of Report**
