# Acelle Controllers Analysis Report

**Generated**: 2026-01-29
**Source Path**: `/Users/functionbytes/Function/Coding/acelle/app/Http/Controllers/`
**Purpose**: Complete analysis of Acelle Mail controllers for integration into the Mailing module

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Complete Controllers List](#complete-controllers-list)
3. [Critical Mailing Controllers](#critical-mailing-controllers)
4. [Controller Structure Analysis](#controller-structure-analysis)
5. [Routes Mapping](#routes-mapping)
6. [Dependencies and Services](#dependencies-and-services)
7. [Middleware and Authorization](#middleware-and-authorization)
8. [Integration Recommendations](#integration-recommendations)

---

## Executive Summary

Acelle Mail contains **101+ controllers** organized into multiple namespaces:
- Root Controllers (37)
- Admin Controllers (27)
- API Controllers (8)
- Auth Controllers (5)
- Site Controllers (8)
- Store Controllers (5)
- Public (Pub) Controllers (1)

**Key Finding**: The mailing system is highly modular with clear separation between customer-facing, admin, and API interfaces.

---

## Complete Controllers List

### Root Level Controllers (37)

| Controller | Purpose | Priority |
|------------|---------|----------|
| `CampaignController` | Campaign management (CRUD, tracking, reporting) | **CRITICAL** |
| `MailListController` | Email list management | **CRITICAL** |
| `SubscriberController` | Subscriber management (import/export) | **CRITICAL** |
| `Automation2Controller` | Marketing automation workflows | **CRITICAL** |
| `SendingServerController` | SMTP/API server configuration | **CRITICAL** |
| `TemplateController` | Email template management | **HIGH** |
| `SegmentController` | Subscriber segmentation | **HIGH** |
| `FieldController` | Custom list fields | **HIGH** |
| `SenderController` | Verified sender identities | **HIGH** |
| `SendingDomainController` | Domain verification | **HIGH** |
| `TrackingDomainController` | Custom tracking domains | **MEDIUM** |
| `BlacklistController` | Email blacklist management | **MEDIUM** |
| `EmailVerificationServerController` | Email validation services | **MEDIUM** |
| `FormController` | Subscription forms | **MEDIUM** |
| `WebsiteController` | Website tracking | **LOW** |
| `AudienceController` | Audience management | **MEDIUM** |
| `AutoTrigger` | Automatic campaign triggers | **HIGH** |
| `DeliveryController` | Delivery monitoring | **MEDIUM** |
| `NotificationController` | System notifications | **LOW** |
| `AccountController` | User account settings | **LOW** |
| `UserController` | User management | **LOW** |
| `CustomerController` | Customer profiles | **LOW** |
| `PlanController` | Subscription plans | **LOW** |
| `SubscriptionController` | Billing subscriptions | **LOW** |
| `InvoiceController` | Invoicing | **LOW** |
| `ProductController` | Product management | **LOW** |
| `SourceController` | Traffic sources | **LOW** |
| `PageController` | Landing pages | **LOW** |
| `SearchController` | Global search | **LOW** |
| `SettingController` | Application settings | **LOW** |
| `AuthController` | Authentication | **LOW** |
| `AdminController` | Admin dashboard | **LOW** |
| `HomeController` | Customer dashboard | **LOW** |
| `InstallController` | Installation wizard | **LOW** |
| `ChatController` | Support chat | **LOW** |
| `SamplesController` | Sample data | **LOW** |
| `EmailController` | Email utilities | **MEDIUM** |
| `Controller` | Base controller | **INFRASTRUCTURE** |

### Admin Controllers (27)

| Controller | Purpose | Priority |
|------------|---------|----------|
| `Admin\CustomerController` | Admin customer management | **MEDIUM** |
| `Admin\SendingServerController` | Admin server management | **HIGH** |
| `Admin\PlanController` | Admin plan configuration | **LOW** |
| `Admin\SubscriptionController` | Admin subscription oversight | **LOW** |
| `Admin\TrackingLogController` | Admin tracking logs | **HIGH** |
| `Admin\BounceLogController` | Admin bounce logs | **HIGH** |
| `Admin\ClickLogController` | Admin click logs | **HIGH** |
| `Admin\OpenLogController` | Admin open logs | **HIGH** |
| `Admin\UnsubscribeLogController` | Admin unsubscribe logs | **HIGH** |
| `Admin\FeedbackLogController` | Admin feedback logs | **MEDIUM** |
| `Admin\BounceHandlerController` | Bounce handler config | **HIGH** |
| `Admin\FeedbackLoopHandlerController` | FBL handler config | **MEDIUM** |
| `Admin\EmailVerificationServerController` | Admin verification servers | **MEDIUM** |
| `Admin\AdminController` | Admin user management | **LOW** |
| `Admin\AdminGroupController` | Admin role groups | **LOW** |
| `Admin\Admin2Controller` | Secondary admin | **LOW** |
| `Admin\AdminGroup2Controller` | Secondary admin groups | **LOW** |
| `Admin\UserController` | Admin user oversight | **LOW** |
| `Admin\HomeController` | Admin dashboard | **LOW** |
| `Admin\SettingController` | Admin settings | **LOW** |
| `Admin\LanguageController` | Language management | **LOW** |
| `Admin\LayoutController` | Layout templates | **LOW** |
| `Admin\CurrencyController` | Currency settings | **LOW** |
| `Admin\TaxController` | Tax configuration | **LOW** |
| `Admin\SubAccountController` | Sub-account management | **LOW** |
| `Admin\PluginController` | Plugin management | **LOW** |
| `Admin\GeoIpController` | GeoIP configuration | **LOW** |
| `Admin\ChatController` | Admin chat oversight | **LOW** |
| `Admin\PaymentController` | Payment gateway config | **LOW** |
| `Admin\Upgrade` | System upgrades | **LOW** |
| `Admin\FormTemplateController` | Form templates | **LOW** |
| `Admin\NotificationController` | Admin notifications | **LOW** |
| `Admin\SearchController` | Admin search | **LOW** |
| `Admin\AccountController` | Admin accounts | **LOW** |
| `Admin\AuthController` | Admin authentication | **LOW** |
| `Admin\ApiController` | Admin API management | **LOW** |
| `Admin\BlacklistController` | Admin blacklist management | **MEDIUM** |

### API Controllers (8)

| Controller | Purpose | Priority |
|------------|---------|----------|
| `Api\CampaignController` | Campaign API endpoints | **CRITICAL** |
| `Api\MailListController` | Mail list API endpoints | **CRITICAL** |
| `Api\SubscriberController` | Subscriber API endpoints | **CRITICAL** |
| `Api\AutomationController` | Automation API endpoints | **HIGH** |
| `Api\SendingServerController` | Sending server API | **HIGH** |
| `Api\CustomerController` | Customer API endpoints | **MEDIUM** |
| `Api\SubscriptionController` | Subscription API | **LOW** |
| `Api\NotificationController` | Notification API | **LOW** |
| `Api\FileController` | File upload API | **MEDIUM** |

### Auth Controllers (5)

| Controller | Purpose |
|------------|---------|
| `Auth\LoginController` | User login |
| `Auth\RegisterController` | User registration |
| `Auth\ForgotPasswordController` | Password reset |
| `Auth\ResetPasswordController` | Password reset completion |
| `Auth\PasswordController` | Password management |

### Site Controllers (8)

| Controller | Purpose |
|------------|---------|
| `Site\SourceController` | Site source management |
| `Site\ProductController` | Site product catalog |
| `Site\CategoryController` | Site categories |
| `Site\CustomerController` | Site customer handling |
| `Site\OrderController` | Site order processing |
| `Site\TemplateController` | Site templates |
| `Site\SettingController` | Site settings |
| `Site\MenuController` | Site menu management |

### Store Controllers (5)

| Controller | Purpose |
|------------|---------|
| `Store\ProductController` | E-commerce products |
| `Store\CategoryController` | Product categories |
| `Store\AttributeController` | Product attributes |
| `Store\MediaController` | Product media |
| `Store\FunnelController` | Sales funnels |

### Public Controllers (1)

| Controller | Purpose | Priority |
|------------|---------|----------|
| `Pub\CampaignController` | Public campaign reports | **HIGH** |

---

## Critical Mailing Controllers

### 1. CampaignController

**Location**: `/app/Http/Controllers/CampaignController.php`
**Lines of Code**: ~2000+
**Namespace**: `Acelle\Http\Controllers`

#### Main Methods

| Method | Route | Purpose | HTTP |
|--------|-------|---------|------|
| `index()` | `/campaigns` | List all campaigns | GET |
| `listing()` | `/campaigns/listing` | Paginated campaign list | GET |
| `create()` | `/campaigns/create` | Show create form | GET |
| `store()` | `/campaigns` | Create new campaign | POST |
| `edit($uid)` | `/campaigns/{uid}/edit` | Edit campaign | GET |
| `update($uid)` | `/campaigns/{uid}` | Update campaign | PATCH |
| `delete()` | `/campaigns/delete` | Bulk delete campaigns | POST |
| `overview($uid)` | `/campaigns/{uid}/overview` | Campaign dashboard | GET |
| `quickView()` | `/campaigns/quick-view` | Quick stats modal | GET |
| `chart24h($uid)` | `/campaigns/{uid}/chart24h` | 24-hour performance | GET |
| `chart($uid)` | `/campaigns/{uid}/chart` | Performance chart | GET |
| `trackingLog($uid)` | `/campaigns/{uid}/tracking-log` | Email tracking logs | GET |
| `openLog($uid)` | `/campaigns/{uid}/open-log` | Email opens | GET |
| `clickLog($uid)` | `/campaigns/{uid}/click-log` | Link clicks | GET |
| `bounceLog($uid)` | `/campaigns/{uid}/bounce-log` | Bounce logs | GET |
| `feedbackLog($uid)` | `/campaigns/{uid}/feedback-log` | Complaint logs | GET |
| `unsubscribeLog($uid)` | `/campaigns/{uid}/unsubscribe-log` | Unsubscribes | GET |
| `subscribers($uid)` | `/campaigns/{uid}/subscribers` | Campaign recipients | GET |
| `openMap($uid)` | `/campaigns/{uid}/open-map` | Geographic open map | GET |
| `links($uid)` | `/campaigns/{uid}/links` | Link performance | GET |
| `send($uid)` | `/campaigns/{uid}/send` | Queue campaign | POST |
| `pause($uid)` | `/campaigns/{uid}/pause` | Pause sending | POST |
| `restart($uid)` | `/campaigns/{uid}/restart` | Resume sending | POST |
| `copy()` | `/campaigns/copy` | Duplicate campaign | POST |
| `sendTestEmail($uid)` | `/campaigns/{uid}/send-test-email` | Send test | POST |
| `preview($uid)` | `/campaigns/{uid}/preview` | Preview email | GET |
| `webView($message_id)` | `/campaigns/{message_id}/web-view` | Web version | GET |
| `open($message_id)` | `/p/{message_id}/open` | Tracking pixel | GET |
| `click($url, $message_id)` | `/p/{url}/click/{message_id}` | Click tracking | GET |
| `unsubscribe($subscriber, $message_id)` | `/c/{subscriber}/unsubscribe/{message_id}` | Unsubscribe link | GET |

#### Dependencies

```php
use Acelle\Model\Campaign;
use Acelle\Model\MailList;
use Acelle\Model\Subscriber;
use Acelle\Model\SendingServer;
use Acelle\Model\Template;
use Acelle\Jobs\SendCampaignJob;
use Acelle\Library\Facades\Hook;
```

#### Authorization Pattern

```php
// Gate-based authorization
if (!$request->user()->customer->can('read', $campaign)) {
    return $this->notAuthorized();
}

// Policy methods: read, create, update, delete, send, pause, restart
```

#### Key Features

- **Multi-step Campaign Creation**: Setup → Recipients → Template → Schedule → Review
- **Real-time Tracking**: Opens, clicks, bounces, complaints tracked via pixel/links
- **Batch Processing**: Uses queued jobs for sending large campaigns
- **A/B Testing**: Support for subject line testing
- **Scheduling**: Immediate send or scheduled delivery
- **Segmentation**: Send to full list or specific segments
- **Performance Analytics**: Charts, maps, detailed logs
- **Export Capabilities**: CSV export of tracking logs

---

### 2. MailListController

**Location**: `/app/Http/Controllers/MailListController.php`
**Lines of Code**: ~1500+
**Namespace**: `Acelle\Http\Controllers`

#### Main Methods

| Method | Route | Purpose | HTTP |
|--------|-------|---------|------|
| `index()` | `/lists` | List all mail lists | GET |
| `listing()` | `/lists/listing` | Paginated list view | GET |
| `create()` | `/lists/create` | Create new list | GET |
| `store()` | `/lists` | Save new list | POST |
| `edit($uid)` | `/lists/{uid}/edit` | Edit list | GET |
| `update($uid)` | `/lists/{uid}` | Update list | PATCH |
| `delete()` | `/lists/delete` | Delete lists | POST |
| `overview($uid)` | `/lists/{uid}/overview` | List dashboard | GET |
| `embeddedForm($uid)` | `/lists/{uid}/embedded-form` | Generate subscribe form | GET/POST |
| `embeddedFormSubscribe($uid)` | `/lists/{uid}/embedded-form-subscribe` | Public subscribe | POST |
| `verification($uid)` | `/lists/{uid}/verification` | Email verification | GET |
| `startVerification($uid)` | `/lists/{uid}/verification/start` | Start verification | POST |
| `verificationProgress($uid, $job_uid)` | `/lists/{uid}/verification/{job_uid}/progress` | Verification status | GET |
| `listGrowthChart($uid)` | `/lists/{uid}/list-growth` | Growth chart data | GET |
| `statisticsChart($uid)` | `/lists/{uid}/list-statistics-chart` | Statistics chart | GET |
| `copy()` | `/lists/copy` | Duplicate list | POST |
| `selectList()` | `/lists/select` | List selection dropdown | GET/POST |
| `cloneForCustomers($uid)` | `/lists/{uid}/clone-to-customers` | Clone to other customers | POST |
| `checkEmail($uid)` | `/lists/{uid}/check-email` | Email availability check | GET |

#### Dependencies

```php
use Acelle\Model\MailList;
use Acelle\Model\Field;
use Acelle\Model\Subscriber;
use Acelle\Model\EmailVerificationServer;
use Acelle\Jobs\VerifyMailListJob;
```

#### Key Features

- **Custom Fields**: Define custom subscriber fields per list
- **Email Verification**: Bulk email validation integration
- **Embedded Forms**: Generate HTML forms for website integration
- **List Growth Tracking**: Historical subscriber count charts
- **Import/Export**: CSV subscriber management
- **Segmentation**: Dynamic segments based on custom fields
- **GDPR Compliance**: Subscription confirmation, unsubscribe handling

---

### 3. SubscriberController

**Location**: `/app/Http/Controllers/SubscriberController.php`
**Lines of Code**: ~1800+
**Namespace**: `Acelle\Http\Controllers`

#### Main Methods

| Method | Route | Purpose | HTTP |
|--------|-------|---------|------|
| `index($list_uid)` | `/lists/{list_uid}/subscribers` | List subscribers | GET |
| `listing($list_uid)` | `/lists/{list_uid}/subscribers/listing` | Paginated view | GET |
| `create($list_uid)` | `/lists/{list_uid}/subscribers/create` | Add subscriber | GET |
| `store($list_uid)` | `/lists/{list_uid}/subscribers/store` | Save subscriber | POST |
| `edit($list_uid, $uid)` | `/lists/{list_uid}/subscribers/{uid}/edit` | Edit subscriber | GET |
| `update($list_uid, $uid)` | `/lists/{list_uid}/subscribers/{uid}/update` | Update subscriber | PATCH |
| `delete($list_uid)` | `/lists/{list_uid}/subscribers/delete` | Delete subscribers | POST |
| `bulkDelete($list_uid)` | `/lists/{list_uid}/subscribers/bulk-delete` | Bulk delete | POST |
| `subscribe($list_uid)` | `/lists/{list_uid}/subscribers/subscribe` | Bulk subscribe | POST |
| `unsubscribe($list_uid)` | `/lists/{list_uid}/subscribers/unsubscribe` | Bulk unsubscribe | POST |
| `import2($list_uid)` | `/lists/{list_uid}/subscribers/import2` | Import wizard | GET |
| `import2Upload($list_uid)` | `/lists/{list_uid}/subscribers/import2/upload` | Upload CSV | POST |
| `import2Mapping($list_uid)` | `/lists/{list_uid}/subscribers/import2/mapping` | Map CSV columns | GET |
| `import2Validate($list_uid)` | `/lists/{list_uid}/subscribers/import2/validate` | Validate import | POST |
| `import2Run($list_uid)` | `/lists/{list_uid}/subscribers/import2/run` | Execute import | POST |
| `import2Progress($list_uid)` | `/lists/{list_uid}/subscribers/import2/progress` | Import progress | GET |
| `export($list_uid)` | `/lists/{list_uid}/subscribers/export` | Export wizard | GET |
| `dispatchExportJob($list_uid)` | `/lists/{list_uid}/subscribers/export/dispatch` | Start export | POST |
| `exportProgress($job_uid)` | `/lists/export/{job_uid}/progress` | Export progress | GET |
| `copy()` | `/subscribers/copy` | Copy subscribers | POST |
| `move()` | `/subscribers/move` | Move subscribers | POST |
| `copyMoveForm($from_uid, $action)` | `/lists/{from_uid}/copy-move-from/{action}` | Copy/move form | GET |
| `assignValues($list_uid)` | `/lists/{list_uid}/subscribers/assign-values` | Bulk assign fields | GET/POST |
| `updateTags($list_uid, $uid)` | `/lists/{list_uid}/subscriber/{uid}/update-tags` | Update tags | GET/POST |
| `removeTag($list_uid, $uid)` | `/lists/{list_uid}/subscriber/{uid}/remove-tag` | Remove tag | POST |
| `resendConfirmationEmail($list_uid, $uids)` | `/lists/{list_uid}/subscribers/resend/confirmation-email/{uids}` | Resend confirmation | POST |
| `startVerification($uid)` | `/subscriber/{uid}/verification/start` | Verify email | POST |
| `avatar($uid)` | `/assets/images/avatar/subscriber-{uid}.jpg` | Subscriber avatar | GET |

#### Dependencies

```php
use Acelle\Model\Subscriber;
use Acelle\Model\MailList;
use Acelle\Model\Field;
use Acelle\Jobs\ImportSubscribersJob;
use Acelle\Jobs\ExportSubscribersJob;
use League\Csv\Reader;
use League\Csv\Writer;
```

#### Key Features

- **Bulk Import**: CSV import with field mapping, validation, progress tracking
- **Bulk Export**: Filtered CSV export with custom fields
- **Custom Fields**: Support for unlimited custom subscriber fields
- **Tags**: Multi-tag support per subscriber
- **Status Management**: Subscribed, unsubscribed, blacklisted, bounced
- **Verification**: Email verification integration
- **Copy/Move**: Transfer subscribers between lists
- **Bulk Operations**: Subscribe, unsubscribe, delete, assign values
- **Avatar Support**: Subscriber profile images

---

### 4. Automation2Controller

**Location**: `/app/Http/Controllers/Automation2Controller.php`
**Lines of Code**: ~2500+
**Namespace**: `Acelle\Http\Controllers`

#### Main Methods

| Method | Route | Purpose | HTTP |
|--------|-------|---------|------|
| `index()` | `/automation2` | List automations | GET |
| `listing()` | `/automation2/listing` | Paginated list | GET |
| `create()` | `/automation2/create` | Create automation | GET |
| `store()` | `/automation2` | Save automation | POST |
| `edit($uid)` | `/automation2/{uid}/edit` | Edit automation | GET |
| `update($uid)` | `/automation2/{uid}` | Update automation | PATCH |
| `delete()` | `/automation2/delete` | Delete automations | POST |
| `show($uid)` | `/automation2/{uid}` | Automation dashboard | GET |
| `enable($uid)` | `/automation2/{uid}/enable` | Enable automation | POST |
| `disable($uid)` | `/automation2/{uid}/disable` | Disable automation | POST |
| `overview($uid)` | `/automation2/{uid}/overview` | Performance overview | GET |
| `subscribers($uid)` | `/automation2/{uid}/subscribers` | Contact list | GET |
| `trigger($uid)` | `/automation2/{uid}/trigger` | Configure trigger | GET/POST |
| `action($uid)` | `/automation2/{uid}/action` | Configure actions | GET/POST |
| `condition($uid)` | `/automation2/{uid}/condition` | Configure conditions | GET/POST |
| `delay($uid)` | `/automation2/{uid}/delay` | Configure delays | GET/POST |
| `builder($uid)` | `/automation2/{uid}/builder` | Visual workflow builder | GET |
| `saveBuilder($uid)` | `/automation2/{uid}/builder/save` | Save workflow | POST |
| `copy()` | `/automation2/copy` | Duplicate automation | POST |
| `test($uid)` | `/automation2/{uid}/test` | Test automation | POST |
| `timeline($uid)` | `/automation2/{uid}/timeline` | Execution timeline | GET |
| `stats($uid)` | `/automation2/{uid}/stats` | Statistics | GET |

#### Dependencies

```php
use Acelle\Model\Automation2;
use Acelle\Model\AutoTrigger;
use Acelle\Model\AutoEvent;
use Acelle\Model\Email;
use Acelle\Model\Timeline;
use Acelle\Jobs\RunAutomation;
```

#### Key Features

- **Visual Workflow Builder**: Drag-and-drop automation design
- **Multiple Triggers**: Subscribe, click, open, date-based, API
- **Actions**: Send email, add tag, remove tag, update field, wait
- **Conditions**: Field conditions, segment membership, engagement
- **Delay Options**: Time-based delays between actions
- **A/B Testing**: Test automation paths
- **Performance Tracking**: Open rates, click rates, conversions
- **Timeline View**: Visual execution history per contact

---

### 5. SendingServerController

**Location**: `/app/Http/Controllers/SendingServerController.php`
**Lines of Code**: ~670
**Namespace**: `Acelle\Http\Controllers`

#### Main Methods

| Method | Route | Purpose | HTTP |
|--------|-------|---------|------|
| `index()` | `/sending_servers` | List servers | GET |
| `listing()` | `/sending_servers/listing` | Paginated list | GET |
| `select()` | `/sending_servers/select` | Select server type | GET |
| `create($type)` | `/sending_servers/create/{type}` | Create server | GET |
| `store($type)` | `/sending_servers/create/{type}` | Save server | POST |
| `edit($uid, $type)` | `/sending_servers/{uid}/edit` | Edit server | GET |
| `update($uid)` | `/sending_servers/{uid}` | Update server | PATCH |
| `delete()` | `/sending_servers/delete` | Delete servers | POST |
| `test($uid)` | `/sending_servers/{uid}/test` | Send test email | GET/POST |
| `testConnection($uid)` | `/sending_servers/{uid}/test-connection` | Test connection | GET |
| `enable()` | `/sending_servers/enable` | Enable servers | POST |
| `disable()` | `/sending_servers/disable` | Disable servers | POST |
| `config($uid)` | `/sending_servers/{uid}/config` | Configure settings | POST |
| `sendingLimit()` | `/sending_servers/sending-limit` | Sending limit form | GET/POST |
| `addDomain($uid)` | `/sending_servers/{uid}/add-domain` | Add verified domain | GET/POST |
| `removeDomain($uid, $identity)` | `/sending_servers/{uid}/remove-domain/{identity}` | Remove domain | GET |
| `fromDropbox()` | `/sending_servers/from-dropbox` | Domain dropdown | GET |
| `awsRegionHost()` | `/sending_servers/aws-region-host` | AWS region helper | GET |

#### Supported Server Types

1. **SMTP**: Standard SMTP servers
2. **Amazon SES**: AWS Simple Email Service
3. **Sendgrid**: SendGrid API
4. **Mailgun**: Mailgun API
5. **SparkPost**: SparkPost API
6. **ElasticEmail**: ElasticEmail API
7. **PHP Mail**: PHP mail() function
8. **Sendmail**: Linux sendmail
9. **Custom**: Plugin-based custom servers

#### Dependencies

```php
use Acelle\Model\SendingServer;
use Acelle\Model\SendingDomain;
use Acelle\Model\BounceHandler;
use Acelle\Model\FeedbackLoopHandler;
use Acelle\Library\Facades\Hook;
```

#### Key Features

- **Multiple Providers**: Support for 9+ sending methods
- **Domain Verification**: SPF/DKIM/DMARC verification
- **Sending Limits**: Hourly/daily quota management
- **Bounce Handling**: Automatic bounce processing
- **Feedback Loops**: Complaint handling
- **Identity Management**: Email/domain verification
- **Connection Testing**: Real-time connectivity tests
- **Plugin System**: Extensible via hooks

---

### 6. TemplateController

**Location**: `/app/Http/Controllers/TemplateController.php`
**Lines of Code**: ~600
**Namespace**: `Acelle\Http\Controllers`

#### Main Methods

| Method | Route | Purpose | HTTP |
|--------|-------|---------|------|
| `index()` | `/templates` | Template gallery | GET |
| `listing()` | `/templates/listing` | Paginated list | GET |
| `create()` | `/templates/create` | Create template | GET |
| `edit($uid)` | `/templates/{uid}/edit` | Edit template | GET |
| `update($uid)` | `/templates/{uid}` | Save template | PATCH |
| `delete()` | `/templates/delete` | Delete templates | POST |
| `preview($uid)` | `/templates/{uid}/preview` | Preview template | GET |
| `copy()` | `/templates/copy` | Duplicate template | POST |
| `builderEdit($uid)` | `/templates/{uid}/builder/edit` | Visual builder | GET |
| `builderCreate()` | `/templates/builder/create` | Create with builder | GET/POST |
| `builderTemplates()` | `/templates/builder/templates` | Builder gallery | GET |
| `builderChangeTemplate($uid, $change_uid)` | `/templates/{uid}/builder/change/{change_uid}` | Switch template | GET |
| `builderEditContent($uid)` | `/templates/{uid}/builder/content` | Edit HTML content | GET |
| `uploadTemplateAssets($uid)` | `/templates/{uid}/upload-assets` | Upload images | POST |
| `uploadTemplate()` | `/templates/upload` | Upload ZIP template | GET/POST |
| `updateThumb($uid)` | `/templates/{uid}/update-thumb` | Update thumbnail | GET/POST |
| `updateThumbUrl($uid)` | `/templates/{uid}/update-thumb-url` | Set thumbnail URL | GET/POST |
| `categories($uid)` | `/templates/{uid}/categories` | Manage categories | GET/POST |
| `export()` | `/templates/export` | Export as ZIP | GET |
| `changeName()` | `/templates/change-name` | Rename template | GET/POST |
| `parseRss()` | `/templates/parse-rss` | RSS feed parser | POST |
| `chat()` | `/templates/chat` | Template chat helper | GET |

#### Dependencies

```php
use Acelle\Model\Template;
use Acelle\Model\TemplateCategory;
use Acelle\Model\Setting;
use Acelle\Library\Tool;
```

#### Key Features

- **Visual Builder**: Drag-and-drop email builder
- **Gallery**: Shared and private templates
- **Categories**: Template categorization
- **Asset Management**: Image uploads and hosting
- **Export/Import**: ZIP template packages
- **RSS Integration**: Dynamic RSS-to-email content
- **Thumbnail Generation**: Automatic template previews
- **Responsive Design**: Mobile-friendly templates

---

### 7. SegmentController

**Location**: `/app/Http/Controllers/SegmentController.php`
**Lines of Code**: ~407
**Namespace**: `Acelle\Http\Controllers`

#### Main Methods

| Method | Route | Purpose | HTTP |
|--------|-------|---------|------|
| `index($list_uid)` | `/lists/{list_uid}/segments` | List segments | GET |
| `listing($list_uid)` | `/lists/{list_uid}/segments/listing` | Paginated view | GET |
| `create($list_uid)` | `/lists/{list_uid}/segments/create` | Create segment | GET |
| `store($list_uid)` | `/lists/{list_uid}/segments` | Save segment | POST |
| `edit($list_uid, $uid)` | `/lists/{list_uid}/segments/{uid}/edit` | Edit segment | GET |
| `update($list_uid, $uid)` | `/lists/{list_uid}/segments/{uid}` | Update segment | PATCH |
| `delete()` | `/lists/{list_uid}/segments/delete` | Delete segments | POST |
| `subscribers($list_uid, $uid)` | `/lists/{list_uid}/segments/{uid}/subscribers` | View members | GET |
| `listingSubscribers($list_uid, $uid)` | `/lists/{list_uid}/segments/{uid}/subscribers/listing` | Member listing | GET |
| `sampleCondition($list_uid)` | `/lists/{list_uid}/segments/sample-condition` | Condition template | GET |
| `selectBox($list_uid)` | `/lists/{list_uid}/segments/select-box` | Segment dropdown | GET |
| `conditionValueControl()` | `/segments/condition-value-control` | Dynamic condition UI | GET |
| `noList()` | `/segments/no-list` | No list error | GET |

#### Dependencies

```php
use Acelle\Model\Segment;
use Acelle\Model\SegmentCondition;
use Acelle\Model\Field;
use Acelle\Model\MailList;
```

#### Key Features

- **Dynamic Segments**: Real-time segment membership
- **Multiple Conditions**: AND/OR logic for conditions
- **Field-based Rules**: Filter by custom field values
- **Operators**: equals, not equals, contains, greater than, less than, blank, not blank
- **Cached Counts**: Optimized subscriber counts
- **Campaign Integration**: Send campaigns to specific segments

---

### 8. FieldController

**Location**: `/app/Http/Controllers/FieldController.php`
**Lines of Code**: ~127
**Namespace**: `Acelle\Http\Controllers`

#### Main Methods

| Method | Route | Purpose | HTTP |
|--------|-------|---------|------|
| `index($list_uid)` | `/lists/{list_uid}/fields` | Manage fields | GET |
| `store($list_uid)` | `/lists/{list_uid}/fields` | Save field changes | POST |
| `sample($list_uid)` | `/lists/{list_uid}/fields/sample` | Field type sample | GET |
| `delete()` | `/fields/delete` | Delete field | POST |

#### Field Types

1. **TEXT**: Single-line text input
2. **TEXTAREA**: Multi-line text area
3. **NUMBER**: Numeric input
4. **DATE**: Date picker
5. **DATETIME**: Date and time picker
6. **DROPDOWN**: Select dropdown
7. **MULTISELECT**: Multi-select dropdown
8. **CHECKBOX**: Checkbox
9. **RADIO**: Radio buttons

#### Key Features

- **Custom Fields**: Unlimited custom fields per list
- **Required Fields**: Mark fields as required
- **Default Values**: Set default field values
- **Tags**: Special field for tagging subscribers
- **System Fields**: EMAIL (required), FIRST_NAME, LAST_NAME

---

### 9. SenderController

**Location**: `/app/Http/Controllers/SenderController.php`
**Lines of Code**: ~331
**Namespace**: `Acelle\Http\Controllers`

#### Main Methods

| Method | Route | Purpose | HTTP |
|--------|-------|---------|------|
| `index()` | `/senders` | List verified senders | GET |
| `listing()` | `/senders/listing` | Paginated list | GET |
| `create()` | `/senders/create` | Add sender | GET |
| `store()` | `/senders` | Save sender | POST |
| `show($uid)` | `/senders/{uid}` | Sender details | GET |
| `edit($uid)` | `/senders/{uid}/edit` | Edit sender | GET |
| `update($uid)` | `/senders/{uid}` | Update sender | PATCH |
| `delete()` | `/senders/delete` | Delete senders | POST |
| `verify()` | `/senders/verify` | Email verification | GET |
| `verifyResult()` | `/senders/verify-result` | Check verification | GET |
| `import()` | `/senders/import` | Import senders | GET/POST |
| `dropbox()` | `/senders/dropbox` | Sender dropdown | GET |

#### Dependencies

```php
use Acelle\Model\Sender;
use Acelle\Model\SendingDomain;
use Acelle\Jobs\ImportSenderJob;
```

#### Key Features

- **Email Verification**: Confirmation email or DNS verification
- **Status Tracking**: Pending, verified, failed
- **Bulk Import**: CSV sender import
- **Campaign Integration**: Use verified senders in campaigns

---

## Controller Structure Analysis

### Common Patterns

#### 1. Authorization Pattern

All controllers use Laravel Gates/Policies for authorization:

```php
// Check permission
if (!$request->user()->customer->can('read', $model)) {
    return $this->notAuthorized();
}

// Gate check
if (\Gate::denies('update', $model)) {
    return $this->notAuthorized();
}
```

#### 2. Listing Pattern

Consistent pattern for AJAX-based paginated listings:

```php
public function index(Request $request)
{
    return view('resource.index');
}

public function listing(Request $request)
{
    $items = Model::search($request->keyword)
        ->filter($request)
        ->orderBy($request->sort_order, $request->sort_direction)
        ->paginate($request->per_page);

    return view('resource._list', ['items' => $items]);
}
```

#### 3. CRUD Pattern

Standard Laravel RESTful CRUD:

```php
public function create(Request $request)
{
    $model = new Model();
    $model->fill($request->old());
    return view('resource.create', ['model' => $model]);
}

public function store(Request $request)
{
    $model = new Model();
    $this->validate($request, $model->rules());
    $model->fill($request->all());
    $model->save();

    $request->session()->flash('alert-success', trans('messages.created'));
    return redirect()->action('Controller@index');
}
```

#### 4. Bulk Operations Pattern

```php
public function delete(Request $request)
{
    $items = Model::whereIn('uid',
        is_array($request->uids) ? $request->uids : explode(',', $request->uids)
    );

    foreach ($items->get() as $item) {
        if ($request->user()->customer->can('delete', $item)) {
            $item->delete();
        }
    }

    echo trans('messages.deleted');
}
```

#### 5. Progress Tracking Pattern

For long-running jobs:

```php
public function import($list_uid)
{
    return view('subscribers.import');
}

public function dispatchImportJob(Request $request, $list_uid)
{
    $job = new ImportJob($file, $list);
    dispatch($job);
    return response()->json(['job_uid' => $job->id]);
}

public function importProgress($job_uid)
{
    $job = Job::find($job_uid);
    return response()->json([
        'status' => $job->status,
        'progress' => $job->progress,
        'total' => $job->total,
    ]);
}
```

---

## Routes Mapping

### Public Routes (No Authentication)

```php
// Tracking URLs
Route::get('p/{message_id}/open', 'CampaignController@open');
Route::get('p/{url}/click/{message_id}', 'CampaignController@click');
Route::get('c/{subscriber}/unsubscribe/{message_id}', 'CampaignController@unsubscribe');
Route::get('campaigns/{message_id}/web-view', 'CampaignController@webView');

// Subscription Forms
Route::post('lists/{uid}/embedded-form-subscribe', 'MailListController@embeddedFormSubscribe');
Route::get('lists/{uid}/check-email', 'MailListController@checkEmail');

// Sender Verification
Route::get('senders/verify', 'SenderController@verify');

// Public Campaign Reports
Route::get('pub/campaigns/{uid}/overview', 'Pub\CampaignController@overview');
Route::get('pub/campaigns/{uid}/tracking-log', 'Pub\CampaignController@trackingLog');
```

### Customer Routes (Authentication Required)

```php
// Campaigns
Route::resource('campaigns', 'CampaignController');
Route::get('campaigns/{uid}/overview', 'CampaignController@overview');
Route::post('campaigns/{uid}/send', 'CampaignController@send');
Route::get('campaigns/{uid}/tracking-log', 'CampaignController@trackingLog');

// Mail Lists
Route::resource('lists', 'MailListController');
Route::get('lists/{uid}/overview', 'MailListController@overview');
Route::post('lists/{uid}/verification/start', 'MailListController@startVerification');

// Subscribers
Route::get('lists/{list_uid}/subscribers', 'SubscriberController@index');
Route::post('lists/{list_uid}/subscribers/import2/run', 'SubscriberController@import2Run');

// Automation
Route::resource('automation2', 'Automation2Controller');
Route::get('automation2/{uid}/builder', 'Automation2Controller@builder');

// Sending Servers
Route::resource('sending_servers', 'SendingServerController');

// Templates
Route::resource('templates', 'TemplateController');

// Segments
Route::get('lists/{list_uid}/segments', 'SegmentController@index');

// Fields
Route::get('lists/{list_uid}/fields', 'FieldController@index');

// Senders
Route::resource('senders', 'SenderController');
```

### Admin Routes

```php
Route::group(['prefix' => 'admin', 'middleware' => 'admin'], function () {
    Route::resource('customers', 'CustomerController');
    Route::resource('sending_servers', 'SendingServerController');
    Route::resource('plans', 'PlanController');
    Route::get('tracking-log', 'TrackingLogController@index');
});
```

### API Routes

```php
Route::group(['prefix' => 'api/v1', 'middleware' => 'auth:api'], function () {
    Route::resource('campaigns', 'Api\CampaignController');
    Route::resource('lists', 'Api\MailListController');
    Route::resource('subscribers', 'Api\SubscriberController');
    Route::resource('automations', 'Api\AutomationController');
});
```

---

## Dependencies and Services

### Core Models Used

```php
// Primary Mailing Models
Acelle\Model\Campaign
Acelle\Model\MailList
Acelle\Model\Subscriber
Acelle\Model\Automation2
Acelle\Model\AutoTrigger
Acelle\Model\AutoEvent
Acelle\Model\SendingServer
Acelle\Model\Template
Acelle\Model\Segment
Acelle\Model\SegmentCondition
Acelle\Model\Field
Acelle\Model\Sender
Acelle\Model\SendingDomain
Acelle\Model\TrackingDomain

// Logging Models
Acelle\Model\TrackingLog
Acelle\Model\OpenLog
Acelle\Model\ClickLog
Acelle\Model\BounceLog
Acelle\Model\FeedbackLog
Acelle\Model\UnsubscribeLog

// Support Models
Acelle\Model\Customer
Acelle\Model\Plan
Acelle\Model\Subscription
Acelle\Model\EmailVerificationServer
Acelle\Model\BounceHandler
Acelle\Model\FeedbackLoopHandler
```

### Queue Jobs

```php
// Campaign Jobs
Acelle\Jobs\SendCampaignJob
Acelle\Jobs\SendMessage

// Import/Export Jobs
Acelle\Jobs\ImportSubscribersJob
Acelle\Jobs\ExportSubscribersJob
Acelle\Jobs\ImportSenderJob

// Verification Jobs
Acelle\Jobs\VerifyMailListJob
Acelle\Jobs\VerifySubscriberJob

// Automation Jobs
Acelle\Jobs\RunAutomation
Acelle\Jobs\RunAutomationWithContext
```

### External Libraries

```php
// CSV Processing
League\Csv\Reader
League\Csv\Writer

// Email Libraries
Swift_Message
PHPMailer

// AWS SDK
Aws\Ses\SesClient

// HTTP Client
GuzzleHttp\Client

// Image Processing
Intervention\Image

// Plugin System
Acelle\Library\Facades\Hook
```

### Services and Helpers

```php
// Email Services
Acelle\Library\MailHandler
Acelle\Library\SendingServerHelper

// Import/Export
Acelle\Library\CsvHelper
Acelle\Library\ImportService

// Tracking
Acelle\Library\TrackingHelper
Acelle\Library\ClickTracker

// Template Rendering
Acelle\Library\TemplateRenderer
Acelle\Library\ExtendedSwiftMessage
```

---

## Middleware and Authorization

### Middleware Stack

```php
// Global Middleware
'web' => [
    'not_installed',
    'not_logged_in',
    'locale',
    'backend',
]

// Customer Middleware
'customer' => [
    'auth',
    'customer',
    'subscription',
]

// Admin Middleware
'admin' => [
    'auth',
    'admin',
]

// API Middleware
'api' => [
    'auth:api',
    'throttle:60,1',
]
```

### Authorization Policies

Each model has a policy defining permissions:

```php
// CampaignPolicy
can('read', $campaign)
can('create', Campaign::class)
can('update', $campaign)
can('delete', $campaign)
can('send', $campaign)
can('pause', $campaign)
can('restart', $campaign)

// MailListPolicy
can('read', $mailList)
can('create', MailList::class)
can('update', $mailList)
can('delete', $mailList)
can('verify', $mailList)

// SubscriberPolicy
can('read', $subscriber)
can('create', Subscriber::class)
can('update', $subscriber)
can('delete', $subscriber)
can('import', Subscriber::class)
can('export', Subscriber::class)
```

### Gate Checks

```php
// Controller-level authorization
if (!$request->user()->customer->can('read', $model)) {
    return $this->notAuthorized();
}

// Using Gate facade
if (\Gate::denies('update', $model)) {
    return $this->notAuthorized();
}

// Middleware authorization
Route::get('campaigns', 'CampaignController@index')
    ->middleware('can:list,Acelle\Model\Campaign');
```

---

## Integration Recommendations

### High Priority Controllers for Migration

#### 1. CampaignController (CRITICAL)
- **Status**: ~2000 LOC, complex workflow
- **Integration**: Port to `modules/Mailing/app/Http/Controllers/CampaignController.php`
- **Dependencies**: Campaign model, tracking system, queue jobs
- **Notes**:
  - Multi-step wizard needs UI adaptation
  - Tracking URLs must maintain compatibility
  - Queue integration with Laravel Horizon

#### 2. MailListController (CRITICAL)
- **Status**: ~1500 LOC
- **Integration**: Port to `modules/Mailing/app/Http/Controllers/ListController.php`
- **Dependencies**: MailList model, Field model, verification service
- **Notes**:
  - Embedded form generation is complex
  - Email verification requires external service integration

#### 3. SubscriberController (CRITICAL)
- **Status**: ~1800 LOC, heavy CSV processing
- **Integration**: Port to `modules/Mailing/app/Http/Controllers/SubscriberController.php`
- **Dependencies**: Import/export jobs, CSV library
- **Notes**:
  - Import wizard is 5 steps with progress tracking
  - Memory-efficient CSV processing required
  - Tag system needs separate table

#### 4. Automation2Controller (CRITICAL)
- **Status**: ~2500 LOC, most complex controller
- **Integration**: Port to `modules/Mailing/app/Http/Controllers/AutomationController.php`
- **Dependencies**: Automation models, timeline tracking, visual builder
- **Notes**:
  - Visual builder uses JSON workflow structure
  - Requires background worker for execution
  - Complex event system

#### 5. SendingServerController (CRITICAL)
- **Status**: ~670 LOC
- **Integration**: Port to `modules/Mailing/app/Http/Controllers/SendingServerController.php`
- **Dependencies**: Multiple email provider classes
- **Notes**:
  - Plugin system for custom providers
  - Domain verification via DNS
  - Quota tracking system

### Medium Priority Controllers

#### 6. TemplateController (HIGH)
- Integration into `modules/Mailing/app/Http/Controllers/TemplateController.php`
- Visual builder requires JavaScript port

#### 7. SegmentController (HIGH)
- Integration into `modules/Mailing/app/Http/Controllers/SegmentController.php`
- Dynamic condition UI

#### 8. FieldController (HIGH)
- Integration into `modules/Mailing/app/Http/Controllers/FieldController.php`
- Field type system

#### 9. SenderController (HIGH)
- Integration into `modules/Mailing/app/Http/Controllers/SenderController.php`
- Email verification workflow

### API Controllers (HIGH)

All API controllers should be ported to:
- `modules/Mailing/app/Http/Controllers/Api/`

Maintain backward compatibility for existing API clients.

### Low Priority Controllers

These can be deferred or simplified:
- `FormController` - Can use Acelle's existing implementation
- `WebsiteController` - Optional feature
- `TrackingDomainController` - Optional custom domains
- `SendingDomainController` - Can share with SendingServerController
- `BlacklistController` - Optional feature
- `EmailVerificationServerController` - Optional feature

---

## Controller Architecture Notes

### Design Patterns Used

1. **Repository Pattern**: Models act as repositories
2. **Service Layer**: Heavy logic in dedicated service classes
3. **Job Pattern**: Long operations use queued jobs
4. **Policy Pattern**: Authorization via Laravel policies
5. **Event Pattern**: Model events trigger side effects

### Performance Optimizations

1. **Eager Loading**: Prevent N+1 queries
   ```php
   $campaigns = Campaign::with('mailList', 'defaultMailList')->get();
   ```

2. **Caching**: Cache expensive computations
   ```php
   $segment->readCache('SubscriberCount', 0);
   ```

3. **Queue Jobs**: Offload heavy operations
   ```php
   dispatch(new ImportSubscribersJob($file, $list));
   ```

4. **Chunk Processing**: Process large datasets in batches
   ```php
   Subscriber::chunk(1000, function ($subscribers) {
       // Process batch
   });
   ```

### Security Considerations

1. **Authorization**: Every method checks user permissions
2. **CSRF Protection**: All POST requests require CSRF token
3. **SQL Injection**: Eloquent ORM prevents SQL injection
4. **XSS Prevention**: Blade escapes output by default
5. **Rate Limiting**: API endpoints use throttle middleware

---

## Database Tables Used by Controllers

### Core Tables

```sql
-- Campaign Management
campaigns
campaign_links
campaign_lists

-- Mail Lists
mail_lists
subscribers
subscriber_fields
fields
segments
segment_conditions

-- Automation
automation2
auto_triggers
auto_events
timelines

-- Sending
sending_servers
sending_domains
senders

-- Templates
templates
template_categories

-- Tracking
tracking_logs
open_logs
click_logs
bounce_logs
feedback_logs
unsubscribe_logs

-- Support
customers
plans
subscriptions
email_verification_servers
bounce_handlers
feedback_loop_handlers
blacklists
```

---

## Conclusion

The Acelle Mail controller architecture is mature, well-organized, and follows Laravel best practices. The critical mailing controllers are ready for integration into the Mailing module with the following considerations:

### Strengths
- Clear separation of concerns
- Consistent authorization patterns
- AJAX-based UI for better UX
- Queue-based processing for scalability
- Comprehensive tracking system
- Plugin-extensible architecture

### Challenges
- Large codebase (~10,000+ LOC in critical controllers)
- Complex multi-step wizards
- Heavy JavaScript dependencies for UI
- Tightly coupled to Acelle's customer/subscription system
- Custom view rendering (Blade templates)

### Next Steps
1. Port critical controllers one by one
2. Adapt authorization to use system's permission system
3. Update routes to match module structure
4. Migrate views to Bootstrap Modernize theme
5. Test tracking URLs for backward compatibility
6. Document API changes for existing integrations

---

**End of Report**
