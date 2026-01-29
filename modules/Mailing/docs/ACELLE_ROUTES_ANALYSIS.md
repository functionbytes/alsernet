# Acelle Mail Routes Analysis

**Document Version:** 1.0
**Analysis Date:** 2026-01-29
**Source Path:** `/Users/functionbytes/Function/Coding/acelle/routes/`

## Executive Summary

This document provides a comprehensive analysis of all routing definitions in the Acelle Mail application. Acelle Mail is a full-featured email marketing platform with extensive functionality for campaign management, subscriber management, automation workflows, and multi-tenant administration.

**Total Route Files:** 4
- `web.php` (1,276 lines) - Main web routes
- `api.php` (78 lines) - RESTful API routes
- `console.php` (20 lines) - Console commands
- `channels.php` (19 lines) - Broadcasting channels

---

## Table of Contents

1. [Route Structure Overview](#route-structure-overview)
2. [Web Routes Analysis](#web-routes-analysis)
3. [API Routes Analysis](#api-routes-analysis)
4. [Console Routes](#console-routes)
5. [Broadcast Channels](#broadcast-channels)
6. [Critical Mailing System Routes](#critical-mailing-system-routes)
7. [Middleware Configuration](#middleware-configuration)
8. [Security Considerations](#security-considerations)

---

## Route Structure Overview

### File Organization

```
acelle/routes/
├── web.php          # Frontend UI routes (Customer + Admin areas)
├── api.php          # RESTful API endpoints (v1)
├── console.php      # Artisan console commands
└── channels.php     # WebSocket/Broadcasting channels
```

### Route Groups Hierarchy

```
web.php
├── Installation Routes (middleware: installed)
├── Public Routes (middleware: not_installed)
├── Authentication Routes (middleware: not_installed, not_logged_in)
├── Frontend Routes (middleware: not_installed, auth, frontend)
│   ├── Subscription-Required Routes (middleware: subscription)
│   └── General Frontend Routes
└── Admin Routes (namespace: Admin, middleware: not_installed, auth, backend)

api.php
└── API v1 Routes (prefix: v1, middleware: auth:api)
```

---

## Web Routes Analysis

### 1. Installation Routes

**Middleware:** `['installed']`
**Purpose:** Initial system setup and configuration

| Method | URI | Controller | Action | Description |
|--------|-----|------------|--------|-------------|
| GET | `/install` | InstallController | starting | Installation landing page |
| GET/POST | `/install/site-info` | InstallController | siteInfo | Site information setup |
| GET | `/install/system-compatibility` | InstallController | systemCompatibility | Check system requirements |
| GET/POST | `/install/database` | InstallController | database | Database configuration |
| GET | `/install/database_import` | InstallController | databaseImport | Import database schema |
| GET | `/install/import` | InstallController | import | Data import process |
| GET/POST | `/install/cron-jobs` | InstallController | cronJobs | Cron job configuration |
| GET | `/install/finishing` | InstallController | finishing | Finalization steps |
| GET | `/install/finish` | InstallController | finish | Complete installation |

**Critical for Mailing:** Database configuration and cron job setup are essential for campaign sending and automation.

---

### 2. Asset Routes (Public Access)

**Middleware:** None (public access)
**Purpose:** File serving for customer assets, email attachments, and tracking pixels

| Method | URI | Description | Security Notes |
|--------|-----|-------------|----------------|
| GET | `/files/{uid}/{name?}` | User uploaded files | UID-based isolation |
| GET | `/thumbs/{uid}/{name?}` | Image thumbnails | UID-based isolation |
| GET | `/p/assets/{path}` | Email assets (deprecated) | Base64 encoded path |
| GET | `/assets/{dirname}/{basename}` | Email assets (current) | Base64 encoded dirname |
| GET | `/setting/{filename}` | System setting files | Admin uploads |
| GET | `/assets/images/avatar/subscriber-{uid?}.jpg` | Subscriber avatars | Dynamic generation |

**Security:** All file routes implement path validation and MIME type detection to prevent unauthorized access.

---

### 3. Authentication Routes

**Middleware:** `['not_installed', 'not_logged_in']`
**Includes:** Laravel Auth::routes() + OAuth

| Method | URI | Controller | Action | Description |
|--------|-----|------------|--------|-------------|
| Multiple | `/login`, `/register`, etc. | Auth\* | various | Standard Laravel auth |
| GET | `/auth/google/redirect` | AuthController | googleRedirect | Google OAuth redirect |
| GET | `/auth/google/callback` | AuthController | googleCallback | Google OAuth callback |
| GET | `/auth/facebook/redirect` | AuthController | facebookRedirect | Facebook OAuth redirect |
| GET | `/auth/facebook/callback` | AuthController | facebookCallback | Facebook OAuth callback |
| GET | `/login/token/{token}` | Controller | tokenLogin | Token-based login |
| GET | `/validate-token/{api_token}` | Controller | validateToken | API token validation |
| GET | `/user/activate/{token}` | UserController | activate | Email verification |
| GET | `/autologin/{api_token}` | Controller | autoLogin | Auto-login via API token |

**Critical for Mailing:** Token-based authentication enables API access and webhook integrations.

---

### 4. Campaign Tracking Routes (Public)

**Middleware:** `['not_installed']`
**Purpose:** Email tracking pixels, click tracking, and unsubscribe handling

| Method | URI | Controller | Action | Named Route | Description |
|--------|-----|------------|--------|-------------|-------------|
| GET | `/p/{message_id}/open` | CampaignController | open | openTrackingUrl | Open tracking pixel |
| GET | `/p/{url}/click/{message_id?}` | CampaignController | click | clickTrackingUrl | Click tracking redirect |
| GET | `/c/{subscriber}/unsubscribe/{message_id?}` | CampaignController | unsubscribe | unsubscribeUrl | Unsubscribe link |
| GET | `/campaigns/{message_id}/web-view` | CampaignController | webView | webViewerUrl | Web version of email |
| GET | `/campaigns/{campaign_uid}/webview/{subscriber_uid}/preview` | CampaignController | webViewPreview | webViewerPreviewUrl | Preview web version |

**Critical for Mailing:** These routes are embedded in every campaign email for tracking and user management.

---

### 5. List Management & Subscription Pages (Public)

**Middleware:** `['not_installed']`
**Purpose:** Public-facing subscription forms and profile management

| Method | URI | Description |
|--------|-----|-------------|
| POST | `/lists/{uid}/embedded-form-subscribe` | Submit embedded subscription form |
| POST | `/lists/{uid}/embedded-form-subscribe-captcha` | Submit with CAPTCHA validation |
| GET | `/lists/{uid}/check-email` | AJAX email validation |
| GET/POST | `/lists/{list_uid}/sign-up` | Subscription form page |
| GET | `/lists/{list_uid}/sign-up/{subscriber_uid}/thank-you` | Post-signup thank you |
| GET | `/lists/{list_uid}/subscribe-confirm/{uid}/{code}` | Double opt-in confirmation |
| GET/POST | `/lists/{list_uid}/unsubscribe/{uid}/{code}` | Unsubscribe form |
| GET | `/lists/{list_uid}/unsubscribe-success/{uid}` | Unsubscribe confirmation |
| GET/POST | `/lists/{list_uid}/update-profile/{uid}/{code}` | Update subscriber profile |
| GET | `/lists/{list_uid}/update-profile-success/{uid}` | Profile update confirmation |

**Critical for Mailing:** These routes ensure GDPR compliance and subscriber consent management.

---

### 6. Delivery Notification Handler (Webhook)

**Middleware:** `['not_installed']`
**Purpose:** Receive bounce notifications, feedback loops, and delivery reports

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/delivery/notify/{stype?}` | Generic delivery notification webhook |
| POST | `/delivery/report` | Delivery report submission |

**Critical for Mailing:** External mail servers (SMTP, SES, etc.) POST delivery events here for bounce handling.

---

### 7. Public Campaign Reports

**Middleware:** `['not_installed', 'not_logged_in']`
**Prefix:** `/pub/campaigns/`
**Purpose:** Shareable campaign analytics and reports

| URI Pattern | Description |
|-------------|-------------|
| `{uid}/overview` | Campaign overview dashboard |
| `{uid}/subscribers` | Subscriber list for campaign |
| `{uid}/subscribers/listing` | AJAX subscriber listing |
| `{uid}/open-map` | Geographic open map |
| `{uid}/tracking-log` | Full tracking log |
| `{uid}/tracking-log/listing` | AJAX tracking log listing |
| `{uid}/bounce-log` | Bounce log viewer |
| `{uid}/feedback-log` | Feedback loop log |
| `{uid}/open-log` | Open event log |
| `{uid}/click-log` | Click event log |
| `{uid}/unsubscribe-log` | Unsubscribe event log |
| `{uid}/chart24h` | 24-hour activity chart |
| `{uid}/chart` | Full campaign chart |
| `{uid}/chart/countries/open` | Open by country chart |
| `{uid}/chart/countries/click` | Click by country chart |
| `{uid}/links` | Campaign link performance |
| `{uid}/preview/content/{subscriber_uid?}` | Email preview with merge tags |

**Critical for Mailing:** Enables public reporting without authentication for external stakeholders.

---

### 8. Frontend Routes (Authenticated Customers)

**Middleware:** `['not_installed', 'auth', 'frontend']`
**Purpose:** Customer dashboard, campaign management, subscriber management

#### 8.1 Dashboard & Overview

| Method | URI | Controller | Action | Description |
|--------|-----|------------|--------|-------------|
| GET | `/` | HomeController | index | Customer dashboard |
| GET | `/audience/growth-chart` | AudienceController | growthChart | Audience growth analytics |
| GET | `/audience/overview` | AudienceController | overview | Audience overview |

#### 8.2 Forms (Landing Pages)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/forms` | Form list |
| GET | `/forms/list` | AJAX form listing |
| GET/POST | `/forms/create` | Create new form |
| GET | `/forms/templates` | Form template gallery |
| GET | `/forms/{uid}/builder` | Form builder interface |
| GET | `/forms/{uid}/build` | Build form |
| GET | `/forms/{uid}/edit/content` | Edit form content |
| POST | `/forms/{uid}/preview` | Preview form |
| POST | `/forms/publish` | Publish form |
| POST | `/forms/unpublish` | Unpublish form |
| GET/POST | `/forms/{uid}/connect` | Connect form to list |
| POST | `/forms/{uid}/disconnect` | Disconnect form |
| POST | `/forms/{uid}/settings` | Form settings |
| POST | `/forms/delete` | Delete forms |

**Critical for Mailing:** Forms are the primary subscriber acquisition tool.

#### 8.3 Websites (Tracking Integration)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/websites` | Website list |
| GET | `/websites/list` | AJAX website listing |
| GET/POST | `/websites/create` | Create website tracking |
| GET | `/websites/{uid}/show` | Website details |
| POST | `/websites/{uid}/connect` | Connect website |
| POST | `/websites/disconnect` | Disconnect website |
| GET | `/websites/{uid}/check` | Check website integration |
| POST | `/websites/delete` | Delete websites |

**Public Route:**
- GET `/websites/{uid}/connect.js` - JavaScript tracking snippet (no auth required)

#### 8.4 Mail Lists

**Resource Route:** `/lists` (MailListController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/lists` | List index |
| GET | `/lists/listing/{page?}` | AJAX listing |
| GET | `/lists/{uid}/overview` | List dashboard |
| GET/POST | `/lists/create` | Create new list |
| GET | `/lists/{uid}/edit` | Edit list |
| PATCH | `/lists/{uid}/update` | Update list |
| POST | `/lists/delete` | Delete lists |
| GET/POST | `/lists/copy` | Copy list |
| GET/POST | `/lists/select` | Select list dialog |
| GET | `/lists/quick-view` | Quick view modal |
| GET | `/lists/{uid}/list-growth` | List growth chart |
| GET | `/lists/{uid}/list-statistics-chart` | Statistics chart |
| GET | `/lists/sort` | Sort lists |

**Email Verification:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/lists/{uid}/verification` | Verification dashboard |
| POST | `/lists/{uid}/verification/start` | Start verification job |
| POST | `/lists/{uid}/verification/{job_uid}/stop` | Stop verification job |
| POST | `/lists/{uid}/verification/reset` | Reset verification |
| GET | `/lists/{uid}/verification/{job_uid}/progress` | Verification progress |
| GET | `/lists/{uid}/email-verification/chart` | Verification chart |

**Embedded Forms:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/lists/{uid}/embedded-form` | Embedded form settings |
| GET | `/lists/{uid}/embedded-form-frame` | Embedded form iframe |

**Critical for Mailing:** Lists are the core data structure for all campaigns.

#### 8.5 Fields (Custom List Fields)

**Prefix:** `/lists/{list_uid}/fields`

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/lists/{list_uid}/fields` | Field list |
| GET | `/lists/{list_uid}/fields/sort` | Sort fields |
| POST | `/lists/{list_uid}/fields/store` | Create field |
| GET | `/lists/{list_uid}/fields/sample/{type}` | Sample field values |
| GET | `/lists/{list_uid}/fields/{uid}/delete` | Delete field |

#### 8.6 Subscribers

**Prefix:** `/lists/{list_uid}/subscribers`

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/lists/{list_uid}/subscribers` | Subscriber list |
| GET | `/lists/{list_uid}/subscribers/listing` | AJAX subscriber listing |
| GET | `/lists/{list_uid}/subscribers/create` | Create subscriber form |
| POST | `/lists/{list_uid}/subscribers/store` | Store new subscriber |
| GET | `/lists/{list_uid}/subscribers/{uid}/edit` | Edit subscriber |
| PATCH | `/lists/{list_uid}/subscribers/{uid}/update` | Update subscriber |
| POST/GET | `/lists/{list_uid}/subscribers/delete` | Delete subscribers |
| POST | `/lists/{list_uid}/subscribers/subscribe` | Bulk subscribe |
| POST | `/lists/{list_uid}/subscribers/unsubscribe` | Bulk unsubscribe |

**Bulk Operations:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/lists/{list_uid}/subscribers/assign-values` | Bulk assign field values |
| GET/POST | `/lists/{list_uid}/subscribers/bulk-delete` | Bulk delete confirmation |

**Copy/Move:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/lists/{from_uid}/copy-move-from/{action}` | Copy/move form |
| POST | `/subscribers/move` | Move subscribers |
| POST | `/subscribers/copy` | Copy subscribers |

**Import/Export:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/lists/{list_uid}/subscribers/import` | Import page (legacy) |
| POST | `/lists/{list_uid}/subscribers/import/dispatch` | Dispatch import job (legacy) |
| GET | `/lists/{list_uid}/import/{job_uid}/progress` | Import progress (legacy) |
| GET | `/lists/import/{job_uid}/log/download` | Download import log (legacy) |
| POST | `/lists/import/{job_uid}/cancel` | Cancel import (legacy) |
| GET | `/lists/{list_uid}/subscribers/import2` | New import wizard |
| GET | `/lists/{list_uid}/subscribers/import2/wizard` | Import wizard interface |
| POST | `/lists/{list_uid}/subscribers/import2/upload` | Upload CSV file |
| GET | `/lists/{list_uid}/subscribers/import2/mapping` | Map CSV columns |
| POST | `/lists/{list_uid}/subscribers/import2/validate` | Validate import data |
| POST | `/lists/{list_uid}/subscribers/import2/run` | Run import |
| GET | `/lists/{list_uid}/subscribers/import2/progress` | Import progress |
| GET | `/lists/{list_uid}/subscribers/import2/progress/content` | Progress content |
| GET | `/lists/{list_uid}/subscribers/export` | Export page |
| POST | `/lists/{list_uid}/subscribers/export/dispatch` | Dispatch export job |
| GET | `/lists/export/{job_uid}/progress` | Export progress |
| GET | `/lists/export/{job_uid}/log/download` | Download exported CSV |
| POST | `/lists/export/{job_uid}/cancel` | Cancel export |

**Tags:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/lists/{list_uid}/subscriber/{uid}/update-tags` | Update subscriber tags |
| POST | `/lists/{list_uid}/subscriber/{uid}/remove-tag` | Remove tag |

**Email Verification:**
| Method | URI | Description |
|--------|-----|-------------|
| POST | `/subscriber/{uid}/verification/start` | Start subscriber verification |
| POST | `/subscriber/{uid}/verification/reset` | Reset verification status |
| POST | `/lists/{list_uid}/subscribers/resend/confirmation-email/{uids?}` | Resend confirmation emails |

**Critical for Mailing:** Subscriber management is the core of list operations.

#### 8.7 Segments

**Prefix:** `/lists/{list_uid}/segments`

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/lists/{list_uid}/segments` | Segment list |
| GET | `/lists/{list_uid}/segments/listing` | AJAX segment listing |
| GET | `/lists/{list_uid}/segments/create` | Create segment |
| POST | `/lists/{list_uid}/segments/store` | Store segment |
| GET | `/lists/{list_uid}/segments/{uid}/edit` | Edit segment |
| PATCH | `/lists/{list_uid}/segments/{uid}/update` | Update segment |
| GET | `/lists/{list_uid}/segments/delete` | Delete segments |
| GET | `/lists/{list_uid}/segments/{uid}/subscribers` | Segment subscribers |
| GET | `/lists/{list_uid}/segments/{uid}/listing_subscribers` | AJAX subscriber listing |
| GET | `/lists/{list_uid}/segments/sample_condition` | Sample condition UI |
| GET | `/segments/condition-value-control` | Condition value control |
| GET | `/segments/select_box` | Segment select box |

**Critical for Mailing:** Segments enable targeted campaigns based on subscriber criteria.

#### 8.8 Pages (Signup/Unsubscribe Pages)

**Prefix:** `/lists/{list_uid}/pages/{alias}`

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/lists/{list_uid}/pages/{alias}/update` | Update page content |
| POST | `/lists/{list_uid}/pages/{alias}/preview` | Preview page |
| POST | `/lists/{list_uid}/pages/{alias}/restore-default` | Restore default template |

**Page Aliases:** `sign-up-form`, `sign-up-thankyou-page`, `sign-up-confirmation-thankyou`, `unsubscribe-form`, `unsubscribe-success-page`, `profile-update-form`, `profile-update-success-page`

#### 8.9 Templates

**Resource Route:** `/templates` (TemplateController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/templates` | Template gallery |
| GET | `/templates/listing/{page?}` | AJAX template listing |
| GET | `/templates/choosing/{campaign_uid}/{page?}` | Choose template for campaign |
| GET/POST | `/templates/create` | Create template (wizard) |
| GET | `/templates/{uid}/edit` | Edit template settings |
| PATCH | `/templates/{uid}/update` | Update template |
| GET | `/templates/delete` | Delete templates |
| GET | `/templates/{uid}/preview` | Preview template |
| GET/POST | `/templates/{uid}/copy` | Copy template |
| GET | `/templates/upload` | Upload template |
| POST | `/templates/upload` | Process template upload |

**Builder (Visual Editor):**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/templates/builder/create` | Create template in builder |
| GET | `/templates/builder/templates/{category_uid?}` | Builder template gallery |
| GET | `/templates/{uid}/builder/edit` | Open builder |
| POST | `/templates/{uid}/builder/edit` | Save builder changes |
| GET | `/templates/{uid}/builder/edit/content` | Get builder content |
| POST | `/templates/{uid}/builder/edit/asset` | Upload asset (image) |
| GET | `/templates/{uid}/builder/change-template/{change_uid}` | Change template design |

**Template Management:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/templates/{uid}/change-name` | Change template name |
| GET/POST | `/templates/{uid}/categories` | Manage template categories |
| GET/POST | `/templates/{uid}/update-thumb` | Upload thumbnail |
| GET/POST | `/templates/{uid}/update-thumb-url` | Set thumbnail URL |
| POST | `/templates/{uid}/export` | Export template |
| GET | `/templates/chat` | AI template assistant |

**RSS Feeds:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/templates/rss/parse` | Parse RSS feed URL |

**Critical for Mailing:** Templates define the email design and are reusable across campaigns.

#### 8.10 Campaigns

**Resource Route:** `/campaigns` (CampaignController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/campaigns` | Campaign list |
| GET | `/campaigns/listing/{page?}` | AJAX campaign listing |
| GET | `/campaigns/select-type` | Select campaign type |
| GET/POST | `/campaigns/create` | Create campaign |
| GET | `/campaigns/{uid}/edit` | Edit campaign (redirect to setup) |
| PATCH | `/campaigns/{uid}/update` | Update campaign |
| POST | `/campaigns/delete` | Delete campaigns |
| GET/POST | `/campaigns/copy` | Copy campaign |
| GET | `/campaigns/delete/confirm` | Delete confirmation modal |

**Campaign Setup Wizard:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/campaigns/{uid}/setup` | Step 1: Campaign setup (name, list, subject) |
| GET/POST | `/campaigns/{uid}/recipients` | Step 2: Recipients (list/segment selection) |
| GET | `/campaigns/{uid}/list-segment-form` | List/segment form |
| GET/POST | `/campaigns/{uid}/template` | Step 3: Template selection |
| GET | `/campaigns/{uid}/template/select` | Template selection UI |
| GET | `/campaigns/{uid}/template/choose/{template_uid}` | Choose specific template |
| GET | `/campaigns/{uid}/template/create` | Create new template from campaign |
| GET/POST | `/campaigns/{uid}/schedule` | Step 4: Schedule delivery |
| GET/POST | `/campaigns/{uid}/confirm` | Step 5: Confirm and send |

**Template Editing:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/campaigns/{uid}/template/builder-select` | Choose builder type |
| GET/POST | `/campaigns/{uid}/template/builder-classic` | Classic builder editor |
| GET/POST | `/campaigns/{uid}/template/builder-plain` | Plain text editor |
| GET/POST | `/campaigns/{uid}/template/edit` | Visual builder |
| GET | `/campaigns/{uid}/template/content` | Get template content |
| GET/POST | `/campaigns/{uid}/template/upload` | Upload HTML template |
| GET/POST | `/campaigns/{uid}/template/layout` | Choose layout |
| GET | `/campaigns/{uid}/template/layout/list` | Layout list |
| GET | `/campaigns/{uid}/template/change/{template_uid}` | Change template |
| GET | `/campaigns/{uid}/template/build/{style}` | Build template |
| GET | `/campaigns/{uid}/template/rebuild` | Rebuild template |
| GET | `/campaigns/{uid}/template/preview` | Preview template |
| GET | `/campaigns/{uid}/template/iframe` | Template iframe |
| GET | `/campaigns/{uid}/template/review` | Review before send |
| GET | `/campaigns/{uid}/template/review-iframe` | Review iframe |

**Plain Text:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/campaigns/{uid}/plain` | Edit plain text version |
| POST | `/campaigns/{uid}/custom-plain/on` | Enable custom plain text |
| POST | `/campaigns/{uid}/custom-plain/off` | Use auto-generated plain text |

**Preheader:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/campaigns/{uid}/preheader` | Preheader settings |
| GET/POST | `/campaigns/{uid}/preheader/add` | Add preheader |
| POST | `/campaigns/{uid}/preheader/remove` | Remove preheader |

**Attachments:**
| Method | URI | Description |
|--------|-----|-------------|
| POST | `/campaigns/{uid}/upload-attachment` | Upload attachment |
| GET | `/campaigns/{uid}/download-attachment` | Download attachment |
| POST | `/campaigns/{uid}/remove-attachment` | Remove attachment |

**Preview & Testing:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/campaigns/{uid}/preview` | Preview campaign |
| GET | `/campaigns/{uid}/preview/content/{subscriber_uid?}` | Preview with merge tags |
| GET | `/campaigns/{uid}/preview-as` | Preview as specific subscriber |
| GET | `/campaigns/{uid}/preview-as/list` | List subscribers for preview |
| GET/POST | `/campaigns/send-test-email` | Send test email |
| GET | `/campaigns/{uid}/spam-score` | Check spam score |

**Campaign Execution:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/campaigns/{uid}/run` | Start campaign |
| POST | `/campaigns/pause` | Pause campaign |
| POST | `/campaigns/restart` | Restart paused campaign |
| GET/POST | `/campaigns/{uid}/resend` | Resend to non-openers |
| GET | `/campaigns/{uid}/update-stats` | Update statistics |

**Analytics & Reports:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/campaigns/{uid}/overview` | Campaign dashboard |
| GET | `/campaigns/quick-view` | Quick view modal |
| GET | `/campaigns/{uid}/chart24h` | 24-hour chart |
| GET | `/campaigns/{uid}/chart` | Full chart |
| GET | `/campaigns/{uid}/chart/countries/open` | Open by country |
| GET | `/campaigns/{uid}/chart/countries/click` | Click by country |
| GET | `/campaigns/{uid}/links` | Link performance |
| GET | `/campaigns/{uid}/subscribers` | Subscriber list |
| GET | `/campaigns/{uid}/subscribers/listing` | AJAX subscriber listing |
| GET | `/campaigns/{uid}/open-map` | Geographic open map |

**Logs:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/campaigns/{uid}/tracking-log` | Full tracking log |
| GET | `/campaigns/{uid}/tracking-log/listing` | AJAX tracking listing |
| GET | `/campaigns/{uid}/tracking-log/download` | Export tracking log |
| GET | `/campaigns/job/{uid}/progress` | Export progress |
| GET | `/campaigns/job/{uid}/download` | Download exported log |
| GET | `/campaigns/{uid}/bounce-log` | Bounce log |
| GET | `/campaigns/{uid}/bounce-log/listing` | AJAX bounce listing |
| GET | `/campaigns/{uid}/feedback-log` | Feedback loop log |
| GET | `/campaigns/{uid}/feedback-log/listing` | AJAX feedback listing |
| GET | `/campaigns/{uid}/open-log` | Open log |
| GET | `/campaigns/{uid}/open-log/listing` | AJAX open listing |
| GET | `/campaigns/{uid}/open-log/{message_id}/execute` | Execute open log action |
| GET | `/campaigns/{uid}/click-log` | Click log |
| GET | `/campaigns/{uid}/click-log/listing` | AJAX click listing |
| GET | `/campaigns/{uid}/click-log/{message_id}/execute` | Execute click log action |
| GET | `/campaigns/{uid}/unsubscribe-log` | Unsubscribe log |
| GET | `/campaigns/{uid}/unsubscribe-log/listing` | AJAX unsubscribe listing |

**Webhooks:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/campaigns/{uid}/webhooks` | Webhook list |
| GET | `/campaigns/{uid}/webhooks/list` | AJAX webhook listing |
| GET/POST | `/campaigns/{uid}/webhooks/add` | Add webhook |
| GET | `/campaigns/{uid}/webhooks/link-select` | Select link for webhook |
| GET/POST | `/campaigns/webhooks/{webhook_uid}/edit` | Edit webhook |
| POST | `/campaigns/webhooks/{webhook_uid}/delete` | Delete webhook |
| GET/POST | `/campaigns/webhooks/{webhook_uid}/test` | Test webhook |
| POST | `/campaigns/webhooks/{webhook_uid}/test/{message_id}` | Test webhook with message |
| GET | `/campaigns/webhooks/{webhook_uid}/sample/request` | Sample webhook request |

**Copy/Move:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/campaigns/{from_uid}/copy-move-from/{action}` | Copy/move form |

**Campaign Select:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/campaigns/select2` | Select2 dropdown for campaigns |

**Critical for Mailing:** Campaigns are the core email sending functionality.

#### 8.11 Automation 2.0 (Marketing Automation)

**Prefix:** `/automation`

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/automation` | Automation list |
| GET | `/automation/listing` | AJAX automation listing |
| GET/POST | `/automation/wizard` | Create automation wizard |
| GET | `/automation/wizard/trigger` | Trigger selection |
| GET/POST | `/automation/wizard/trigger/option` | Trigger options |
| GET | `/automation/wizard/trigger/option/field-select` | Field select for trigger |
| GET | `/automation/{uid}/edit` | Visual automation editor |
| POST | `/automation/disable` | Disable automations |
| POST | `/automation/enable` | Enable automations |
| DELETE | `/automation/delete` | Delete automations |
| GET/POST | `/automation/{uid}/copy` | Copy automation |

**Automation Settings:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/automation/{uid}/settings` | Automation settings |
| POST | `/automation/{uid}/update` | Update automation settings |
| POST | `/automation/{uid}/data/save` | Save automation data (canvas) |
| GET | `/automation/{uid}/last-saved` | Get last saved time |

**Trigger & Actions:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/automation/{uid}/trigger/select` | Trigger selection popup |
| POST | `/automation/{uid}/trigger/select` | Submit trigger selection |
| GET | `/automation/{uid}/trigger/select/confirm` | Confirm trigger |
| GET/POST | `/automation/{uid}/trigger/edit` | Edit trigger |
| GET | `/automation/{uid}/action/select` | Action selection popup |
| POST | `/automation/{uid}/action/select` | Submit action selection |
| GET | `/automation/{uid}/action/select/confirm` | Confirm action |
| GET/POST | `/automation/{uid}/action/edit` | Edit action |
| GET | `/automation/{uid}/operation/select` | Operation selection |
| GET/POST | `/automation/{uid}/operation/create` | Create operation |
| GET/POST | `/automation/{uid}/operation/edit` | Edit operation |
| GET | `/automation/{uid}/operation/show` | Show operation |

**Conditions & Wait:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/automation/{uid}/condition/setting` | Condition settings |
| GET | `/automation/{uid}/condition/remove` | Remove condition |
| GET/POST | `/automation/condition/wait/custom` | Custom wait time |
| GET/POST | `/automation/{uid}/wait-time` | Set wait time |
| GET | `/automation/segment-select` | Segment selection |

**Email Elements:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/automation/{uid}/email/{email_uid?}` | Email settings |
| GET/POST | `/automation/{uid}/email/setup` | Email setup wizard |
| GET/POST | `/automation/{uid}/email/{email_uid}/confirm` | Confirm email |
| GET/POST | `/automation/{uid}/email/{email_uid}/template` | Email template |
| POST | `/automation/{uid}/email/{email_uid}/delete` | Delete email |

**Email Templates:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/automation/{uid}/template/{email_uid}/create` | Create template |
| GET/POST | `/automation/{uid}/template/{email_uid}/edit` | Edit template |
| GET | `/automation/{uid}/template/{email_uid}/content` | Get template content |
| GET/POST | `/automation/{uid}/template/{email_uid}/upload` | Upload template |
| GET/POST | `/automation/{uid}/template/{email_uid}/layout` | Choose layout |
| GET | `/automation/{uid}/template/{email_uid}/layout/list` | Layout list |
| POST | `/automation/{uid}/template/{email_uid}/remove-template` | Remove template |
| GET | `/automation/{uid}/template/{email_uid}/builder-select` | Builder selection |
| GET/POST | `/automation/{uid}/template/{email_uid}/edit-classic` | Classic editor |
| GET/POST | `/automation/{uid}/template/{email_uid}/plain-edit` | Plain text editor |
| GET | `/automation/{uid}/template/{email_uid}/preview` | Preview template |
| GET | `/automation/{uid}/template/{email_uid}/preview/content` | Preview content |
| GET/POST | `/automation/{email_uid}/send-test-email` | Send test email |

**Email Preheader:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/automation/{uid}/template/{email_uid}/preheader` | Preheader settings |
| GET/POST | `/automation/{uid}/template/{email_uid}/preheader/add` | Add preheader |
| POST | `/automation/{uid}/template/{email_uid}/preheader/remove` | Remove preheader |

**Email Attachments:**
| Method | URI | Description |
|--------|-----|-------------|
| POST | `/automation/{uid}/template/{email_uid}/attachment` | Upload attachment |
| GET | `/automation/{uid}/template/{email_uid}/attachment/{attachment_uid}` | Download attachment |
| POST | `/automation/{uid}/template/{email_uid}/attachment/{attachment_uid}/remove` | Remove attachment |

**Email Webhooks:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/automation/emails/{email_uid}/webhooks` | Webhook list |
| GET | `/automation/emails/{email_uid}/webhooks/list` | AJAX webhook listing |
| GET/POST | `/automation/emails/{email_uid}/webhooks/add` | Add webhook |
| GET | `/automation/emails/{email_uid}/webhooks/link-select` | Link selection |
| GET/POST | `/automation/emails/webhooks/{webhook_uid}/edit` | Edit webhook |
| POST | `/automation/emails/webhooks/{webhook_uid}/delete` | Delete webhook |
| GET/POST | `/automation/emails/webhooks/{webhook_uid}/test` | Test webhook |
| GET | `/automation/emails/webhooks/{webhook_uid}/sample/request` | Sample request |

**Contacts & Timeline:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/automation/{uid}/contacts` | Contact list |
| GET/POST | `/automation/{uid}/contacts/list` | AJAX contact listing |
| GET | `/automation/{uid}/contact/{contact_uid}/profile` | Contact profile |
| POST | `/automation/{uid}/contact/{contact_uid}/remove` | Remove contact |
| GET | `/automation/{uid}/contact/{contact_uid}/retry` | Retry contact |
| GET/POST | `/automation/{uid}/contact/{contact_uid}/tag` | Tag contact |
| GET/POST | `/automation/{uid}/contacts/tag` | Bulk tag contacts |
| POST | `/automation/{uid}/contacts/{contact_uid}/tag/remove` | Remove tag |
| GET | `/automation/{uid}/timeline` | Timeline view |
| GET | `/automation/{uid}/timeline/list` | AJAX timeline listing |
| POST | `/automation/{uid}/contacts/export` | Export contacts |
| GET/POST | `/automation/{uid}/contacts/copy-to-new-list` | Copy to new list |

**Subscribers:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/automation/{uid}/subscribers` | Subscriber list |
| GET | `/automation/{uid}/subscribers/list` | AJAX subscriber listing |
| GET | `/automation/{uid}/subscribers/{subscriber_uid}/show` | Show subscriber |
| POST | `/automation/{uid}/subscribers/{subscriber_uid}/remove` | Remove subscriber |
| POST | `/automation/{uid}/subscribers/{subscriber_uid}/restart` | Restart subscriber |

**Insight & Analytics:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/automation/{uid}/insight` | Automation analytics |

**Cart Abandonment (E-commerce):**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/automation/{uid}/cart/stats` | Cart abandonment stats |
| GET | `/automation/{uid}/cart/list` | Cart list |
| GET | `/automation/{uid}/cart/items` | Cart items |
| GET/POST | `/automation/{uid}/car/change-store` | Change store |
| GET/POST | `/automation/{uid}/car/wait` | Cart wait time |
| GET/POST | `/automation/{uid}/car/change-list` | Change list |

**Execution & Debug:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/automation/{automation}/run` | Run automation |
| GET | `/automation/{automation}/{subscriber}/trigger` | Trigger for subscriber |
| POST | `/automation/{uid}/trigger-all` | Trigger for all subscribers |
| GET | `/automation/{uid}/debug` | Debug automation |
| GET | `/trigger/{id}` | Show trigger |
| GET | `/trigger/{id}/check` | Check trigger |

**Critical for Mailing:** Automation enables sophisticated drip campaigns and behavioral email sequences.

#### 8.12 Sending Servers

**Resource Route:** `/sending_servers` (SendingServerController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/sending_servers` | Sending server list |
| GET | `/sending_servers/listing/{page?}` | AJAX listing |
| GET | `/sending_servers/create/{type}` | Create sending server |
| POST | `/sending_servers/create/{type}` | Store sending server |
| GET | `/sending_servers/{id}/edit/{type}` | Edit sending server |
| PATCH | `/sending_servers/{id}/update/{type}` | Update sending server |
| GET | `/sending_servers/delete` | Delete sending servers |
| GET | `/sending_servers/disable` | Disable servers |
| GET | `/sending_servers/enable` | Enable servers |
| GET | `/sending_servers/sort` | Sort servers |
| GET | `/sending_servers/select` | Select server dialog |

**Testing & Configuration:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/sending_servers/{uid}/test` | Test sending server |
| POST | `/sending_servers/{uid}/test-connection` | Test connection |
| POST | `/sending_servers/{uid}/config` | Update configuration |
| GET | `/sending_servers/aws-region-host` | Get AWS region host |

**Sending Limit:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/sending_servers/sending-limit` | Sending limit settings |

**Domains:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/sending_servers/{uid}/add-domain` | Add domain |
| POST | `/sending_servers/{uid}/remove-domain/{domain}` | Remove domain |

**Senders:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/sending_servers/{uid}/senders/dropbox` | Sender dropbox |

**Server Types:** SMTP, SendGrid, Amazon SES, Mailgun, SparkPost, ElasticEmail, SendinBlue, etc.

**Critical for Mailing:** Sending servers are required for all outbound email delivery.

#### 8.13 Sending Domains

**Resource Route:** `/sending_domains` (SendingDomainController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/sending_domains` | Domain list |
| GET | `/sending_domains/listing/{page?}` | AJAX listing |
| GET/POST | `/sending_domains/create` | Create domain |
| GET | `/sending_domains/{id}/edit` | Edit domain |
| PATCH | `/sending_domains/{id}/update` | Update domain |
| GET | `/sending_domains/delete` | Delete domains |
| GET | `/sending_domains/sort` | Sort domains |

**Verification:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/sending_domains/{id}/records` | DNS records |
| POST | `/sending_domains/{id}/verify` | Verify domain |
| POST | `/sending_domains/{id}/updateDkimSelector` | Update DKIM selector |

**Critical for Mailing:** Verified domains improve deliverability and enable DKIM signing.

#### 8.14 Tracking Domains

**Resource Route:** `/tracking_domains` (TrackingDomainController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/tracking_domains` | Tracking domain list |
| GET | `/tracking_domains/listing/{page?}` | AJAX listing |
| GET/POST | `/tracking_domains/create` | Create tracking domain |
| GET | `/tracking_domains/{uid}/edit` | Edit tracking domain |
| PATCH | `/tracking_domains/{uid}/update` | Update tracking domain |
| GET | `/tracking_domains/delete` | Delete tracking domains |

**Verification:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/tracking_domains/{uid}/verify` | Verify tracking domain |
| GET | `/tracking_domains/{uid}/cname` | Show CNAME record |
| GET | `/tracking_domains/{uid}/verify/cname` | Verify CNAME |

**Critical for Mailing:** Custom tracking domains improve email deliverability and branding.

#### 8.15 Email Verification Servers

**Resource Route:** `/email_verification_servers` (EmailVerificationServerController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/email_verification_servers` | Verification server list |
| GET | `/email_verification_servers/listing/{page?}` | AJAX listing |
| GET/POST | `/email_verification_servers/create` | Create server |
| GET | `/email_verification_servers/{id}/edit` | Edit server |
| PATCH | `/email_verification_servers/{id}/update` | Update server |
| GET | `/email_verification_servers/delete` | Delete servers |
| GET | `/email_verification_servers/disable` | Disable servers |
| GET | `/email_verification_servers/enable` | Enable servers |
| GET | `/email_verification_servers/sort` | Sort servers |
| GET | `/email_verification_servers/options` | Server options |

**Critical for Mailing:** Email verification reduces bounces and improves sender reputation.

#### 8.16 Blacklists

**Resource Route:** `/blacklists` (BlacklistController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/blacklists` | Blacklist view |
| GET | `/blacklists/listing/{page?}` | AJAX blacklist listing |
| GET/POST | `/blacklists/create` | Add blacklist entry |
| GET | `/blacklists/{id}/edit` | Edit blacklist entry |
| PATCH | `/blacklists/{id}/update` | Update blacklist entry |
| GET | `/blacklists/delete` | Delete blacklist entries |

**Import:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/blacklists/import` | Import blacklist |
| POST | `/blacklists/import/start` | Start import job |
| GET | `/blacklists/import/{job_uid}/progress` | Import progress |
| POST | `/blacklists/import/{job_uid}/cancel` | Cancel import |

**Critical for Mailing:** Blacklists prevent sending to spam traps and complaint addresses.

#### 8.17 Senders (From Email Addresses)

**Resource Route:** `/senders` (SenderController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/senders` | Sender list |
| GET | `/senders/listing/{page?}` | AJAX listing |
| GET/POST | `/senders/create` | Create sender |
| GET | `/senders/{id}/edit` | Edit sender |
| PATCH | `/senders/{id}/update` | Update sender |
| GET | `/senders/delete` | Delete senders |
| GET | `/senders/sort` | Sort senders |
| GET | `/senders/dropbox` | Sender dropbox |

**Verification (Public):**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/senders/verify/{token}` | Verify sender email |
| GET | `/senders/verify/{uid}/result` | Verification result |

**Critical for Mailing:** Verified senders are required to send campaigns.

#### 8.18 Notifications

**Resource Route:** `/notifications` (NotificationController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/notifications` | Notification center |
| POST | `/notifications/{id}/hide` | Hide notification |

#### 8.19 Products (E-commerce Integration)

**Resource Route:** `/products` (ProductController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/products` | Product list |
| GET | `/products/listing` | AJAX product listing |
| GET/POST | `/products/create` | Create product |
| GET | `/products/{id}/edit` | Edit product |
| PATCH | `/products/{id}/update` | Update product |
| DELETE | `/products/{id}` | Delete product |

**Widget (Public):**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/products/widget/products/list` | Widget product list |
| POST | `/products/widget/product` | Widget product details |
| GET | `/products/widget/products/options` | Widget product options |

#### 8.20 Sources (E-commerce Sync)

**Resource Route:** `/sources` (SourceController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/sources` | Source list |
| GET | `/sources/listing` | AJAX source listing |
| GET | `/sources/connect` | Connect source |
| GET/POST | `/sources/woo-connect` | Connect WooCommerce |
| GET | `/sources/{uid}/detail` | Source details |
| POST | `/sources/{uid}/sync` | Sync source data |
| POST | `/sources/delete` | Delete sources |

#### 8.21 Site (E-commerce Store Builder)

**Prefix:** `/site`

**Products:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/site/products` | Product list |
| GET | `/site/products-2` | Product list (alt view) |
| GET | `/site/products/listing` | AJAX product listing |
| GET/POST | `/site/products/add` | Add product |
| GET/POST | `/site/products/{id}/edit` | Edit product |
| POST | `/site/products/{id}/delete` | Delete product |
| POST | `/site/products/{id}/activate` | Activate product |

**Templates:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/site/templates` | Template gallery |
| GET | `/site/templates/listing` | AJAX template listing |
| POST | `/site/templates/{id}/activate` | Activate template |

**Settings:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/site/settings/home-page` | Home page settings |
| GET | `/site/settings/shop` | Shop settings |
| GET | `/site/settings/slider` | Slider settings |
| GET | `/site/settings/products` | Product settings |
| GET | `/site/settings/shipping` | Shipping settings |
| GET | `/site/settings/payments` | Payment settings |
| GET | `/site/settings/account-privacy` | Account/privacy settings |
| GET | `/site/settings/emails` | Email settings |
| GET | `/site/settings/account` | Account settings |
| GET | `/site/settings/site` | Site settings |

**Orders & Customers:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/site/orders` | Order list |
| GET | `/site/customers` | Customer list |

**Categories & Menus:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/site/menus` | Menu management |
| GET | `/site/categories` | Category management |

**Sources:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/site/sources` | Source list |
| GET | `/site/sources/listing` | AJAX source listing |
| GET | `/site/sources/connect` | Connect source |
| GET/POST | `/site/sources/woo-connect` | Connect WooCommerce |
| GET | `/site/sources/{uid}/detail` | Source details |
| POST | `/site/sources/{uid}/sync` | Sync source |
| POST | `/site/sources/delete` | Delete sources |

#### 8.22 Store (Internal Store Module)

**Products:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/store/products` | Product list |
| GET | `/store/products/list` | AJAX product listing |
| GET/POST | `/store/products/search` | Search products |
| GET | `/store/products/attributes` | Product attributes |
| GET/POST | `/store/products/create` | Create product |
| GET | `/store/products/{id}/edit` | Edit product |
| PATCH | `/store/products/{id}/update` | Update product |
| DELETE | `/store/products/delete` | Delete product |
| DELETE | `/store/products/delete-selected` | Bulk delete |
| PATCH | `/store/products` | Update status |
| PUT | `/store/products` | Multitask operation |

**Orders:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/store/orders` | Order list |
| GET | `/store/orders/list` | AJAX order listing |
| GET/POST | `/store/orders/create` | Create order |
| GET | `/store/orders/{id}/edit` | Edit order |

**Attributes:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/store/attributes` | Attribute list |
| GET | `/store/attributes/list` | AJAX attribute listing |
| GET/POST | `/store/attributes/create` | Create attribute |
| GET | `/store/attributes/{id}/edit` | Edit attribute |
| DELETE | `/store/attributes/delete-selected` | Bulk delete |
| PUT | `/store/attributes/chagse-status` | Change status |
| PATCH | `/store/attributes` | Update status |
| PUT | `/store/attributes` | Multitask operation |

**Categories:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/store/categories` | Category list |
| GET | `/store/categories/list` | AJAX category listing |
| GET | `/store/categories/collection` | Category collection |
| GET | `/store/categories/get-attributes` | Get category attributes |
| GET/POST | `/store/categories/create` | Create category |
| GET | `/store/categories/{id}/edit` | Edit category |
| DELETE | `/store/categories/delete-selected` | Bulk delete |
| PUT | `/store/categories/chagse-status` | Change status |
| PATCH | `/store/categories` | Update status |
| PUT | `/store/categories` | Multitask operation |

**Funnel:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/funnel` | Funnel list |
| GET | `/funnel/list` | AJAX funnel listing |
| GET/POST | `/funnel/search` | Search funnels |
| POST | `/funnel/getmessage` | Get funnel message |
| GET/POST | `/funnel/create` | Create funnel |
| GET | `/funnel/{id}/edit` | Edit funnel |
| DELETE | `/funnel/delete-selected` | Bulk delete |
| PATCH | `/funnel` | Update status |
| PUT | `/funnel` | Multitask operation |

**Media:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/store/media` | Media library |
| GET/POST | `/store/media/create` | Upload media |

#### 8.23 Account Management

**Profile:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/account/profile` | Edit profile |
| GET/POST | `/account/contact` | Update contact info |

**API:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/account/api` | API token management |
| GET | `/account/renew` | Renew API token |
| GET | `/frontend/docs/api/v1` | API documentation |

**Billing:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/account/billing` | Billing information |
| GET/POST | `/account/billing/edit` | Edit billing address |

**Payment Methods:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/account/payment/edit` | Edit payment method |
| POST | `/account/payment/remove` | Remove payment method |

**Activity & Logs:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/account/activity` | Activity log |
| GET | `/account/logs` | System logs |
| GET | `/account/logs/listing` | AJAX log listing |
| GET | `/account/quota_log` | Quota usage log |
| GET | `/account/quota_log_2` | Quota usage log (v2) |

**UI Preferences:**
| Method | URI | Description |
|--------|-----|-------------|
| POST | `/account/change-theme-mode` | Toggle light/dark mode |
| GET | `/account/save-auto-theme-mode` | Save auto theme preference |
| GET | `/account/leftbar/state` | Leftbar state (collapsed/expanded) |
| GET/POST | `/account/wizard/menu-layout` | Setup wizard: menu layout |
| GET/POST | `/account/wizard/color-scheme` | Setup wizard: color scheme |

#### 8.24 Subscription Management (Customer)

**Middleware:** `['not_installed', 'auth', 'frontend', 'subscription']`
**Purpose:** Plan subscription, upgrades, and billing

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/account/subscription` | Subscription dashboard |
| GET | `/account/subscription/select-plan` | Select plan page |
| POST | `/account/subscription/select-plan` | Submit plan selection |
| POST | `/account/subscription/assign-plan` | Assign plan (admin action) |
| GET/POST | `/account/subscription/change-plan` | Change current plan |
| GET/POST | `/account/subscription/{invoice_uid}/billing-information` | Billing info for invoice |
| POST | `/account/subscription/checkout/{invoice_uid}` | Checkout invoice |
| GET | `/account/subscription/payment/{invoice_uid}` | Payment page |
| POST | `/account/subscription/verify-transaction/{invoice_uid}` | Verify pending transaction |
| GET | `/account/subscription/order-box/{invoice_uid}` | Order summary box |
| POST | `/account/subscription/invoice/{invoice_uid}/cancel` | Cancel invoice |
| POST | `/account/subscription/cancel-now` | Cancel subscription immediately |
| POST | `/account/subscription/recurring/enable` | Enable auto-renewal |
| POST | `/account/subscription/recurring/disable` | Disable auto-renewal |

**Critical for Mailing:** Subscription status controls access to mailing features.

#### 8.25 Customer Switching (Multi-Account)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/customers/login-back` | Return to original customer account |
| GET | `/customers/admin-area` | Access admin area |

#### 8.26 Invoices

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/invoices/{uid}/download` | Download invoice PDF |

#### 8.27 Chat (AI Assistant)

| Method | URI | Description |
|--------|-----|-------------|
| POST | `/chat` | Send chat message to AI |

#### 8.28 Search

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/search/general` | General search |
| GET | `/search/campaigns` | Search campaigns |
| GET | `/search/lists` | Search lists |
| GET | `/search/automations` | Search automations |
| GET | `/search/templates` | Search templates |
| GET | `/search/subscribers` | Search subscribers |
| GET | `/search/forms` | Search forms |
| GET | `/search/websites` | Search websites |

#### 8.29 Plans (Public View)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/plans/list/{style?}` | Public plan listing page |
| GET | `/plans/select2` | Plan select dropdown |

---

### 9. Admin Routes (Backend)

**Namespace:** `Admin`
**Middleware:** `['not_installed', 'auth', 'backend']`
**Prefix:** `/admin`

#### 9.1 Admin Dashboard

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin` | Admin dashboard |
| GET | `/admin/docs/api/v1` | API documentation |

#### 9.2 Admin Search

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/search/general` | General search |
| GET | `/admin/search/customers` | Search customers |
| GET | `/admin/search/templates` | Search templates |
| GET | `/admin/search/plans` | Search plans |
| GET | `/admin/search/sending-servers` | Search sending servers |

#### 9.3 Admin Notifications

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/notifications` | Notification center |
| GET | `/admin/notifications/listing` | AJAX notification listing |
| GET | `/admin/notifications/popup` | Notification popup |
| POST | `/admin/notifications/delete` | Delete notifications |

#### 9.4 User Management

**Resource Route:** `/admin/users` (UserController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/users` | User list |
| GET | `/admin/users/listing/{page?}` | AJAX user listing |
| GET/POST | `/admin/users/create` | Create user |
| GET | `/admin/users/{id}/edit` | Edit user |
| PATCH | `/admin/users/{id}/update` | Update user |
| GET | `/admin/users/delete` | Delete users |
| GET | `/admin/users/sort` | Sort users |
| GET | `/admin/users/{uid}/switch` | Switch to user account |

#### 9.5 Form Templates (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/templates/forms` | Form template list |
| GET | `/admin/templates/forms/list` | AJAX form template listing |
| GET | `/admin/templates/forms/{uid}/preview` | Preview form template |

#### 9.6 Email Templates (Admin)

**Resource Route:** `/admin/templates` (TemplateController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/templates` | Template gallery |
| GET | `/admin/templates/listing/{page?}` | AJAX template listing |
| GET/POST | `/admin/templates/create` | Create template |
| GET | `/admin/templates/{uid}/edit` | Edit template |
| PATCH | `/admin/templates/{uid}/update` | Update template |
| GET | `/admin/templates/delete` | Delete templates |
| GET | `/admin/templates/{uid}/preview` | Preview template |
| GET/POST | `/admin/templates/{uid}/copy` | Copy template |
| GET | `/admin/templates/upload` | Upload template |
| POST | `/admin/templates/upload` | Process template upload |

**Builder:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/templates/builder/create` | Create in builder |
| GET | `/admin/templates/builder/templates/{category_uid}` | Builder template gallery |
| GET | `/admin/templates/{uid}/builder/edit` | Open builder |
| POST | `/admin/templates/{uid}/builder/edit` | Save builder |
| GET | `/admin/templates/{uid}/builder/edit/content` | Get content |
| POST | `/admin/templates/{uid}/builder/edit/asset` | Upload asset |
| GET | `/admin/templates/{uid}/builder/change-template/{change_uid}` | Change template |

**Template Management:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/templates/{uid}/change-name` | Change name |
| GET/POST | `/admin/templates/{uid}/categories` | Manage categories |
| GET/POST | `/admin/templates/{uid}/update-thumb` | Upload thumbnail |
| GET/POST | `/admin/templates/{uid}/update-thumb-url` | Set thumbnail URL |
| POST | `/admin/templates/{uid}/export` | Export template |
| GET | `/admin/templates/chat` | AI template assistant |

#### 9.7 Layouts (Admin)

**Resource Route:** `/admin/layouts` (LayoutController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/layouts` | Layout list |
| GET | `/admin/layouts/listing/{page?}` | AJAX layout listing |
| GET/POST | `/admin/layouts/create` | Create layout |
| GET | `/admin/layouts/{id}/edit` | Edit layout |
| PATCH | `/admin/layouts/{id}/update` | Update layout |
| GET | `/admin/layouts/sort` | Sort layouts |

#### 9.8 Sending Servers (Admin)

**Resource Route:** `/admin/sending_servers` (SendingServerController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/sending_servers` | Server list |
| GET | `/admin/sending_servers/listing/{page?}` | AJAX server listing |
| GET | `/admin/sending_servers/create/{type}` | Create server |
| POST | `/admin/sending_servers/create/{type}` | Store server |
| GET | `/admin/sending_servers/{id}/edit/{type}` | Edit server |
| PATCH | `/admin/sending_servers/{id}/update/{type}` | Update server |
| GET | `/admin/sending_servers/delete` | Delete servers |
| GET | `/admin/sending_servers/disable` | Disable servers |
| GET | `/admin/sending_servers/enable` | Enable servers |
| GET | `/admin/sending_servers/sort` | Sort servers |
| GET | `/admin/sending_servers/select` | Select server |
| GET | `/admin/sending_servers/select2` | Select2 dropdown |

**Testing & Configuration:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/sending_servers/{uid}/test` | Test server |
| POST | `/admin/sending_servers/{uid}/test-connection` | Test connection |
| POST | `/admin/sending_servers/{uid}/config` | Update config |
| GET | `/admin/sending_servers/aws-region-host` | AWS region host |
| GET/POST | `/admin/sending_servers/sending-limit` | Sending limit |

**Domains:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/sending_servers/{uid}/add-domain` | Add domain |
| POST | `/admin/sending_servers/{uid}/remove-domain/{domain}` | Remove domain |

**Senders:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/sending_servers/{uid}/senders/dropbox` | Sender dropbox |

#### 9.9 Bounce Handlers (Admin)

**Resource Route:** `/admin/bounce_handlers` (BounceHandlerController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/bounce_handlers` | Bounce handler list |
| GET | `/admin/bounce_handlers/listing/{page?}` | AJAX listing |
| GET/POST | `/admin/bounce_handlers/create` | Create bounce handler |
| GET | `/admin/bounce_handlers/{id}/edit` | Edit bounce handler |
| PATCH | `/admin/bounce_handlers/{id}/update` | Update bounce handler |
| GET | `/admin/bounce_handlers/delete` | Delete bounce handlers |
| GET | `/admin/bounce_handlers/sort` | Sort handlers |
| POST | `/admin/bounce_handlers/{uid}/test` | Test bounce handler |
| GET | `/admin/bounce_handlers/{uid}/run` | Run bounce handler manually |

#### 9.10 Feedback Loop Handlers (Admin)

**Resource Route:** `/admin/feedback_loop_handlers` (FeedbackLoopHandlerController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/feedback_loop_handlers` | FBL handler list |
| GET | `/admin/feedback_loop_handlers/listing/{page?}` | AJAX listing |
| GET/POST | `/admin/feedback_loop_handlers/create` | Create FBL handler |
| GET | `/admin/feedback_loop_handlers/{id}/edit` | Edit FBL handler |
| PATCH | `/admin/feedback_loop_handlers/{id}/update` | Update FBL handler |
| GET | `/admin/feedback_loop_handlers/delete` | Delete FBL handlers |
| GET | `/admin/feedback_loop_handlers/sort` | Sort handlers |
| POST | `/admin/feedback_loop_handlers/{uid}/test` | Test FBL handler |

#### 9.11 Languages (Admin)

**Resource Route:** `/admin/languages` (LanguageController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/languages` | Language list |
| GET | `/admin/languages/listing/{page?}` | AJAX language listing |
| GET/POST | `/admin/languages/create` | Create language |
| GET | `/admin/languages/{id}/edit` | Edit language |
| PATCH | `/admin/languages/{id}/update` | Update language |
| GET | `/admin/languages/delete` | Delete languages |
| GET | `/admin/languages/delete/confirm` | Delete confirmation |
| GET | `/admin/languages/disable` | Disable languages |
| GET | `/admin/languages/enable` | Enable languages |

**Translation:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/languages/{id}/translate/intro` | Translation intro |
| GET/POST | `/admin/languages/{id}/translate` | Translate language strings |
| GET | `/admin/languages/{id}/download` | Download language file |
| GET/POST | `/admin/languages/{id}/upload` | Upload language file |

#### 9.12 Settings (Admin)

**Prefix:** `/admin/settings`

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/settings/{tab?}` | Settings page (general) |
| GET/POST | `/admin/settings/general` | General settings |
| GET/POST | `/admin/settings/sending` | Sending settings |
| GET | `/admin/settings/urls` | URL settings |
| GET/POST | `/admin/settings/cronjob` | Cron job configuration |
| GET/POST | `/admin/settings/mailer` | System mailer config |
| GET/POST | `/admin/settings/mailer/test` | Test system mailer |
| GET/POST | `/admin/settings/license` | License management |
| POST | `/admin/settings/license/remove` | Remove license |
| GET | `/admin/settings/logs` | View system logs |
| GET | `/log` | Download log file |

**Payment:**
| Method | URI | Description |
|--------|-----|-------------|
| POST | `/admin/settings/payment` | Update payment settings |

**Advanced:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/settings/advanced` | Advanced settings |
| POST | `/admin/settings/advanced/{name}/update` | Update advanced setting |

**Upgrade:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/settings/upgrade` | Upgrade page |
| GET | `/u` | Shortcut to upgrade page |
| GET/POST | `/q` | Quick upgrade from URL |
| POST | `/admin/settings/upgrade/upload` | Upload upgrade patch |
| POST | `/admin/settings/upgrade` | Process upgrade |
| POST | `/admin/settings/upgrade/cancel` | Cancel upgrade |

**URL Update:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/update-urls` | Update all URLs in database |

#### 9.13 Tracking Logs (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/tracking_log` | Tracking log viewer |
| GET | `/admin/tracking_log/listing` | AJAX tracking listing |

#### 9.14 Bounce Log (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/bounce_log` | Bounce log viewer |
| GET | `/admin/bounce_log/listing` | AJAX bounce listing |

#### 9.15 Open Log (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/open_log` | Open log viewer |
| GET | `/admin/open_log/listing` | AJAX open listing |

#### 9.16 Click Log (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/click_log` | Click log viewer |
| GET | `/admin/click_log/listing` | AJAX click listing |

#### 9.17 Feedback Log (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/feedback_log` | Feedback log viewer |
| GET | `/admin/feedback_log/listing` | AJAX feedback listing |

#### 9.18 Unsubscribe Log (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/unsubscribe_log` | Unsubscribe log viewer |
| GET | `/admin/unsubscribe_log/listing` | AJAX unsubscribe listing |

#### 9.19 Blacklist (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/blacklist` | Blacklist viewer |
| GET | `/admin/blacklist/listing` | AJAX blacklist listing |
| GET | `/admin/blacklist/delete` | Delete blacklist entries |
| GET | `/admin/blacklists/{id}/reason` | View blacklist reason |

**Import:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/blacklists/import` | Import blacklist |
| POST | `/admin/blacklists/import/start` | Start import |
| GET | `/admin/blacklists/import/{job_uid}/progress` | Import progress |
| POST | `/admin/blacklists/import/{job_uid}/cancel` | Cancel import |

#### 9.20 Customers (Admin)

**Resource Route:** `/admin/customers` (CustomerController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/customers` | Customer list |
| GET | `/admin/customers/listing/{page?}` | AJAX customer listing |
| GET/POST | `/admin/customers/create` | Create customer |
| GET | `/admin/customers/{id}/edit` | Edit customer |
| PATCH | `/admin/customers/{id}/update` | Update customer |
| GET | `/admin/customers/delete` | Delete customers |
| GET | `/admin/customers/disable` | Disable customers |
| GET | `/admin/customers/enable` | Enable customers |
| GET | `/admin/customers/sort` | Sort customers |
| GET | `/admin/customers/select2` | Select2 dropdown |

**Customer Actions:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/customers/login-as/{uid}` | Login as customer |
| GET | `/admin/customers/{uid}/one-click-login` | One-click login link |
| GET | `/admin/customers/{id}/contact` | View contact info |
| POST | `/admin/customers/{uid}/contact` | Update contact info |
| GET/POST | `/admin/customers/{uid}/assign-plan` | Assign plan to customer |
| GET | `/admin/customers/{id}/subscriptions` | View subscriptions |
| GET | `/admin/customers/{uid}/su-account` | Sub-account management |
| GET | `/admin/customers/growthChart` | Customer growth chart |

#### 9.21 Admin Groups (Admin)

**Resource Route:** `/admin/admin_groups` (AdminGroupController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/admin_groups` | Admin group list |
| GET | `/admin/admin_groups/listing/{page?}` | AJAX group listing |
| GET/POST | `/admin/admin_groups/create` | Create admin group |
| GET | `/admin/admin_groups/{id}/edit` | Edit admin group |
| PATCH | `/admin/admin_groups/{id}/update` | Update admin group |
| GET | `/admin/admin_groups/delete` | Delete admin groups |
| GET | `/admin/admin_groups/sort` | Sort groups |

#### 9.22 Admins (Admin)

**Resource Route:** `/admin/admins` (AdminController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/admins` | Admin user list |
| GET | `/admin/admins/listing/{page?}` | AJAX admin listing |
| GET/POST | `/admin/admins/create` | Create admin user |
| GET | `/admin/admins/{id}/edit` | Edit admin user |
| PATCH | `/admin/admins/{id}/update` | Update admin user |
| GET | `/admin/admins/delete` | Delete admin users |
| GET | `/admin/admins/disable` | Disable admin users |
| GET | `/admin/admins/enable` | Enable admin users |
| GET | `/admin/admins/sort` | Sort admins |
| GET | `/admin/admins/login-as/{uid}` | Login as admin |
| GET | `/admin/admins/{uid}/one-click-login` | One-click login link |
| GET | `/admin/admins/login-back` | Return to original admin |

#### 9.23 Admin Account (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/account/profile` | Edit admin profile |
| GET/POST | `/admin/account/contact` | Update admin contact |
| GET | `/admin/account/api` | API token management |
| GET | `/admin/account/api/renew` | Renew API token |
| POST | `/admin/account/change-theme-mode` | Toggle theme |
| GET | `/admin/account/save-auto-theme-mode` | Save auto theme |
| GET | `/admin/account/leftbar/state` | Leftbar state |

#### 9.24 Plans (Admin)

**Resource Route:** `/admin/plans` (PlanController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/plans` | Plan list |
| GET | `/admin/plans/listing/{page?}` | AJAX plan listing |
| GET/POST | `/admin/plans/create` | Create plan |
| GET | `/admin/plans/{id}/edit` | Edit plan (redirect to general) |
| PATCH | `/admin/plans/{id}/update` | Update plan |
| GET | `/admin/plans/delete` | Delete plans |
| GET | `/admin/plans/delete/confirm` | Delete confirmation |
| GET | `/admin/plans/disable` | Disable plans |
| POST | `/admin/plans/enable` | Enable plans |
| GET/POST | `/admin/plans/copy` | Copy plan |
| GET | `/admin/plans/sort` | Sort plans |
| GET | `/admin/plans/select2` | Select2 dropdown |
| GET | `/admin/plans/pieChart` | Plan distribution chart |

**Plan Wizard:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/plans/wizard` | Plan creation wizard |
| GET/POST | `/admin/plans/{uid}/wizard/sending-server` | Wizard: sending server |

**Plan Settings:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/plans/{uid}/general` | General settings |
| GET | `/admin/plans/{uid}/quota` | Quota settings |
| GET | `/admin/plans/{uid}/security` | Security settings |
| GET | `/admin/plans/{uid}/email-footer` | Email footer settings |
| GET | `/admin/plans/{uid}/payment` | Payment settings |
| GET | `/admin/plans/{uid}/billing-history` | Billing history |
| POST | `/admin/plans/{uid}/save` | Save plan settings |

**Billing Cycle:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/plans/{uid}/billing-cycle` | Billing cycle settings |

**Sending Limit:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/plans/{uid}/sending-limit` | Sending limit settings |

**Email Verification:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/plans/{uid}/email-verification` | Email verification settings |

**Sending Servers:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/plans/{uid}/sending-server` | Sending server assignment |
| GET/POST | `/admin/plans/{uid}/sending-server/option` | Sending server option |
| GET/POST | `/admin/plans/{uid}/sending-server/own` | Own sending server |
| GET/POST | `/admin/plans/{uid}/sending-server/subaccount` | Subaccount configuration |
| GET | `/admin/plans/{id}/sending_servers` | Sending server list |
| GET/POST | `/admin/plans/{id}/sending_servers/add` | Add sending server |
| POST | `/admin/plans/{id}/sending_servers/{sending_server_uid}/remove` | Remove sending server |
| POST | `/admin/plans/{id}/sending_servers/{sending_server_uid}/set-primary` | Set primary server |
| GET/POST | `/admin/plans/{id}/sending_servers/fitness` | Fitness algorithm settings |

**Terms of Service:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/plans/{uid}/tos` | Terms of service |

**Visibility:**
| Method | URI | Description |
|--------|-----|-------------|
| POST | `/admin/plans/{uid}/visible/on` | Make plan visible |
| POST | `/admin/plans/{uid}/visible/off` | Hide plan |

**Public View:**
| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/plans/public-view/{style?}` | Public plan page preview |

#### 9.25 Currencies (Admin)

**Resource Route:** `/admin/currencies` (CurrencyController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/currencies` | Currency list |
| GET | `/admin/currencies/listing/{page?}` | AJAX currency listing |
| GET/POST | `/admin/currencies/create` | Create currency |
| GET | `/admin/currencies/{id}/edit` | Edit currency |
| PATCH | `/admin/currencies/{id}/update` | Update currency |
| GET | `/admin/currencies/delete` | Delete currencies |
| GET | `/admin/currencies/disable` | Disable currencies |
| GET | `/admin/currencies/enable` | Enable currencies |
| GET | `/admin/currencies/sort` | Sort currencies |
| GET | `/admin/currencies/select2` | Select2 dropdown |

#### 9.26 Subscriptions (Admin)

**Resource Route:** `/admin/subscriptions` (SubscriptionController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/subscriptions` | Subscription list |
| GET | `/admin/subscriptions/listing/{page?}` | AJAX subscription listing |
| GET/POST | `/admin/subscriptions/create` | Create subscription |
| GET | `/admin/subscriptions/{id}/edit` | Edit subscription |
| PATCH | `/admin/subscriptions/{id}/update` | Update subscription |
| DELETE | `/admin/subscriptions/delete` | Delete subscriptions |
| GET | `/admin/subscriptions/sort` | Sort subscriptions |

**Subscription Actions:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/subscriptions/{id}/invoices` | View invoices |
| POST | `/admin/subscriptions/{id}/approve` | Approve pending subscription |
| GET/POST | `/admin/subscriptions/{id}/reject-pending` | Reject pending subscription |
| POST | `/admin/subscriptions/{id}/terminate` | Terminate subscription |
| POST | `/admin/subscriptions/{id}/replenish-sending-credits` | Replenish sending credits |
| POST | `/admin/subscriptions/{id}/recurring/enable` | Enable recurring billing |
| POST | `/admin/subscriptions/{id}/recurring/disable` | Disable recurring billing |

#### 9.27 Email Verification Servers (Admin)

**Resource Route:** `/admin/email_verification_servers` (EmailVerificationServerController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/email_verification_servers` | Verification server list |
| GET | `/admin/email_verification_servers/listing/{page?}` | AJAX listing |
| GET/POST | `/admin/email_verification_servers/create` | Create server |
| GET | `/admin/email_verification_servers/{id}/edit` | Edit server |
| PATCH | `/admin/email_verification_servers/{id}/update` | Update server |
| GET | `/admin/email_verification_servers/delete` | Delete servers |
| GET | `/admin/email_verification_servers/disable` | Disable servers |
| GET | `/admin/email_verification_servers/enable` | Enable servers |
| GET | `/admin/email_verification_servers/sort` | Sort servers |
| GET | `/admin/email_verification_servers/options` | Server options |

#### 9.28 Sub Accounts (Admin)

**Resource Route:** `/admin/sub_accounts` (SubAccountController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/sub_accounts` | Sub-account list |
| GET | `/admin/sub_accounts/listing/{page?}` | AJAX listing |
| GET/POST | `/admin/sub_accounts/create` | Create sub-account |
| GET | `/admin/sub_accounts/{id}/edit` | Edit sub-account |
| PATCH | `/admin/sub_accounts/{id}/update` | Update sub-account |
| GET | `/admin/sub_accounts/{uid}/delete/confirm` | Delete confirmation |
| DELETE | `/admin/sub_accounts/{uid}/delete` | Delete sub-account |

#### 9.29 Payment Gateways (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/payment/gateways/index` | Payment gateway list |
| GET | `/admin/payment/gateways/edit/{name}` | Edit gateway settings |
| POST | `/admin/payment/gateways/update/{name}` | Update gateway |
| POST | `/admin/payment/gateways/{name}/disable` | Disable gateway |
| POST | `/admin/payment/gateways/{name}/enable` | Enable gateway |
| POST | `/admin/payment/gateways/{name}/set-primary` | Set primary gateway |

**Supported Gateways:** Stripe, PayPal, Braintree, Coinpayments, Razorpay, PayStack, etc.

#### 9.30 Plugins (Admin)

**Resource Route:** `/admin/plugins` (PluginController)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/plugins` | Plugin list |
| GET | `/admin/plugins/listing/{page?}` | AJAX plugin listing |
| GET/POST | `/admin/plugins/install` | Install plugin |
| GET | `/admin/plugins/{id}/edit` | Edit plugin settings |
| GET/POST | `/admin/plugins/{uid}/delete` | Delete plugin |
| GET | `/admin/plugins/disable` | Disable plugins |
| GET | `/admin/plugins/enable` | Enable plugins |
| GET | `/admin/plugins/sort` | Sort plugins |
| GET | `/admin/plugins/reindex` | Reindex plugins |

#### 9.31 GeoIP (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/geoip` | GeoIP dashboard |
| GET/POST | `/admin/geoip/setting` | GeoIP settings |
| POST | `/admin/geoip/reset` | Reset GeoIP database |

#### 9.32 Tax Management (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/tax/settings` | Tax settings |
| GET | `/tax/countries` | Country tax list |
| GET/POST | `/tax/add` | Add tax rule |
| POST | `/tax/remove/{code}` | Remove country tax |

#### 9.33 Invoices (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/invoices/template/edit` | Edit invoice template |
| GET | `/admin/invoices/{uid}/download` | Download invoice PDF |

#### 9.34 OAuth (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/auth` | OAuth settings |
| GET/POST | `/admin/auth/google-oauth` | Google OAuth config |
| GET/POST | `/admin/auth/facebook-oauth` | Facebook OAuth config |

#### 9.35 Chat (Admin AI Assistant)

| Method | URI | Description |
|--------|-----|-------------|
| POST | `/admin/chat` | Send chat message |

#### 9.36 Verification (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/admin/verify/index` | Verification center |

#### 9.37 Upgrade (Admin)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/upgrade/finalize` | Finalize upgrade |
| GET | `/admin/migrate/run` | Run migrations |

**Job Index Management:**
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/jobs/drop-index` | Drop jobs table index |
| GET | `/jobs/create-index` | Create jobs table index |

---

## API Routes Analysis

**Base URL:** `/api/v1`
**Authentication:** API token via `auth:api` middleware
**Namespace:** `Api`

### Authentication

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1` | Get authenticated user info |
| GET | `/api/v1/me` | Get authenticated user info (alias) |

### Lists

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1/lists` | List all mail lists |
| GET | `/api/v1/lists/{uid}` | Get list details |
| POST | `/api/v1/lists` | Create new list |
| PATCH | `/api/v1/lists/{uid}` | Update list |
| DELETE | `/api/v1/lists/{uid}` | Delete list |
| POST | `/api/v1/lists/{uid}/add-field` | Add custom field to list |

### Campaigns

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1/campaigns` | List all campaigns |
| GET | `/api/v1/campaigns/{uid}` | Get campaign details |
| POST | `/api/v1/campaigns` | Create campaign |
| PATCH | `/api/v1/campaigns/{uid}` | Update campaign |
| DELETE | `/api/v1/campaigns/{uid}` | Delete campaign |
| POST | `/api/v1/campaigns/{uid}/pause` | Pause campaign |
| POST | `/api/v1/campaigns/{uid}/run` | Start campaign |
| POST | `/api/v1/campaigns/{uid}/resume` | Resume paused campaign |

### Subscribers

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1/subscribers` | List all subscribers |
| GET | `/api/v1/subscribers/{uid}` | Get subscriber details |
| GET | `/api/v1/subscribers/email/{email}` | Get subscriber by email |
| POST | `/api/v1/subscribers` | Create subscriber |
| PATCH | `/api/v1/subscribers/{uid}` | Update subscriber |
| DELETE | `/api/v1/subscribers/{uid}` | Delete subscriber |
| PATCH | `/api/v1/lists/{list_uid}/subscribers/{uid}/subscribe` | Subscribe subscriber |
| PATCH | `/api/v1/lists/{list_uid}/subscribers/{uid}/unsubscribe` | Unsubscribe subscriber |
| PATCH | `/api/v1/lists/{list_uid}/subscribers/email/{email}/unsubscribe` | Unsubscribe by email |
| POST | `/api/v1/subscribers/{uid}/add-tag` | Add tag to subscriber |

### Automations

| Method | URI | Description |
|--------|-----|-------------|
| POST | `/api/v1/automations/{uid}/api/call` | Trigger automation via API |
| POST | `/api/v1/automations/{uid}/execute` | Execute automation for subscriber |

### Sending Servers

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1/sending_servers` | List sending servers |
| GET | `/api/v1/sending_servers/{uid}` | Get sending server details |
| POST | `/api/v1/sending_servers` | Create sending server |
| PATCH | `/api/v1/sending_servers/{uid}` | Update sending server |
| DELETE | `/api/v1/sending_servers/{uid}` | Delete sending server |

### Plans

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1/plans` | List plans |
| GET | `/api/v1/plans/{uid}` | Get plan details |
| POST | `/api/v1/plans` | Create plan |
| PATCH | `/api/v1/plans/{uid}` | Update plan |
| DELETE | `/api/v1/plans/{uid}` | Delete plan |

### Customers

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1/customers` | List customers |
| GET | `/api/v1/customers/{uid}` | Get customer details |
| POST | `/api/v1/customers` | Create customer |
| PATCH | `/api/v1/customers/{uid}` | Update customer |
| DELETE | `/api/v1/customers/{uid}` | Delete customer |
| PATCH | `/api/v1/customers/{uid}/disable` | Disable customer |
| PATCH | `/api/v1/customers/{uid}/enable` | Enable customer |
| POST | `/api/v1/customers/{uid}/assign-plan/{plan_uid}` | Assign plan to customer |
| POST | `/api/v1/customers/{uid}/change-plan/{plan_uid}` | Change customer plan |
| GET/POST | `/api/v1/login-token` | Generate login token |

### Subscriptions

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1/subscriptions` | List subscriptions |
| GET | `/api/v1/subscriptions/{uid}` | Get subscription details |
| POST | `/api/v1/subscriptions` | Create subscription |
| PATCH | `/api/v1/subscriptions/{uid}` | Update subscription |
| DELETE | `/api/v1/subscriptions/{uid}` | Delete subscription |

### File Upload

| Method | URI | Description |
|--------|-----|-------------|
| POST | `/api/v1/file/upload` | Upload file |

### Notifications

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1/notification` | List notifications |
| GET | `/api/v1/notification/{id}` | Get notification details |
| POST | `/api/v1/notification` | Create notification |
| PATCH | `/api/v1/notification/{id}` | Update notification |
| DELETE | `/api/v1/notification/{id}` | Delete notification |

**API Authentication Methods:**
1. API Token in Authorization header: `Authorization: Bearer {api_token}`
2. API Token in query string: `?api_token={api_token}`

---

## Console Routes

**File:** `console.php`

### Artisan Commands

| Command | Description |
|---------|-------------|
| `inspire` | Display an inspiring quote |

**Note:** Console routes in Acelle are minimal. Most cron jobs and scheduled tasks are registered in the application's Kernel or Console directory.

---

## Broadcast Channels

**File:** `channels.php`

### Broadcasting Channels

| Channel | Authorization | Description |
|---------|---------------|-------------|
| `App.Models.User.{id}` | User ID match | Private user channel for real-time updates |

**Broadcasting Driver:** Configured in `config/broadcasting.php` (typically Pusher, Redis, or Laravel Echo)

---

## Critical Mailing System Routes

### Core Campaign Flow

1. **Campaign Creation**
   - `/campaigns/select-type` - Choose campaign type (regular, autoresponder, etc.)
   - `/campaigns/create` - Create campaign record
   - `/campaigns/{uid}/setup` - Configure campaign (name, subject, from)

2. **List & Recipient Selection**
   - `/campaigns/{uid}/recipients` - Select target list and segment
   - `/lists/{list_uid}/segments` - Manage segments for targeting

3. **Template Design**
   - `/campaigns/{uid}/template/select` - Choose template
   - `/campaigns/{uid}/template/edit` - Visual email builder
   - `/campaigns/{uid}/template/builder-classic` - Classic editor
   - `/campaigns/{uid}/plain` - Plain text version

4. **Schedule & Send**
   - `/campaigns/{uid}/schedule` - Schedule delivery
   - `/campaigns/{uid}/confirm` - Final review
   - `/campaigns/{uid}/run` - Execute campaign

5. **Tracking & Analytics**
   - `/p/{message_id}/open` - Open tracking pixel (public)
   - `/p/{url}/click/{message_id?}` - Click tracking (public)
   - `/campaigns/{uid}/overview` - Campaign dashboard
   - `/campaigns/{uid}/tracking-log` - Full tracking log

### Core Automation Flow

1. **Automation Creation**
   - `/automation/wizard` - Create automation wizard
   - `/automation/wizard/trigger` - Select trigger
   - `/automation/{uid}/edit` - Visual automation editor

2. **Email Elements**
   - `/automation/{uid}/email/{email_uid}` - Configure email
   - `/automation/{uid}/template/{email_uid}/edit` - Design email
   - `/automation/{uid}/email/{email_uid}/confirm` - Confirm email

3. **Execution**
   - `/automation/{automation}/run` - Enable automation
   - `/automation/{automation}/{subscriber}/trigger` - Manual trigger
   - `/api/v1/automations/{uid}/execute` - API trigger

### Core List Management

1. **Subscriber Import**
   - `/lists/{list_uid}/subscribers/import2` - Import wizard
   - `/lists/{list_uid}/subscribers/import2/upload` - Upload CSV
   - `/lists/{list_uid}/subscribers/import2/mapping` - Map columns
   - `/lists/{list_uid}/subscribers/import2/run` - Execute import

2. **Email Verification**
   - `/lists/{uid}/verification` - Verification dashboard
   - `/lists/{uid}/verification/start` - Start verification
   - `/lists/{uid}/verification/{job_uid}/progress` - Monitor progress

3. **Subscriber Management**
   - `/lists/{list_uid}/subscribers` - Subscriber list
   - `/lists/{list_uid}/subscribers/create` - Add subscriber
   - `/lists/{list_uid}/subscribers/{uid}/edit` - Edit subscriber
   - `/subscribers/copy` - Copy subscribers between lists

### Core Deliverability

1. **Sending Servers**
   - `/sending_servers/create/{type}` - Add SMTP/API server
   - `/sending_servers/{uid}/test` - Test connection
   - `/sending_servers/{uid}/add-domain` - Add verified domain

2. **Domain Verification**
   - `/sending_domains/create` - Add domain
   - `/sending_domains/{id}/records` - DNS records
   - `/sending_domains/{id}/verify` - Verify SPF/DKIM
   - `/tracking_domains/{uid}/verify` - Verify tracking domain

3. **Bounce/Feedback Handling**
   - `/delivery/notify/{stype?}` - Webhook endpoint (public)
   - `/bounce_handlers/{uid}/run` - Process bounces
   - `/feedback_loop_handlers/{uid}/test` - Test FBL

### Public-Facing Routes (GDPR Compliance)

1. **Subscription Management**
   - `/lists/{list_uid}/sign-up` - Public signup form
   - `/lists/{list_uid}/subscribe-confirm/{uid}/{code}` - Double opt-in
   - `/lists/{list_uid}/unsubscribe/{uid}/{code}` - Unsubscribe form
   - `/c/{subscriber}/unsubscribe/{message_id?}` - Unsubscribe link

2. **Profile Management**
   - `/lists/{list_uid}/update-profile/{uid}/{code}` - Update profile
   - `/lists/{list_uid}/unsubscribe/{uid}/{code}` - Unsubscribe

3. **Email Web View**
   - `/campaigns/{message_id}/web-view` - Web version of email

---

## Middleware Configuration

### Middleware Groups

| Middleware | Purpose | Applied To |
|------------|---------|------------|
| `installed` | Block access if system not installed | Installation routes (inverted) |
| `not_installed` | Require system to be installed | All main routes |
| `not_logged_in` | Only allow logged-out users | Auth routes |
| `auth` | Require authentication | Protected routes |
| `frontend` | Customer area access | Customer routes |
| `backend` | Admin area access | Admin routes |
| `subscription` | Require active subscription | Premium features |
| `auth:api` | API token authentication | API routes |

### Middleware Stack Examples

**Installation Routes:**
```php
['middleware' => ['installed']]
```

**Public Routes (No Auth):**
```php
['middleware' => ['not_installed']]
```

**Customer Dashboard:**
```php
['middleware' => ['not_installed', 'auth', 'frontend']]
```

**Customer Premium Features:**
```php
['middleware' => ['not_installed', 'auth', 'frontend', 'subscription']]
```

**Admin Dashboard:**
```php
['namespace' => 'Admin', 'middleware' => ['not_installed', 'auth', 'backend']]
```

**API Endpoints:**
```php
['namespace' => 'Api', 'prefix' => 'v1', 'middleware' => 'auth:api']
```

---

## Security Considerations

### Authentication & Authorization

1. **Multi-Guard Authentication**
   - Web sessions for UI users
   - API tokens for programmatic access
   - OAuth 2.0 for Google/Facebook

2. **Role-Based Access Control**
   - Admin users: Full system access
   - Customers: Limited to own resources (lists, campaigns, subscribers)
   - Sub-accounts: Delegated customer access

3. **Resource Isolation**
   - UID-based routes prevent enumeration
   - Middleware enforces ownership checks
   - Policy-based authorization on sensitive operations

### CSRF Protection

**Protected Routes:** All POST, PATCH, PUT, DELETE requests require CSRF token
**Exceptions:**
- `/delivery/notify/{stype?}` - Webhook endpoint (verified by signature)
- API routes under `/api/v1` - Token-based auth

### Rate Limiting

**API Routes:** `auth:api` middleware includes rate limiting (configured in `app/Http/Kernel.php`)
**Web Routes:** Session-based throttling for login, registration, password reset

### Input Validation

**Form Requests:** All data modification routes use dedicated Form Request classes
**API Validation:** Controller-level validation with JSON error responses

### SQL Injection Prevention

**Eloquent ORM:** All database queries use parameter binding
**Raw Queries:** Rare, but use `DB::raw()` with parameter binding

### XSS Prevention

**Blade Templates:** Auto-escaping with `{{ }}` syntax
**User Content:** HTML Purifier for email templates and rich text

### File Upload Security

**Validation:**
- MIME type checking
- File extension whitelist
- Size limits

**Storage:**
- Segregated by customer UID
- Outside web root (`storage/app/users/{uid}/`)
- Served via route with MIME validation

### Webhook Security

**Delivery Notification:** `/delivery/notify/{stype?}`
- IP whitelisting (configured per sending server)
- Signature verification (HMAC for supported providers)
- Rate limiting

**Campaign Webhooks:** User-defined webhooks
- HTTPS enforcement (optional)
- Custom headers for authentication
- Timeout limits

---

## Route Naming Patterns

### Named Routes

**Important Named Routes:**
| Name | URI | Usage |
|------|-----|-------|
| `openTrackingUrl` | `/p/{message_id}/open` | Embedded in email headers |
| `clickTrackingUrl` | `/p/{url}/click/{message_id?}` | Wraps all email links |
| `unsubscribeUrl` | `/c/{subscriber}/unsubscribe/{message_id?}` | Unsubscribe link |
| `webViewerUrl` | `/campaigns/{message_id}/web-view` | Email web version |
| `webViewerPreviewUrl` | `/campaigns/{campaign_uid}/webview/{subscriber_uid}/preview` | Web version preview |
| `updateProfileUrl` | `/lists/{list_uid}/update-profile/{uid}/{code}` | Profile update link |
| `unsubscribeForm` | `/lists/{list_uid}/unsubscribe/{uid}/{code}` | Unsubscribe form |
| `mail_list` | `/lists/{uid}/overview` | List dashboard |
| `appkey` | `/appkey` | Application key retrieval |
| `campaign_message` | `/campaigns/test/{message}` | Campaign test notification |
| `logout` | `/logout` | Logout (GET method) |
| `automation_execute` | `/api/v1/automations/{uid}/execute` | API automation trigger |

### Route Patterns

**Resource Routes:** Standard Laravel resourceful controllers
```php
Route::resource('lists', 'MailListController');
```

**AJAX Listings:** Pattern: `{resource}/listing/{page?}`
```php
GET /lists/listing/{page?}
GET /campaigns/listing/{page?}
GET /subscribers/listing
```

**Bulk Actions:** Pattern: `{resource}/delete`, `{resource}/enable`, etc.
```php
GET /campaigns/delete
POST /campaigns/pause
POST /subscribers/subscribe
```

**Sub-Resources:** Pattern: `{parent}/{parent_uid}/{child}`
```php
GET /lists/{list_uid}/subscribers
GET /lists/{list_uid}/fields
GET /lists/{list_uid}/segments
```

**Wizard Steps:** Pattern: `{resource}/{uid}/{step}`
```php
GET /campaigns/{uid}/setup
GET /campaigns/{uid}/recipients
GET /campaigns/{uid}/template
GET /campaigns/{uid}/schedule
GET /campaigns/{uid}/confirm
```

---

## API Versioning & Deprecation

**Current API Version:** v1
**Prefix:** `/api/v1`
**Deprecated Routes:**
- `/p/assets/{path}` - Use `/assets/{dirname}/{basename}` instead

**Future Considerations:**
- API v2 would use prefix `/api/v2`
- Legacy v1 maintained for backward compatibility
- Deprecation notices in response headers

---

## Performance Optimization

### Route Caching

**Production Recommendation:**
```bash
php artisan route:cache
```

**Note:** Route caching is incompatible with Closure-based routes. Acelle uses several Closures in `web.php` for file serving.

### AJAX Listing Routes

**Pattern:** All data tables use separate `/listing` routes
- Reduces initial page load
- Enables server-side pagination
- Supports filtering and sorting

**Example:**
```php
GET /campaigns         # Page skeleton
GET /campaigns/listing # AJAX data
```

### Lazy Loading

**Modals & Popups:** Loaded on-demand via AJAX
```php
GET /campaigns/quick-view
GET /lists/quick-view
GET /campaigns/{uid}/preview
```

---

## Integration Points

### External Services

**Sending Providers:**
- Amazon SES: `/sending_servers/create/amazon-ses`
- SendGrid: `/sending_servers/create/sendgrid`
- Mailgun: `/sending_servers/create/mailgun`
- SparkPost: `/sending_servers/create/sparkpost`
- SMTP: `/sending_servers/create/smtp`

**Payment Gateways:**
- Stripe: `/admin/payment/gateways/edit/stripe`
- PayPal: `/admin/payment/gateways/edit/paypal`
- Braintree: `/admin/payment/gateways/edit/braintree`

**OAuth Providers:**
- Google: `/auth/google/redirect`, `/auth/google/callback`
- Facebook: `/auth/facebook/redirect`, `/auth/facebook/callback`

**E-commerce Platforms:**
- WooCommerce: `/sources/woo-connect`
- PrestaShop: Plugin-based integration

### Webhooks (Inbound)

**Delivery Notifications:**
```
POST /delivery/notify/ses      # Amazon SES
POST /delivery/notify/sendgrid # SendGrid
POST /delivery/notify/mailgun  # Mailgun
POST /delivery/notify          # Generic
```

**Payload:** JSON or form-encoded
**Authentication:** IP whitelist + signature verification

### Webhooks (Outbound)

**Campaign Events:**
- Open events
- Click events
- Custom link-specific webhooks

**Configuration:** `/campaigns/{uid}/webhooks`
**Testing:** `/campaigns/webhooks/{webhook_uid}/test`

---

## Cron Job Integration

### Required Cron Job

**Frequency:** Every minute
```bash
* * * * * php /path/to/acelle/artisan schedule:run >> /dev/null 2>&1
```

**Scheduled Tasks:**
- Campaign sending queue processing
- Automation trigger checking
- Bounce/feedback processing
- Email verification jobs
- Subscription renewal checks
- Database cleanup
- Report generation

**Configuration Route:** `/admin/settings/cronjob`

---

## Migration & Upgrade Routes

### Database Migrations

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/migrate/run` | Run pending migrations |
| GET | `/jobs/drop-index` | Drop jobs table index |
| GET | `/jobs/create-index` | Create jobs table index |

### Application Upgrade

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/settings/upgrade` | Upgrade page |
| GET | `/u` | Shortcut to upgrade |
| GET/POST | `/q` | Quick upgrade from URL |
| POST | `/admin/settings/upgrade/upload` | Upload patch |
| POST | `/admin/settings/upgrade` | Execute upgrade |
| POST | `/admin/settings/upgrade/cancel` | Cancel upgrade |
| GET | `/admin/upgrade/finalize` | Finalize upgrade |

---

## Conclusion

The Acelle Mail routing structure is comprehensive and well-organized, with clear separation between:
- Public routes (tracking, subscription management)
- Customer routes (campaign management, automation)
- Admin routes (system configuration, user management)
- API routes (programmatic access)

**Key Strengths:**
1. RESTful API design with consistent patterns
2. Comprehensive middleware protection
3. GDPR-compliant subscription management
4. Extensive campaign analytics and tracking
5. Powerful automation system with visual builder
6. Multi-tenant architecture with resource isolation

**Critical Routes for Mailing Module Migration:**
- Campaign creation and sending workflows
- Automation trigger and email configuration
- Subscriber import/export with progress tracking
- Email tracking (open, click, bounce, unsubscribe)
- Sending server and domain verification
- Webhook handlers for delivery notifications

This analysis provides the foundation for designing the new Alsernet Mailing module routing structure while maintaining compatibility with existing Acelle integrations.
