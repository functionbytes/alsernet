# Form Requests Migration Report

**Date:** 2026-01-29
**Module:** Mailing
**Source:** Acelle Mail Validation Patterns
**Target:** Laravel 12 Form Request Classes
**Status:** ✅ COMPLETED

---

## Executive Summary

Successfully migrated validation logic from Acelle Mail's model-based validation pattern to modern Laravel 12 Form Request classes. Created 8 critical Form Request classes following Laravel best practices with Spatie Permission integration.

### Key Achievements

✅ **8 Form Request classes created** - All critical operations covered
✅ **Spatie Permission integration** - Authorization via `authorize()` method
✅ **Spanish validation messages** - Complete custom error messages
✅ **Laravel 12 syntax** - Modern array-based validation rules
✅ **Conditional validation** - Context-aware rules implementation
✅ **Type-specific validation** - Dynamic rules for different server types

---

## Migration Overview

### Source Pattern (Acelle Mail)

Acelle Mail uses a **non-standard approach**:
- Validation rules defined as static properties or methods in Model classes
- Inline validation in controllers using `$this->validate()`
- Gate-based authorization scattered across controllers
- Sparse custom validation messages

### Target Pattern (Mailing Module)

Modern Laravel 12 approach:
- Dedicated Form Request class per action
- Centralized authorization in `authorize()` method
- Validation rules in `rules()` method
- Spanish custom messages in `messages()` method
- Custom attribute names in `attributes()` method

---

## Migrated Form Requests

### 1. CreateCampaignRequest.php

**Purpose:** Validate campaign creation requests

**Location:** `/modules/Mailing/app/Http/Requests/CreateCampaignRequest.php`

**Key Features:**
- ✅ Basic campaign fields validation (name, subject, from_email, from_name, reply_to)
- ✅ Optional tracking settings (track_open, track_click, sign_dkim)
- ✅ Conditional validation for custom tracking domain
- ✅ Conditional validation for default sending server email
- ✅ HTML and plain text content validation
- ✅ Permission check: `mailing.campaigns.create`

**Validation Rules Implemented:**
```php
'name' => ['required', 'string', 'max:255'],
'subject' => ['required', 'string', 'max:255'],
'from_email' => ['required', 'email', 'max:255'],
'from_name' => ['required', 'string', 'max:255'],
'reply_to' => ['required', 'email', 'max:255'],
'mail_list_id' => ['nullable', 'exists:mails_lists,id'],
'template_id' => ['nullable', 'exists:mails_templates,id'],
// + conditional rules
```

**Custom Messages:** 12 Spanish error messages

**Acelle Source Pattern:**
```php
// Campaign Model - rules() method
public function rules($request = null) {
    $rules = [
        'name' => 'required',
        'subject' => 'required',
        'from_email' => 'required|email',
        // ...
    ];

    if ($this->use_default_sending_server_from_email) {
        $rules['from_email'] = 'nullable|email';
    }

    return $rules;
}
```

---

### 2. UpdateCampaignRequest.php

**Purpose:** Validate campaign update requests

**Location:** `/modules/Mailing/app/Http/Requests/UpdateCampaignRequest.php`

**Key Features:**
- ✅ Same validation as CreateCampaignRequest
- ✅ Additional status field validation
- ✅ Permission check: `mailing.campaigns.edit`

**Additional Rules:**
```php
'status' => ['nullable', 'in:new,queuing,sending,done,paused,error'],
```

**Custom Messages:** 13 Spanish error messages

**Differences from Create:**
- Allows status updates (new, queuing, sending, done, paused, error)
- Different permission requirement

---

### 3. SendCampaignRequest.php

**Purpose:** Validate campaign sending/scheduling requests

**Location:** `/modules/Mailing/app/Http/Requests/SendCampaignRequest.php`

**Key Features:**
- ✅ Send type validation (immediate vs scheduled)
- ✅ Scheduled date validation (must be future date)
- ✅ Delivery rate limiting validation
- ✅ Test emails array validation
- ✅ Automatic test emails parsing
- ✅ Permission check: `mailing.campaigns.send`

**Validation Rules Implemented:**
```php
'send_type' => ['required', 'in:now,scheduled'],
'scheduled_at' => ['required_if:send_type,scheduled', 'nullable', 'date', 'after:now'],
'delivery_rate' => ['nullable', 'integer', 'min:1', 'max:100000'],
'delivery_unit' => ['nullable', 'in:hour,minute'],
'test_emails' => ['nullable', 'array'],
'test_emails.*' => ['email'],
```

**Special Feature:**
```php
protected function prepareForValidation(): void
{
    // Convert comma-separated test emails to array
    if ($this->has('test_emails') && is_string($this->test_emails)) {
        $emails = array_map('trim', explode(',', $this->test_emails));
        $this->merge(['test_emails' => array_filter($emails)]);
    }
}
```

**Custom Messages:** 10 Spanish error messages

---

### 4. CreateMailListRequest.php

**Purpose:** Validate mail list creation requests

**Location:** `/modules/Mailing/app/Http/Requests/CreateMailListRequest.php`

**Key Features:**
- ✅ Basic list information validation
- ✅ **Nested contact information validation** (9 fields)
- ✅ Complex regex validation for comma-separated email lists
- ✅ URL validation for website
- ✅ Country foreign key validation
- ✅ Permission check: `mailing.lists.create`

**Validation Rules Implemented:**
```php
// Basic fields
'name' => ['required', 'string', 'max:255'],
'from_email' => ['required', 'email', 'max:255'],
'from_name' => ['required', 'string', 'max:255'],

// Nested contact validation
'contact.company' => ['required', 'string', 'max:255'],
'contact.address_1' => ['required', 'string', 'max:255'],
'contact.country_id' => ['required', 'exists:mails_countries,id'],
'contact.state' => ['required', 'string', 'max:255'],
'contact.city' => ['required', 'string', 'max:255'],
'contact.zip' => ['required', 'string', 'max:20'],
'contact.phone' => ['required', 'string', 'max:50'],
'contact.email' => ['required', 'email', 'max:255'],
'contact.url' => ['nullable', 'url', 'max:255'],

// Complex email list validation (comma-separated)
'email_subscribe' => ['nullable', 'regex:/^[\W]*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4}[\W]*,{1}[\W]*)*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4})[\W]*$/'],
'email_unsubscribe' => ['nullable', 'regex:/^[\W]*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4}[\W]*,{1}[\W]*)*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4})[\W]*$/'],
'email_daily' => ['nullable', 'regex:/^[\W]*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4}[\W]*,{1}[\W]*)*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4})[\W]*$/'],
```

**Custom Messages:** 17 Spanish error messages

**Acelle Source Pattern:**
```php
// MailList Model - static $rules property
public static $rules = [
    'name' => 'required',
    'from_email' => 'required|email',
    'from_name' => 'required',
    'contact.company' => 'required',
    'contact.address_1' => 'required',
    // ... 9 nested contact fields
    'email_subscribe' => 'nullable|regex:"^[\W]*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4}[\W]*,{1}[\W]*)*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4})[\W]*$"',
];
```

---

### 5. UpdateMailListRequest.php

**Purpose:** Validate mail list update requests

**Location:** `/modules/Mailing/app/Http/Requests/UpdateMailListRequest.php`

**Key Features:**
- ✅ Identical validation to CreateMailListRequest
- ✅ Permission check: `mailing.lists.edit`

**Custom Messages:** 17 Spanish error messages

**Note:** Update has same validation as create for mail lists (no unique email constraints)

---

### 6. ImportSubscribersRequest.php

**Purpose:** Validate subscriber import requests

**Location:** `/modules/Mailing/app/Http/Requests/ImportSubscribersRequest.php`

**Key Features:**
- ✅ File upload validation (CSV, TXT, XLSX)
- ✅ Import type validation (new, update, replace)
- ✅ File encoding validation (UTF-8, ISO-8859-1, Windows-1252)
- ✅ Field mapping array validation
- ✅ Tag assignment validation
- ✅ Default value preparation
- ✅ Permission check: `mailing.lists.import`

**Validation Rules Implemented:**
```php
'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'], // 10MB max
'mail_list_id' => ['required', 'exists:mails_lists,id'],
'import_type' => ['required', 'in:new,update,replace'],
'has_header' => ['nullable', 'boolean'],
'delimiter' => ['nullable', 'string', 'max:1'],
'encoding' => ['nullable', 'in:UTF-8,ISO-8859-1,Windows-1252'],
'mapping' => ['nullable', 'array'],
'mapping.*' => ['nullable', 'string'],
'status' => ['nullable', 'in:subscribed,unconfirmed'],
'verify_email' => ['nullable', 'boolean'],
'send_welcome_email' => ['nullable', 'boolean'],
'tags' => ['nullable', 'array'],
'tags.*' => ['string', 'max:100'],
'skip_duplicates' => ['nullable', 'boolean'],
'update_existing' => ['nullable', 'boolean'],
```

**Special Feature:**
```php
protected function prepareForValidation(): void
{
    // Set sensible defaults
    if (!$this->has('delimiter')) {
        $this->merge(['delimiter' => ',']);
    }
    if (!$this->has('encoding')) {
        $this->merge(['encoding' => 'UTF-8']);
    }
    if (!$this->has('status')) {
        $this->merge(['status' => 'subscribed']);
    }
}
```

**Custom Messages:** 17 Spanish error messages

---

### 7. CreateSendingServerRequest.php

**Purpose:** Validate sending server creation requests

**Location:** `/modules/Mailing/app/Http/Requests/CreateSendingServerRequest.php`

**Key Features:**
- ✅ Multi-type server validation (SMTP, Sendmail, SES, Mailgun, SendGrid, SparkPost, ElasticEmail)
- ✅ **Type-specific conditional validation** (different rules per server type)
- ✅ Quota/rate limiting validation
- ✅ Port number validation (1-65535)
- ✅ Encryption type validation (TLS, SSL, none)
- ✅ AWS region validation
- ✅ URL validation for API endpoints
- ✅ Permission check: `mailing.sending_servers.manage`

**Validation Rules Implemented:**

**Base Rules (all types):**
```php
'name' => ['required', 'string', 'max:255'],
'type' => ['required', 'in:smtp,sendmail,amazon-ses,mailgun,sendgrid,sparkpost,elasticemail'],
'quota_value' => ['required', 'integer', 'min:0'],
'quota_base' => ['required', 'integer', 'min:1'],
'quota_unit' => ['required', 'in:minute,hour,day'],
```

**Type-Specific Rules:**

**SMTP:**
```php
'host' => ['required', 'string', 'max:255'],
'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
'smtp_username' => ['nullable', 'string', 'max:255'],
'smtp_password' => ['nullable', 'string', 'max:255'],
'encryption' => ['nullable', 'in:tls,ssl,none'],
```

**Sendmail:**
```php
'sendmail_path' => ['required', 'string', 'max:255'],
```

**Amazon SES:**
```php
'aws_access_key_id' => ['required', 'string', 'max:255'],
'aws_secret_access_key' => ['required', 'string', 'max:255'],
'aws_region' => ['required', 'string', 'max:50'],
```

**Mailgun:**
```php
'mailgun_domain' => ['required', 'string', 'max:255'],
'mailgun_api_key' => ['required', 'string', 'max:255'],
'mailgun_endpoint' => ['nullable', 'url', 'max:255'],
```

**SendGrid:**
```php
'sendgrid_api_key' => ['required', 'string', 'max:255'],
```

**SparkPost:**
```php
'sparkpost_api_key' => ['required', 'string', 'max:255'],
'sparkpost_endpoint' => ['nullable', 'url', 'max:255'],
```

**ElasticEmail:**
```php
'elasticemail_api_key' => ['required', 'string', 'max:255'],
```

**Special Feature:**
```php
public function rules(): array
{
    $rules = [/* base rules */];

    // Dynamic type-specific validation
    switch ($this->input('type')) {
        case 'smtp':
            $rules = array_merge($rules, [/* SMTP rules */]);
            break;
        case 'amazon-ses':
            $rules = array_merge($rules, [/* SES rules */]);
            break;
        // ... other types
    }

    return $rules;
}
```

**Custom Messages:** 33 Spanish error messages (covering all server types)

---

### 8. UpdateSendingServerRequest.php

**Purpose:** Validate sending server update requests

**Location:** `/modules/Mailing/app/Http/Requests/UpdateSendingServerRequest.php`

**Key Features:**
- ✅ Identical validation to CreateSendingServerRequest
- ✅ Type-specific conditional validation
- ✅ Permission check: `mailing.sending_servers.manage`

**Custom Messages:** 33 Spanish error messages

---

## Technical Implementation Details

### Authorization Pattern

All Form Requests use **Spatie Permission** for authorization:

```php
public function authorize(): bool
{
    return $this->user()->can('mailing.campaigns.create');
}
```

**Permissions Used:**
- `mailing.campaigns.create`
- `mailing.campaigns.edit`
- `mailing.campaigns.send`
- `mailing.lists.create`
- `mailing.lists.edit`
- `mailing.lists.import`
- `mailing.sending_servers.manage`

### Validation Syntax

**Modern Laravel 12 array-based rules:**
```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
    ];
}
```

**NOT using deprecated string-based rules:**
```php
// ❌ OLD (Acelle pattern)
'name' => 'required|string|max:255'

// ✅ NEW (Laravel 12 pattern)
'name' => ['required', 'string', 'max:255']
```

### Custom Messages Pattern

All Form Requests include comprehensive Spanish error messages:

```php
public function messages(): array
{
    return [
        'name.required' => 'El nombre es obligatorio.',
        'email.email' => 'El email debe ser válido.',
        'email.unique' => 'Este email ya está registrado.',
    ];
}
```

### Custom Attributes Pattern

All Form Requests include Spanish attribute names:

```php
public function attributes(): array
{
    return [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'phone' => 'teléfono',
    ];
}
```

### Data Preparation Pattern

Some Form Requests include `prepareForValidation()` for data cleanup:

```php
protected function prepareForValidation(): void
{
    // Set defaults
    if (!$this->has('delimiter')) {
        $this->merge(['delimiter' => ',']);
    }

    // Clean arrays
    if ($this->has('test_emails') && is_string($this->test_emails)) {
        $emails = array_map('trim', explode(',', $this->test_emails));
        $this->merge(['test_emails' => array_filter($emails)]);
    }
}
```

---

## Validation Features Implemented

### 1. Conditional Validation

**Example: CreateCampaignRequest**
```php
// Rule changes based on checkbox state
if ($this->boolean('custom_tracking_domain')) {
    $rules['tracking_domain_uid'] = ['required', 'string'];
}

if ($this->boolean('use_default_sending_server_from_email')) {
    $rules['from_email'] = ['nullable', 'email', 'max:255'];
}
```

### 2. Nested Validation

**Example: CreateMailListRequest**
```php
'contact.company' => ['required', 'string', 'max:255'],
'contact.address_1' => ['required', 'string', 'max:255'],
'contact.email' => ['required', 'email', 'max:255'],
// ... 9 nested contact fields
```

### 3. Complex Regex Validation

**Example: Email list validation (comma-separated emails)**
```php
'email_subscribe' => [
    'nullable',
    'regex:/^[\W]*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4}[\W]*,{1}[\W]*)*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4})[\W]*$/'
],
```

### 4. Array Validation

**Example: ImportSubscribersRequest**
```php
'mapping' => ['nullable', 'array'],
'mapping.*' => ['nullable', 'string'],

'tags' => ['nullable', 'array'],
'tags.*' => ['string', 'max:100'],

'test_emails' => ['nullable', 'array'],
'test_emails.*' => ['email'],
```

### 5. Conditional Required Fields

**Example: SendCampaignRequest**
```php
'scheduled_at' => [
    'required_if:send_type,scheduled',
    'nullable',
    'date',
    'after:now'
],
```

### 6. Type-Specific Validation

**Example: CreateSendingServerRequest**
```php
switch ($this->input('type')) {
    case 'smtp':
        $rules['host'] = ['required', 'string', 'max:255'];
        break;
    case 'amazon-ses':
        $rules['aws_access_key_id'] = ['required', 'string', 'max:255'];
        break;
}
```

### 7. File Upload Validation

**Example: ImportSubscribersRequest**
```php
'file' => [
    'required',
    'file',
    'mimes:csv,txt,xlsx',
    'max:10240' // 10MB
],
```

### 8. Enum Validation

```php
'status' => ['nullable', 'in:new,queuing,sending,done,paused,error'],
'import_type' => ['required', 'in:new,update,replace'],
'quota_unit' => ['required', 'in:minute,hour,day'],
'encryption' => ['nullable', 'in:tls,ssl,none'],
```

---

## Controller Integration Examples

### Before (Acelle Pattern)

```php
namespace Acelle\Http\Controllers;

class CampaignController extends Controller
{
    public function store(Request $request)
    {
        $campaign = new Campaign();

        // Manual authorization check
        if (\Gate::denies('create', $campaign)) {
            return $this->notAuthorized();
        }

        $campaign->fill($request->all());

        // Inline validation using model rules
        $this->validate($request, $campaign->rules($request));

        $campaign->save();

        return redirect()->action('CampaignController@index');
    }
}
```

### After (Mailing Module Pattern)

```php
namespace Modules\Mailing\Http\Controllers;

use Modules\Mailing\Http\Requests\CreateCampaignRequest;
use Modules\Mailing\Models\Campaign;

class CampaignController extends Controller
{
    /**
     * Store a newly created campaign.
     *
     * Authorization and validation are handled automatically by Form Request.
     */
    public function store(CreateCampaignRequest $request)
    {
        // Request is already validated and authorized
        $campaign = Campaign::create($request->validated());

        return redirect()
            ->route('mailing.campaigns.index')
            ->with('success', 'Campaña creada exitosamente.');
    }
}
```

**Benefits:**
- ✅ Cleaner controller (no validation/authorization boilerplate)
- ✅ Type safety (Form Request type hint)
- ✅ Testable (can test Form Request independently)
- ✅ Reusable (same validation for web and API routes)
- ✅ Centralized error messages

---

## Statistics

### Lines of Code

| Form Request | LOC | Validation Rules | Custom Messages | Attributes |
|--------------|-----|------------------|-----------------|------------|
| CreateCampaignRequest | 108 | 13 | 12 | 13 |
| UpdateCampaignRequest | 112 | 14 | 13 | 14 |
| SendCampaignRequest | 95 | 6 | 10 | 5 |
| CreateMailListRequest | 132 | 19 | 17 | 19 |
| UpdateMailListRequest | 132 | 19 | 17 | 19 |
| ImportSubscribersRequest | 148 | 16 | 17 | 13 |
| CreateSendingServerRequest | 212 | 35+ (type-specific) | 33 | 25 |
| UpdateSendingServerRequest | 196 | 35+ (type-specific) | 33 | 25 |
| **TOTAL** | **1,135** | **157+** | **152** | **133** |

### Coverage

**Critical Operations Covered:**
- ✅ Campaign creation and updates
- ✅ Campaign sending (immediate and scheduled)
- ✅ Mail list creation and updates
- ✅ Subscriber import (CSV/XLSX)
- ✅ Sending server configuration (7 types)

**Not Yet Covered (Future Work):**
- Subscriber individual creation/update
- Template creation/update
- Automation workflow creation
- Segment creation/update
- Email verification configuration
- Bounce handler configuration
- Tracking domain configuration

---

## Testing Recommendations

### Unit Tests for Form Requests

```php
namespace Tests\Unit\Requests;

use Modules\Mailing\Http\Requests\CreateCampaignRequest;
use Tests\TestCase;

class CreateCampaignRequestTest extends TestCase
{
    /** @test */
    public function it_validates_required_fields()
    {
        $request = new CreateCampaignRequest();

        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('required', $rules['name']);
    }

    /** @test */
    public function it_requires_tracking_domain_when_custom_tracking_enabled()
    {
        $request = CreateCampaignRequest::create(
            '/mailing/campaigns',
            'POST',
            ['custom_tracking_domain' => true]
        );

        $rules = $request->rules();

        $this->assertArrayHasKey('tracking_domain_uid', $rules);
        $this->assertContains('required', $rules['tracking_domain_uid']);
    }

    /** @test */
    public function it_authorizes_users_with_create_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('mailing.campaigns.create');

        $request = new CreateCampaignRequest();
        $request->setUserResolver(fn() => $user);

        $this->assertTrue($request->authorize());
    }
}
```

### Integration Tests

```php
namespace Tests\Feature\Mailing;

use Modules\Mailing\Models\Campaign;
use Tests\TestCase;

class CampaignControllerTest extends TestCase
{
    /** @test */
    public function it_creates_campaign_with_valid_data()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('mailing.campaigns.create');

        $response = $this->actingAs($user)->post('/mailing/campaigns', [
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'from_email' => 'sender@example.com',
            'from_name' => 'Test Sender',
            'reply_to' => 'reply@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('mails_campaigns', [
            'name' => 'Test Campaign',
        ]);
    }

    /** @test */
    public function it_returns_validation_errors_for_invalid_data()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('mailing.campaigns.create');

        $response = $this->actingAs($user)->post('/mailing/campaigns', [
            'name' => '', // Invalid
            'from_email' => 'invalid-email', // Invalid
        ]);

        $response->assertSessionHasErrors(['name', 'from_email']);
    }
}
```

---

## Migration from Acelle Controllers

### Step-by-Step Guide

**1. Identify controller method:**
```php
// Acelle: app/Http/Controllers/CampaignController.php
public function store(Request $request) { ... }
```

**2. Create corresponding Form Request:**
```bash
php artisan make:request Mailing/CreateCampaignRequest
```

**3. Extract validation rules from model:**
```php
// From: Acelle\Model\Campaign::rules()
// To: Modules\Mailing\Http\Requests\CreateCampaignRequest::rules()
```

**4. Update controller to use Form Request:**
```php
// Before
public function store(Request $request)
{
    $this->validate($request, $campaign->rules());
    // ...
}

// After
public function store(CreateCampaignRequest $request)
{
    $campaign = Campaign::create($request->validated());
    // ...
}
```

**5. Remove inline authorization:**
```php
// Before
if (\Gate::denies('create', $campaign)) {
    return $this->notAuthorized();
}

// After (handled in Form Request)
public function authorize(): bool
{
    return $this->user()->can('mailing.campaigns.create');
}
```

---

## Best Practices Applied

### 1. Spatie Permission Integration

✅ **DO:**
```php
public function authorize(): bool
{
    return $this->user()->can('mailing.campaigns.create');
}
```

❌ **DON'T:**
```php
public function authorize(): bool
{
    return \Gate::allows('create', Campaign::class);
}
```

### 2. Array-Based Validation Rules

✅ **DO:**
```php
'email' => ['required', 'email', 'max:255']
```

❌ **DON'T:**
```php
'email' => 'required|email|max:255'
```

### 3. Spanish Error Messages

✅ **DO:**
```php
public function messages(): array
{
    return [
        'name.required' => 'El nombre es obligatorio.',
    ];
}
```

❌ **DON'T:**
```php
// Rely on default English messages
```

### 4. Custom Attributes

✅ **DO:**
```php
public function attributes(): array
{
    return [
        'from_email' => 'email de remitente',
    ];
}
```

### 5. Data Preparation

✅ **DO:**
```php
protected function prepareForValidation(): void
{
    if (!$this->has('delimiter')) {
        $this->merge(['delimiter' => ',']);
    }
}
```

### 6. Type Hints

✅ **DO:**
```php
public function rules(): array
public function messages(): array
public function authorize(): bool
```

---

## Known Limitations

### 1. Email Regex Complexity

The comma-separated email regex from Acelle is complex and may not handle all edge cases:

```php
'email_subscribe' => [
    'nullable',
    'regex:/^[\W]*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4}[\W]*,{1}[\W]*)*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4})[\W]*$/'
],
```

**Recommendation:** Consider using a custom validation rule for better maintainability:

```php
'email_subscribe' => ['nullable', new CommaSeparatedEmailsRule()],
```

### 2. Type-Specific Validation Duplication

CreateSendingServerRequest and UpdateSendingServerRequest have identical type-specific validation logic.

**Recommendation:** Extract to a shared trait:

```php
trait SendingServerValidationRules
{
    protected function getTypeSpecificRules(string $type): array
    {
        return match($type) {
            'smtp' => [/* SMTP rules */],
            'amazon-ses' => [/* SES rules */],
            // ...
        };
    }
}
```

### 3. No Unique Email Validation for Lists

MailList requests don't validate uniqueness of list names or from_email addresses.

**Recommendation:** Add unique validation if needed:

```php
'name' => ['required', 'string', 'max:255', 'unique:mails_lists,name'],
```

---

## Future Enhancements

### Additional Form Requests Needed

**High Priority:**
1. `CreateSubscriberRequest.php`
2. `UpdateSubscriberRequest.php`
3. `CreateTemplateRequest.php`
4. `UpdateTemplateRequest.php`
5. `CreateAutomationRequest.php`
6. `UpdateAutomationRequest.php`

**Medium Priority:**
7. `CreateSegmentRequest.php`
8. `UpdateSegmentRequest.php`
9. `CreateSendingDomainRequest.php`
10. `UpdateSendingDomainRequest.php`
11. `CreateTrackingDomainRequest.php`
12. `UpdateTrackingDomainRequest.php`

**Low Priority:**
13. `CreateBounceHandlerRequest.php`
14. `CreateFeedbackLoopHandlerRequest.php`
15. `CreateEmailVerificationServerRequest.php`

### Custom Validation Rules

Create reusable validation rules:

```php
// app/Rules/CommaSeparatedEmailsRule.php
class CommaSeparatedEmailsRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $emails = array_map('trim', explode(',', $value));

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $fail("El campo :attribute contiene un email inválido: {$email}");
            }
        }
    }
}
```

### Form Request Base Class

Create a base class for common functionality:

```php
namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class MailingRequest extends FormRequest
{
    /**
     * Get the error messages for defined validation rules.
     */
    public function messages(): array
    {
        return array_merge(
            $this->commonMessages(),
            $this->customMessages()
        );
    }

    /**
     * Common error messages used across all Mailing requests.
     */
    protected function commonMessages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'email' => 'El campo :attribute debe ser un email válido.',
            'max' => 'El campo :attribute no puede exceder :max caracteres.',
        ];
    }

    /**
     * Request-specific custom messages.
     */
    abstract protected function customMessages(): array;
}
```

---

## Conclusion

### Summary

Successfully created 8 modern Laravel 12 Form Request classes that replace Acelle Mail's model-based validation pattern. All requests include:

- ✅ Spatie Permission authorization
- ✅ Array-based validation rules
- ✅ Spanish custom error messages
- ✅ Custom attribute names
- ✅ Data preparation where needed
- ✅ Conditional validation logic
- ✅ Type-specific validation

### Impact

**Code Quality:**
- Cleaner controllers (no validation boilerplate)
- Testable validation logic
- Centralized error messages
- Type-safe request handling

**Developer Experience:**
- Clear validation requirements
- Reusable across web and API routes
- Easy to extend and maintain
- Better IDE autocompletion

**User Experience:**
- Consistent Spanish error messages
- Clear validation feedback
- Proper field labeling

### Next Steps

1. **Implement remaining Form Requests** (Subscriber, Template, Automation, etc.)
2. **Write comprehensive tests** for all Form Requests
3. **Update controllers** to use Form Requests
4. **Create custom validation rules** for complex patterns
5. **Document usage** in controller documentation

---

**Report Generated:** 2026-01-29
**Author:** Claude Code Agent
**Status:** ✅ Migration Complete
**Files Created:** 8 Form Request classes + 1 report
**Total Lines:** 1,135 LOC
**Validation Rules:** 157+
**Custom Messages:** 152
**Custom Attributes:** 133
