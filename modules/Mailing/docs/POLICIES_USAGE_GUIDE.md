# Policies Usage Guide

**Module:** Mailing
**Date:** 2026-01-29

This guide provides practical examples of using the migrated policies in controllers, routes, and Blade views.

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Controller Examples](#controller-examples)
3. [Route Protection](#route-protection)
4. [Blade Directives](#blade-directives)
5. [Policy Methods Reference](#policy-methods-reference)
6. [Testing Examples](#testing-examples)

---

## Quick Start

### Run Permissions Seeder

```bash
php artisan db:seed --class="Modules\Mailing\Database\Seeders\MailingPermissionsSeeder"
```

### Assign Permissions to Users

```php
use Spatie\Permission\Models\Role;

// Assign role to user
$user->assignRole('manager');

// Or assign specific permissions
$user->givePermissionTo('mailing.campaigns.create');

// Check permissions
if ($user->hasPermissionTo('mailing.campaigns.send')) {
    // User can send campaigns
}
```

---

## Controller Examples

### CampaignController

```php
<?php

namespace Modules\Mailing\Http\Controllers;

use Modules\Mailing\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Display a listing of campaigns
     */
    public function index()
    {
        // Check if user can view campaigns
        $this->authorize('viewAny', Campaign::class);

        $campaigns = Campaign::latest()->paginate(20);

        return view('mailing::campaigns.index', compact('campaigns'));
    }

    /**
     * Show the form for creating a new campaign
     */
    public function create()
    {
        $this->authorize('create', Campaign::class);

        return view('mailing::campaigns.create');
    }

    /**
     * Store a newly created campaign
     */
    public function store(Request $request)
    {
        $this->authorize('create', Campaign::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $campaign = Campaign::create($validated);

        return redirect()
            ->route('mailing.campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully');
    }

    /**
     * Update the specified campaign
     */
    public function update(Request $request, Campaign $campaign)
    {
        // Will check if campaign is sent (auto-denied in policy)
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $campaign->update($validated);

        return redirect()
            ->route('mailing.campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully');
    }

    /**
     * Send the campaign
     */
    public function send(Campaign $campaign)
    {
        $this->authorize('run', $campaign);

        // Campaign send logic...

        return redirect()
            ->route('mailing.campaigns.show', $campaign)
            ->with('success', 'Campaign sent successfully');
    }

    /**
     * Pause the campaign
     */
    public function pause(Campaign $campaign)
    {
        $this->authorize('pause', $campaign);

        // Pause logic...

        return redirect()
            ->route('mailing.campaigns.show', $campaign)
            ->with('success', 'Campaign paused');
    }

    /**
     * Restart the campaign
     */
    public function restart(Campaign $campaign)
    {
        $this->authorize('restart', $campaign);

        // Restart logic...

        return redirect()
            ->route('mailing.campaigns.show', $campaign)
            ->with('success', 'Campaign restarted');
    }

    /**
     * Delete the campaign
     */
    public function destroy(Campaign $campaign)
    {
        // Will check if campaign is sending/sent (auto-denied in policy)
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()
            ->route('mailing.campaigns.index')
            ->with('success', 'Campaign deleted successfully');
    }
}
```

### SubscriberController

```php
<?php

namespace Modules\Mailing\Http\Controllers;

use Modules\Mailing\Models\Subscriber;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Subscriber::class);

        $subscribers = Subscriber::latest()->paginate(50);

        return view('mailing::subscribers.index', compact('subscribers'));
    }

    public function create()
    {
        $this->authorize('create', Subscriber::class);

        return view('mailing::subscribers.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Subscriber::class);

        $validated = $request->validate([
            'email' => 'required|email|unique:subscribers',
            'name' => 'nullable|string|max:255',
        ]);

        $subscriber = Subscriber::create($validated);

        return redirect()
            ->route('mailing.subscribers.index')
            ->with('success', 'Subscriber created successfully');
    }

    public function unsubscribe(Subscriber $subscriber)
    {
        $this->authorize('unsubscribe', $subscriber);

        $subscriber->update(['status' => 'unsubscribed']);

        return back()->with('success', 'Subscriber unsubscribed');
    }

    public function subscribe(Subscriber $subscriber)
    {
        $this->authorize('subscribe', $subscriber);

        $subscriber->update(['status' => 'subscribed']);

        return back()->with('success', 'Subscriber reactivated');
    }
}
```

### TemplateController

```php
<?php

namespace Modules\Mailing\Http\Controllers;

use Modules\Mailing\Models\Template;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Template::class);

        $templates = Template::latest()->paginate(20);

        return view('mailing::templates.index', compact('templates'));
    }

    public function create()
    {
        $this->authorize('create', Template::class);

        return view('mailing::templates.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Template::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $template = Template::create($validated);

        return redirect()
            ->route('mailing.templates.show', $template)
            ->with('success', 'Template created successfully');
    }

    public function copy(Template $template)
    {
        $this->authorize('copy', $template);

        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->save();

        return redirect()
            ->route('mailing.templates.edit', $newTemplate)
            ->with('success', 'Template copied successfully');
    }

    public function preview(Template $template)
    {
        $this->authorize('preview', $template);

        return view('mailing::templates.preview', compact('template'));
    }
}
```

---

## Route Protection

### Using Middleware

```php
// routes/web.php or modules/Mailing/routes/web.php

use Modules\Mailing\Models\Campaign;
use Modules\Mailing\Models\Subscriber;
use Modules\Mailing\Models\Template;
use Modules\Mailing\Models\Automation;

Route::middleware(['auth'])->prefix('mailing')->name('mailing.')->group(function () {

    // Campaigns
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/send', [CampaignController::class, 'send'])
        ->name('campaigns.send')
        ->can('run', 'campaign');

    Route::post('campaigns/{campaign}/pause', [CampaignController::class, 'pause'])
        ->name('campaigns.pause')
        ->can('pause', 'campaign');

    Route::post('campaigns/{campaign}/restart', [CampaignController::class, 'restart'])
        ->name('campaigns.restart')
        ->can('restart', 'campaign');

    // Subscribers
    Route::resource('subscribers', SubscriberController::class);
    Route::post('subscribers/{subscriber}/unsubscribe', [SubscriberController::class, 'unsubscribe'])
        ->name('subscribers.unsubscribe')
        ->can('unsubscribe', 'subscriber');

    // Templates
    Route::resource('templates', TemplateController::class);
    Route::post('templates/{template}/copy', [TemplateController::class, 'copy'])
        ->name('templates.copy')
        ->can('copy', 'template');

    // Automations
    Route::resource('automations', AutomationController::class);
    Route::post('automations/{automation}/enable', [AutomationController::class, 'enable'])
        ->name('automations.enable')
        ->can('enable', 'automation');

    Route::post('automations/{automation}/disable', [AutomationController::class, 'disable'])
        ->name('automations.disable')
        ->can('disable', 'automation');
});
```

### Using Gate Checks

```php
// In controller or service

if (Gate::allows('create', Campaign::class)) {
    // User can create campaigns
}

if (Gate::denies('delete', $campaign)) {
    abort(403, 'You cannot delete this campaign');
}

// Using helper
if (auth()->user()->can('send', $campaign)) {
    // User can send this campaign
}
```

---

## Blade Directives

### Campaign Actions

```blade
{{-- campaigns/show.blade.php --}}

<div class="campaign-actions">
    @can('update', $campaign)
        <a href="{{ route('mailing.campaigns.edit', $campaign) }}" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
    @endcan

    @can('run', $campaign)
        <form action="{{ route('mailing.campaigns.send', $campaign) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success">
                <i class="fas fa-paper-plane"></i> Send
            </button>
        </form>
    @endcan

    @can('pause', $campaign)
        <form action="{{ route('mailing.campaigns.pause', $campaign) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-pause"></i> Pause
            </button>
        </form>
    @endcan

    @can('restart', $campaign)
        <form action="{{ route('mailing.campaigns.restart', $campaign) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-info">
                <i class="fas fa-play"></i> Restart
            </button>
        </form>
    @endcan

    @can('delete', $campaign)
        <form action="{{ route('mailing.campaigns.destroy', $campaign) }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">
                <i class="fas fa-trash"></i> Delete
            </button>
        </form>
    @endcan

    @can('copy', $campaign)
        <a href="{{ route('mailing.campaigns.copy', $campaign) }}" class="btn btn-secondary">
            <i class="fas fa-copy"></i> Duplicate
        </a>
    @endcan

    @can('viewAnalytics', $campaign)
        <a href="{{ route('mailing.campaigns.analytics', $campaign) }}" class="btn btn-info">
            <i class="fas fa-chart-line"></i> Analytics
        </a>
    @endcan
</div>
```

### List/Index View

```blade
{{-- campaigns/index.blade.php --}}

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Campaigns</h5>

        @can('create', \Modules\Mailing\Models\Campaign::class)
            <a href="{{ route('mailing.campaigns.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Campaign
            </a>
        @endcan
    </div>

    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $campaign)
                    <tr>
                        <td>{{ $campaign->name }}</td>
                        <td>{{ $campaign->status }}</td>
                        <td>{{ $campaign->created_at->format('Y-m-d') }}</td>
                        <td>
                            @can('view', $campaign)
                                <a href="{{ route('mailing.campaigns.show', $campaign) }}">View</a>
                            @endcan

                            @can('update', $campaign)
                                <a href="{{ route('mailing.campaigns.edit', $campaign) }}">Edit</a>
                            @endcan

                            @can('delete', $campaign)
                                <form action="{{ route('mailing.campaigns.destroy', $campaign) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
```

### Subscriber Actions

```blade
{{-- subscribers/show.blade.php --}}

@can('update', $subscriber)
    <a href="{{ route('mailing.subscribers.edit', $subscriber) }}" class="btn btn-primary">
        Edit Subscriber
    </a>
@endcan

@if($subscriber->status === 'subscribed')
    @can('unsubscribe', $subscriber)
        <form action="{{ route('mailing.subscribers.unsubscribe', $subscriber) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning">Unsubscribe</button>
        </form>
    @endcan
@else
    @can('subscribe', $subscriber)
        <form action="{{ route('mailing.subscribers.subscribe', $subscriber) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success">Reactivate</button>
        </form>
    @endcan
@endif

@can('delete', $subscriber)
    <form action="{{ route('mailing.subscribers.destroy', $subscriber) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
@endcan
```

### Automation Actions

```blade
{{-- automations/index.blade.php --}}

@can('viewAny', \Modules\Mailing\Models\Automation::class)
    <div class="automations-list">
        @foreach($automations as $automation)
            <div class="automation-card">
                <h4>{{ $automation->name }}</h4>

                @if($automation->status === 'inactive')
                    @can('enable', $automation)
                        <form action="{{ route('mailing.automations.enable', $automation) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success">Enable</button>
                        </form>
                    @endcan
                @else
                    @can('disable', $automation)
                        <form action="{{ route('mailing.automations.disable', $automation) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-warning">Disable</button>
                        </form>
                    @endcan
                @endif

                @can('copy', $automation)
                    <a href="{{ route('mailing.automations.copy', $automation) }}">Duplicate</a>
                @endcan

                @can('delete', $automation)
                    <form action="{{ route('mailing.automations.destroy', $automation) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                @endcan
            </div>
        @endforeach
    </div>
@else
    <p>Automations are currently disabled.</p>
@endcan
```

---

## Policy Methods Reference

### CampaignPolicy

| Method | Permission | Description |
|--------|-----------|-------------|
| `viewAny()` | `mailing.campaigns.view` | List campaigns |
| `view()` | `mailing.campaigns.view` | View single campaign |
| `create()` | `mailing.campaigns.create` | Create campaign |
| `update()` | `mailing.campaigns.edit` | Update campaign (not if sent) |
| `delete()` | `mailing.campaigns.delete` | Delete campaign (not if sending/sent) |
| `send()` | `mailing.campaigns.send` | Send campaign |
| `pause()` | `mailing.campaigns.pause` | Pause campaign |
| `run()` | `mailing.campaigns.send` | Start campaign (NEW status only) |
| `restart()` | `mailing.campaigns.send` | Restart campaign |
| `sendTestEmail()` | `mailing.campaigns.test` | Send test email |
| `resend()` | `mailing.campaigns.send` | Resend campaign |
| `copy()` | `mailing.campaigns.create` + `view` | Duplicate campaign |
| `preview()` | `mailing.campaigns.view` | Preview campaign |
| `viewAnalytics()` | `mailing.campaigns.view` | View analytics |

### SubscriberPolicy

| Method | Permission | Description |
|--------|-----------|-------------|
| `viewAny()` | `mailing.subscribers.view` | List subscribers |
| `view()` | `mailing.subscribers.view` | View single subscriber |
| `create()` | `mailing.subscribers.create` | Create subscriber |
| `update()` | `mailing.subscribers.edit` | Update subscriber |
| `delete()` | `mailing.subscribers.delete` | Delete subscriber |
| `manageGroups()` | `mailing.subscribers.manage` | Manage groups |
| `sync()` | `mailing.subscribers.sync` | Sync with external |
| `subscribe()` | `mailing.subscribers.edit` | Reactivate subscriber |
| `unsubscribe()` | `mailing.subscribers.edit` | Unsubscribe subscriber |
| `import()` | `mailing.subscribers.import` | Import subscribers |
| `export()` | `mailing.subscribers.export` | Export subscribers |

### MailListPolicy

| Method | Permission | Description |
|--------|-----------|-------------|
| `viewAny()` | `mailing.lists.view` | List mail lists |
| `view()` | `mailing.lists.view` | View single list |
| `create()` | `mailing.lists.create` | Create list |
| `update()` | `mailing.lists.edit` | Update list |
| `delete()` | `mailing.lists.delete` | Delete list |
| `addMoreSubscribers()` | `mailing.subscribers.create` | Add subscribers |
| `import()` | `mailing.lists.import` | Import subscribers |
| `export()` | `mailing.lists.export` | Export subscribers |

### TemplatePolicy

| Method | Permission | Description |
|--------|-----------|-------------|
| `viewAny()` | `mailing.templates.view` | List templates |
| `view()` | `mailing.templates.view` | View single template |
| `create()` | `mailing.templates.create` | Create template |
| `update()` | `mailing.templates.edit` | Update template |
| `delete()` | `mailing.templates.delete` | Delete template |
| `image()` | `mailing.templates.edit` | Upload images |
| `preview()` | `mailing.templates.view` | Preview template |
| `copy()` | `mailing.templates.create` + `view` | Duplicate template |

### AutomationPolicy

| Method | Permission | Feature Check | Description |
|--------|-----------|---------------|-------------|
| `viewAny()` | `mailing.automations.view` | ✅ | List automations |
| `view()` | `mailing.automations.view` | ✅ | View single automation |
| `create()` | `mailing.automations.create` | ✅ | Create automation |
| `update()` | `mailing.automations.edit` | ✅ | Update automation |
| `delete()` | `mailing.automations.delete` | ✅ | Delete automation |
| `enable()` | `mailing.automations.edit` | ✅ | Enable automation |
| `disable()` | `mailing.automations.edit` | ✅ | Disable automation |
| `copy()` | `mailing.automations.create` + `view` | ✅ | Duplicate automation |

---

## Testing Examples

### Feature Tests

```php
<?php

namespace Modules\Mailing\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Mailing\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CampaignPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_campaign()
    {
        $user = User::factory()->create();
        $user->assignRole('manager');

        $this->assertTrue($user->can('create', Campaign::class));
    }

    public function test_administrative_cannot_create_campaign()
    {
        $user = User::factory()->create();
        $user->assignRole('administrative');

        $this->assertFalse($user->can('create', Campaign::class));
    }

    public function test_cannot_update_sent_campaign()
    {
        $user = User::factory()->create();
        $user->assignRole('manager');

        $campaign = Campaign::factory()->sent()->create();

        $this->assertFalse($user->can('update', $campaign));
    }

    public function test_can_only_pause_queued_campaign()
    {
        $user = User::factory()->create();
        $user->assignRole('manager');

        $campaign = Campaign::factory()->queued()->create();

        $this->assertTrue($user->can('pause', $campaign));
    }

    public function test_super_admin_bypasses_all_checks()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $campaign = Campaign::factory()->sent()->create();

        // Super-admin can do anything
        $this->assertTrue($user->can('update', $campaign));
        $this->assertTrue($user->can('delete', $campaign));
    }
}
```

---

## Environment Configuration

Add to your `.env`:

```env
# Feature Toggles
MAILING_AUTOMATIONS_ENABLED=true
MAILING_SEGMENTS_ENABLED=true
MAILING_FORMS_ENABLED=true

# Resource Limits (optional, null = no limit)
MAILING_CAMPAIGN_LIMIT=100
MAILING_LIST_LIMIT=50
MAILING_AUTOMATION_LIMIT=25
```

---

## Common Patterns

### Check Multiple Permissions

```php
// In controller
if ($user->canAny(['create', 'update'], Campaign::class)) {
    // User can create OR update campaigns
}

// In Blade
@canany(['update', 'delete'], $campaign)
    <div class="admin-actions">...</div>
@endcanany
```

### Permission Inheritance

```php
// Super-admin automatically has all permissions
$superAdmin->hasRole('super-admin'); // true
$superAdmin->can('mailing.campaigns.create'); // true (automatic)

// Regular roles need explicit permissions
$manager->hasRole('manager'); // true
$manager->can('mailing.campaigns.create'); // true (from seeder)
```

### Custom Permission Checks

```php
// Direct permission check
if ($user->hasPermissionTo('mailing.campaigns.send')) {
    // User has explicit permission
}

// Role check
if ($user->hasRole('manager')) {
    // User has manager role
}

// Multiple roles
if ($user->hasAnyRole(['manager', 'admin'])) {
    // User has at least one of these roles
}
```

---

**Last Updated:** 2026-01-29
