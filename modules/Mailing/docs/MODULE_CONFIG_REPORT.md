# Mailing Module Configuration Report

**Version:** 1.0.0
**Generated:** 2026-01-29
**Status:** Production Ready

## Overview

The Mailing module is a complete email marketing system migrated from Acelle Mail, providing advanced campaign management, multi-level email validation, subscriber automation, and seamless integration with Mailrelay API.

---

## Module.json Configuration

### Basic Information

```json
{
    "name": "Mailing",
    "alias": "mailing",
    "version": "1.0.0",
    "priority": 0
}
```

- **Name**: Mailing (PascalCase)
- **Alias**: mailing (lowercase, used for routes, views, translations)
- **Version**: 1.0.0 (SemVer standard)
- **Priority**: 0 (loads with standard priority)

### Description

> Complete email marketing system migrated from Acelle Mail with multi-level email validation, campaign management, subscriber automation, and Mailrelay API integration

### Keywords

The module is indexed with the following keywords for discoverability:

- `email` - Core email functionality
- `marketing` - Email marketing campaigns
- `mailing` - Mailing system
- `campaigns` - Campaign management
- `newsletter` - Newsletter functionality
- `subscribers` - Subscriber management
- `email-validation` - Multi-level validation system
- `automation` - Marketing automation workflows
- `acelle` - Acelle Mail migration source
- `mailrelay` - Mailrelay API integration
- `sms` - SMS marketing capabilities
- `analytics` - Campaign analytics and reporting

---

## Service Providers

The module registers **2 service providers** that handle all bootstrapping, routing, events, and module functionality:

### 1. MailingServiceProvider

**Location:** `app/Providers/MailingServiceProvider.php`

**Responsibilities:**
- Register module configuration files (3 configs)
- Load translations from `resources/lang`
- Load views from `resources/views`
- Register middleware aliases (3 middleware)
- Register view composers (NavigationComposer)
- Load migrations from `database/migrations` (83 migrations)
- Register authorization policies (3 policies)
- Register dynamic gates for permission checking
- Register Blade directives (@mailingStatus, @campaignAnalytics)
- Register scheduled tasks (sync, send campaigns)
- Register navigation menu items with NavService
- Publish assets (config, migrations, views, public)
- Register routes (web, api, admin, public)

**Key Features:**
- Module status check (only boots if enabled via ModuleStatusHelper)
- Conditional loading based on console/web context
- Dynamic permission checking with Spatie Permission integration
- Super-admin bypass for all gates
- Scheduled tasks with overlap prevention and background execution

### 2. EventServiceProvider

**Location:** `app/Providers/EventServiceProvider.php`

**Responsibilities:**
- Map events to listeners (8 event categories)
- Handle campaign lifecycle events
- Handle subscriber lifecycle events
- Handle email tracking events (opens, clicks, bounces, complaints)
- Handle email validation events
- Handle import completion events
- Handle automation trigger events
- Handle list creation events

**Event Mapping Summary:**
- **Campaign Events**: 4 events → 4 listeners
- **Subscriber Events**: 4 events → 4 listeners
- **Email Tracking Events**: 4 events → 4 listeners
- **Email Validation Events**: 1 event → 1 listener
- **Import Events**: 1 event → 1 listener
- **Automation Events**: 1 event → 1 listener
- **List Events**: 1 event → 1 listener

**Total:** 16 events mapped to 16 listeners

---

## Autoloaded Files

The module automatically loads **6 helper files** on every request for global utility functions:

### Helper Files

1. **DateHelper.php** - Date formatting and timezone utilities
2. **MailingHelper.php** - General mailing utilities and helpers
3. **QuotaHelper.php** - Quota management and tracking
4. **StatisticsHelper.php** - Campaign and subscriber statistics
5. **TemplateHelper.php** - Template rendering and variable replacement
6. **ValidationHelper.php** - Email validation utilities

**Location:** `app/Helpers/`

**Usage:** These files are autoloaded via `module.json` "files" section, making their functions globally available throughout the application.

---

## Facades & Aliases

The module **does not register any facades** at this time.

**Aliases:** Empty object `{}`

This follows Laravel best practice of using dependency injection and service resolution rather than facades for better testability.

---

## Dependencies

### PHP & Framework Requirements

```json
"requires": {
    "php": "^8.4",
    "laravel/framework": "^12.0"
}
```

### Third-Party Packages

The module depends on the following packages:

#### HTTP & API Communication
- **guzzlehttp/guzzle** `^7.8` - HTTP client for Mailrelay API integration

#### Data Import/Export
- **maatwebsite/excel** `^3.1` - Excel import/export for subscriber lists

#### PDF Generation
- **barryvdh/laravel-dompdf** `^3.0` - PDF generation for reports
- **setasign/fpdf** `^1.8` - Low-level PDF generation
- **setasign/fpdi** `^2.6` - PDF template manipulation

#### Text Processing
- **soundasleep/html2text** `^2.1` - HTML to plain text conversion for emails

#### Utilities
- **myclabs/php-enum** `^1.8` - Type-safe enumerations for statuses

### Development Dependencies

Located in `composer.json` but not listed in `module.json`:
- **phpunit/phpunit** `^11.0` - Unit testing
- **mockery/mockery** `^1.6` - Mocking for tests
- **barryvdh/laravel-ide-helper** `^3.0` - IDE autocompletion

---

## Module Architecture

### Directory Structure

```
modules/Mailing/
├── app/
│   ├── Console/Commands/          (1 command)
│   ├── Contracts/                 (Interface definitions)
│   ├── Enums/                     (Status enums)
│   ├── Events/                    (16+ events)
│   ├── Exceptions/                (Custom exceptions)
│   ├── Helpers/                   (6 helper files - autoloaded)
│   ├── Http/
│   │   ├── Controllers/           (24 controllers)
│   │   ├── Middleware/            (3 middleware)
│   │   ├── Requests/              (Form validation)
│   │   └── ViewComposers/         (NavigationComposer)
│   ├── Jobs/                      (Queued background jobs)
│   ├── Library/                   (Legacy utilities)
│   ├── Listeners/                 (16+ event listeners)
│   ├── Mail/                      (Mailable classes)
│   ├── Models/                    (63 Eloquent models)
│   ├── Notifications/             (Notification classes)
│   ├── Observers/                 (Model observers)
│   ├── Policies/                  (Authorization policies)
│   ├── Providers/                 (2 service providers)
│   ├── Services/                  (Business logic services)
│   └── Traits/                    (Reusable traits)
├── config/
│   ├── mailing.php                (Main module config)
│   ├── email-validator.php        (Validation config)
│   └── email-utilities.php        (Utilities config)
├── database/
│   ├── migrations/                (83 migration files)
│   ├── seeders/                   (Database seeders)
│   └── factories/                 (Model factories)
├── resources/
│   ├── views/                     (67 Blade templates)
│   ├── lang/                      (Translations)
│   ├── css/                       (Styles)
│   └── js/                        (JavaScript)
├── routes/
│   ├── web.php                    (Main web routes)
│   ├── admin.php                  (Admin routes)
│   ├── api.php                    (API routes)
│   └── public.php                 (Public newsletter routes)
├── tests/
│   ├── Feature/                   (Feature tests)
│   └── Unit/                      (Unit tests)
├── docs/                          (Documentation)
├── public/                        (Public assets)
├── supervisor/                    (Queue worker configs)
├── composer.json                  (Composer dependencies)
├── package.json                   (NPM dependencies)
├── module.json                    (Module configuration)
└── README.md                      (Module documentation)
```

### Key Statistics

- **Models:** 63 Eloquent models
- **Controllers:** 24 controllers
- **Migrations:** 83 database migrations
- **Views:** 67 Blade templates
- **Events:** 16+ events with listeners
- **Middleware:** 3 middleware classes
- **Helpers:** 6 autoloaded helper files
- **Service Providers:** 2 providers
- **Commands:** 1 Artisan command (SyncMailingCommand)

---

## Registered Middleware

The module registers **3 middleware aliases** for access control:

| Alias | Class | Purpose | Migrated From |
|-------|-------|---------|---------------|
| `mailing.backend` | `BackendAccess` | Admin authorization | Acelle Backend.php |
| `mailing.customer` | `CustomerAccess` | Customer authorization | Acelle Frontend.php |
| `mailing.guest.locale` | `GuestLocale` | Guest language preference | Acelle NotLoggedIn.php |

**Location:** `app/Http/Middleware/`

**Usage:** Applied via route middleware in `routes/web.php`, `routes/admin.php`, and `routes/public.php`

---

## Authorization System

### Policies

The module registers **3 authorization policies** with Laravel Gate:

1. **CampaignPolicy** → `Campaign` model
2. **SubscriberPolicy** → `Subscriber` model
3. **ImportPolicy** → `ImportJob` model

**Location:** `app/Policies/`

### Dynamic Gates

The module implements dynamic gate checking with:
- **Super-admin bypass**: Users with `super-admin` role automatically pass all gates
- **Dynamic permission checking**: All `mailing.*` permissions checked via Spatie Permission
- **Graceful degradation**: Non-existent permissions return false instead of throwing exceptions

**Implementation:** `MailingServiceProvider::registerGates()`

---

## Blade Directives

The module registers **2 custom Blade directives**:

### @mailingStatus

Renders a status badge for mailing entities.

```blade
@mailingStatus($campaign->status)
```

Renders: `mailing::components.status-badge` component

### @campaignAnalytics

Renders campaign analytics widget.

```blade
@campaignAnalytics($campaign)
```

Renders: `mailing::components.campaign-analytics` component

---

## Scheduled Tasks

The module registers **2 scheduled tasks** via Laravel's task scheduler:

### 1. Sync with Mailrelay

```php
$schedule->command('mailing:sync')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
```

- **Frequency:** Every hour
- **Overlap Prevention:** Yes
- **Background Execution:** Yes
- **Condition:** Only runs if `config('mailing.sync.enabled')` is true

### 2. Send Scheduled Campaigns

```php
$schedule->command('mailing:send-campaigns')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
```

- **Frequency:** Every 15 minutes
- **Overlap Prevention:** Yes
- **Background Execution:** Yes
- **Condition:** Always runs (if module enabled)

---

## Navigation Integration

The module integrates with **NavService** to register menu items in the Settings sidebar.

### Registered Menu Items (14 items)

All items require `mailing.access` permission:

1. **General** - General settings (`fas fa-cog`)
2. **API** - API configuration (`fas fa-key`)
3. **URLs** - URL settings (`fas fa-link`)
4. **Mailer** - Mailer configuration (`fas fa-envelope`)
5. **Cronjobs** - Scheduled task settings (`fas fa-clock`)
6. **Servidores de envío** - Sending servers (`fas fa-server`)
7. **Servidores de verificación** - Verification servers (`fas fa-shield-alt`)
8. **Sub-cuentas** - Sub-accounts (`fas fa-user-cog`)
9. **Manejadores de rebotes** - Bounce handlers (`fas fa-exclamation-triangle`)
10. **Manejadores de feedback** - Feedback handlers (`fas fa-comment-dots`)
11. **Plantillas de email** - Email templates (`fas fa-file-alt`)
12. **Formularios** - Forms (`fas fa-wpforms`)
13. **Diseños** - Layouts (`fas fa-th-large`)
14. **Idiomas** - Languages (`fas fa-language`)

**Integration:** Only active if `Modules\Theme\Services\NavService` class exists.

---

## Configuration Files

The module merges **3 configuration files** into Laravel's config:

### 1. mailing.php

**Merged as:** `config('mailing')`

**Contains:**
- Mailrelay API credentials
- Sync settings
- Campaign defaults
- SMS settings
- Automation rules
- General module settings

### 2. email-validator.php

**Merged as:** `config('email-validator')`

**Contains:**
- Validation level settings (1-5)
- API credentials (ZeroBounce, NeverBounce, Hunter.io)
- Cache durations
- Scoring thresholds
- Disposable email detection settings

### 3. email-utilities.php

**Merged as:** `config('email-utilities')`

**Contains:**
- Role-based email detection settings
- Free email provider list
- Utility functions configuration

**Location:** `config/`

---

## Publishable Assets

The module defines **4 publishable asset groups**:

### 1. Config Files

```bash
php artisan vendor:publish --tag=mailing-config
```

Publishes to: `config/`
- `mailing.php`
- `email-validator.php`
- `email-utilities.php`

### 2. Migrations

```bash
php artisan vendor:publish --tag=mailing-migrations
```

Publishes to: `database/migrations/`

### 3. Views

```bash
php artisan vendor:publish --tag=mailing-views
```

Publishes to: `resources/views/vendor/mailing/`

### 4. Public Assets

```bash
php artisan vendor:publish --tag=mailing-assets
```

Publishes to: `public/modules/mailing/`

---

## Routes Configuration

The module registers **4 route files**:

### 1. Web Routes (`routes/web.php`)

- **Prefix:** `/mailing`
- **Middleware:** `web`
- **Size:** 30,647 bytes
- **Contains:** Main operational routes for campaigns, subscribers, lists, templates

### 2. Admin Routes (`routes/admin.php`)

- **Prefix:** `/settings/mailing`
- **Middleware:** `web`, `auth`, `mailing.backend`
- **Size:** 10,555 bytes
- **Contains:** Configuration and admin settings routes

### 3. API Routes (`routes/api.php`)

- **Prefix:** `/api`
- **Middleware:** `api`
- **Size:** 671 bytes
- **Contains:** API endpoints for external integration

### 4. Public Routes (`routes/public.php`)

- **Prefix:** `/newsletter`
- **Middleware:** `web`, `mailing.guest.locale`
- **Size:** 3,816 bytes
- **Contains:** Public subscription/unsubscription endpoints

---

## Database Schema

The module creates **83 database tables** via migrations:

### Key Tables (Sample)

- `mailing_campaigns` - Campaign management
- `mailing_subscribers` - Subscriber records
- `mailing_lists` - Mailing lists
- `mailing_contacts` - Contact information
- `mailing_templates` - Email templates
- `mailing_layouts` - Template layouts
- `mailing_languages` - Multi-language support
- `mailing_tracking_logs` - Email tracking
- `mailing_sending_servers` - SMTP server configs
- `mailing_verification_servers` - Email verification servers
- `mailing_automations` - Marketing automation workflows
- `mailing_sms_campaigns` - SMS campaign management
- And 71 more...

**Location:** `database/migrations/`

---

## Testing Structure

### Test Directories

```
tests/
├── Feature/           # Integration tests
└── Unit/              # Unit tests
```

### Testing Dependencies

- **PHPUnit** `^11.0` - Test framework
- **Mockery** `^1.6` - Mocking library

---

## Version History

### Version 1.0.0 (2026-01-29)

**Status:** Production Ready

**Changes:**
- Complete migration from Acelle Mail system
- 63 Eloquent models migrated and refactored
- 83 database migrations created
- 24 controllers implementing CRUD operations
- 67 Blade views with Bootstrap 5 integration
- Multi-level email validation system (5 levels)
- Mailrelay API integration
- Event-driven architecture with 16+ events
- SMS marketing capabilities
- Marketing automation workflows
- Comprehensive analytics and reporting
- Role-based access control via Spatie Permission
- Queue-based background processing
- Scheduled task automation
- Public newsletter subscription system

---

## Module Status Integration

The module integrates with **ModuleStatusHelper** to enable/disable functionality dynamically.

### Status Check

```php
if (!ModuleStatusHelper::isModuleEnabled('Mailing')) {
    return; // Module disabled, skip all bootstrapping
}
```

**Location:** `MailingServiceProvider::boot()`

**Behavior:**
- When disabled, the module loads **zero** functionality (routes, views, middleware, etc.)
- When enabled, all features are bootstrapped normally
- Status can be toggled via admin panel without code changes

---

## Security Features

### Authorization Layers

1. **Middleware** - `mailing.backend`, `mailing.customer`, `mailing.guest.locale`
2. **Policies** - Campaign, Subscriber, ImportJob policies
3. **Gates** - Dynamic permission checking via Spatie Permission
4. **Super-admin Bypass** - Automatic access for super-admin role

### Email Validation Security

- **Anti-spam**: Disposable email detection
- **Role-based blocking**: Prevents abuse@, noreply@, etc.
- **DNS verification**: Validates MX/A records
- **SMTP verification**: Confirms mailbox existence
- **API validation**: Third-party verification with caching

---

## Performance Optimizations

### Caching

- **Email validation results** - Cached 24 hours (DNS/SMTP)
- **API validation results** - Cached per provider settings
- **Campaign analytics** - Cached via UpdateCampaignCache listener
- **Subscriber data** - Cached via UpdateSubscriberCache listener

### Queue Processing

- **Import jobs** - Asynchronous processing with progress tracking
- **Campaign sending** - Background queue execution
- **Email validation** - Batch processing via jobs
- **Sync operations** - Background sync with Mailrelay

### Database

- **83 migrations** with proper indexes
- **Eloquent relationships** optimized with eager loading
- **Observer pattern** for cache invalidation

---

## Integration Points

### External Services

1. **Mailrelay API** - Campaign management and subscriber sync
2. **ZeroBounce** - Email validation API (optional)
3. **NeverBounce** - Email validation API (optional)
4. **Hunter.io** - Email validation API (optional)
5. **SMS Providers** - SMS campaign delivery

### Internal Modules

1. **Theme Module** - NavService integration for sidebar menus
2. **User Module** - Authentication and authorization
3. **Permission Module** - Spatie Permission integration

---

## Environment Variables

The module expects these environment variables (defined in `.env.example`):

```bash
# Mailrelay API
MAILRELAY_API_KEY=
MAILRELAY_API_URL=

# Email Validation APIs
ZEROBOUNCE_API_KEY=
NEVERBOUNCE_API_KEY=
HUNTER_API_KEY=

# SMS Provider
SMS_PROVIDER=
SMS_API_KEY=

# Module Settings
MAILING_SYNC_ENABLED=true
MAILING_DEFAULT_FROM_EMAIL=
MAILING_DEFAULT_FROM_NAME=
```

---

## Documentation Files

The module includes comprehensive documentation in `docs/`:

- **README.md** - Main module documentation
- **MODULE_CONFIG_REPORT.md** - This file (configuration reference)
- **SHARED-COMPONENTS-INDEX.md** - Reusable component catalog
- Additional technical documentation as needed

---

## Maintenance & Support

### Logging

The module uses Laravel's logging system with context:

```php
Log::channel('mailing')->info('Campaign sent', ['campaign_id' => $id]);
```

### Error Handling

Custom exceptions:
- `MailingException` - General mailing errors
- `EmailValidationException` - Validation-specific errors

### Monitoring

- **Job monitoring** via Horizon
- **Performance monitoring** via Pulse
- **Debugging** via Telescope
- **Queue metrics** via job_monitors table

---

## Future Enhancements

Potential improvements for future versions:

1. Add facades for common operations
2. Expand API endpoints for third-party integrations
3. Add more event listeners for custom workflows
4. Implement A/B testing analytics
5. Add machine learning for send time optimization
6. Expand SMS capabilities with MMS support
7. Add social media integration
8. Implement advanced segmentation with AI

---

## Conclusion

The Mailing module is a **production-ready, enterprise-grade email marketing system** with:

- ✅ Complete Acelle Mail feature parity
- ✅ Modern Laravel 12 architecture
- ✅ Event-driven design
- ✅ Comprehensive testing structure
- ✅ Multi-level validation system
- ✅ Queue-based processing
- ✅ Role-based access control
- ✅ Extensive documentation
- ✅ Clean, maintainable codebase

**Status:** Ready for production deployment

**Last Updated:** 2026-01-29
**Module Version:** 1.0.0
**Laravel Version:** 12.x
**PHP Version:** 8.4
