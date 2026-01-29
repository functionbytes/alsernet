# Mailing Routes Quick Reference

## File Overview

| File | Routes | Purpose | Middleware |
|------|--------|---------|------------|
| `web.php` | 200+ | Customer-facing UI | `['web', 'auth']` |
| `api.php` | 80+ | RESTful API | `['api', 'auth:sanctum']` |
| `public.php` | 25+ | Tracking & Public Forms | None |
| `admin.php` | 60+ | Administration | `['web', 'auth']` |

**Total Routes:** 365+

---

## URL Structure Examples

### Web Routes (Customer UI)
```
https://domain.com/mailing/dashboard
https://domain.com/mailing/campaigns
https://domain.com/mailing/campaigns/123/edit
https://domain.com/mailing/campaigns/123/send
https://domain.com/mailing/lists
https://domain.com/mailing/lists/456/subscribers
https://domain.com/mailing/automations
https://domain.com/mailing/settings
https://domain.com/mailing/settings/sending-servers
```

### API Routes
```
https://domain.com/api/mailing/campaigns
https://domain.com/api/mailing/campaigns/123
https://domain.com/api/mailing/campaigns/123/send
https://domain.com/api/mailing/lists
https://domain.com/api/mailing/lists/456/subscribers
https://domain.com/api/mailing/stats/dashboard
```

### Public Routes (No Auth)
```
https://domain.com/mailing/public/t/{message_id}
https://domain.com/mailing/public/c/{link_id}
https://domain.com/mailing/public/unsubscribe/{message_id}
https://domain.com/mailing/public/web/{message_id}
https://domain.com/mailing/public/subscribe/{form_uid}
https://domain.com/mailing/webhooks/ses/bounce
```

### Admin Routes
```
https://domain.com/mailing/admin/settings
https://domain.com/mailing/admin/users
https://domain.com/mailing/admin/plans
https://domain.com/mailing/admin/system
https://domain.com/mailing/admin/logs
https://domain.com/mailing/admin/reports
https://domain.com/mailing/admin/backup
```

---

## Route Naming Convention

All routes follow this pattern: `mailing.{group}.{action}`

### Examples
```php
// Web routes
route('mailing.dashboard')
route('mailing.campaigns.index')
route('mailing.campaigns.create')
route('mailing.campaigns.store')
route('mailing.campaigns.show', $campaign)
route('mailing.campaigns.edit', $campaign)
route('mailing.campaigns.update', $campaign)
route('mailing.campaigns.destroy', $campaign)
route('mailing.campaigns.send', $campaign)

// API routes
route('mailing.api.campaigns.index')
route('mailing.api.campaigns.show', $campaign)
route('mailing.api.campaigns.stats', $campaign)

// Public routes
route('mailing.public.tracking.open', $messageId)
route('mailing.public.unsubscribe.show', $messageId)
route('mailing.public.subscribe.show', $formUid)

// Admin routes
route('mailing.admin.settings.index')
route('mailing.admin.users.index')
route('mailing.admin.logs.email')
```

---

## Resource Controllers

All main resources follow standard RESTful patterns:

### Campaigns
| Method | URI | Name | Action |
|--------|-----|------|--------|
| GET | `/mailing/campaigns` | campaigns.index | index |
| GET | `/mailing/campaigns/create` | campaigns.create | create |
| POST | `/mailing/campaigns` | campaigns.store | store |
| GET | `/mailing/campaigns/{campaign}` | campaigns.show | show |
| GET | `/mailing/campaigns/{campaign}/edit` | campaigns.edit | edit |
| PUT/PATCH | `/mailing/campaigns/{campaign}` | campaigns.update | update |
| DELETE | `/mailing/campaigns/{campaign}` | campaigns.destroy | destroy |

### Lists
| Method | URI | Name | Action |
|--------|-----|------|--------|
| GET | `/mailing/lists` | lists.index | index |
| GET | `/mailing/lists/create` | lists.create | create |
| POST | `/mailing/lists` | lists.store | store |
| GET | `/mailing/lists/{list}` | lists.show | show |
| GET | `/mailing/lists/{list}/edit` | lists.edit | edit |
| PUT/PATCH | `/mailing/lists/{list}` | lists.update | update |
| DELETE | `/mailing/lists/{list}` | lists.destroy | destroy |

### Subscribers (nested under lists)
| Method | URI | Name | Action |
|--------|-----|------|--------|
| GET | `/mailing/lists/{list}/subscribers` | subscribers.index | index |
| GET | `/mailing/lists/{list}/subscribers/create` | subscribers.create | create |
| POST | `/mailing/lists/{list}/subscribers` | subscribers.store | store |
| GET | `/mailing/lists/{list}/subscribers/{subscriber}` | subscribers.show | show |
| GET | `/mailing/lists/{list}/subscribers/{subscriber}/edit` | subscribers.edit | edit |
| PUT/PATCH | `/mailing/lists/{list}/subscribers/{subscriber}` | subscribers.update | update |
| DELETE | `/mailing/lists/{list}/subscribers/{subscriber}` | subscribers.destroy | destroy |

---

## Permission Middleware Examples

```php
// View campaigns
Route::get('/campaigns', [CampaignController::class, 'index'])
    ->middleware('permission:mailing.campaigns.view');

// Create campaign
Route::post('/campaigns', [CampaignController::class, 'store'])
    ->middleware('permission:mailing.campaigns.create');

// Send campaign (special permission)
Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])
    ->middleware('permission:mailing.campaigns.send');

// Admin only
Route::get('/admin/users', [UsersController::class, 'index'])
    ->middleware('permission:mailing.admin.users.view');
```

---

## API Authentication Example

```php
// Using Sanctum token
$response = Http::withToken($apiToken)
    ->get('https://domain.com/api/mailing/campaigns');

// Creating token for user
$token = $user->createToken('mailing-api')->plainTextToken;

// Headers
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

---

## Controller Generation Commands

### Web Controllers
```bash
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/CampaignController --resource
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/DashboardController
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/MailListController --resource
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/SubscriberController --resource
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/SegmentController --resource
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/AutomationController --resource
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/FormController --resource
php artisan make:controller Modules/Mailing/app/Http/Controllers/Web/BlacklistController
```

### API Controllers
```bash
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/CampaignController --api
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/ListController --api
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/SubscriberController --api
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/TemplateController --api
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/AutomationController --api
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/WebhookController --api
php artisan make:controller Modules/Mailing/app/Http/Controllers/Api/StatsController
```

### Public Controllers
```bash
php artisan make:controller Modules/Mailing/app/Http/Controllers/Public/TrackingController
php artisan make:controller Modules/Mailing/app/Http/Controllers/Public/UnsubscribeController
php artisan make:controller Modules/Mailing/app/Http/Controllers/Public/SubscribeController
php artisan make:controller Modules/Mailing/app/Http/Controllers/Public/WebVersionController
php artisan make:controller Modules/Mailing/app/Http/Controllers/Public/UpdateProfileController
```

### Admin Controllers
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

## Form Request Generation

```bash
php artisan make:request Modules/Mailing/app/Http/Requests/StoreCampaignRequest
php artisan make:request Modules/Mailing/app/Http/Requests/UpdateCampaignRequest
php artisan make:request Modules/Mailing/app/Http/Requests/StoreMailListRequest
php artisan make:request Modules/Mailing/app/Http/Requests/UpdateMailListRequest
php artisan make:request Modules/Mailing/app/Http/Requests/StoreSubscriberRequest
php artisan make:request Modules/Mailing/app/Http/Requests/UpdateSubscriberRequest
php artisan make:request Modules/Mailing/app/Http/Requests/StoreSegmentRequest
php artisan make:request Modules/Mailing/app/Http/Requests/UpdateSegmentRequest
php artisan make:request Modules/Mailing/app/Http/Requests/StoreAutomationRequest
php artisan make:request Modules/Mailing/app/Http/Requests/UpdateAutomationRequest
```

---

## API Resource Generation

```bash
php artisan make:resource Modules/Mailing/app/Http/Resources/CampaignResource
php artisan make:resource Modules/Mailing/app/Http/Resources/CampaignCollection
php artisan make:resource Modules/Mailing/app/Http/Resources/MailListResource
php artisan make:resource Modules/Mailing/app/Http/Resources/MailListCollection
php artisan make:resource Modules/Mailing/app/Http/Resources/SubscriberResource
php artisan make:resource Modules/Mailing/app/Http/Resources/SubscriberCollection
php artisan make:resource Modules/Mailing/app/Http/Resources/TemplateResource
php artisan make:resource Modules/Mailing/app/Http/Resources/AutomationResource
```

---

## Testing Commands

```bash
# List all mailing routes
php artisan route:list --path=mailing

# List API routes only
php artisan route:list --path=api/mailing

# Search for specific route
php artisan route:list --path=mailing --name=campaigns

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Test route exists
php artisan route:list | grep "mailing.campaigns.send"
```

---

## Common Route Patterns

### Dashboard
```php
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
```

### Resource with custom actions
```php
Route::prefix('campaigns')->name('campaigns.')->group(function () {
    Route::resource('', CampaignController::class)->parameters(['' => 'campaign']);
    Route::post('/{campaign}/send', [CampaignController::class, 'send'])->name('send');
    Route::post('/{campaign}/pause', [CampaignController::class, 'pause'])->name('pause');
    Route::post('/{campaign}/resume', [CampaignController::class, 'resume'])->name('resume');
});
```

### Nested resources
```php
Route::prefix('lists/{list}')->group(function () {
    Route::resource('subscribers', SubscriberController::class);
    Route::resource('segments', SegmentController::class);
});
```

### Bulk actions
```php
Route::post('/bulk-delete', [SubscriberController::class, 'bulkDelete'])->name('bulk-delete');
Route::post('/bulk-subscribe', [SubscriberController::class, 'bulkSubscribe'])->name('bulk-subscribe');
Route::post('/import', [SubscriberController::class, 'import'])->name('import');
```

---

## Webhook Signature Verification (To Implement)

```php
// In TrackingController
public function sesBounce(Request $request)
{
    // Verify SNS signature
    $message = json_decode($request->getContent(), true);

    if (!$this->verifySnsSignature($message)) {
        abort(403, 'Invalid signature');
    }

    // Process bounce...
}
```

---

## Rate Limiting (To Implement)

```php
// In api.php
Route::middleware(['throttle:60,1']) // 60 requests per minute
    ->prefix('mailing')
    ->group(function () {
        // API routes...
    });

// Custom rate limiter in RouteServiceProvider
RateLimiter::for('mailing-api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

---

## Route Model Binding (To Implement)

```php
// In RouteServiceProvider or boot method
Route::bind('campaign', function ($value) {
    return \Modules\Mailing\app\Models\Campaign::where('uid', $value)
        ->orWhere('id', $value)
        ->firstOrFail();
});
```

---

## Quick Checklist for Implementation

- [ ] Create all 30+ controllers
- [ ] Implement 60+ permissions in seeder
- [ ] Create Form Request classes (20+)
- [ ] Create API Resources (10+)
- [ ] Implement route model binding
- [ ] Add rate limiting to API
- [ ] Implement webhook signature verification
- [ ] Write feature tests for all routes
- [ ] Document API in Postman/OpenAPI
- [ ] Add route comments for documentation
- [ ] Optimize with route caching
- [ ] Add logging for sensitive actions

---

**Last Updated:** January 29, 2026
**Author:** Claude Code Agent
