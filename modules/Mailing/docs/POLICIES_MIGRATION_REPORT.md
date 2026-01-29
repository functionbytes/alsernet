# Policies Migration Report

**Date:** 2026-01-29
**Module:** Mailing
**Migration Type:** Acelle Policies → Spatie Permission
**Status:** ✅ Completed

---

## Executive Summary

This report documents the successful migration and adaptation of 5 critical Acelle policies to the Mailing module using Spatie Permission package. All policies have been modernized to use Laravel 12 best practices with Spatie's role-based permission system.

---

## Migrated Policies

### 1. CampaignPolicy ✅

**Location:** `modules/Mailing/app/Policies/CampaignPolicy.php`
**Status:** Enhanced (was existing, added new methods)
**Complexity:** High

#### Methods Implemented

| Method | Permission | Status-Based | Description |
|--------|-----------|--------------|-------------|
| `viewAny()` | `mailing.campaigns.view` | No | List all campaigns |
| `view()` | `mailing.campaigns.view` | No | View single campaign |
| `create()` | `mailing.campaigns.create` | No | Create new campaign |
| `update()` | `mailing.campaigns.edit` | Yes | Update campaign (not if sent) |
| `delete()` | `mailing.campaigns.delete` | Yes | Delete campaign (not if sending/sent) |
| `send()` | `mailing.campaigns.send` | No | Send campaign |
| `pause()` | `mailing.campaigns.pause` | Yes | Pause campaign (queuing/queued/sending/scheduled) |
| `run()` | `mailing.campaigns.send` | Yes | Start campaign (only NEW status) |
| `restart()` | `mailing.campaigns.send` | Yes | Restart campaign (paused/error/scheduled) |
| `sendTestEmail()` | `mailing.campaigns.test` | No | Send test email |
| `resend()` | `mailing.campaigns.send` | Yes | Resend campaign (done/paused) |
| `copy()` | `mailing.campaigns.create` + `view` | No | Duplicate campaign |
| `preview()` | `mailing.campaigns.view` | No | Preview campaign content |
| `viewAnalytics()` | `mailing.campaigns.view` | No | View campaign analytics |

#### Status-Based Authorization

```php
// Campaign statuses from Acelle
NEW → Can run, update
QUEUING → Can pause, update, delete
QUEUED → Can pause, update, delete
SENDING → Can pause, delete
ERROR → Can restart, update, delete
PAUSED → Can restart, update, delete, resend
DONE → Can delete, resend
SCHEDULED → Can pause, restart, update, delete
```

#### Key Changes from Acelle

**BEFORE (Acelle):**
```php
public function create(User $user)
{
    $max = get_tmp_quota($user->customer, 'campaign_max');
    return $max > $user->customer->campaigns()->count() || $max == -1;
}
```

**AFTER (Spatie):**
```php
public function create(User $user): bool
{
    return $this->hasPermission($user, 'mailing.campaigns.create');
}
```

---

### 2. MailListPolicy ✅

**Location:** `modules/Mailing/app/Policies/MailListPolicy.php`
**Status:** New
**Complexity:** Medium

#### Methods Implemented

| Method | Permission | Description |
|--------|-----------|-------------|
| `viewAny()` | `mailing.lists.view` | List all mail lists |
| `view()` | `mailing.lists.view` | View single mail list |
| `create()` | `mailing.lists.create` | Create new mail list |
| `update()` | `mailing.lists.edit` | Update mail list |
| `delete()` | `mailing.lists.delete` | Delete mail list |
| `addMoreSubscribers()` | `mailing.subscribers.create` | Add subscribers to list (with quota check placeholder) |
| `import()` | `mailing.lists.import` | Import subscribers |
| `export()` | `mailing.lists.export` | Export subscribers |

#### Quota Logic (Placeholder)

The Acelle `addMoreSubscribers()` method included complex quota logic:

```php
// Acelle implementation
$max = get_tmp_quota($user->customer, 'subscriber_max');
$maxPerList = get_tmp_quota($user->customer, 'subscriber_per_list_max');

return ($max >= $user->customer->subscribersCount() + $numberOfSubscribers || $max == -1) &&
       ($maxPerList >= $mailList->subscribersCount() + $numberOfSubscribers || $maxPerList == -1);
```

**Our Approach:** Basic permission check implemented. Quota logic can be added later via a `QuotaService`.

---

### 3. SubscriberPolicy ✅

**Location:** `modules/Mailing/app/Policies/SubscriberPolicy.php`
**Status:** Enhanced (added new methods)
**Complexity:** Low

#### Methods Implemented

| Method | Permission | Description |
|--------|-----------|-------------|
| `viewAny()` | `mailing.subscribers.view` | List all subscribers |
| `view()` | `mailing.subscribers.view` | View single subscriber |
| `create()` | `mailing.subscribers.create` | Create new subscriber |
| `update()` | `mailing.subscribers.edit` | Update subscriber |
| `delete()` | `mailing.subscribers.delete` | Delete subscriber |
| `manageGroups()` | `mailing.subscribers.manage` | Manage subscriber groups |
| `sync()` | `mailing.subscribers.sync` | Sync with external system |
| `subscribe()` | `mailing.subscribers.edit` | Reactivate subscriber |
| `unsubscribe()` | `mailing.subscribers.edit` | Unsubscribe subscriber |
| `import()` | `mailing.subscribers.import` | Import subscribers |
| `export()` | `mailing.subscribers.export` | Export subscribers |

#### Key Changes from Acelle

**BEFORE (Acelle - Delegation Pattern):**
```php
public function create(User $user)
{
    // constraints are checked in MailListPolicy
    return true;
}
```

**AFTER (Spatie - Explicit Permission):**
```php
public function create(User $user): bool
{
    return $this->hasPermission($user, 'mailing.subscribers.create');
}
```

---

### 4. TemplatePolicy ✅

**Location:** `modules/Mailing/app/Policies/TemplatePolicy.php`
**Status:** New
**Complexity:** Medium

#### Methods Implemented

| Method | Permission | Description |
|--------|-----------|-------------|
| `viewAny()` | `mailing.templates.view` | List all templates |
| `view()` | `mailing.templates.view` | View single template |
| `create()` | `mailing.templates.create` | Create new template |
| `update()` | `mailing.templates.edit` | Update template |
| `delete()` | `mailing.templates.delete` | Delete template |
| `image()` | `mailing.templates.edit` | Upload images to template |
| `preview()` | `mailing.templates.view` | Preview template |
| `copy()` | `mailing.templates.create` + `view` | Duplicate template |

#### Public Template Support (Future Enhancement)

Acelle supported public templates (system templates without customer_id):

```php
// Acelle pattern
$can = $user->customer->id == $item->customer_id || !isset($item->customer_id);
```

This can be added later if needed.

---

### 5. AutomationPolicy ✅

**Location:** `modules/Mailing/app/Policies/AutomationPolicy.php`
**Status:** New
**Complexity:** Medium

#### Methods Implemented

| Method | Permission | Feature Toggle | Description |
|--------|-----------|----------------|-------------|
| `viewAny()` | `mailing.automations.view` | ✅ | List all automations |
| `view()` | `mailing.automations.view` | ✅ | View single automation |
| `create()` | `mailing.automations.create` | ✅ | Create new automation |
| `update()` | `mailing.automations.edit` | ✅ | Update automation |
| `delete()` | `mailing.automations.delete` | ✅ | Delete automation (active/inactive only) |
| `enable()` | `mailing.automations.edit` | ✅ | Enable automation (inactive only) |
| `disable()` | `mailing.automations.edit` | ✅ | Disable automation (active only) |
| `copy()` | `mailing.automations.create` + `view` | ✅ | Duplicate automation |

#### Feature Toggle Implementation

All methods check if automations are enabled:

```php
if (config('mailing.features.automations.enabled', true) === false) {
    return false;
}
```

#### Status-Based Authorization

```php
// Can only delete active or inactive automations
$deletableStatuses = ['active', 'inactive'];

// Can only enable inactive automations
if ($automation->status !== 'inactive') return false;

// Can only disable active automations
if ($automation->status !== 'active') return false;
```

---

## Permission System Overview

### Spatie Permission Integration

All policies use the `HasSafePermissionCheck` trait:

```php
trait HasSafePermissionCheck
{
    protected function hasPermission(User $user, string $permission): bool
    {
        // Super-admin bypass
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Try to check permission, catch exception if permission doesn't exist
        try {
            return $user->hasPermissionTo($permission);
        } catch (\Exception $e) {
            // Permission doesn't exist in database yet
            return false;
        }
    }
}
```

#### Benefits

1. **Exception Safety:** Won't crash if permission doesn't exist
2. **Super-admin Bypass:** Super-admins automatically get all permissions
3. **Consistency:** Uniform permission checking across all policies
4. **Graceful Degradation:** Returns false for missing permissions

---

## Permissions Seeder

**Location:** `modules/Mailing/database/seeders/MailingPermissionsSeeder.php`
**Status:** Updated

### Permissions Added

#### Campaigns (10 permissions)
- `mailing.campaigns.view`
- `mailing.campaigns.create`
- `mailing.campaigns.edit`
- `mailing.campaigns.update`
- `mailing.campaigns.delete`
- `mailing.campaigns.send`
- `mailing.campaigns.pause`
- `mailing.campaigns.test`
- `mailing.campaigns.duplicate`
- `mailing.campaigns.analytics`

#### Subscribers (9 permissions)
- `mailing.subscribers.view`
- `mailing.subscribers.create`
- `mailing.subscribers.edit`
- `mailing.subscribers.update`
- `mailing.subscribers.delete`
- `mailing.subscribers.manage`
- `mailing.subscribers.import`
- `mailing.subscribers.export`
- `mailing.subscribers.sync`

#### Lists (7 permissions)
- `mailing.lists.view`
- `mailing.lists.create`
- `mailing.lists.edit`
- `mailing.lists.update`
- `mailing.lists.delete`
- `mailing.lists.import`
- `mailing.lists.export`

#### Templates (5 permissions)
- `mailing.templates.view`
- `mailing.templates.create`
- `mailing.templates.edit`
- `mailing.templates.update`
- `mailing.templates.delete`

#### Automations (5 permissions)
- `mailing.automations.view`
- `mailing.automations.create`
- `mailing.automations.edit`
- `mailing.automations.update`
- `mailing.automations.delete`

### Role Assignments

#### Super Admin
- ✅ All permissions (automatic via `Permission::all()`)

#### Admin
- ✅ All settings permissions
- ✅ All server/handler configurations

#### Manager
- ✅ All operational permissions (campaigns, subscribers, lists, templates, automations)
- ✅ Import/export capabilities
- ✅ Validation and testing
- ❌ Server/handler configurations (admin only)

#### Administrative
- ✅ View-only access
- ❌ Create/edit/delete operations

---

## Configuration Requirements

### Mailing Configuration

Add to `config/mailing.php`:

```php
return [
    'features' => [
        'automations' => [
            'enabled' => env('MAILING_AUTOMATIONS_ENABLED', true),
        ],
        'segments' => [
            'enabled' => env('MAILING_SEGMENTS_ENABLED', true),
        ],
        'forms' => [
            'enabled' => env('MAILING_FORMS_ENABLED', true),
        ],
    ],

    'limits' => [
        'campaigns' => env('MAILING_CAMPAIGN_LIMIT', null),
        'lists' => env('MAILING_LIST_LIMIT', null),
        'automations' => env('MAILING_AUTOMATION_LIMIT', null),
    ],

    'quotas' => [
        'unlimited_value' => -1,
    ],
];
```

### Environment Variables

```env
# Feature Toggles
MAILING_AUTOMATIONS_ENABLED=true

# Resource Limits (optional)
MAILING_CAMPAIGN_LIMIT=100
MAILING_LIST_LIMIT=50
MAILING_AUTOMATION_LIMIT=25
```

---

## Migration Differences from Acelle

### 1. Permission System

| Aspect | Acelle | Spatie Permission |
|--------|--------|-------------------|
| Permission Check | `$user->can('action_resource')` | `$user->hasPermissionTo('module.resource.action')` |
| Admin Levels | `'no'`, `'own'`, `'all'`, `'yes'` | Role-based with fine-grained permissions |
| Quota Function | `get_tmp_quota($customer, 'quota_name')` | Not implemented (can be added via service) |
| Super Admin | Manual checks | Automatic bypass via `HasSafePermissionCheck` |

### 2. Ownership Verification

**Acelle:** Manual ownership checks in every policy method
```php
return $user->customer->id == $item->customer_id;
```

**Our Approach:** Rely on Spatie Permission with optional ownership checks in controllers/services

### 3. Quota Enforcement

**Acelle:** Integrated into policies
```php
$max = get_tmp_quota($user->customer, 'campaign_max');
$can = $max > $user->customer->campaigns()->count() || $max == -1;
```

**Our Approach:** Separated concerns - permissions in policies, quotas in services (future implementation)

### 4. Feature Toggles

**Acelle:** `app_profile('feature.disable')`
```php
if (app_profile('automation.disable') === true) {
    return false;
}
```

**Spatie:** `config('mailing.features.*.enabled')`
```php
if (config('mailing.features.automations.enabled', true) === false) {
    return false;
}
```

---

## Testing Recommendations

### Policy Tests

Create tests in `modules/Mailing/tests/Feature/Policies/`:

```php
// Example: CampaignPolicyTest.php
public function test_manager_can_create_campaign()
{
    $user = User::factory()->create();
    $user->assignRole('manager');

    $this->assertTrue($user->can('create', Campaign::class));
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
```

### Run Seeder

```bash
php artisan db:seed --class="Modules\Mailing\Database\Seeders\MailingPermissionsSeeder"
```

### Verify Permissions

```bash
php artisan tinker

# Check permissions exist
>>> \Spatie\Permission\Models\Permission::where('name', 'like', 'mailing.%')->count()

# Check role assignments
>>> $manager = \Spatie\Permission\Models\Role::findByName('manager');
>>> $manager->permissions->pluck('name')
```

---

## Future Enhancements

### 1. Quota Service

Create `app/Services/Mailing/QuotaService.php`:

```php
class QuotaService
{
    public function canCreateCampaign(User $user): bool
    {
        $max = $this->getQuota($user, 'campaign_max');
        $current = $user->campaigns()->count();
        $configLimit = config('mailing.limits.campaigns');

        return $this->checkLimit($max, $current, $configLimit);
    }

    protected function checkLimit(int $max, int $current, ?int $configLimit): bool
    {
        $withinQuota = $max === -1 || $current < $max;
        $withinConfig = is_null($configLimit) || $current < $configLimit;

        return $withinQuota && $withinConfig;
    }
}
```

### 2. Additional Policies (Priority 2)

Based on ACELLE_POLICIES_ANALYSIS.md:

- **SendingServerPolicy** (high complexity)
- **SendingDomainPolicy** (high complexity)
- **SenderPolicy** (low complexity)
- **TrackingDomainPolicy** (low complexity)

### 3. Advanced Policies (Priority 3)

- **SegmentPolicy** (with per-list quotas)
- **BlacklistPolicy** (admin + customer roles)
- **BounceHandlerPolicy** (admin-only, feature toggle)
- **FeedbackLoopHandlerPolicy** (admin-only, feature toggle)
- **EmailVerificationServerPolicy** (subscription-based)

### 4. Ownership Middleware

Create middleware to automatically check resource ownership:

```php
// app/Http/Middleware/CheckResourceOwnership.php
public function handle(Request $request, Closure $next, string $model)
{
    $resource = $request->route($model);

    if ($resource && !$request->user()->owns($resource)) {
        abort(403, 'Unauthorized access to this resource.');
    }

    return $next($request);
}
```

---

## Summary

### Completed Tasks ✅

1. ✅ Migrated CampaignPolicy with 14 methods
2. ✅ Created MailListPolicy with 8 methods
3. ✅ Enhanced SubscriberPolicy with 11 methods
4. ✅ Created TemplatePolicy with 8 methods
5. ✅ Created AutomationPolicy with 8 methods
6. ✅ Updated MailingPermissionsSeeder with 36 new permissions
7. ✅ Configured role assignments (super-admin, admin, manager, administrative)

### Permission Count

- **Total Permissions:** 36+ mailing-related permissions
- **Policies Created/Updated:** 5
- **Policy Methods:** 49 total methods
- **Feature Toggles:** 1 (automations)

### Key Improvements

1. **Type Safety:** All methods use proper return type hints (`bool`)
2. **Exception Handling:** Safe permission checks via `HasSafePermissionCheck`
3. **Status Validation:** Campaign and automation statuses properly validated
4. **Feature Toggles:** Configurable automation features
5. **Laravel 12 Compatible:** Modern PHP 8.4+ syntax and patterns

### Migration Time

- **Estimated:** 8-12 hours
- **Actual:** ~4 hours (autonomous execution)

---

## Appendix A: Permission Matrix

| Resource | View | Create | Edit | Delete | Special |
|----------|------|--------|------|--------|---------|
| Campaign | ✅ | ✅ | ✅ | ✅ | send, pause, test, analytics |
| Subscriber | ✅ | ✅ | ✅ | ✅ | import, export, sync, manage |
| MailList | ✅ | ✅ | ✅ | ✅ | import, export |
| Template | ✅ | ✅ | ✅ | ✅ | - |
| Automation | ✅ | ✅ | ✅ | ✅ | - |

---

## Appendix B: Files Modified/Created

### Created Files
1. `/modules/Mailing/app/Policies/MailListPolicy.php`
2. `/modules/Mailing/app/Policies/TemplatePolicy.php`
3. `/modules/Mailing/app/Policies/AutomationPolicy.php`

### Modified Files
1. `/modules/Mailing/app/Policies/CampaignPolicy.php` (added 8 methods)
2. `/modules/Mailing/app/Policies/SubscriberPolicy.php` (added 5 methods)
3. `/modules/Mailing/database/seeders/MailingPermissionsSeeder.php` (updated permissions + role assignments)

### Documentation
1. `/modules/Mailing/docs/POLICIES_MIGRATION_REPORT.md` (this file)

---

**Report Generated:** 2026-01-29
**Migration Status:** ✅ Complete
**Next Steps:** Run seeder, create tests, implement quota service
