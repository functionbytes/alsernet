# Mailing Routes Implementation Guide

## Overview

This guide provides step-by-step instructions for implementing the migrated Mailing routes.

**Status:** ✅ Routes migrated and documented
**Next:** Controller implementation and integration

---

## File Structure

```
modules/Mailing/
├── routes/
│   ├── web.php          ✅ Created (200+ routes)
│   ├── api.php          ✅ Created (80+ routes)
│   ├── public.php       ✅ Created (25+ routes)
│   └── admin.php        ✅ Created (60+ routes)
├── app/Http/Controllers/
│   ├── Web/             ⏳ To create (8 controllers)
│   ├── Api/             ⏳ To create (7 controllers)
│   ├── Public/          ⏳ To create (5 controllers)
│   └── Admin/           ⏳ To create (7 controllers)
├── database/seeders/
│   └── MailingPermissionsSeeder.php  ✅ Exists (comprehensive)
└── docs/
    ├── ROUTES_MIGRATION_REPORT.md       ✅ Created
    ├── ROUTES_QUICK_REFERENCE.md        ✅ Created
    └── ROUTES_IMPLEMENTATION_GUIDE.md   ✅ This file
```

---

## Step 1: Register Routes in Service Provider

**File:** `modules/Mailing/app/Providers/MailingServiceProvider.php`

Add or update the `boot()` method:

```php
<?php

namespace Modules\Mailing\app\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MailingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleRoutes();
        $this->loadMigrationsFrom(module_path('Mailing', 'database/migrations'));
        $this->loadViewsFrom(module_path('Mailing', 'resources/views'), 'mailing');
    }

    protected function loadModuleRoutes(): void
    {
        $modulePath = module_path('Mailing');

        // Web routes (customer-facing)
        Route::middleware('web')
            ->group($modulePath . '/routes/web.php');

        // API routes
        Route::middleware('api')
            ->prefix('api')
            ->group($modulePath . '/routes/api.php');

        // Public routes (tracking, webhooks)
        Route::middleware('web')
            ->group($modulePath . '/routes/public.php');

        // Admin routes
        Route::middleware('web')
            ->group($modulePath . '/routes/admin.php');
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
```

---

## Step 2: Run Permissions Seeder

The permissions seeder already exists with all required permissions.

```bash
# Run the seeder
php artisan db:seed --class="Modules\Mailing\Database\Seeders\MailingPermissionsSeeder"

# Or add to DatabaseSeeder.php
$this->call(\Modules\Mailing\Database\Seeders\MailingPermissionsSeeder::class);
```

**Roles Created:**
- `super-admin` - All permissions
- `admin` - Full operational + settings
- `mailing_admin` - Complete mailing control
- `mailing_manager` - Campaign and subscriber management
- `mailing_user` - Limited access (view + create)
- `mailing_viewer` - Read-only
- `manager` (legacy) - Operational + templates
- `administrative` (legacy) - Limited view

---

## Step 3: Create Controllers

### 3.1 Web Controllers (Customer UI)

```bash
# Dashboard
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/DashboardController

# Campaigns
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/CampaignController --resource

# Mail Lists
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/MailListController --resource

# Subscribers
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/SubscriberController --resource

# Segments
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/SegmentController --resource

# Automations
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/AutomationController --resource

# Forms
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/FormController --resource

# Blacklist
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/BlacklistController
```

### 3.2 API Controllers

```bash
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/CampaignController --api
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/ListController --api
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/SubscriberController --api
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/TemplateController --api
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/AutomationController --api
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/WebhookController --api
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/StatsController
```

### 3.3 Public Controllers (No Auth)

```bash
php artisan make:controller Modules/Mailing/app/Http/Controllers/Public/TrackingController
php artisan make:controller Modules/Mailing/app/Http/Controllers/Public/UnsubscribeController
php artisan make:controller Modules/Mailing/app/Http/Controllers/Public/SubscribeController
php artisan make:controller Modules/Mailing/app/Http/Controllers/Public/WebVersionController
php artisan make:controller Modules/Mailing/app/Http/Controllers/Public/UpdateProfileController
```

### 3.4 Admin Controllers

```bash
php artisan make:controller Modules/Mailing/app/Http/Controllers/Admin/SettingsController
php artisan make:controller Modules/Mailing/app/Http/Controllers/Admin/UsersController --resource
php artisan make:controller Modules/Mailing/app/Http/Controllers/Admin/PlansController --resource
php artisan make:controller Modules/Mailing/app/Http/Controllers/Admin/SystemController
php artisan make:controller Modules/Mailing/app/Http/Controllers/Admin/LogsController
php artisan make:controller Modules/Mailing/app/Http/Controllers/Admin/ReportsController
php artisan make:controller Modules/Mailing/app/Http/Controllers/Admin/BackupController
```

---

## Step 4: Create Form Requests

```bash
# Campaign requests
php artisan make:request Modules/Mailing/app/Http/Requests/StoreCampaignRequest
php artisan make:request Modules/Mailing/app/Http/Requests/UpdateCampaignRequest

# Mail List requests
php artisan make:request Modules/Mailing/app/Http/Requests/StoreMailListRequest
php artisan make:request Modules/Mailing/app/Http/Requests/UpdateMailListRequest

# Subscriber requests
php artisan make:request Modules/Mailing/app/Http/Requests/StoreSubscriberRequest
php artisan make:request Modules/Mailing/app/Http/Requests/UpdateSubscriberRequest
php artisan make:request Modules/Mailing/app/Http/Requests/ImportSubscribersRequest

# Segment requests
php artisan make:request Modules/Mailing/app/Http/Requests/StoreSegmentRequest
php artisan make:request Modules/Mailing/app/Http/Requests/UpdateSegmentRequest

# Automation requests
php artisan make:request Modules/Mailing/app/Http/Requests/StoreAutomationRequest
php artisan make:request Modules/Mailing/app/Http/Requests/UpdateAutomationRequest

# Form requests
php artisan make:request Modules/Mailing/app/Http/Requests/StoreFormRequest
php artisan make:request Modules/Mailing/app/Http/Requests/UpdateFormRequest
```

**Example Form Request:**

```php
<?php

namespace Modules\Mailing\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailing.campaigns.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'from_email' => ['required', 'email', 'max:255'],
            'list_id' => ['required', 'exists:mailing_lists,id'],
            'template_id' => ['nullable', 'exists:mailing_templates,id'],
            'content' => ['required', 'string'],
            'schedule_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
```

---

## Step 5: Create API Resources

```bash
# Campaign resources
php artisan make:resource Modules/Mailing/app/Http/Resources/CampaignResource
php artisan make:resource Modules/Mailing/app/Http/Resources/CampaignCollection

# List resources
php artisan make:resource Modules/Mailing/app/Http/Resources/MailListResource
php artisan make:resource Modules/Mailing/app/Http/Resources/MailListCollection

# Subscriber resources
php artisan make:resource Modules/Mailing/app/Http/Resources/SubscriberResource
php artisan make:resource Modules/Mailing/app/Http/Resources/SubscriberCollection

# Template resources
php artisan make:resource Modules/Mailing/app/Http/Resources/TemplateResource

# Automation resources
php artisan make:resource Modules/Mailing/app/Http/Resources/AutomationResource

# Stats resources
php artisan make:resource Modules/Mailing/app/Http/Resources/StatsResource
```

**Example API Resource:**

```php
<?php

namespace Modules\Mailing\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'name' => $this->name,
            'subject' => $this->subject,
            'from_name' => $this->from_name,
            'from_email' => $this->from_email,
            'status' => $this->status,
            'list_id' => $this->list_id,
            'list' => new MailListResource($this->whenLoaded('list')),
            'template_id' => $this->template_id,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'stats' => [
                'total_recipients' => $this->total_recipients,
                'delivered' => $this->delivered_count,
                'opens' => $this->opens_count,
                'clicks' => $this->clicks_count,
                'bounces' => $this->bounces_count,
                'unsubscribes' => $this->unsubscribes_count,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

---

## Step 6: Implement Route Model Binding

**File:** `app/Providers/RouteServiceProvider.php` or `bootstrap/app.php`

```php
use Illuminate\Support\Facades\Route;
use Modules\Mailing\app\Models\Campaign;
use Modules\Mailing\app\Models\MailList;
use Modules\Mailing\app\Models\Subscriber;

// In boot() method
public function boot(): void
{
    parent::boot();

    // Bind campaigns by uid or id
    Route::bind('campaign', function ($value) {
        return Campaign::where('uid', $value)
            ->orWhere('id', $value)
            ->firstOrFail();
    });

    // Bind lists
    Route::bind('list', function ($value) {
        return MailList::where('uid', $value)
            ->orWhere('id', $value)
            ->firstOrFail();
    });

    // Bind subscribers
    Route::bind('subscriber', function ($value) {
        return Subscriber::where('uid', $value)
            ->orWhere('id', $value)
            ->firstOrFail();
    });
}
```

---

## Step 7: Add API Rate Limiting

**File:** `app/Providers/RouteServiceProvider.php`

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

protected function configureRateLimiting(): void
{
    // Standard API rate limit
    RateLimiter::for('mailing-api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    // Strict limit for public tracking endpoints
    RateLimiter::for('mailing-tracking', function (Request $request) {
        return Limit::perMinute(200)->by($request->ip());
    });

    // Webhook endpoints
    RateLimiter::for('mailing-webhooks', function (Request $request) {
        return Limit::perMinute(100)->by($request->ip());
    });
}
```

**Apply to routes in `api.php`:**

```php
Route::middleware(['api', 'auth:sanctum', 'throttle:mailing-api'])
    ->prefix('mailing')
    ->name('mailing.api.')
    ->group(function () {
        // API routes...
    });
```

---

## Step 8: Verify Routes

```bash
# List all mailing routes
php artisan route:list --path=mailing

# Count routes
php artisan route:list --path=mailing --json | jq '. | length'

# Check specific route
php artisan route:list --name=mailing.campaigns.send

# Cache routes (production only)
php artisan route:cache
```

---

## Step 9: Create Tests

```bash
# Feature tests for Web
php artisan make:test Modules/Mailing/Tests/Feature/CampaignTest
php artisan make:test Modules/Mailing/Tests/Feature/MailListTest
php artisan make:test Modules/Mailing/Tests/Feature/SubscriberTest

# Feature tests for API
php artisan make:test Modules/Mailing/Tests/Feature/Api/CampaignApiTest
php artisan make:test Modules/Mailing/Tests/Feature/Api/ListApiTest
php artisan make:test Modules/Mailing/Tests/Feature/Api/SubscriberApiTest

# Feature tests for Public
php artisan make:test Modules/Mailing/Tests/Feature/Public/TrackingTest
php artisan make:test Modules/Mailing/Tests/Feature/Public/UnsubscribeTest
```

**Example Test:**

```php
<?php

namespace Modules\Mailing\Tests\Feature;

use Tests\TestCase;
use Modules\Mailing\app\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_campaigns_list(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('mailing.campaigns.view');

        Campaign::factory()->count(5)->create();

        $response = $this->actingAs($user)
            ->get(route('mailing.campaigns.index'));

        $response->assertStatus(200);
        $response->assertSee('Campaigns');
    }

    public function test_user_can_create_campaign(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('mailing.campaigns.create');

        $response = $this->actingAs($user)
            ->post(route('mailing.campaigns.store'), [
                'name' => 'Test Campaign',
                'subject' => 'Test Subject',
                'from_name' => 'Test Sender',
                'from_email' => 'test@example.com',
                'list_id' => MailList::factory()->create()->id,
                'content' => '<p>Test content</p>',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('mailing_campaigns', [
            'name' => 'Test Campaign',
        ]);
    }
}
```

---

## Step 10: Documentation

### 10.1 Create Postman/Thunder Client Collection

Export API routes to a collection:

```json
{
  "info": {
    "name": "Mailing API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Campaigns",
      "item": [
        {
          "name": "List Campaigns",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/api/mailing/campaigns",
            "header": [
              {
                "key": "Authorization",
                "value": "Bearer {{token}}"
              },
              {
                "key": "Accept",
                "value": "application/json"
              }
            ]
          }
        }
      ]
    }
  ]
}
```

### 10.2 Create OpenAPI/Swagger Documentation

```yaml
openapi: 3.0.0
info:
  title: Mailing API
  version: 1.0.0
paths:
  /api/mailing/campaigns:
    get:
      summary: List campaigns
      security:
        - bearerAuth: []
      responses:
        '200':
          description: Successful response
```

---

## Troubleshooting

### Routes Not Found

```bash
# Clear all caches
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

# Verify service provider is registered
php artisan about
```

### Permission Denied

```bash
# Verify permissions exist
php artisan permission:show

# Assign permission to user
$user->givePermissionTo('mailing.campaigns.view');

# Or via role
$user->assignRole('mailing_manager');
```

### Controller Not Found

Verify namespace in routes matches controller location:
```php
// Route
use Modules\Mailing\app\Http\Controllers\Web\CampaignController;

// Controller file location
modules/Mailing/app/Http/Controllers/Web/CampaignController.php
```

---

## Performance Optimization

### 1. Route Caching (Production Only)

```bash
php artisan route:cache
```

### 2. Eager Loading in Controllers

```php
public function index()
{
    $campaigns = Campaign::with(['list', 'template'])
        ->latest()
        ->paginate(20);

    return view('mailing::campaigns.index', compact('campaigns'));
}
```

### 3. API Response Caching

```php
public function stats(Campaign $campaign)
{
    $stats = Cache::remember(
        "campaign.{$campaign->id}.stats",
        now()->addMinutes(5),
        fn() => $campaign->calculateStats()
    );

    return new StatsResource($stats);
}
```

---

## Next Steps

1. ✅ Routes created and documented
2. ✅ Permissions seeder exists
3. ⏳ Create all controllers (27 total)
4. ⏳ Create Form Requests (10+ classes)
5. ⏳ Create API Resources (8+ classes)
6. ⏳ Implement route model binding
7. ⏳ Add rate limiting
8. ⏳ Write feature tests
9. ⏳ Create API documentation
10. ⏳ Deploy to staging

---

## Additional Resources

- [Laravel Routing Documentation](https://laravel.com/docs/routing)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [API Resource Documentation](https://laravel.com/docs/eloquent-resources)

---

**Last Updated:** January 29, 2026
**Version:** 1.0.0
**Status:** Implementation Ready
