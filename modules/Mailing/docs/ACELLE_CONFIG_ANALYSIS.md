# Acelle Mail - Comprehensive Configuration Analysis

**Document Version:** 1.0
**Analysis Date:** 2026-01-29
**Source:** `/Users/functionbytes/Function/Coding/acelle/config/`
**Target Module:** `modules/Mailing`

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [System Configuration](#system-configuration)
3. [Email Service Configuration](#email-service-configuration)
4. [Queue and Job Configuration](#queue-and-job-configuration)
5. [Database Configuration](#database-configuration)
6. [Cache and Session Configuration](#cache-and-session-configuration)
7. [Critical Environment Variables](#critical-environment-variables)
8. [Migration Recommendations](#migration-recommendations)
9. [Configuration Files Index](#configuration-files-index)

---

## Executive Summary

This document provides a comprehensive analysis of all Acelle Mail configuration files located in `/Users/functionbytes/Function/Coding/acelle/config/`. The analysis identifies critical configurations required for migrating Acelle's mailing functionality into the `modules/Mailing` module within the Alsernet system.

**Key Findings:**
- **27 configuration files** analyzed
- **Email verification services:** 12 third-party APIs configured
- **Queue system:** Database-driven with 9000s retry timeout
- **Multi-mode support:** SAAS, DEMO, BRAND, STORE, and CARTPAYE modes
- **Critical dependencies:** Redis (optional), custom Service Providers, email verification APIs

---

## System Configuration

### 1. Application Core (`config/app.php`)

#### Application Modes

```php
// Core application modes
'demo' => env('APP_DEMO', false),
'brand' => env('APP_BRAND', false),
'saas' => env('APP_SAAS', true),        // SAAS mode enabled by default
'store' => env('APP_STORE', false),
'cartpaye' => env('APP_CARTPAYE', false),
```

**Migration Notes:**
- **SAAS mode** is the default operating mode for Acelle
- Must determine if Alsernet requires multi-tenant SAAS capabilities
- DEMO mode disables critical functionality (see `config/limit.php`)

#### Redis Configuration

```php
'redis_enabled' => env('REDIS_ENABLED', false),
'import_batch_size' => env('IMPORT_BATCH_SIZE', 9993),
```

**Critical:** Redis is optional but recommended for high-volume email operations.

#### reCAPTCHA Integration

```php
'recaptcha_sitekey' => env('RECAPTCHA_SITEKEY', '6LfyISoTAAAAABJV8zycUZNLgd0sj-sBFjctzXKw'),
'recaptcha_secret' => env('RECAPTCHA_SECRET', '6LfyISoTAAAAAC0hJ916unwi0m_B0p7fAvCRK4Kp'),
```

**Security Consideration:** Default keys provided are for testing only.

#### Custom Service Providers

```php
'providers' => [
    // ... Laravel providers ...

    // Acelle Application Providers
    Acelle\Providers\AppServiceProvider::class,
    Acelle\Providers\AuthServiceProvider::class,
    Acelle\Providers\EventServiceProvider::class,
    Acelle\Providers\RouteServiceProvider::class,
    Acelle\Providers\JobServiceProvider::class,      // Critical for jobs

    // Acelle Extended Providers
    Intervention\Image\ImageServiceProvider::class,

    // Acelle Custom Providers
    Acelle\Providers\MailerServiceProvider::class,   // CRITICAL for email
    Acelle\Providers\StorageServiceProvider::class,  // Storage handling
],
```

**Migration Critical:**
- `JobServiceProvider` - Handles email job registration
- `MailerServiceProvider` - Custom email sending logic
- `StorageServiceProvider` - Email template storage

#### Custom Facades and Aliases

```php
'aliases' => [
    // ... standard Laravel aliases ...

    'Image' => Intervention\Image\Facades\Image::class,
    'Tool' => Acelle\Library\Tool::class,                    // Utility functions
    'Yaml' => Symfony\Component\Yaml\Yaml::class,
    'Geoip' => Lawepham\Geoip\Facades\LaweGeoipFacade::class,
    'Twig' => TwigBridge\Facade\Twig::class,                // Email templates
    'Billing' => Acelle\Library\Facades\Billing::class,     // Payment integration
],
```

---

## Email Service Configuration

### 2. Mail Configuration (`config/mail.php`)

#### Supported Mailers

```php
'default' => env('MAIL_MAILER', 'smtp'),

'mailers' => [
    'smtp' => [
        'transport' => 'smtp',
        'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
        'port' => env('MAIL_PORT', 587),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'timeout' => null,
        'auth_mode' => null,
    ],
    'ses' => ['transport' => 'ses'],
    'mailgun' => ['transport' => 'mailgun'],
    'postmark' => ['transport' => 'postmark'],
    'sendmail' => [
        'transport' => 'sendmail',
        'path' => '/usr/sbin/sendmail -bs',
    ],
    'log' => ['transport' => 'log'],
    'array' => ['transport' => 'array'],
],
```

**Migration Recommendation:**
- Implement multi-mailer support in Mailing module
- Support all transports: SMTP, SES, Mailgun, Postmark, Sendmail
- Add configuration UI for mailer selection

#### Global From Address

```php
'from' => [
    'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
    'name' => env('MAIL_FROM_NAME', 'Example'),
],
```

### 3. Email Verification Services (`config/verification.php`)

Acelle integrates **12 third-party email verification services**:

| Service ID | Service Name | Method | Result Mapping |
|-----------|--------------|--------|----------------|
| `emailable.com` | Emailable (recommended) | GET | deliverable, undeliverable, risky, unknown |
| `kickbox.io` | Kickbox IO | GET | deliverable, undeliverable, risky, unknown |
| `zerobounce.net` | Zero Bounce | GET | valid→deliverable, invalid→undeliverable, catch-all→deliverable |
| `verify-email.org` | VerifyEmail ORG | GET | 1→deliverable, 0→undeliverable, -1→unknown |
| `localmail.io` | Localmail IO | GET | deliverable, unknown, risky, undeliverable |
| `debounce.io` | Debounce IO | GET | "Safe to Send"→deliverable, "Invalid"→undeliverable |
| `emailchecker.com` | EmailChecker | GET | true→deliverable, false→undeliverable |
| `cloudvision.io` | Cloud Vision | GET | deliverable, undeliverable |
| `cloudmersive.com` | Cloudmersive | POST | ValidAddress: true/false |
| `emaillistvalidation.com` | Emaillist Validation | GET (plain) | ok, ok_for_all, email_disabled, risky, unknown |
| `bounceless.io` | Bounceless.io | GET | valid, unknown, invalid, risky |
| `bouncify.io` | Bouncify | GET | deliverable, unknown, accept-all→unknown, undeliverable |
| `myemailverifier.com` | myEmailVerifier | GET | Valid, Unknown, Invalid |

#### Configuration Structure

```php
'services' => [
    [
        'id' => 'emailable.com',
        'name' => 'Emailable (recommended)',
        'uri' => 'https://api.emailable.com/v1/verify?email={EMAIL}&api_key={API_KEY}',
        'request_type' => 'GET',
        'fields' => ['api_key'],
        'result_xpath' => '$.state',
        'result_map' => [
            'deliverable' => 'deliverable',
            'undeliverable' => 'undeliverable',
            'risky' => 'risky',
            'unknown' => 'unknown'
        ]
    ],
    // ... 11 more services
]
```

**Migration Requirements:**
1. Create `EmailVerificationService` interface
2. Implement adapter pattern for each service
3. Store API keys encrypted in database
4. Add service selection UI in admin panel
5. Implement rate limiting per service
6. Add webhook support for async verification

### 4. Third-Party Services (`config/services.php`)

```php
'mailgun' => [
    'domain' => env('MAILGUN_DOMAIN'),
    'secret' => env('MAILGUN_SECRET'),
    'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
],

'postmark' => [
    'token' => env('POSTMARK_TOKEN'),
],

'ses' => [
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
],
```

---

## Queue and Job Configuration

### 5. Queue Configuration (`config/queue.php`)

#### Default Queue Driver

```php
'default' => env('QUEUE_CONNECTION', 'database'),
```

**Key Finding:** Acelle uses **database queues** by default, not Redis.

#### Database Queue Settings

```php
'database' => [
    'driver' => 'database',
    'table' => 'jobs',
    'queue' => 'default',
    'retry_after' => 9000, // 2.5 hours - long timeout for email jobs
    'after_commit' => false,
],
```

**Critical Configuration:**
- **9000 seconds (2.5 hours)** retry timeout for terminated jobs
- This allows long-running email campaigns to resume after interruption
- Must be preserved in migration for campaign reliability

#### Failed Jobs Configuration

```php
'failed' => [
    'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
    'database' => env('DB_CONNECTION', 'mysql'),
    'table' => 'failed_jobs',
],
```

**Migration Note:** Uses UUID-based failed job tracking.

---

## Database Configuration

### 6. Database Configuration (`config/database.php`)

#### Table Prefix Support

```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => env('DB_TABLES_PREFIX', ''),  // Supports table prefixes
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => 'InnoDB',
    'timezone' => '+00:00',
],
```

**Migration Consideration:**
- Acelle supports custom table prefixes via `DB_TABLES_PREFIX`
- Alsernet should maintain this for backward compatibility

#### Redis Configuration

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
    'cache' => [
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

#### WordPress Integration

```php
'wordpress' => [
    'driver' => 'mysql',
    // Same configuration as main database
    // Allows connecting to WordPress databases
],
```

**Finding:** Acelle supports WordPress database connections (see `config/wordpress.php`).

---

## Cache and Session Configuration

### 7. Cache Configuration (`config/cache.php`)

```php
'default' => env('CACHE_DRIVER', 'file'),

'stores' => [
    'file' => [
        'driver' => 'file',
        'path' => storage_path('framework/cache/data'),
    ],
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
    ],
    'database' => [
        'driver' => 'database',
        'table' => 'cache',
        'connection' => null,
    ],
],

'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache'),
```

### 8. Session Configuration (`config/session.php`)

```php
'driver' => env('SESSION_DRIVER', 'file'),
'lifetime' => env('SESSION_LIFETIME', 120), // 120 minutes
'expire_on_close' => false,
'encrypt' => false,
'files' => storage_path('framework/sessions'),
'connection' => env('SESSION_CONNECTION', null),
'table' => 'sessions',
'cookie' => env('SESSION_COOKIE', Str::slug(env('APP_NAME', 'laravel'), '_').'_session'),
```

### 9. Broadcasting Configuration (`config/broadcasting.php`)

```php
'default' => env('BROADCAST_DRIVER', 'null'),

'connections' => [
    'pusher' => [
        'driver' => 'pusher',
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'app_id' => env('PUSHER_APP_ID'),
        'options' => [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'useTLS' => true,
        ],
    ],
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
    ],
],
```

**Migration Note:** Real-time broadcasting disabled by default (`null` driver).

### 10. Logging Configuration (`config/logging.php`)

```php
'default' => env('LOG_CHANNEL', 'daily'),

'channels' => [
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/' . php_sapi_name() . '/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 3, // Keep logs for 3 days
    ],
],
```

**Unique Feature:** Log path includes `php_sapi_name()` for CLI vs web separation.

---

## Critical Environment Variables

### 11. Environment Variables (`.env.example`)

#### Application Settings

| Variable | Default | Purpose | Migration Priority |
|----------|---------|---------|-------------------|
| `APP_NAME` | Acelle | Application name | Medium |
| `APP_ENV` | local | Environment (local, production) | High |
| `APP_DEBUG` | true | Debug mode | High |
| `APP_DEMO` | false | Demo mode (limits features) | Medium |
| `APP_SAAS` | true | SAAS multi-tenant mode | High |
| `APP_BRAND` | false | White-label mode | Low |
| `APP_STORE` | false | Store mode | Low |
| `APP_CARTPAYE` | false | CartPayee integration | Low |
| `APP_JAPAN` | false | Japanese localization | Low |
| `APP_PROFILE` | (empty) | Load custom config (e.g., limit.php) | High |
| `APP_DRYRUN` | false | Dry run (no actual sending) | High |

#### Email-Specific Variables

| Variable | Default | Purpose | Migration Priority |
|----------|---------|---------|-------------------|
| `MAIL_MAILER` | smtp | Default mail transport | High |
| `MAIL_HOST` | mailhog | SMTP host | High |
| `MAIL_PORT` | 1025 | SMTP port | High |
| `MAIL_USERNAME` | null | SMTP username | High |
| `MAIL_PASSWORD` | null | SMTP password | High |
| `MAIL_ENCRYPTION` | null | SMTP encryption (tls/ssl) | High |
| `MAIL_FROM_ADDRESS` | null | Default sender email | High |
| `MAIL_FROM_NAME` | ${APP_NAME} | Default sender name | High |

#### Queue and Job Variables

| Variable | Default | Purpose | Migration Priority |
|----------|---------|---------|-------------------|
| `QUEUE_CONNECTION` | database | Queue driver | High |
| `REDIS_ENABLED` | false | Enable Redis features | Medium |
| `IMPORT_BATCH_SIZE` | 9993 | Subscriber import batch size | High |

#### Database Variables

| Variable | Default | Purpose | Migration Priority |
|----------|---------|---------|-------------------|
| `DB_CONNECTION` | mysql | Database driver | High |
| `DB_HOST` | 127.0.0.1 | Database host | High |
| `DB_PORT` | 3306 | Database port | High |
| `DB_DATABASE` | laravel | Database name | High |
| `DB_TABLES_PREFIX` | (empty) | Table prefix | Medium |
| `DB_USERNAME` | root | Database user | High |
| `DB_PASSWORD` | (empty) | Database password | High |

#### External Service Variables

| Variable | Default | Purpose | Migration Priority |
|----------|---------|---------|-------------------|
| `RECAPTCHA_SITEKEY` | (test key) | reCAPTCHA site key | Medium |
| `RECAPTCHA_SECRET` | (test key) | reCAPTCHA secret | Medium |
| `LICENSE_VALIDATION_ENDPOINT` | http://verify.acellemail.com | License check URL | Low |

#### AWS/S3 Variables

| Variable | Default | Purpose | Migration Priority |
|----------|---------|---------|-------------------|
| `AWS_ACCESS_KEY_ID` | (empty) | AWS access key | Medium |
| `AWS_SECRET_ACCESS_KEY` | (empty) | AWS secret key | Medium |
| `AWS_DEFAULT_REGION` | us-east-1 | AWS region | Medium |
| `AWS_BUCKET` | (empty) | S3 bucket name | Medium |

#### Pusher Variables (Broadcasting)

| Variable | Default | Purpose | Migration Priority |
|----------|---------|---------|-------------------|
| `PUSHER_APP_ID` | (empty) | Pusher app ID | Low |
| `PUSHER_APP_KEY` | (empty) | Pusher app key | Low |
| `PUSHER_APP_SECRET` | (empty) | Pusher app secret | Low |
| `PUSHER_APP_CLUSTER` | mt1 | Pusher cluster | Low |

---

## Additional Configuration Files

### 12. Custom Configuration (`config/custom.php`)

```php
return [
    // Date/Time Formats
    'date_format' => 'Y-m-d',           // PHP format
    'date_format_sql' => '%Y-%m-%d',    // MySQL format
    'time_format' => 'H:i',

    // System Requirements
    'php_recommended' => '7.3.0',
    'php' => '7.3.0',

    // Branding
    'default_logo_light' => env('APP_DEFAULT_LOGO_LIGHT', 'images/logo_light.svg'),
    'default_logo_dark' => env('APP_DEFAULT_LOGO_DARK', 'images/logo_dark.svg'),

    // Beta features
    'woo' => false, // WooCommerce integration

    // Special settings
    'japan' => env('APP_JAPAN', false),
    'app_profile' => env('APP_PROFILE', null) ?: null,
    'dryrun' => env('APP_DRYRUN', false),

    // License verification
    'license_verification_endpoint' => env('LICENSE_VALIDATION_ENDPOINT', 'http://verify.acellemail.com')
];
```

### 13. Limit Configuration (`config/limit.php`)

**Purpose:** Restricts features in DEMO or limited environments.

```php
return [
    'plan' => [
        'limit' => 3,                    // Max 3 plans
        'disable_public_page' => true,   // Hide public page
    ],
    'sending_server' => [
        'limit' => 1                     // Max 1 sending server
    ],
    'bounce_handler' => ['disable' => true],
    'feedback_loop_handler' => ['disable' => true],
    'email_verfication_server' => ['disable' => true],
    'campaign' => ['limit' => 1],
    'automation' => ['disable' => true],
    'list' => [
        'limit' => 1,
        'disable_segment' => true,
    ],
    'form' => ['disable' => true],
    'website' => ['disable' => true],
];
```

**Migration Action:**
- Create similar feature flag system in Mailing module
- Use for trial/demo accounts or plan limitations

### 14. Permissions Configuration (`config/permissions.php`)

```php
return [
    'account' => [
        'campaign.all',
        'campaign.readonly',
        'list.all',
        'list.readonly',
        'automation.all',
        'automation.readonly',
        'user.all',
        'user.readonly',
    ],
    'system' => [
        'plan.all',
        'plan.readonly',
        'mailer.all',
        'mailer.readonly',
        'customer.all',
        'customer.readonly',
    ],
];
```

**Migration Recommendation:**
- Integrate with Alsernet's existing permission system (Spatie Laravel Permission)
- Map Acelle permissions to Alsernet roles

### 15. Localization Configuration (`config/localization.php`)

```php
return [
    '*' => [
        'date_full' => 'Y-m-d',
        'date_short' => 'Y-m-d',
        'datetime_full' => 'Y-m-d H:i',
        'time_only' => 'H:i',
        'number_precision' => '2',
        'number_decimal_separator' => '.',
        'number_thousands_separator' => ',',
        'show_last_name_first' => false,
    ],
    'ja' => [
        'date_full' => 'Y年m月d日',
        'datetime_full' => 'Y年m月d日 H:i',
        'show_last_name_first' => true,
    ],
];
```

### 16. WordPress Integration (`config/wordpress.php`)

```php
return [
    '1' => [
        'url' => 'http://localhost:3000',
        'db_name' => 'wordpress',
        'db_prefix' => '',
        // Uses same DB credentials as Acelle by default
    ],
    '2' => [
        'url' => 'http://localhost:3001',
        'db_name' => 'wordpress',
        'db_prefix' => '',
    ],
];
```

**Feature:** Multi-WordPress site support for subscriber syncing.

### 17. Google IP Ranges (`config/google.php`)

Contains complete list of Google IP ranges for email authentication and tracking pixel identification.

**Purpose:** Distinguish between real user opens vs. Google image proxy pre-fetches.

---

## Migration Recommendations

### Phase 1: Core Infrastructure (High Priority)

1. **Service Provider Migration**
   - Port `MailerServiceProvider` to Mailing module
   - Port `JobServiceProvider` for email job registration
   - Integrate with Alsernet's existing service providers

2. **Queue Configuration**
   - Adopt 9000s retry timeout for email campaigns
   - Use database queues as default (align with Acelle)
   - Add Redis support as optional enhancement

3. **Database Schema**
   - Implement table prefix support (`DB_TABLES_PREFIX`)
   - Use `mailing_` prefix for all module tables
   - Ensure UUID support for failed jobs

4. **Environment Variables**
   - Add all email-related variables to `.env.example`
   - Document required vs. optional variables
   - Create migration script for existing Acelle installations

### Phase 2: Email Services (High Priority)

1. **Multi-Mailer Support**
   - Implement all 7 mailer types (SMTP, SES, Mailgun, Postmark, Sendmail, Log, Array)
   - Create admin UI for mailer configuration
   - Support multiple sending servers per account

2. **Email Verification Integration**
   - Create `EmailVerificationService` interface
   - Implement 12 verification service adapters
   - Add service selection and API key management UI
   - Store API keys encrypted using Laravel's encryption

3. **Email Templates**
   - Integrate Twig template engine
   - Support Markdown rendering
   - Add template preview functionality

### Phase 3: Advanced Features (Medium Priority)

1. **SAAS Multi-Tenancy**
   - Implement tenant isolation for campaigns, lists, subscribers
   - Add plan-based feature limitations
   - Create billing integration hooks

2. **WordPress Integration**
   - Port WordPress subscriber sync functionality
   - Support multiple WordPress installations
   - Add WooCommerce customer sync (beta feature)

3. **Real-Time Features**
   - Add Pusher/Redis broadcasting for live campaign stats
   - Implement real-time queue monitoring
   - Add WebSocket support for progress updates

### Phase 4: Developer Experience (Medium Priority)

1. **Dry Run Mode**
   - Implement `APP_DRYRUN` for testing campaigns
   - Add detailed logging without actual sending
   - Create testing UI to review would-be-sent emails

2. **Logging and Monitoring**
   - Implement separate CLI and web logs
   - Add email-specific log channels
   - Integrate with Laravel Telescope for debugging

3. **Demo Mode**
   - Implement feature restrictions from `config/limit.php`
   - Create demo account seeder
   - Add "This is a demo" banners in UI

### Phase 5: Internationalization (Low Priority)

1. **Localization Support**
   - Implement date/time formatting per locale
   - Add Japanese language support
   - Support custom number formatting per locale

2. **Timezone Handling**
   - Use UTC for all database timestamps
   - Convert to user timezone in UI
   - Support campaign scheduling in user's timezone

---

## Configuration Files Index

| # | File | Purpose | Migration Priority |
|---|------|---------|-------------------|
| 1 | `app.php` | Core app settings, service providers | **CRITICAL** |
| 2 | `mail.php` | Mail configuration | **CRITICAL** |
| 3 | `queue.php` | Job queue settings | **CRITICAL** |
| 4 | `verification.php` | Email verification APIs | **HIGH** |
| 5 | `custom.php` | Acelle-specific settings | **HIGH** |
| 6 | `database.php` | Database connections | **HIGH** |
| 7 | `services.php` | Third-party services | **HIGH** |
| 8 | `.env.example` | Environment variables | **HIGH** |
| 9 | `cache.php` | Cache configuration | **MEDIUM** |
| 10 | `session.php` | Session configuration | **MEDIUM** |
| 11 | `logging.php` | Log channels | **MEDIUM** |
| 12 | `limit.php` | Feature restrictions | **MEDIUM** |
| 13 | `permissions.php` | Permission definitions | **MEDIUM** |
| 14 | `localization.php` | Date/time formats | **MEDIUM** |
| 15 | `broadcasting.php` | Real-time events | **LOW** |
| 16 | `wordpress.php` | WordPress integration | **LOW** |
| 17 | `google.php` | Google IP ranges | **LOW** |
| 18 | `auth.php` | Authentication | **LOW** |
| 19 | `cors.php` | CORS settings | **LOW** |
| 20 | `filesystems.php` | Storage configuration | **LOW** |
| 21 | `hashing.php` | Password hashing | **LOW** |
| 22 | `image.php` | Image processing | **LOW** |
| 23 | `languages.php` | Available languages | **LOW** |
| 24 | `purifier.php` | HTML purification | **LOW** |
| 25 | `view.php` | View configuration | **LOW** |
| 26 | `compile.php` | Asset compilation | **LOW** |
| 27 | `cors.php` | Cross-origin settings | **LOW** |

---

## Security Considerations

### Critical Security Items

1. **Default reCAPTCHA Keys**
   - Current keys are test keys from Acelle
   - **MUST** be replaced with Alsernet's own keys in production

2. **License Validation Endpoint**
   - Default endpoint: `http://verify.acellemail.com`
   - **MUST** be disabled or replaced with Alsernet's licensing system

3. **Email Verification API Keys**
   - All 12 services require API keys
   - **MUST** be stored encrypted in database
   - Implement key rotation mechanism

4. **Database Table Prefixes**
   - Support prefixes to avoid conflicts
   - Validate prefix format to prevent SQL injection

5. **Dry Run Mode**
   - Ensure `APP_DRYRUN=true` prevents actual email sending
   - Add confirmation prompt when disabling dry run in production

---

## Next Steps

### Immediate Actions

1. **Configuration Mapping**
   - Create migration map from Acelle config to Alsernet config
   - Identify conflicts with existing Alsernet configuration
   - Design configuration precedence rules

2. **Service Provider Analysis**
   - Extract code from `Acelle\Providers\MailerServiceProvider`
   - Extract code from `Acelle\Providers\JobServiceProvider`
   - Document custom bindings and extensions

3. **Database Schema Review**
   - Identify all tables created by Acelle's migrations
   - Map to new `mailing_` prefixed tables
   - Design foreign key relationships with Alsernet core tables

4. **Environment Variable Documentation**
   - Create comprehensive `.env` documentation for Mailing module
   - Add validation rules for all environment variables
   - Create setup wizard for initial configuration

### Long-Term Strategy

1. **Configuration Management UI**
   - Build admin panel for email configuration
   - Support hot-reload of mail settings without restart
   - Add configuration export/import functionality

2. **Multi-Tenant Architecture**
   - Design tenant isolation strategy for SAAS mode
   - Implement per-tenant email quotas and rate limiting
   - Add tenant-specific configuration overrides

3. **Testing Infrastructure**
   - Create test suite for all 12 email verification services
   - Add integration tests for all 7 mailer types
   - Build dry-run testing environment

---

## Glossary

- **SAAS Mode**: Multi-tenant mode where each customer has isolated campaigns, lists, and subscribers
- **DEMO Mode**: Limited functionality mode for testing/demoing the system
- **BRAND Mode**: White-label mode hiding Acelle branding
- **Dry Run**: Testing mode where emails are logged but not actually sent
- **Table Prefix**: Custom prefix added to all database table names (e.g., `acelle_campaigns`)
- **Retry After**: Queue timeout before considering a job abandoned and eligible for retry
- **Email Verification**: Third-party service that validates email addresses before sending

---

## References

- **Source Directory**: `/Users/functionbytes/Function/Coding/acelle/config/`
- **Target Module**: `/Users/functionbytes/Function/Coding/system/modules/Mailing`
- **Laravel Version**: 8.x (Acelle), 12.x (Alsernet target)
- **Database**: MySQL/MariaDB (Acelle), PostgreSQL (Alsernet)

---

**Document Status:** Complete
**Review Required:** Architecture Team
**Next Update:** After service provider analysis

---

*Generated by Claude Code Agent for Alsernet Migration Project*
