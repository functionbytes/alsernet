# Acelle Models Analysis Report

**Generated:** January 29, 2026
**Source Directory:** `/Users/functionbytes/Function/Coding/acelle/app/Model/`
**Purpose:** Comprehensive analysis of all Acelle Mail models for Mailing module integration

---

## Executive Summary

Total models found: **117 models**

This report analyzes the critical models from the Acelle Mail system that are essential for understanding the mailing infrastructure, their relationships, traits used, important methods, and identifying which models are critical for the mailing functionality.

---

## 1. Core Mailing Models

### 1.1 Campaign Model
**File:** `Campaign.php`
**Purpose:** Central model for email campaigns - the heart of the mailing system

#### Key Relationships
- `belongsTo`: MailList (defaultMailList), TrackingDomain
- `belongsToMany`: MailList (through campaigns_lists_segments)
- `hasMany`: CampaignLink, CampaignWebhook, TrackingLog, BounceLog, OpenLog, ClickLog, FeedbackLog, UnsubscribeLog, CampaignsListsSegment

#### Traits Used
- `HasTemplate` - Template management functionality

#### Constants
```php
// Campaign types
TYPE_REGULAR = 'regular'
TYPE_PLAIN_TEXT = 'plain-text'

// Delivery statuses
DELIVERY_STATUS_FAILED = 'failed'
DELIVERY_STATUS_SENT = 'sent'
DELIVERY_STATUS_NEW = 'new'
DELIVERY_STATUS_SKIPPED = 'skipped'
DELIVERY_STATUS_BOUNCED = 'bounced'
DELIVERY_STATUS_FEEDBACK = 'feedback'
```

#### Critical Methods
- `trackMessage()` - Logs delivery tracking information
- `subscribers()` - Gets campaign subscribers from lists/segments
- `subscribersToSend()` - Queries subscribers not yet sent to
- `loadDeliveryJobs()` - Loads sending jobs for the campaign
- `pickSendingServer()` - Selects sending server for campaign
- `prepareEmail()` - Builds email message for subscriber
- `updateLinks()` - Extracts and updates campaign links for tracking
- `openRate()`, `clickRate()`, `bounceRate()`, `unsubscribeRate()` - Analytics methods
- `resend()` - Re-sends campaign to failed/not-opened/not-clicked
- `copy()` - Duplicates campaign
- `getCacheIndex()` - Defines cached metrics

#### Fillable Attributes
```php
'name', 'subject', 'from_name', 'from_email', 'reply_to',
'track_open', 'track_click', 'sign_dkim', 'track_fbl',
'html', 'plain', 'template_source', 'tracking_domain_id',
'use_default_sending_server_from_email', 'skip_failed_message'
```

---

### 1.2 MailList Model
**File:** `MailList.php`
**Purpose:** Manages subscriber lists and their configurations

#### Key Relationships
- `belongsTo`: Customer, Contact
- `hasMany`: Field, Segment, Subscriber, Automation2, Page, MailListsSendingServer
- `belongsToMany`: Campaign (through campaigns_lists_segments), SendingServer (through mail_lists_sending_servers)
- `hasManyThrough`: SubscriberField

#### Traits Used
- `QueryHelper` - Query building utilities
- `TrackJobs` - Background job tracking
- `HasUid` - Unique identifier management
- `HasCache` - Caching functionality

#### Constants
```php
SOURCE_EMBEDDED_FORM = 'embedded-form'
SOURCE_WEB = 'web'
SOURCE_API = 'api'

IMPORT_TEMP_DIR = 'app/tmp/import/'
EXPORT_TEMP_DIR = 'app/tmp/export/'
```

#### Critical Methods
- `createDefaultFields()` - Creates EMAIL, FIRST_NAME, LAST_NAME fields
- `getFields()`, `getEmailField()`, `getFieldByTag()` - Field management
- `import()` - Imports subscribers from CSV
- `export()` - Exports subscribers to CSV
- `uploadCsv()` - Handles CSV file upload
- `dispatchImportJob()`, `dispatchExportJob()`, `dispatchVerificationJob()` - Job dispatching
- `pickSendingServer()` - Selects server from pool
- `getSendingServers()` - Gets available sending servers
- `subscribe()` - Handles subscription workflow
- `sendSubscriptionConfirmationEmail()` - Sends double opt-in email
- `subscribersCount()`, `unsubscribeCount()`, `subscribeRate()` - Metrics
- `getCacheIndex()` - Defines cached data

#### Fillable Attributes
```php
'name', 'from_email', 'from_name', 'remind_message', 'send_to',
'email_daily', 'email_subscribe', 'email_unsubscribe',
'send_welcome_email', 'unsubscribe_notification',
'subscribe_confirmation', 'all_sending_servers'
```

---

### 1.3 Subscriber Model
**File:** `Subscriber.php`
**Purpose:** Represents email subscribers with their data and status

#### Key Relationships
- `belongsTo`: MailList
- `hasMany`: SubscriberField, TrackingLog, UnsubscribeLog

#### Traits Used
- `HasUid` - Unique identifier management

#### Constants
```php
// Status constants
STATUS_SUBSCRIBED = 'subscribed'
STATUS_UNSUBSCRIBED = 'unsubscribed'
STATUS_BLACKLISTED = 'blacklisted'
STATUS_SPAM_REPORTED = 'spam-reported'
STATUS_UNCONFIRMED = 'unconfirmed'

// Subscription types
SUBSCRIPTION_TYPE_ADDED = 'added'
SUBSCRIPTION_TYPE_DOUBLE_OPTIN = 'double'
SUBSCRIPTION_TYPE_SINGLE_OPTIN = 'single'
SUBSCRIPTION_TYPE_IMPORTED = 'imported'

// Verification statuses
VERIFICATION_STATUS_DELIVERABLE = 'deliverable'
VERIFICATION_STATUS_UNDELIVERABLE = 'undeliverable'
VERIFICATION_STATUS_UNKNOWN = 'unknown'
VERIFICATION_STATUS_RISKY = 'risky'
VERIFICATION_STATUS_UNVERIFIED = 'unverified'
```

#### Critical Methods
- `updateFields()` - Updates subscriber custom fields
- `unsubscribe()` - Handles unsubscription with logging
- `sendToBlacklist()` - Blacklists subscriber email
- `markAsSpamReported()` - Marks as spam reporter
- `verify()` - Verifies email using verification service
- `getTags()`, `updateTags()`, `addTags()` - Tag management
- `copy()`, `move()` - Copy/move between lists
- `openLogs()`, `clickLogs()` - Gets activity logs
- `getFullName()` - Gets first + last name
- `confirm()` - Confirms double opt-in subscription
- `getHistory()` - Gets subscriber activity history

#### Fillable Attributes
```php
'mail_list_id', 'email', 'image'
```

#### Scopes
- `scopeSubscribed()`, `scopeUnsubscribed()`
- `scopeVerified()`, `scopeUnverified()`
- `scopeDeliverable()`, `scopeUndeliverable()`, `scopeRisky()`, `scopeUnknown()`
- `scopeDeliverableOrNotVerified()`

---

### 1.4 SendingServer Model
**File:** `SendingServer.php`
**Purpose:** Abstract class for different types of sending servers (SMTP, API-based)

#### Key Relationships
- `belongsTo`: Customer, Admin, BounceHandler
- `hasMany`: TrackingLog, SendingDomain, Sender, PlansSendingServer
- `belongsToMany`: Plan (through plans_sending_servers)

#### Traits Used
- `HasUid` - Unique identifier management

#### Constants
```php
DELIVERY_STATUS_SENT = 'sent'
DELIVERY_STATUS_FAILED = 'failed'
STATUS_ACTIVE = 'active'
STATUS_INACTIVE = 'inactive'

// Server types
TYPE_AMAZON_API = 'amazon-api'
TYPE_AMAZON_SMTP = 'amazon-smtp'
TYPE_SENDGRID_API = 'sendgrid-api'
TYPE_SENDGRID_SMTP = 'sendgrid-smtp'
TYPE_MAILGUN_API = 'mailgun-api'
TYPE_MAILGUN_SMTP = 'mailgun-smtp'
TYPE_ELASTICEMAIL_API = 'elasticemail-api'
TYPE_ELASTICEMAIL_SMTP = 'elasticemail-smtp'
TYPE_SPARKPOST_API = 'sparkpost-api'
TYPE_SPARKPOST_SMTP = 'sparkpost-smtp'
TYPE_SENDMAIL = 'sendmail'
TYPE_SMTP = 'smtp'
TYPE_BLASTENGINE_API = 'blastengine-api'
TYPE_BLASTENGINE_SMTP = 'blastengine-smtp'
```

#### Critical Methods
- `mapServerType()` - Maps server to its specific class type
- `test()` - Tests server connection
- `send()` - Sends email message (abstract implementation in subclasses)
- `setupBeforeSend()` - Setup operations before sending
- `getVerp()` - Gets VERP address for bounce handling
- `sendTestEmail()` - Sends a test email
- `getRateLimits()`, `getRateLimitTracker()` - Rate limiting
- `getVerifiedIdentities()` - Gets verified emails/domains
- `allowUnverifiedFromEmailAddress()` - Check if unverified FROM allowed
- `allowVerifyingOwnDomains()`, `allowVerifyingOwnEmails()` - Permission checks

#### Fillable Attributes
```php
'name', 'type', 'host', 'aws_access_key_id', 'aws_secret_access_key',
'aws_region', 'domain', 'api_key', 'api_secret_key', 'smtp_username',
'smtp_password', 'smtp_port', 'smtp_protocol', 'quota_value',
'sendmail_path', 'quota_base', 'quota_unit', 'bounce_handler_id',
'feedback_loop_handler_id', 'status', 'default_from_email', 'username'
```

---

### 1.5 Template Model
**File:** `Template.php`
**Purpose:** Email template management with builder support

#### Key Relationships
- `belongsTo`: Customer, Admin
- `belongsToMany`: TemplateCategory (through templates_categories)

#### Traits Used
- `HasUid` - Unique identifier management

#### Constants
```php
BUILDER_ENABLED = true
BUILDER_DISABLED = false

TYPE_EMAIL = 'email'
TYPE_POPUP = 'popup'

IS_PRIVATE = true
```

#### Critical Methods
- `copy()` - Duplicates template
- `copyAsPrivate()` - Creates private copy for campaign
- `loadContent()` - Loads template from directory
- `uploadTemplate()` - Uploads template ZIP
- `toZip()` - Exports template to ZIP
- `getStoragePath()` - Gets template storage path
- `getThumbUrl()` - Gets thumbnail URL
- `transformAssetsUrls()` - Transforms relative URLs to absolute
- `getContentWithTransformedAssetsUrls()` - Transforms URLs for tracking
- `uploadAsset()` - Uploads image/asset
- `findCssFiles()` - Finds linked CSS files
- `changeTemplate()` - Swaps template content

#### Fillable Attributes
```php
'uid', 'name', 'content', 'builder', 'is_default', 'theme', 'type'
```

---

### 1.6 TrackingLog Model
**File:** `TrackingLog.php`
**Purpose:** Tracks email delivery status and results

#### Key Relationships
- `belongsTo`: Campaign, Subscriber, SendingServer, Customer, MailList

#### Constants
```php
STATUS_SENT = 'sent'
STATUS_FAILED = 'failed'
STATUS_BOUNCED = 'bounced'
STATUS_FEEDBACK_ABUSE = 'feedback-abuse'
STATUS_FEEDBACK_SPAM = 'feedback-spam'
```

#### Fillable Attributes
```php
'email_id', 'campaign_id', 'message_id', 'runtime_message_id',
'subscriber_id', 'sending_server_id', 'customer_id', 'status',
'error', 'auto_trigger_id', 'sub_account_id', 'mail_list_id'
```

#### Scopes
- `scopeSent()` - Only sent messages
- `scopeFailed()` - Only failed messages

---

### 1.7 Segment Model
**File:** `Segment.php`
**Purpose:** List segmentation with complex conditions

#### Key Relationships
- `belongsTo`: MailList
- `hasMany`: SegmentCondition

#### Traits Used
- `HasUid`, `HasCache`

#### Critical Methods
- `getSubscribersConditions()` - Builds SQL conditions from segment rules
- `subscribers()` - Gets subscribers matching segment
- `isSubscriberIncluded()` - Checks if subscriber matches
- `updateConditions()` - Updates segment conditions
- `subscribersCount()` - Counts matching subscribers

#### Operators Supported
```php
// Field operators
'equal', 'not_equal', 'contains', 'not_contains',
'starts', 'ends', 'not_starts', 'not_ends',
'greater', 'less', 'blank', 'not_blank'

// Date operators
'created_date_greater', 'created_date_less', 'created_date_last_x_days'

// Verification operators
'verification_equal', 'verification_not_equal'

// Tag operators
'tag_contains', 'tag_not_contains'

// Activity operators
'last_open_email_greater_than_days', 'last_open_email_less_than_days'
'last_link_click_greater_than_days', 'last_link_click_less_than_days'
```

---

### 1.8 Customer Model
**File:** `Customer.php`
**Purpose:** Customer account management with subscriptions

#### Key Relationships
- `hasOne`: User
- `belongsTo`: Admin, Contact, Language
- `hasMany`: MailList, Campaign, Subscriber (through MailList), Template, SendingServer, SendingDomain, EmailVerificationServer, Blacklist, Sender, TrackingDomain, Product, Invoice, Form, Website, Source, BillingAddress, Subscription, Automation2, TrackingLog, Log

#### Traits Used
- `TrackJobs`, `HasUid`, `HasCache`

#### Constants
```php
STATUS_INACTIVE = 'inactive'
STATUS_ACTIVE = 'active'

// Directory structure
BASE_DIR = 'app/customers'
ATTACHMENTS_DIR = 'home/attachments'
TEMPLATES_DIR = 'home/templates'
PRODUCT_DIR = 'home/products'
LOGS_DIR = 'home/logs/'
```

#### Critical Methods
- `subscribersCount()`, `subscribersUsage()` - Quota tracking
- `getTimezone()`, `getLanguageCode()` - Localization
- `createAccountAndUser()` - Creates customer + user
- `activeSendingServers()` - Gets active servers
- `getVerifiedIdentities()` - Gets all verified emails/domains
- `assignGeneralPlan()` - Assigns subscription plan
- `getCurrentActiveGeneralSubscription()` - Gets active subscription
- `getEmailVerificationServers()` - Gets verification servers
- `getCacheIndex()` - Defines cached metrics

#### Fillable Attributes
```php
'name', 'timezone', 'language_id', 'color_scheme',
'text_direction', 'menu_layout', 'theme_mode'
```

---

## 2. Supporting Models

### 2.1 Field & SubscriberField
- **Field**: Defines custom fields for mail lists
- **SubscriberField**: Stores field values for each subscriber

### 2.2 Tracking & Analytics Models
- **OpenLog**: Tracks email opens with IP/user agent
- **ClickLog**: Tracks link clicks
- **BounceLog**: Records bounced emails
- **FeedbackLog**: Tracks spam reports
- **UnsubscribeLog**: Records unsubscribe actions

### 2.3 Campaign Support Models
- **CampaignLink**: Stores campaign URLs for tracking
- **CampaignWebhook**: Campaign event webhooks
- **CampaignsListsSegment**: Links campaigns to lists/segments

### 2.4 Server Infrastructure Models
- **SendingServerAmazonApi**, **SendingServerAmazonSmtp**, **SendingServerMailgunApi**, etc. - Specific implementations for each provider
- **SendingDomain**: Verified sending domains
- **Sender**: Verified sender identities
- **TrackingDomain**: Custom tracking domains

### 2.5 List Management Models
- **SegmentCondition**: Individual segment rules
- **Blacklist**: Blocked email addresses
- **Contact**: Contact information for lists

### 2.6 Automation Models
- **Automation2**: Marketing automation workflows
- **AutoTrigger**: Automation triggers
- **Email**: Automation email templates

### 2.7 Additional Support Models
- **Language**: System languages
- **Layout**: System page layouts
- **Page**: Landing/form pages
- **TemplateCategory**: Template categorization
- **IpLocation**: IP geolocation for analytics

---

## 3. Common Traits Used

### 3.1 HasUid
**Purpose:** Generates and manages unique identifiers
**Used by:** Campaign, MailList, Subscriber, SendingServer, Template, Segment, Customer, and most other models

### 3.2 HasCache
**Purpose:** Provides caching functionality for expensive calculations
**Used by:** MailList, Segment, Customer
**Key Methods:**
- `updateCache()` - Updates all cached values
- `readCache($key, $default)` - Reads cached value
- `getCacheIndex()` - Defines what to cache

### 3.3 TrackJobs
**Purpose:** Background job monitoring
**Used by:** MailList, Customer
**Key Methods:**
- `dispatchWithMonitor()` - Dispatches job with tracking
- `jobMonitors()` - Gets job history

### 3.4 HasTemplate
**Purpose:** Template management functionality
**Used by:** Campaign
**Key Methods:**
- `getTemplateContent()` - Gets template HTML
- `setTemplate()` - Assigns template

### 3.5 QueryHelper
**Purpose:** Query building utilities
**Used by:** MailList

---

## 4. Critical Models for Mailing System

### Tier 1: Core (Must Have)
1. **Campaign** - The heart of the mailing system
2. **MailList** - Manages subscriber lists
3. **Subscriber** - Subscriber data and status
4. **SendingServer** - Email delivery infrastructure
5. **TrackingLog** - Delivery tracking

### Tier 2: Essential (High Priority)
6. **Template** - Email templates
7. **Field** / **SubscriberField** - Custom fields
8. **Segment** / **SegmentCondition** - List segmentation
9. **Customer** - Account management
10. **CampaignLink** - Link tracking

### Tier 3: Analytics (Important)
11. **OpenLog** - Open tracking
12. **ClickLog** - Click tracking
13. **BounceLog** - Bounce tracking
14. **UnsubscribeLog** - Unsubscribe tracking
15. **FeedbackLog** - Spam report tracking

### Tier 4: Infrastructure (Supporting)
16. **SendingDomain** - Domain verification
17. **Sender** - Sender verification
18. **TrackingDomain** - Custom tracking domains
19. **BounceHandler** / **FeedbackLoopHandler** - Bounce/FBL processing
20. **Blacklist** - Blocked addresses

### Tier 5: Advanced Features (Optional)
21. **Automation2** - Marketing automation
22. **Form** / **Page** - Landing pages
23. **EmailVerificationServer** - Email validation
24. **IpLocation** - Geolocation tracking

---

## 5. Key Relationships Map

```
Customer
  └── MailList
       ├── Field (hasMany)
       ├── Subscriber (hasMany)
       │    └── SubscriberField (hasMany)
       ├── Segment (hasMany)
       │    └── SegmentCondition (hasMany)
       └── Campaign (belongsToMany)
            ├── Template (belongsTo)
            ├── SendingServer (for delivery)
            ├── TrackingLog (hasMany)
            │    ├── OpenLog (via message_id)
            │    ├── ClickLog (via message_id)
            │    ├── BounceLog (via message_id)
            │    ├── FeedbackLog (via message_id)
            │    └── UnsubscribeLog (via message_id)
            ├── CampaignLink (hasMany)
            └── CampaignWebhook (hasMany)

SendingServer
  ├── SendingDomain (hasMany)
  ├── Sender (hasMany)
  └── BounceHandler (belongsTo)
```

---

## 6. Important Methods & Scopes Summary

### Campaign Methods
- **Delivery**: `loadDeliveryJobs()`, `subscribersToSend()`, `pickSendingServer()`, `trackMessage()`
- **Analytics**: `openRate()`, `clickRate()`, `bounceRate()`, `deliveredCount()`
- **Management**: `copy()`, `resend()`, `updateLinks()`

### MailList Methods
- **Import/Export**: `import()`, `export()`, `uploadCsv()`, `readCsv()`
- **Subscribers**: `subscribe()`, `subscribersCount()`, `pickSendingServer()`
- **Email**: `sendSubscriptionConfirmationEmail()`, `sendSubscriptionWelcomeEmail()`

### Subscriber Methods
- **Status**: `unsubscribe()`, `confirm()`, `sendToBlacklist()`
- **Data**: `updateFields()`, `getTags()`, `updateTags()`
- **Activity**: `openLogs()`, `clickLogs()`, `getHistory()`
- **Verification**: `verify()`, `isDeliverable()`

### Segment Methods
- **Query**: `getSubscribersConditions()`, `subscribers()`, `isSubscriberIncluded()`
- **Management**: `updateConditions()`, `subscribersCount()`

---

## 7. Database Schema Observations

### Key Tables
- `campaigns` - Campaign master data
- `mail_lists` - List master data
- `subscribers` - Subscriber master data
- `subscriber_fields` - Custom field values
- `tracking_logs` - Delivery tracking (parent)
- `open_logs`, `click_logs`, `bounce_logs`, `feedback_logs`, `unsubscribe_logs` - Event tracking (children)
- `sending_servers` - Server configurations
- `templates` - Email templates
- `segments` - List segments
- `segment_conditions` - Segment rules
- `fields` - Custom field definitions

### Junction Tables
- `campaigns_lists_segments` - Campaign to List/Segment relationship
- `mail_lists_sending_servers` - List to Server assignment
- `plans_sending_servers` - Plan to Server assignment
- `templates_categories` - Template categorization

---

## 8. Critical Features for Integration

### 8.1 Email Sending Flow
1. Campaign selects subscribers from lists/segments
2. Filters by status (subscribed) and verification (deliverable or unverified)
3. Picks sending server using RouletteWheel (weighted selection)
4. Prepares email with template + subscriber data
5. Sends via server's send() method
6. Tracks in TrackingLog
7. Records opens/clicks/bounces/unsubscribes

### 8.2 Subscription Flow
1. Subscriber submits form/API request
2. MailList->subscribe() validates data
3. Creates/updates Subscriber record
4. Updates custom fields via SubscriberField
5. Sends confirmation email if double opt-in
6. Subscriber clicks link to confirm
7. Status changes to 'subscribed'
8. Sends welcome email if configured

### 8.3 Import Flow
1. Upload CSV via MailList->uploadCsv()
2. Read and validate headers
3. Map CSV columns to Fields
4. Batch process subscribers
5. Create/update Subscriber + SubscriberField records
6. Update blacklist
7. Dispatch MailListImported event

### 8.4 Segmentation Flow
1. Define segment with conditions
2. SegmentCondition builds SQL WHERE clause
3. Joins subscriber_fields for custom field conditions
4. Applies matching logic (all/any)
5. Returns filtered subscriber query
6. Campaign uses segment to filter recipients

---

## 9. Events & Jobs

### Events
- `MailListSubscription` - New subscriber confirmed
- `MailListUnsubscription` - Subscriber unsubscribed
- `MailListUpdated` - List data changed
- `MailListImported` - Import completed

### Jobs
- `ImportSubscribersJob` - Import CSV
- `ExportSubscribersJob` - Export CSV
- `VerifyMailListJob` - Verify emails
- `SendMessage` - Send single email
- `ExecuteCampaignCallback` - Webhook callback
- `UpdateSegmentJob` - Update segment cache

---

## 10. Recommendations for Integration

### Must Migrate
1. **Core tables**: campaigns, mail_lists, subscribers, tracking_logs
2. **Field system**: fields, subscriber_fields
3. **Tracking**: open_logs, click_logs, bounce_logs, unsubscribe_logs
4. **Relationships**: campaigns_lists_segments, mail_lists_sending_servers

### Consider Simplifying
1. **SendingServer**: Create adapter pattern instead of full migration
2. **Template**: May integrate with existing template system
3. **Customer**: Map to existing user system
4. **Automation2**: Evaluate if needed initially

### Keep Separate
1. **Billing/Subscription**: Not relevant for non-SaaS
2. **Admin/Permission**: Use existing RBAC
3. **Product/Website**: E-commerce specific features

---

## Conclusion

The Acelle Mail models represent a sophisticated, well-architected email marketing system. The core models (Campaign, MailList, Subscriber, SendingServer, TrackingLog) form a solid foundation. The extensive use of relationships, traits (HasUid, HasCache, TrackJobs), and clear separation of concerns makes the system modular and maintainable.

For integration into the Mailing module, focus on:
1. **Core mailing infrastructure** (Tier 1-2 models)
2. **Analytics tracking** (Tier 3 models)
3. **Adapting** authentication, templates, and sending servers to existing system
4. **Evaluating** whether advanced features (automation, forms) are needed

The models demonstrate excellent practices:
- Clear constants for statuses and types
- Comprehensive relationship definitions
- Caching for expensive operations
- Job queuing for long-running tasks
- Event-driven architecture
- Flexible segmentation with query builders

This analysis provides the foundation for informed architectural decisions when integrating Acelle's mailing capabilities.
