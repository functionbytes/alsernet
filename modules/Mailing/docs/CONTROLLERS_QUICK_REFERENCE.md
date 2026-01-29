# Controllers Quick Reference Card

## 🚀 Mailing Module Controllers

**Total:** 14 Controllers | **Status:** ✅ Migrated | **Date:** 2026-01-29

---

## 📋 Controller List

| Controller | Purpose | Key Methods |
|-----------|---------|-------------|
| **TemplateController** | Email templates | CRUD, preview, import/export |
| **LayoutController** | Email layouts | CRUD, preview, copy |
| **FieldController** | Custom fields | CRUD, sort, options |
| **SegmentController** | Segmentation | CRUD, preview, export |
| **SendingServerController** | SMTP/API servers | CRUD, test, stats |
| **SendingDomainController** | Domain auth | CRUD, verify, DKIM |
| **TrackingDomainController** | Tracking domains | CRUD, verify, test |
| **SenderController** | Sender IDs | CRUD, verify, stats |
| **EmailVerificationServerController** | Email validation | CRUD, test, bulk |
| **BlacklistController** | Blacklist | CRUD, import/export |
| **FormController** | Sub forms | CRUD, embed, submit |
| **PageController** | Landing pages | CRUD, render, profile |
| **DeliveryController** | Delivery stats | Reports, charts, export |
| **AudienceController** | Audience analytics | Growth, engagement, location |

---

## 🔗 Common Routes

### Resource Routes (Standard)
```
GET    /mailing/{resource}              index
GET    /mailing/{resource}/create       create
POST   /mailing/{resource}              store
GET    /mailing/{resource}/{id}         show
GET    /mailing/{resource}/{id}/edit    edit
PUT    /mailing/{resource}/{id}         update
DELETE /mailing/{resource}/{id}         destroy
```

### Custom Actions (Examples)
```
POST   /mailing/templates/{id}/copy
GET    /mailing/templates/{id}/preview
POST   /mailing/sending-servers/{id}/test
POST   /mailing/sending-domains/{id}/verify
POST   /mailing/segments/preview
POST   /mailing/blacklist/import
```

---

## 📁 File Structure

```
modules/Mailing/app/Http/Controllers/
├── TemplateController.php
├── LayoutController.php
├── FieldController.php
├── SegmentController.php
├── SendingServerController.php
├── SendingDomainController.php
├── TrackingDomainController.php
├── SenderController.php
├── EmailVerificationServerController.php
├── BlacklistController.php
├── FormController.php
├── PageController.php
├── DeliveryController.php
└── AudienceController.php
```

---

## 🎨 View Paths

**Pattern:** `mailing::{resource}.{action}`

```php
// Examples
view('mailing::templates.index')
view('mailing::segments.create')
view('mailing::delivery.campaign-report')
```

---

## 🔐 Permission Format

**Pattern:** `{action} {resource}`

```php
// Examples
'view templates'
'create campaigns'
'edit sending-servers'
'delete segments'
```

---

## 🌍 Translation Keys

**Pattern:** `mailing::messages.{resource}.{key}`

```php
// Examples
__('mailing::messages.template.created')
__('mailing::messages.segment.updated')
__('mailing::messages.blacklist.imported')
```

---

## 🔄 Import/Export Features

| Controller | Import | Export |
|-----------|--------|--------|
| TemplateController | ✅ JSON | ✅ JSON |
| LayoutController | ✅ JSON | ✅ JSON |
| SegmentController | ❌ | ✅ CSV |
| BlacklistController | ✅ CSV/TXT | ✅ CSV |
| FormController | ❌ | ✅ CSV |
| DeliveryController | ❌ | ✅ CSV |
| AudienceController | ❌ | ✅ CSV |

---

## 📊 Statistics Endpoints

```php
// Sending Server Stats
GET /sending-servers/{id}/statistics

// Tracking Domain Stats
GET /tracking-domains/{id}/statistics

// Sender Stats
GET /senders/{id}/statistics

// Email Verification Stats
GET /email-verification-servers/{id}/statistics

// Segment Stats
GET /segments/{id}/statistics

// Form Stats
GET /forms/{id}/statistics

// List Stats
GET /lists/{id}/stats
```

---

## 🧪 Testing Endpoints

```php
// Test Sending Server
POST /sending-servers/{id}/test
Body: { "to_email": "test@example.com" }

// Test Email Verification
POST /email-verification-servers/{id}/test
Body: { "email": "test@example.com" }

// Test Tracking Domain
POST /tracking-domains/{id}/test
```

---

## 🔍 Search & Filter

All index methods support:

```php
?keyword=search-term       // Search
?status=active             // Filter by status
?sort=created_at           // Sort column
&order=desc                // Sort direction
&per_page=25               // Pagination
```

---

## 🚨 Validation Rules

### Common Fields

```php
'email' => 'required|email|max:255'
'name' => 'required|max:255'
'status' => 'in:active,inactive'
'api_key' => 'required'
```

### Server Types

```php
'type' => 'required|in:smtp,sendmail,php-mail,amazon-ses,amazon-api,mailgun-api,sendgrid-api,sparkpost-api,elasticemail-api'
```

### SMTP Settings

```php
'host' => 'required'
'port' => 'required|numeric'
'username' => 'required'
'password' => 'required'
'encryption' => 'required|in:none,ssl,tls'
```

---

## 🔗 Model Relationships

### Template
- `belongsTo(Layout)`
- `hasMany(Campaign)`

### MailList
- `hasMany(Subscriber)`
- `hasMany(Field)`
- `hasMany(Segment)`
- `hasMany(Form)`
- `hasMany(Page)`

### Campaign
- `belongsTo(MailList)`
- `belongsTo(Template)`
- `belongsTo(SendingServer)`
- `belongsTo(Sender)`
- `hasMany(TrackingLog)`

### Segment
- `belongsTo(MailList)`
- `belongsToMany(Subscriber)` (via conditions)

---

## 📈 Key Metrics Provided

### Delivery Metrics
- Total sent
- Delivered count/rate
- Bounced count/rate
- Complaint count/rate
- Open rate
- Click rate

### Audience Metrics
- Total subscribers
- Active subscribers
- Growth rate
- Engagement score
- Geographic distribution

---

## 🛠️ Utility Methods

### SegmentController
```php
applyCondition($query, $field, $operator, $value)
// Operators: equal, not_equal, contains, not_contains,
// starts_with, ends_with, greater, less, is_null, etc.
```

### SendingDomainController
```php
generateDkimKeys()         // Generate RSA key pair
verifyDkim($domain)        // Verify DKIM DNS
verifySpf($domain)         // Verify SPF DNS
verifyDmarc($domain)       // Verify DMARC DNS
```

---

## 🎯 Priority Features

### Critical
1. SendingServerController - Email delivery
2. SendingDomainController - Domain authentication
3. DeliveryController - Delivery monitoring

### High
4. TemplateController - Email creation
5. SegmentController - Targeting
6. FormController - Subscriber acquisition
7. AudienceController - Analytics

### Medium
8. BlacklistController - Data quality
9. EmailVerificationServerController - Validation

---

## 🔧 Configuration Required

### Environment Variables
```env
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=

# Optional: Email Verification
ZEROBOUNCE_API_KEY=
NEVERBOUNCE_API_KEY=
```

### Config Files
```php
config/mail.php           // Laravel mail config
config/mailing.php        // Module-specific config
```

---

## 📚 Dependencies

### Required Packages
- `guzzlehttp/guzzle` - HTTP client
- `league/csv` - CSV handling
- `spatie/laravel-permission` - Permissions

### Models Required
- Campaign, MailList, Subscriber
- Template, Layout, Field, Segment
- SendingServer, SendingDomain, TrackingDomain
- Sender, EmailVerificationServer, Blacklist
- Form, Page, TrackingLog

---

## ✅ Checklist for New Controllers

- [ ] Namespace: `Modules\Mailing\Http\Controllers`
- [ ] View prefix: `mailing::`
- [ ] Route prefix: `mailing.`
- [ ] Translation prefix: `mailing::`
- [ ] Input validation
- [ ] Permission checks
- [ ] Error handling
- [ ] Return type hints
- [ ] PHPDoc blocks
- [ ] Tests written

---

## 🐛 Common Issues & Solutions

### Issue: View not found
**Solution:** Ensure view path uses `mailing::` prefix

### Issue: Permission denied
**Solution:** Check Spatie permissions are seeded

### Issue: Model not found
**Solution:** Verify model exists in `Modules\Mailing\Models`

### Issue: Route not found
**Solution:** Check routes registered in `routes/web.php`

---

## 📞 Support Resources

- Main Report: `CONTROLLERS_ADDITIONAL_MIGRATION_REPORT.md`
- Summary: `CONTROLLERS_MIGRATION_SUMMARY.md`
- MCP Agents: `MAILING_AGENTS_REPORT.md`

---

**Quick Start:**
1. Register routes
2. Create views
3. Seed permissions
4. Write tests
5. Deploy!

**Last Updated:** 2026-01-29
