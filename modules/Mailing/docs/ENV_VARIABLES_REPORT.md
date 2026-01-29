# Environment Variables Report - Mailing Module

**Document Version:** 1.0
**Created:** 2026-01-29
**Module:** Mailing
**Total Variables:** 178

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Variable Categories](#variable-categories)
3. [Required Variables](#required-variables)
4. [Optional Variables](#optional-variables)
5. [External Service Providers](#external-service-providers)
6. [Security Considerations](#security-considerations)
7. [Environment-Specific Recommendations](#environment-specific-recommendations)
8. [Migration from Acelle](#migration-from-acelle)
9. [Variable Reference](#variable-reference)

---

## Executive Summary

### Overview

This document provides a comprehensive reference for all environment variables used by the Mailing module. The configuration has been designed to support:

- **Mailrelay API Integration** - Primary mailing service
- **12 Email Verification Services** - Third-party email validation
- **7 Email Sending Providers** - Multiple SMTP and API-based senders
- **Acelle Mail Compatibility** - Full feature parity with Acelle
- **Advanced Campaign Features** - Tracking, automation, segmentation
- **Enterprise Features** - Bounce handling, feedback loops, DKIM signing

### Statistics

| Category | Count | Percentage |
|----------|-------|------------|
| Core API Configuration | 15 | 8.4% |
| Sync & Queue Settings | 18 | 10.1% |
| Webhook Configuration | 10 | 5.6% |
| Cache Settings | 7 | 3.9% |
| Campaign & Automation | 25 | 14.0% |
| Email Tracking | 12 | 6.7% |
| Import/Export | 8 | 4.5% |
| Sending Servers | 9 | 5.1% |
| Bounce & Feedback Handlers | 16 | 9.0% |
| Templates & Storage | 15 | 8.4% |
| Security & Validation | 14 | 7.9% |
| Performance & Localization | 12 | 6.7% |
| External Services | 17 | 9.6% |
| **TOTAL** | **178** | **100%** |

---

## Variable Categories

### 1. Core API Configuration (15 variables)

Configuration for Mailrelay API integration and connection management.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_URL` | string | https://inoqualab.mailrelay.com/api/v1 | ✅ |
| `MAILING_API_KEY` | string | - | ✅ |
| `MAILING_TIMEOUT` | integer | 30 | ❌ |
| `MAILING_CONNECT_TIMEOUT` | integer | 10 | ❌ |
| `MAILING_RETRY_MAX_ATTEMPTS` | integer | 3 | ❌ |
| `MAILING_RETRY_DELAY` | integer | 100 | ❌ |
| `MAILING_RETRY_MULTIPLIER` | integer | 2 | ❌ |
| `MAILING_RATE_LIMIT_ENABLED` | boolean | true | ❌ |
| `MAILING_RATE_LIMIT_MAX_REQUESTS` | integer | 60 | ❌ |
| `MAILING_RATE_LIMIT_DECAY_MINUTES` | integer | 1 | ❌ |
| `MAILING_RATE_LIMIT_DELAY` | integer | 50 | ❌ |
| `MAILING_THROW_EXCEPTIONS` | boolean | true | ❌ |
| `MAILING_NOTIFY_ON_ERROR` | boolean | false | ❌ |
| `MAILING_ERROR_NOTIFICATION_RECIPIENTS` | string | - | ❌ |
| `MAILING_DEBUG` | boolean | false | ❌ |

### 2. Subscriber Sync Configuration (9 variables)

Settings for synchronizing subscribers with Mailrelay.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_AUTO_SYNC` | boolean | true | ❌ |
| `MAILING_SYNC_BATCH_INTERVAL` | integer | 60 | ❌ |
| `MAILING_SYNC_BATCH_SIZE` | integer | 100 | ❌ |
| `MAILING_SYNC_USE_QUEUE` | boolean | true | ❌ |
| `MAILING_SYNC_QUEUE_NAME` | string | mailing | ❌ |
| `MAILING_SYNC_RETRY_FAILED` | boolean | true | ❌ |
| `MAILING_SYNC_MAX_RETRIES` | integer | 3 | ❌ |
| `MAILING_SYNC_TRACK_HISTORY` | boolean | true | ❌ |
| `MAILING_SYNC_METADATA` | boolean | true | ❌ |

### 3. Webhook Configuration (10 variables)

Webhook processing for email events (opens, clicks, bounces).

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_WEBHOOK_ENABLED` | boolean | true | ❌ |
| `MAILING_WEBHOOK_PATH` | string | /api/webhooks/mailrelay | ❌ |
| `MAILING_WEBHOOK_SECRET` | string | - | ⚠️ |
| `MAILING_WEBHOOK_VERIFY_SIGNATURE` | boolean | true | ❌ |
| `MAILING_WEBHOOK_LOG_EVENTS` | boolean | true | ❌ |
| `MAILING_WEBHOOK_USE_QUEUE` | boolean | true | ❌ |
| `MAILING_WEBHOOK_QUEUE_NAME` | string | webhooks | ❌ |
| `MAILING_WEBHOOK_TIMEOUT` | integer | 30 | ❌ |
| `MAILING_WEBHOOK_MAX_PAYLOAD_SIZE` | integer | 1048576 | ❌ |
| `MAILING_WEBHOOK_IP_WHITELIST` | string | - | ❌ |

⚠️ **Required if `MAILING_WEBHOOK_VERIFY_SIGNATURE=true`**

### 4. Cache Configuration (7 variables)

Response caching for improved performance.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_CACHE_ENABLED` | boolean | true | ❌ |
| `MAILING_CACHE_DRIVER` | string | null | ❌ |
| `MAILING_CACHE_TTL_SUBSCRIBERS` | integer | 3600 | ❌ |
| `MAILING_CACHE_TTL_GROUPS` | integer | 3600 | ❌ |
| `MAILING_CACHE_TTL_CAMPAIGNS` | integer | 1800 | ❌ |
| `MAILING_CACHE_TTL_ANALYTICS` | integer | 300 | ❌ |
| `MAILING_CACHE_PREFIX` | string | mailing | ❌ |

### 5. Email Tracking (7 variables)

Track email opens, clicks, and user interactions.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_TRACK_OPENS` | boolean | true | ❌ |
| `MAILING_TRACK_CLICKS` | boolean | true | ❌ |
| `MAILING_TRACK_UNSUBSCRIBES` | boolean | true | ❌ |
| `MAILING_TRACKING_DOMAIN` | string | null | ❌ |
| `MAILING_TRACKING_HTTPS` | boolean | true | ❌ |
| `MAILING_CLICK_TRACKING_QUEUE` | string | tracking | ❌ |
| `MAILING_OPEN_TRACKING_QUEUE` | string | tracking | ❌ |

### 6. Campaign Management (7 variables)

Email campaign configuration and analytics.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_CAMPAIGN_DEFAULT_SENDER_ID` | integer | null | ❌ |
| `MAILING_CAMPAIGN_TRACK_ANALYTICS` | boolean | true | ❌ |
| `MAILING_CAMPAIGN_ANALYTICS_SYNC_INTERVAL` | integer | 30 | ❌ |
| `MAILING_CAMPAIGN_TEST_MODE` | boolean | false | ❌ |
| `MAILING_CAMPAIGN_TEST_EMAILS` | string | - | ❌ |
| `MAILING_DEFAULT_GROUP_ID` | integer | null | ❌ |
| `MAILING_DEFAULT_GROUP_AUTO_ASSIGN` | boolean | true | ❌ |

### 7. Subscriber Import (8 variables)

Bulk import subscribers from CSV/Excel files.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_IMPORT_MAX_FILE_SIZE` | integer | 50 | ❌ |
| `MAILING_IMPORT_BATCH_SIZE` | integer | 1000 | ❌ |
| `MAILING_IMPORT_QUEUE` | string | imports | ❌ |
| `MAILING_IMPORT_CSV_DELIMITER` | string | , | ❌ |
| `MAILING_IMPORT_CSV_ENCLOSURE` | string | " | ❌ |
| `MAILING_IMPORT_SKIP_DUPLICATES` | boolean | true | ❌ |
| `MAILING_IMPORT_VALIDATE_EMAILS` | boolean | true | ❌ |
| `MAILING_IMPORT_MAX_EXECUTION_TIME` | integer | 7200 | ❌ |

### 8. Sending Server Configuration (9 variables)

Multi-server email sending with health monitoring.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_SENDING_SERVER_DEFAULT_TYPE` | string | smtp | ❌ |
| `MAILING_SENDING_SERVER_ROTATION` | string | round-robin | ❌ |
| `MAILING_SENDING_SERVER_HEALTH_CHECK` | boolean | true | ❌ |
| `MAILING_SENDING_SERVER_HEALTH_CHECK_INTERVAL` | integer | 15 | ❌ |
| `MAILING_SENDING_SERVER_AUTO_DISABLE_THRESHOLD` | integer | 5 | ❌ |
| `MAILING_SENDING_SERVER_QUOTA_HOUR` | integer | 10000 | ❌ |
| `MAILING_SENDING_SERVER_QUOTA_DAY` | integer | 100000 | ❌ |
| `MAILING_SENDING_SERVER_CONNECTION_TIMEOUT` | integer | 30 | ❌ |

### 9. Bounce Handler (12 variables)

Automatic bounce email processing via IMAP/POP3.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_BOUNCE_HANDLER_ENABLED` | boolean | false | ❌ |
| `MAILING_BOUNCE_HANDLER_TYPE` | string | imap | ❌ |
| `MAILING_BOUNCE_HANDLER_HOST` | string | - | ⚠️ |
| `MAILING_BOUNCE_HANDLER_PORT` | integer | 993 | ❌ |
| `MAILING_BOUNCE_HANDLER_USERNAME` | string | - | ⚠️ |
| `MAILING_BOUNCE_HANDLER_PASSWORD` | string | - | ⚠️ |
| `MAILING_BOUNCE_HANDLER_ENCRYPTION` | string | ssl | ❌ |
| `MAILING_BOUNCE_HANDLER_QUEUE` | string | bounces | ❌ |
| `MAILING_BOUNCE_HANDLER_INTERVAL` | integer | 5 | ❌ |
| `MAILING_BOUNCE_HANDLER_MAX_PROCESS` | integer | 500 | ❌ |
| `MAILING_BOUNCE_HARD_ACTION` | string | unsubscribe | ❌ |
| `MAILING_BOUNCE_SOFT_THRESHOLD` | integer | 5 | ❌ |

⚠️ **Required if `MAILING_BOUNCE_HANDLER_ENABLED=true`**

### 10. Feedback Loop Handler (9 variables)

Process spam complaints from ISPs.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_FEEDBACK_LOOP_ENABLED` | boolean | false | ❌ |
| `MAILING_FEEDBACK_LOOP_TYPE` | string | imap | ❌ |
| `MAILING_FEEDBACK_LOOP_HOST` | string | - | ⚠️ |
| `MAILING_FEEDBACK_LOOP_PORT` | integer | 993 | ❌ |
| `MAILING_FEEDBACK_LOOP_USERNAME` | string | - | ⚠️ |
| `MAILING_FEEDBACK_LOOP_PASSWORD` | string | - | ⚠️ |
| `MAILING_FEEDBACK_LOOP_ENCRYPTION` | string | ssl | ❌ |
| `MAILING_FEEDBACK_LOOP_QUEUE` | string | feedback | ❌ |
| `MAILING_FEEDBACK_LOOP_INTERVAL` | integer | 10 | ❌ |

⚠️ **Required if `MAILING_FEEDBACK_LOOP_ENABLED=true`**

### 11. Email Templates (9 variables)

Template management and caching.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_TEMPLATE_CACHE_ENABLED` | boolean | true | ❌ |
| `MAILING_TEMPLATE_CACHE_TTL` | integer | 60 | ❌ |
| `MAILING_TEMPLATE_DEFAULT_LANGUAGE` | string | en | ❌ |
| `MAILING_TEMPLATE_ALLOW_CUSTOM_CSS` | boolean | true | ❌ |
| `MAILING_TEMPLATE_ALLOW_CUSTOM_JS` | boolean | false | ❌ |
| `MAILING_TEMPLATE_MAX_SIZE` | integer | 500 | ❌ |
| `MAILING_TEMPLATE_STORAGE_PATH` | string | mailing/templates | ❌ |
| `MAILING_TEMPLATE_VERSIONING` | boolean | true | ❌ |

### 12. Automation & Workflows (6 variables)

Automated email sequences and triggers.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_AUTOMATION_ENABLED` | boolean | true | ❌ |
| `MAILING_AUTOMATION_QUEUE` | string | automation | ❌ |
| `MAILING_AUTOMATION_INTERVAL` | integer | 1 | ❌ |
| `MAILING_AUTOMATION_MAX_PROCESS` | integer | 100 | ❌ |
| `MAILING_AUTOMATION_TRIGGERS_ENABLED` | boolean | true | ❌ |
| `MAILING_AUTOMATION_MAX_DEPTH` | integer | 5 | ❌ |

### 13. Segmentation (5 variables)

Subscriber segmentation and targeting.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_SEGMENTATION_ENABLED` | boolean | true | ❌ |
| `MAILING_SEGMENTATION_CACHE_TTL` | integer | 30 | ❌ |
| `MAILING_SEGMENTATION_REALTIME_THRESHOLD` | integer | 10000 | ❌ |
| `MAILING_SEGMENTATION_QUEUE` | string | segments | ❌ |
| `MAILING_SEGMENTATION_MAX_CONDITIONS` | integer | 20 | ❌ |

### 14. Mailing Lists (7 variables)

List management and subscriber lifecycle.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_LIST_DOUBLE_OPTIN_DEFAULT` | boolean | true | ❌ |
| `MAILING_LIST_SEND_WELCOME_EMAIL_DEFAULT` | boolean | false | ❌ |
| `MAILING_LIST_SEND_UNSUBSCRIBE_NOTIFICATION` | boolean | true | ❌ |
| `MAILING_LIST_MAX_PER_USER` | integer | 100 | ❌ |
| `MAILING_LIST_VERIFICATION_ENABLED` | boolean | true | ❌ |
| `MAILING_LIST_CLEANUP_ENABLED` | boolean | false | ❌ |
| `MAILING_LIST_CLEANUP_THRESHOLD_DAYS` | integer | 365 | ❌ |

### 15. Email Verification Services (7 variables)

Third-party email validation integration.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_VERIFICATION_ADVANCED_ENABLED` | boolean | false | ❌ |
| `MAILING_VERIFICATION_SERVICE` | string | internal | ❌ |
| `MAILING_VERIFICATION_API_KEY` | string | - | ⚠️ |
| `MAILING_VERIFICATION_API_SECRET` | string | - | ❌ |
| `MAILING_VERIFICATION_QUEUE` | string | verification | ❌ |
| `MAILING_VERIFICATION_AUTO_IMPORT` | boolean | false | ❌ |
| `MAILING_VERIFICATION_REMOVE_INVALID` | boolean | true | ❌ |

⚠️ **Required if `MAILING_VERIFICATION_ADVANCED_ENABLED=true`**

### 16. Email Validation (5 variables)

Basic email validation before syncing.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_VALIDATION_ENABLED` | boolean | true | ❌ |
| `MAILING_VALIDATION_MIN_SCORE` | integer | 70 | ❌ |
| `MAILING_VALIDATION_SKIP_DOMAINS` | string | - | ❌ |
| `MAILING_VALIDATION_BLOCK_DISPOSABLE` | boolean | true | ❌ |
| `MAILING_VALIDATION_BLOCK_ROLE_BASED` | boolean | false | ❌ |

### 17. Deliverability & Authentication (8 variables)

DKIM, SPF, DMARC configuration.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_DKIM_ENABLED` | boolean | false | ❌ |
| `MAILING_DKIM_SELECTOR` | string | default | ❌ |
| `MAILING_DKIM_PRIVATE_KEY_PATH` | string | - | ⚠️ |
| `MAILING_DKIM_DOMAIN` | string | - | ⚠️ |
| `MAILING_SPF_ENABLED` | boolean | true | ❌ |
| `MAILING_DMARC_ENABLED` | boolean | false | ❌ |
| `MAILING_HEADER_LIST_UNSUBSCRIBE` | boolean | true | ❌ |
| `MAILING_HEADER_PRECEDENCE` | string | bulk | ❌ |

⚠️ **Required if `MAILING_DKIM_ENABLED=true`**

### 18. Reporting & Analytics (6 variables)

Report generation and real-time statistics.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_REPORTING_ENABLED` | boolean | true | ❌ |
| `MAILING_REPORTING_QUEUE` | string | reports | ❌ |
| `MAILING_REPORTING_CACHE_TTL` | integer | 60 | ❌ |
| `MAILING_REPORTING_REALTIME_STATS` | boolean | true | ❌ |
| `MAILING_REPORTING_AGGREGATION_INTERVAL` | integer | 5 | ❌ |
| `MAILING_REPORTING_MAX_HISTORY_DAYS` | integer | 365 | ❌ |

### 19. File Storage (5 variables)

Attachment and export file management.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_STORAGE_DISK` | string | local | ❌ |
| `MAILING_STORAGE_ATTACHMENTS_PATH` | string | mailing/attachments | ❌ |
| `MAILING_STORAGE_MAX_ATTACHMENT_SIZE` | integer | 10 | ❌ |
| `MAILING_STORAGE_EXPORT_RETENTION_DAYS` | integer | 7 | ❌ |
| `MAILING_STORAGE_TEMP_CLEANUP_INTERVAL` | integer | 24 | ❌ |

### 20. Security Settings (8 variables)

CAPTCHA, rate limiting, and anti-spam.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_SECURITY_CAPTCHA_ENABLED` | boolean | false | ❌ |
| `MAILING_SECURITY_CAPTCHA_TYPE` | string | recaptcha | ❌ |
| `MAILING_SECURITY_RECAPTCHA_SITE_KEY` | string | - | ⚠️ |
| `MAILING_SECURITY_RECAPTCHA_SECRET_KEY` | string | - | ⚠️ |
| `MAILING_SECURITY_HONEYPOT_ENABLED` | boolean | true | ❌ |
| `MAILING_SECURITY_IP_RATE_LIMITING` | boolean | true | ❌ |
| `MAILING_SECURITY_MAX_SUBSCRIPTIONS_PER_IP` | integer | 10 | ❌ |
| `MAILING_SECURITY_BLACKLIST_CHECK` | boolean | false | ❌ |

⚠️ **Required if `MAILING_SECURITY_CAPTCHA_ENABLED=true`**

### 21. Localization (4 variables)

Multi-language and timezone support.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_LOCALIZATION_DEFAULT_LANGUAGE` | string | es | ❌ |
| `MAILING_LOCALIZATION_TIMEZONE_ENABLED` | boolean | true | ❌ |
| `MAILING_LOCALIZATION_DEFAULT_TIMEZONE` | string | Europe/Madrid | ❌ |
| `MAILING_LOCALIZATION_RTL_ENABLED` | boolean | false | ❌ |

### 22. Performance Optimization (6 variables)

Query caching and connection pooling.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_PERFORMANCE_QUERY_CACHE` | boolean | true | ❌ |
| `MAILING_PERFORMANCE_QUERY_CACHE_TTL` | integer | 10 | ❌ |
| `MAILING_PERFORMANCE_LAZY_LOADING` | boolean | true | ❌ |
| `MAILING_PERFORMANCE_CHUNK_SIZE` | integer | 1000 | ❌ |
| `MAILING_PERFORMANCE_CONNECTION_POOLING` | boolean | true | ❌ |
| `MAILING_PERFORMANCE_MAX_CONNECTIONS` | integer | 100 | ❌ |

### 23. Logging Configuration (7 variables)

API request/response logging.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_LOGGING_ENABLED` | boolean | false | ❌ |
| `MAILING_LOGGING_CHANNEL` | string | stack | ❌ |
| `MAILING_LOGGING_LEVEL` | string | info | ❌ |
| `MAILING_LOG_REQUESTS` | boolean | true | ❌ |
| `MAILING_LOG_RESPONSES` | boolean | true | ❌ |
| `MAILING_LOG_REQUEST_BODY` | boolean | false | ❌ |
| `MAILING_LOG_RESPONSE_BODY` | boolean | false | ❌ |

### 24. Testing & Development (3 variables)

Sandbox and mock API for testing.

| Variable | Type | Default | Required |
|----------|------|---------|----------|
| `MAILING_SANDBOX_MODE` | boolean | false | ❌ |
| `MAILING_MOCK_API` | boolean | false | ❌ |
| `MAILING_DEBUG` | boolean | false | ❌ |

---

## External Service Providers

### Email Sending Services (6 providers)

| Provider | Variables | Status |
|----------|-----------|--------|
| **AWS SES** | AWS_SES_KEY, AWS_SES_SECRET, AWS_SES_REGION | Optional |
| **Mailgun** | MAILGUN_DOMAIN, MAILGUN_SECRET, MAILGUN_ENDPOINT | Optional |
| **SendGrid** | SENDGRID_API_KEY | Optional |
| **SparkPost** | SPARKPOST_API_KEY | Optional |
| **Elastic Email** | ELASTICEMAIL_API_KEY | Optional |
| **Postmark** | POSTMARK_TOKEN | Optional |

### Email Verification Services (14 providers)

| Provider | Variable | Cost |
|----------|----------|------|
| Emailable (Recommended) | EMAILABLE_API_KEY | Paid |
| Kickbox | KICKBOX_API_KEY | Paid |
| ZeroBounce | ZEROBOUNCE_API_KEY | Paid |
| NeverBounce | NEVERBOUNCE_API_KEY | Paid |
| VerifyEmail.org | VERIFYEMAIL_API_KEY | Paid |
| Localmail.io | LOCALMAIL_API_KEY | Paid |
| Debounce.io | DEBOUNCE_API_KEY | Paid |
| EmailChecker.com | EMAILCHECKER_API_KEY | Paid |
| Cloud Vision | CLOUDVISION_API_KEY | Paid |
| Cloudmersive | CLOUDMERSIVE_API_KEY | Free tier |
| Emaillist Validation | EMAILLISTVALIDATION_API_KEY | Paid |
| Bounceless.io | BOUNCELESS_API_KEY | Paid |
| Bouncify | BOUNCIFY_API_KEY | Paid |
| myEmailVerifier | MYEMAILVERIFIER_API_KEY | Paid |

---

## Required Variables

### Minimum Configuration

To run the Mailing module, only these variables are **absolutely required**:

```bash
# Core API (required)
MAILING_URL=https://your-account.mailrelay.com/api/v1
MAILING_API_KEY=your_api_key_here
```

### Recommended Production Configuration

For production use, these additional variables are **strongly recommended**:

```bash
# Core API
MAILING_URL=https://your-account.mailrelay.com/api/v1
MAILING_API_KEY=your_production_api_key

# Webhook security
MAILING_WEBHOOK_SECRET=your_secure_webhook_secret_here

# Queue configuration
MAILING_SYNC_USE_QUEUE=true
MAILING_WEBHOOK_USE_QUEUE=true

# Cache for performance
MAILING_CACHE_ENABLED=true
MAILING_CACHE_DRIVER=redis

# Email tracking
MAILING_TRACKING_DOMAIN=track.yourdomain.com
MAILING_TRACKING_HTTPS=true

# Error notifications
MAILING_NOTIFY_ON_ERROR=true
MAILING_ERROR_NOTIFICATION_RECIPIENTS=admin@yourdomain.com

# Logging for audit trail
MAILING_LOGGING_ENABLED=true
MAILING_LOGGING_LEVEL=warning
```

---

## Security Considerations

### Critical Security Variables

1. **API Credentials**
   - `MAILING_API_KEY` - Never commit to version control
   - Rotate regularly (every 90 days minimum)
   - Use different keys for staging/production

2. **Webhook Security**
   - `MAILING_WEBHOOK_SECRET` - Use strong random string (32+ characters)
   - Always set `MAILING_WEBHOOK_VERIFY_SIGNATURE=true` in production
   - Consider using `MAILING_WEBHOOK_IP_WHITELIST` for additional security

3. **External Service Keys**
   - All `*_API_KEY` variables should be encrypted at rest
   - Store in Laravel's encrypted `.env` or secrets manager
   - Implement key rotation policy

4. **CAPTCHA Keys**
   - `MAILING_SECURITY_RECAPTCHA_SITE_KEY` - Public, can be exposed
   - `MAILING_SECURITY_RECAPTCHA_SECRET_KEY` - Must be secret

5. **DKIM Private Key**
   - `MAILING_DKIM_PRIVATE_KEY_PATH` - Protect file with 600 permissions
   - Never include private key in repository
   - Generate unique keys per domain

### Security Best Practices

```bash
# Production security settings
MAILING_WEBHOOK_VERIFY_SIGNATURE=true
MAILING_VALIDATION_ENABLED=true
MAILING_VALIDATION_BLOCK_DISPOSABLE=true
MAILING_SECURITY_HONEYPOT_ENABLED=true
MAILING_SECURITY_IP_RATE_LIMITING=true
MAILING_SECURITY_CAPTCHA_ENABLED=true

# Disable in production
MAILING_SANDBOX_MODE=false
MAILING_MOCK_API=false
MAILING_DEBUG=false
MAILING_LOG_REQUEST_BODY=false  # May log sensitive data
MAILING_LOG_RESPONSE_BODY=false
```

---

## Environment-Specific Recommendations

### Development Environment

```bash
# Use test API credentials
MAILING_URL=https://test.mailrelay.com/api/v1
MAILING_API_KEY=test_key_here

# Enable debugging
MAILING_DEBUG=true
MAILING_LOGGING_ENABLED=true
MAILING_LOG_REQUESTS=true
MAILING_LOG_RESPONSES=true

# Use sandbox mode to prevent actual sending
MAILING_SANDBOX_MODE=true
MAILING_CAMPAIGN_TEST_MODE=true

# Mock external services
MAILING_MOCK_API=true

# Disable heavy features
MAILING_BOUNCE_HANDLER_ENABLED=false
MAILING_FEEDBACK_LOOP_ENABLED=false
MAILING_VERIFICATION_ADVANCED_ENABLED=false

# Use file-based cache
MAILING_CACHE_DRIVER=file

# Disable rate limiting for testing
MAILING_RATE_LIMIT_ENABLED=false
```

### Staging Environment

```bash
# Use staging API credentials
MAILING_URL=https://staging.mailrelay.com/api/v1
MAILING_API_KEY=staging_key_here

# Enable testing features
MAILING_CAMPAIGN_TEST_MODE=true
MAILING_CAMPAIGN_TEST_EMAILS=tester1@example.com,tester2@example.com

# Moderate logging
MAILING_LOGGING_ENABLED=true
MAILING_LOGGING_LEVEL=info

# Use Redis for caching
MAILING_CACHE_DRIVER=redis

# Enable queues
MAILING_SYNC_USE_QUEUE=true
MAILING_WEBHOOK_USE_QUEUE=true

# Test bounce handler with test mailbox
MAILING_BOUNCE_HANDLER_ENABLED=true
MAILING_BOUNCE_HANDLER_HOST=test-mail.example.com
```

### Production Environment

```bash
# Production API credentials
MAILING_URL=https://youraccount.mailrelay.com/api/v1
MAILING_API_KEY=prod_key_secure_here

# Security hardening
MAILING_WEBHOOK_SECRET=very_secure_random_string_32_chars_min
MAILING_WEBHOOK_VERIFY_SIGNATURE=true
MAILING_SECURITY_CAPTCHA_ENABLED=true
MAILING_SECURITY_RECAPTCHA_SITE_KEY=your_recaptcha_site_key
MAILING_SECURITY_RECAPTCHA_SECRET_KEY=your_recaptcha_secret_key

# Performance optimization
MAILING_CACHE_ENABLED=true
MAILING_CACHE_DRIVER=redis
MAILING_PERFORMANCE_QUERY_CACHE=true
MAILING_PERFORMANCE_CONNECTION_POOLING=true

# Queue all heavy operations
MAILING_SYNC_USE_QUEUE=true
MAILING_WEBHOOK_USE_QUEUE=true
MAILING_AUTOMATION_ENABLED=true

# Enable bounce and feedback handling
MAILING_BOUNCE_HANDLER_ENABLED=true
MAILING_BOUNCE_HANDLER_HOST=mail.yourdomain.com
MAILING_BOUNCE_HANDLER_USERNAME=bounces@yourdomain.com
MAILING_BOUNCE_HANDLER_PASSWORD=secure_password

MAILING_FEEDBACK_LOOP_ENABLED=true
MAILING_FEEDBACK_LOOP_HOST=mail.yourdomain.com
MAILING_FEEDBACK_LOOP_USERNAME=complaints@yourdomain.com

# Email authentication
MAILING_DKIM_ENABLED=true
MAILING_DKIM_SELECTOR=mail
MAILING_DKIM_PRIVATE_KEY_PATH=/secure/path/to/dkim_private.key
MAILING_DKIM_DOMAIN=yourdomain.com
MAILING_SPF_ENABLED=true
MAILING_DMARC_ENABLED=true

# Error notifications
MAILING_NOTIFY_ON_ERROR=true
MAILING_ERROR_NOTIFICATION_RECIPIENTS=admin@yourdomain.com,ops@yourdomain.com

# Conservative logging
MAILING_LOGGING_ENABLED=true
MAILING_LOGGING_LEVEL=warning
MAILING_LOG_REQUEST_BODY=false
MAILING_LOG_RESPONSE_BODY=false

# Tracking
MAILING_TRACKING_DOMAIN=track.yourdomain.com
MAILING_TRACKING_HTTPS=true

# Disable test features
MAILING_SANDBOX_MODE=false
MAILING_MOCK_API=false
MAILING_DEBUG=false
MAILING_CAMPAIGN_TEST_MODE=false
```

---

## Migration from Acelle

### Acelle Compatibility Variables

The following variables maintain backward compatibility with Acelle Mail installations:

```bash
# Application modes (Acelle legacy)
APP_DEMO=false              # Demo mode restrictions
APP_SAAS=true               # Multi-tenant SAAS mode
APP_BRAND=false             # White-label mode
APP_DRYRUN=false            # Dry run mode (log only, no sending)

# Database configuration
DB_TABLES_PREFIX=mailing_   # Table prefix for shared databases

# Import settings
IMPORT_BATCH_SIZE=9993      # Legacy import batch size

# Redis flag
REDIS_ENABLED=false         # Legacy Redis feature flag
```

### Acelle to Alsernet Mapping

| Acelle Variable | Alsernet Variable | Notes |
|----------------|-------------------|-------|
| `ACELLE_API_KEY` | `MAILING_API_KEY` | Direct mapping |
| `QUEUE_CONNECTION` | Uses Laravel config | No change needed |
| `MAIL_FROM_ADDRESS` | Uses Laravel config | No change needed |
| `ACELLE_WEBHOOK_URL` | `MAILING_WEBHOOK_PATH` | Relative path |
| `BOUNCE_HANDLER_*` | `MAILING_BOUNCE_HANDLER_*` | Prefixed |
| `FBL_HANDLER_*` | `MAILING_FEEDBACK_LOOP_*` | Renamed |

### Migration Checklist

When migrating from Acelle to Alsernet Mailing module:

- [ ] Copy `.env.example` to project root `.env`
- [ ] Replace `MAILING_URL` with your Mailrelay API endpoint
- [ ] Set `MAILING_API_KEY` with your API key
- [ ] Configure webhook secret: `MAILING_WEBHOOK_SECRET`
- [ ] Update tracking domain: `MAILING_TRACKING_DOMAIN`
- [ ] Configure bounce handler credentials (if used)
- [ ] Configure feedback loop credentials (if used)
- [ ] Set up DKIM keys (if email authentication enabled)
- [ ] Update external service API keys (AWS SES, Mailgun, etc.)
- [ ] Configure email verification service keys (if used)
- [ ] Test in sandbox mode first: `MAILING_SANDBOX_MODE=true`
- [ ] Verify queue workers are running
- [ ] Check Redis connection (if caching enabled)
- [ ] Review and adjust rate limits
- [ ] Configure error notification recipients

---

## Variable Reference

### Quick Search

Use this reference to quickly find variables by feature:

**API & Authentication**
- API connection: `MAILING_URL`, `MAILING_API_KEY`, `MAILING_TIMEOUT`
- Retry logic: `MAILING_RETRY_*`
- Rate limiting: `MAILING_RATE_LIMIT_*`

**Queues & Background Jobs**
- Sync queue: `MAILING_SYNC_*`
- Webhook queue: `MAILING_WEBHOOK_*`
- Import queue: `MAILING_IMPORT_*`
- Automation queue: `MAILING_AUTOMATION_*`

**Caching & Performance**
- Cache config: `MAILING_CACHE_*`
- Performance: `MAILING_PERFORMANCE_*`

**Email Features**
- Tracking: `MAILING_TRACK_*`, `MAILING_TRACKING_*`
- Campaigns: `MAILING_CAMPAIGN_*`
- Lists: `MAILING_LIST_*`
- Templates: `MAILING_TEMPLATE_*`

**Advanced Features**
- Bounce handling: `MAILING_BOUNCE_*`
- Feedback loops: `MAILING_FEEDBACK_LOOP_*`
- Automation: `MAILING_AUTOMATION_*`
- Segmentation: `MAILING_SEGMENTATION_*`

**Security & Validation**
- Email validation: `MAILING_VALIDATION_*`
- Email verification: `MAILING_VERIFICATION_*`
- Security: `MAILING_SECURITY_*`
- DKIM/SPF: `MAILING_DKIM_*`, `MAILING_SPF_*`

**External Services**
- AWS SES: `AWS_SES_*`
- Mailgun: `MAILGUN_*`
- SendGrid: `SENDGRID_*`
- Verification: `*_API_KEY` (14 services)

**Reporting & Analytics**
- Reports: `MAILING_REPORTING_*`
- Storage: `MAILING_STORAGE_*`

**Localization**
- Language: `MAILING_LOCALIZATION_*`

**Development**
- Testing: `MAILING_SANDBOX_MODE`, `MAILING_MOCK_API`
- Debugging: `MAILING_DEBUG`, `MAILING_LOGGING_*`

---

## Validation Rules

### Format Requirements

- **Email addresses**: Must be valid RFC 5322 format
- **URLs**: Must include protocol (http:// or https://)
- **Domains**: Must be valid FQDN without protocol
- **API keys**: Alphanumeric with hyphens/underscores allowed
- **File paths**: Absolute paths recommended
- **Integers**: Positive integers only
- **Booleans**: `true` or `false` (lowercase)

### Value Constraints

| Variable | Min | Max | Pattern |
|----------|-----|-----|---------|
| `MAILING_TIMEOUT` | 1 | 300 | Integer |
| `MAILING_CONNECT_TIMEOUT` | 1 | 60 | Integer |
| `MAILING_RETRY_MAX_ATTEMPTS` | 1 | 10 | Integer |
| `MAILING_RETRY_DELAY` | 10 | 10000 | Integer (ms) |
| `MAILING_VALIDATION_MIN_SCORE` | 0 | 100 | Integer |
| `MAILING_IMPORT_MAX_FILE_SIZE` | 1 | 500 | Integer (MB) |
| `MAILING_STORAGE_MAX_ATTACHMENT_SIZE` | 1 | 100 | Integer (MB) |
| `MAILING_WEBHOOK_MAX_PAYLOAD_SIZE` | 1024 | 10485760 | Integer (bytes) |

---

## Troubleshooting

### Common Issues

**1. API Connection Fails**
- Verify `MAILING_URL` includes `/api/v1` suffix
- Check `MAILING_API_KEY` is correct
- Increase `MAILING_TIMEOUT` if network is slow
- Check firewall allows outbound HTTPS on port 443

**2. Webhooks Not Working**
- Ensure `MAILING_WEBHOOK_ENABLED=true`
- Verify webhook URL is publicly accessible
- Check `MAILING_WEBHOOK_SECRET` matches in both systems
- Review `MAILING_WEBHOOK_IP_WHITELIST` if configured
- Enable logging: `MAILING_WEBHOOK_LOG_EVENTS=true`

**3. Queue Jobs Failing**
- Ensure queue workers are running: `php artisan queue:work`
- Check queue connection in main Laravel config
- Verify queue names match: `MAILING_SYNC_QUEUE_NAME`
- Review failed jobs table

**4. Cache Not Working**
- Verify Redis is running if using Redis driver
- Test cache connection: `php artisan cache:clear`
- Check `MAILING_CACHE_ENABLED=true`
- Verify cache driver exists: `MAILING_CACHE_DRIVER`

**5. Bounce Handler Not Processing**
- Verify IMAP credentials are correct
- Test IMAP connection manually
- Check port is accessible: `MAILING_BOUNCE_HANDLER_PORT`
- Ensure encryption matches server: `MAILING_BOUNCE_HANDLER_ENCRYPTION`
- Review bounce queue: `MAILING_BOUNCE_HANDLER_QUEUE`

---

## Next Steps

1. **Copy `.env.example` to your project**
   ```bash
   cp modules/Mailing/.env.example .env.mailing
   ```

2. **Configure required variables**
   - Set `MAILING_URL` and `MAILING_API_KEY`
   - Generate webhook secret: `php artisan key:generate --show`

3. **Choose environment profile**
   - Development: Use sandbox mode
   - Staging: Enable test mode
   - Production: Full configuration

4. **Test configuration**
   ```bash
   php artisan mailing:test-connection
   php artisan mailing:verify-config
   ```

5. **Start queue workers**
   ```bash
   php artisan queue:work --queue=mailing,webhooks,imports,automation
   ```

6. **Monitor logs**
   ```bash
   tail -f storage/logs/mailing.log
   ```

---

## Support & Documentation

- **Module Documentation**: `modules/Mailing/docs/`
- **Configuration Reference**: `modules/Mailing/config/mailing.php`
- **API Documentation**: `modules/Mailing/docs/API.md`
- **Mailrelay API Docs**: https://mailrelay.com/api/v1/docs

---

**Document Status:** Complete
**Last Updated:** 2026-01-29
**Version:** 1.0
**Maintained By:** Alsernet Development Team
