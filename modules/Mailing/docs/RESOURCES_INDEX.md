# API Resources Index - Mailing Module

**Generated**: 2026-01-29
**Total Resources**: 22 files (20 Resources + 5 Collections + 1 README)
**Lines of Code**: ~1,690
**Location**: `modules/Mailing/app/Http/Resources/Api/`

---

## Resources by Category

### 🎯 Core Resources (4)

| # | Resource | File | Purpose | LOC |
|---|----------|------|---------|-----|
| 1 | `CampaignResource` | `CampaignResource.php` | Email campaign data with stats and tracking | 140 |
| 2 | `MailListResource` | `MailListResource.php` | Email list with subscriber counts | 145 |
| 3 | `SubscriberResource` | `SubscriberResource.php` | Subscriber with custom fields and tags | 125 |
| 4 | `AutomationResource` | `AutomationResource.php` | Marketing automation workflows | 120 |

**Subtotal**: 530 LOC

---

### 🔧 Supporting Resources (7)

| # | Resource | File | Purpose | LOC |
|---|----------|------|---------|-----|
| 5 | `TemplateResource` | `TemplateResource.php` | Email templates | 70 |
| 6 | `SegmentResource` | `SegmentResource.php` | List segments | 65 |
| 7 | `SegmentConditionResource` | `SegmentConditionResource.php` | Segment conditions | 35 |
| 8 | `FieldResource` | `FieldResource.php` | Custom fields | 60 |
| 9 | `SenderResource` | `SenderResource.php` | Verified senders | 55 |
| 10 | `SendingServerResource` | `SendingServerResource.php` | SMTP/API servers | 120 |
| 11 | `CustomerResource` | `CustomerResource.php` | Customer info | 45 |

**Subtotal**: 450 LOC

---

### 📊 Tracking Resources (6)

| # | Resource | File | Purpose | LOC |
|---|----------|------|---------|-----|
| 12 | `TrackingLogResource` | `TrackingLogResource.php` | Master tracking log | 75 |
| 13 | `OpenLogResource` | `OpenLogResource.php` | Email opens tracking | 65 |
| 14 | `ClickLogResource` | `ClickLogResource.php` | Link clicks tracking | 65 |
| 15 | `BounceLogResource` | `BounceLogResource.php` | Email bounces | 60 |
| 16 | `FeedbackLogResource` | `FeedbackLogResource.php` | Spam complaints | 55 |
| 17 | `UnsubscribeLogResource` | `UnsubscribeLogResource.php` | Unsubscribe events | 50 |

**Subtotal**: 370 LOC

---

### 📦 Collections (5)

| # | Collection | File | Purpose | LOC |
|---|------------|------|---------|-----|
| 18 | `CampaignCollection` | `CampaignCollection.php` | Paginated campaigns | 55 |
| 19 | `MailListCollection` | `MailListCollection.php` | Paginated mail lists | 55 |
| 20 | `SubscriberCollection` | `SubscriberCollection.php` | Paginated subscribers with status summary | 75 |
| 21 | `AutomationCollection` | `AutomationCollection.php` | Paginated automations with status summary | 70 |
| 22 | `TrackingLogCollection` | `TrackingLogCollection.php` | Paginated tracking with engagement summary | 85 |

**Subtotal**: 340 LOC

---

## Quick Reference Guide

### Import Statements

```php
// Core
use Modules\Mailing\Http\Resources\Api\CampaignResource;
use Modules\Mailing\Http\Resources\Api\MailListResource;
use Modules\Mailing\Http\Resources\Api\SubscriberResource;
use Modules\Mailing\Http\Resources\Api\AutomationResource;

// Supporting
use Modules\Mailing\Http\Resources\Api\TemplateResource;
use Modules\Mailing\Http\Resources\Api\SegmentResource;
use Modules\Mailing\Http\Resources\Api\FieldResource;
use Modules\Mailing\Http\Resources\Api\SenderResource;
use Modules\Mailing\Http\Resources\Api\SendingServerResource;

// Tracking
use Modules\Mailing\Http\Resources\Api\TrackingLogResource;
use Modules\Mailing\Http\Resources\Api\OpenLogResource;
use Modules\Mailing\Http\Resources\Api\ClickLogResource;
use Modules\Mailing\Http\Resources\Api\BounceLogResource;

// Collections
use Modules\Mailing\Http\Resources\Api\CampaignCollection;
use Modules\Mailing\Http\Resources\Api\MailListCollection;
use Modules\Mailing\Http\Resources\Api\SubscriberCollection;
```

---

## Features by Resource

### CampaignResource

**Key Features**:
- ✅ Complete statistics (delivered, opened, clicked, bounced, unsubscribed)
- ✅ Calculated rates (open rate, click rate, bounce rate, unsubscribe rate)
- ✅ Conditional HTML/Plain content loading
- ✅ HATEOAS links to tracking logs
- ✅ Eager loaded relationships (mailList, segment, template, sendingServer)

**Conditional Parameters**:
- `?include_content=true` - Include HTML and plain text

**Relationships**:
- `mailList`, `segment`, `template`, `sendingServer`

---

### MailListResource

**Key Features**:
- ✅ Structured contact information
- ✅ Subscriber count by status (active, unsubscribed, bounced, blacklisted)
- ✅ Subscription settings
- ✅ Email verification settings
- ✅ Links to subscribers, segments, fields, embedded forms

**Relationships**:
- `fields`, `segments`, `customer`

---

### SubscriberResource

**Key Features**:
- ✅ Standard fields (first_name, last_name, full_name)
- ✅ Custom fields (conditional)
- ✅ Tags (conditional)
- ✅ Subscription info (IP, source, date)
- ✅ Email verification status
- ✅ Engagement stats (opens, clicks)

**Conditional Parameters**:
- `?include_custom_fields=true` - Include all custom fields
- `?include_tags=true` - Include subscriber tags
- `?include_verification=true` - Include verification details

**Relationships**:
- `mailList`

---

### AutomationResource

**Key Features**:
- ✅ Trigger type and settings
- ✅ Workflow JSON data (conditional)
- ✅ Contact statistics (total, active, completed)
- ✅ Email statistics (sent, opens, clicks)
- ✅ Calculated open/click rates

**Conditional Parameters**:
- `?include_trigger=true` - Include trigger settings
- `?include_workflow=true` - Include workflow JSON

**Relationships**:
- `mailList`, `segment`

---

### SendingServerResource

**Key Features**:
- ✅ Server type (SMTP, Sendgrid, Mailgun, SES, etc.)
- ✅ Quota settings and current usage
- ✅ **Sanitized credentials** (passwords/keys hidden as `***`)
- ✅ Type-specific configuration

**Conditional Parameters**:
- `?include_settings=true` - Include server settings (sanitized)

**Security**:
- API keys, passwords, and tokens are ALWAYS sanitized
- Only connection details (host, port, region) are shown

---

### Tracking Resources

**Common Features** (all tracking resources):
- ✅ Geolocation data (country, region, city, coordinates)
- ✅ Device detection (type, OS, browser)
- ✅ IP address and user agent
- ✅ ISO 8601 timestamps
- ✅ Relationships to subscriber and campaign

**BounceLogResource**:
- Bounce type (hard, soft, complaint)
- Diagnostic code
- Raw bounce data (conditional with `?include_raw=true`)

**FeedbackLogResource**:
- Feedback type (abuse, fraud, not-spam, other)
- Raw feedback content (conditional with `?include_raw=true`)

---

### Collections

**Common Features** (all collections):
- ✅ Pagination metadata (total, count, per_page, current_page, total_pages)
- ✅ Navigation links (first, last, prev, next)
- ✅ Success status wrapper
- ✅ ISO 8601 timestamp

**SubscriberCollection**:
- Status summary (subscribed, unsubscribed, bounced, blacklisted counts)

**AutomationCollection**:
- Status summary (active, inactive, paused counts)

**TrackingLogCollection**:
- Engagement summary (sent, delivered, failed, bounced, opened, clicked counts)

---

## Usage Examples

### Single Resource

```php
// In Controller
public function show($uid)
{
    $campaign = Campaign::with('mailList', 'segment')
        ->where('uid', $uid)
        ->firstOrFail();

    return new CampaignResource($campaign);
}
```

**Response**:
```json
{
  "data": {
    "id": 123,
    "uid": "abc123",
    "name": "Summer Sale",
    "stats": { ... },
    "links": { ... }
  },
  "status": "success",
  "timestamp": "2026-01-29T16:00:00+00:00"
}
```

---

### Collection with Pagination

```php
// In Controller
public function index(Request $request)
{
    $campaigns = Campaign::query()
        ->when($request->status, fn($q) => $q->where('status', $request->status))
        ->with('mailList')
        ->paginate($request->per_page ?? 15);

    return new CampaignCollection($campaigns);
}
```

**Response**:
```json
{
  "data": [ ... ],
  "meta": {
    "total": 47,
    "count": 15,
    "per_page": 15,
    "current_page": 1,
    "total_pages": 4
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "status": "success",
  "timestamp": "2026-01-29T16:00:00+00:00"
}
```

---

## Performance Considerations

### Always Eager Load

```php
// ❌ Bad - N+1 queries
$campaigns = Campaign::all();
return CampaignResource::collection($campaigns);

// ✅ Good - Eager loaded
$campaigns = Campaign::with('mailList', 'segment')->get();
return CampaignResource::collection($campaigns);
```

### Cache Heavy Computations

```php
// In Resource
protected function getSubscribersCount(): int
{
    return Cache::remember(
        "list.{$this->id}.subscribers",
        3600,
        fn() => $this->subscribers()->count()
    );
}
```

### Use Conditional Loading Wisely

```php
// Only load content when needed
GET /api/campaigns/abc123?include_content=true

// Only load custom fields for exports
GET /api/subscribers/export?include_custom_fields=true
```

---

## File Sizes

| Category | Files | LOC | Avg LOC/File |
|----------|-------|-----|--------------|
| Core Resources | 4 | 530 | 132 |
| Supporting Resources | 7 | 450 | 64 |
| Tracking Resources | 6 | 370 | 62 |
| Collections | 5 | 340 | 68 |
| **Total** | **22** | **1,690** | **77** |

---

## Next Steps

### 1. Integration with Controllers

Create API controllers that use these resources:
- `modules/Mailing/app/Http/Controllers/Api/CampaignController.php`
- `modules/Mailing/app/Http/Controllers/Api/MailListController.php`
- `modules/Mailing/app/Http/Controllers/Api/SubscriberController.php`

### 2. Route Definition

Define routes in:
- `modules/Mailing/routes/api.php`

### 3. Testing

Create tests in:
- `modules/Mailing/tests/Feature/Api/Resources/`

### 4. Documentation

Generate API documentation:
- OpenAPI/Swagger specification
- Postman collection
- Code examples

---

## Related Documentation

- **Migration Report**: `modules/Mailing/docs/RESOURCES_MIGRATION_REPORT.md`
- **Quick Start**: `modules/Mailing/app/Http/Resources/Api/README.md`
- **Controller Analysis**: `modules/Mailing/docs/ACELLE_CONTROLLERS_ANALYSIS.md`

---

**Last Updated**: 2026-01-29
