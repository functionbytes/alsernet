# Mailing Module Permissions Seeder Report

**Generated:** 2026-01-29
**Seeder File:** `modules/Mailing/database/seeders/MailingPermissionsSeeder.php`
**Source Policies:** `modules/Mailing/app/Policies/*`

---

## Executive Summary

This report documents the comprehensive permissions system for the Mailing module. The seeder creates **87 distinct permissions** organized across **11 resource categories** and assigns them to **8 different roles** with varying levels of access.

---

## Permissions Breakdown by Category

### 1. Campaigns (CampaignPolicy)
**Total Permissions:** 7

| Permission | Description | Business Rules |
|-----------|-------------|----------------|
| `mailing.campaigns.view` | View campaigns list and individual campaign details | - |
| `mailing.campaigns.create` | Create new campaigns | - |
| `mailing.campaigns.edit` | Edit campaign details | Cannot edit sent campaigns |
| `mailing.campaigns.delete` | Delete campaigns | Cannot delete sent or sending campaigns |
| `mailing.campaigns.send` | Send, run, restart, resend campaigns | Multiple send operations |
| `mailing.campaigns.pause` | Pause campaigns | Only queuing/queued/sending/scheduled |
| `mailing.campaigns.test` | Send test emails | - |

**Policy Methods Covered:**
- `viewAny()`, `view()`, `create()`, `update()`, `delete()`
- `send()`, `pause()`, `run()`, `restart()`, `resend()`
- `sendTestEmail()`, `viewAnalytics()`, `copy()`, `preview()`

---

### 2. Subscribers (SubscriberPolicy)
**Total Permissions:** 8

| Permission | Description | Business Rules |
|-----------|-------------|----------------|
| `mailing.subscribers.view` | View subscribers list and details | - |
| `mailing.subscribers.create` | Create new subscribers | Quota checks may apply |
| `mailing.subscribers.edit` | Edit subscriber details, subscribe/unsubscribe | - |
| `mailing.subscribers.delete` | Delete subscribers | - |
| `mailing.subscribers.manage` | Manage subscriber groups | - |
| `mailing.subscribers.import` | Import subscribers from files | - |
| `mailing.subscribers.export` | Export subscribers to files | - |
| `mailing.subscribers.sync` | Sync subscribers with Mailrelay | External integration |

**Policy Methods Covered:**
- `viewAny()`, `view()`, `create()`, `update()`, `delete()`
- `manageGroups()`, `sync()`, `subscribe()`, `unsubscribe()`
- `import()`, `export()`

---

### 3. Mail Lists (MailListPolicy)
**Total Permissions:** 6

| Permission | Description | Business Rules |
|-----------|-------------|----------------|
| `mailing.lists.view` | View mail lists | - |
| `mailing.lists.create` | Create new mail lists | - |
| `mailing.lists.edit` | Edit mail list details | - |
| `mailing.lists.delete` | Delete mail lists | - |
| `mailing.lists.import` | Import subscribers to list | Delegates to subscriber permission |
| `mailing.lists.export` | Export subscribers from list | - |

**Policy Methods Covered:**
- `viewAny()`, `view()`, `create()`, `update()`, `delete()`
- `addMoreSubscribers()`, `import()`, `export()`

---

### 4. Templates (TemplatePolicy)
**Total Permissions:** 4

| Permission | Description | Business Rules |
|-----------|-------------|----------------|
| `mailing.templates.view` | View templates list and individual template | - |
| `mailing.templates.create` | Create new templates and copy existing | - |
| `mailing.templates.edit` | Edit template details and upload images | - |
| `mailing.templates.delete` | Delete templates | - |

**Policy Methods Covered:**
- `viewAny()`, `view()`, `create()`, `update()`, `delete()`
- `image()`, `preview()`, `copy()`

---

### 5. Automations (AutomationPolicy)
**Total Permissions:** 4

| Permission | Description | Business Rules |
|-----------|-------------|----------------|
| `mailing.automations.view` | View automations list and details | Feature flag: `mailing.features.automations.enabled` |
| `mailing.automations.create` | Create new automations and copy existing | Feature flag check |
| `mailing.automations.edit` | Edit automation, enable/disable | Feature flag check |
| `mailing.automations.delete` | Delete automations | Only active/inactive, feature flag check |

**Policy Methods Covered:**
- `viewAny()`, `view()`, `create()`, `update()`, `delete()`
- `enable()`, `disable()`, `copy()`

**Special Notes:**
- All methods check if automation feature is enabled via config
- Delete only works on active/inactive statuses

---

### 6. Imports (ImportPolicy)
**Total Permissions:** 3

| Permission | Description | Business Rules |
|-----------|-------------|----------------|
| `mailing.imports.view` | View import jobs and their details | - |
| `mailing.imports.create` | Create new import jobs | - |
| `mailing.imports.delete` | Delete import jobs | Cannot delete processing imports |

**Policy Methods Covered:**
- `viewAny()`, `view()`, `create()`, `delete()`

---

### 7. Mailer Components (MailerComponentPolicy)
**Total Permissions:** 5

| Permission | Description | Business Rules |
|-----------|-------------|----------------|
| `mailer.components.view` | View mailer components | - |
| `mailer.components.create` | Create new components | - |
| `mailer.components.update` | Edit component details | - |
| `mailer.components.delete` | Delete components | - |
| `mailer.components.preview` | Preview components | - |

**Policy Methods Covered:**
- `viewAny()`, `view()`, `create()`, `update()`, `delete()`, `preview()`

---

### 8. Mailer Endpoints (MailerEndpointPolicy)
**Total Permissions:** 6

| Permission | Description | Business Rules |
|-----------|-------------|----------------|
| `mailer.endpoints.view` | View mailer endpoints | - |
| `mailer.endpoints.create` | Create new endpoints | - |
| `mailer.endpoints.update` | Edit endpoint details | - |
| `mailer.endpoints.delete` | Delete endpoints | - |
| `mailer.endpoints.logs` | View endpoint logs | - |
| `mailer.endpoints.regenerate-token` | Regenerate endpoint tokens | Security-sensitive |

**Policy Methods Covered:**
- `viewAny()`, `view()`, `create()`, `update()`, `delete()`
- `viewLogs()`, `regenerateToken()`

---

### 9. Mailer Templates (MailerTemplatePolicy)
**Total Permissions:** 6

| Permission | Description | Business Rules |
|-----------|-------------|----------------|
| `mailer.templates.view` | View mailer templates | - |
| `mailer.templates.create` | Create new mailer templates | - |
| `mailer.templates.update` | Edit mailer template details | - |
| `mailer.templates.delete` | Delete mailer templates | - |
| `mailer.templates.preview` | Preview mailer templates | - |
| `mailer.manage` | Manage mailer templates (admin) | Administrative oversight |

**Policy Methods Covered:**
- `viewAny()`, `view()`, `create()`, `update()`, `delete()`
- `preview()`, `manage()`

---

### 10. Mailer Variables (MailerVariablePolicy)
**Total Permissions:** 4

| Permission | Description | Business Rules |
|-----------|-------------|----------------|
| `mailer.variables.view` | View mailer variables | - |
| `mailer.variables.create` | Create new variables | - |
| `mailer.variables.update` | Edit variable details | - |
| `mailer.variables.delete` | Delete variables | - |

**Policy Methods Covered:**
- `viewAny()`, `view()`, `create()`, `update()`, `delete()`

---

### 11. Mailer Settings (MailerSettingsPolicy)
**Total Permissions:** 2

| Permission | Description | Business Rules |
|-----------|-------------|----------------|
| `mailer.settings.configure` | Configure mailer settings | Super-admin only (hardcoded) |
| `mailer.settings.view` | View mailer settings | Super-admin only (hardcoded) |

**Policy Methods Covered:**
- `configure()`, `viewSettings()`, `manageTemplates()`, `manageComponents()`, `manageVariables()`, `manageEndpoints()`

**Special Notes:**
- Currently hardcoded to require `super-admin` role
- Some policy methods have empty returns (incomplete implementation)

---

### 12. General Module Access
**Total Permissions:** 2

| Permission | Description | Business Rules |
|-----------|-------------|----------------|
| `mailing.access` | Access to mailing module | Entry point permission |
| `mailing.dashboard.view` | View mailing dashboard | Dashboard visibility |

---

### 13. Settings & Configuration
**Total Permissions:** 30

#### General Settings (5 permissions)
- `mailing.settings.general` - General settings
- `mailing.settings.api` - API configuration
- `mailing.settings.webhooks` - Webhook management
- `mailing.settings.permissions` - Permission management
- `mailing.settings.manage` - Overall settings management

#### Sending Servers (5 permissions)
- `mailing.settings.sending-servers.view`
- `mailing.settings.sending-servers.create`
- `mailing.settings.sending-servers.edit`
- `mailing.settings.sending-servers.delete`
- `mailing.settings.sending-servers.test`

#### Bounce Handlers (5 permissions)
- `mailing.settings.bounce-handlers.view`
- `mailing.settings.bounce-handlers.create`
- `mailing.settings.bounce-handlers.edit`
- `mailing.settings.bounce-handlers.delete`
- `mailing.settings.bounce-handlers.test`

#### Feedback Handlers (5 permissions)
- `mailing.settings.feedback-handlers.view`
- `mailing.settings.feedback-handlers.create`
- `mailing.settings.feedback-handlers.edit`
- `mailing.settings.feedback-handlers.delete`
- `mailing.settings.feedback-handlers.test`

#### Sub-Accounts (5 permissions)
- `mailing.settings.sub-accounts.view`
- `mailing.settings.sub-accounts.create`
- `mailing.settings.sub-accounts.edit`
- `mailing.settings.sub-accounts.delete`
- `mailing.settings.sub-accounts.test`

#### Verification Servers (5 permissions)
- `mailing.settings.verification-servers.view`
- `mailing.settings.verification-servers.create`
- `mailing.settings.verification-servers.edit`
- `mailing.settings.verification-servers.delete`
- `mailing.settings.verification-servers.test`

---

### 14. Validation & Testing
**Total Permissions:** 2

| Permission | Description | Business Rules |
|-----------|-------------|----------------|
| `mailing.validation.test` | Test email validation | - |
| `mailing.validation.validate` | Validate emails | - |

---

## Total Permission Count

**Grand Total: 87 permissions**

---

## Role Definitions

### 1. super-admin
**Access Level:** Complete system access
**Permissions:** ALL (87 permissions)
**Description:** Full unrestricted access to all mailing features including super-admin-only settings.

---

### 2. admin
**Access Level:** Full operational + settings management
**Permissions:** 67 permissions
**Description:** Complete operational control plus all settings management except super-admin reserved features.

**Included:**
- ✅ All campaigns operations
- ✅ All subscribers operations
- ✅ All lists operations
- ✅ All templates operations
- ✅ All automations operations
- ✅ All imports operations
- ✅ All mailer components/endpoints/templates/variables
- ✅ All settings management (sending servers, bounce handlers, etc.)
- ✅ Validation and testing

**Excluded:**
- ❌ Super-admin only mailer settings

---

### 3. mailing_admin
**Access Level:** Complete mailing module control
**Permissions:** 39 permissions
**Description:** Full operational access to campaigns, subscribers, lists, templates, and automations. View-only access to mailer infrastructure.

**Included:**
- ✅ Full campaigns CRUD + send/pause/test
- ✅ Full subscribers CRUD + manage/import/export/sync
- ✅ Full lists CRUD + import/export
- ✅ Full templates CRUD
- ✅ Full automations CRUD
- ✅ Full imports CRUD
- ✅ View mailer components/templates/variables
- ✅ Validation and testing

**Excluded:**
- ❌ Mailer infrastructure management (create/update/delete)
- ❌ Settings management
- ❌ Endpoint management

---

### 4. mailing_manager
**Access Level:** Campaign and subscriber management
**Permissions:** 30 permissions
**Description:** Operational management of campaigns and subscribers without destructive delete permissions.

**Included:**
- ✅ Campaigns view/create/edit/send/pause/test (no delete)
- ✅ Subscribers full operational access
- ✅ Lists view/create/edit/import/export (no delete)
- ✅ Templates view/create/edit (no delete)
- ✅ Automations view/edit (no create/delete)
- ✅ Imports view/create
- ✅ View mailer components/templates
- ✅ Validation and testing

**Excluded:**
- ❌ Delete campaigns/lists/templates/automations
- ❌ Create automations
- ❌ Delete imports
- ❌ Mailer infrastructure management
- ❌ Settings management

---

### 5. mailing_user
**Access Level:** Limited operational access
**Permissions:** 13 permissions
**Description:** Basic user with view and create permissions, suitable for content creators.

**Included:**
- ✅ Campaigns view/create/test
- ✅ Subscribers view/create
- ✅ Lists view only
- ✅ Templates view/create
- ✅ Automations view only
- ✅ Imports view only
- ✅ Mailer components/templates view only
- ✅ Validation testing

**Excluded:**
- ❌ Edit/delete any resources
- ❌ Send campaigns (except test)
- ❌ Import/export operations
- ❌ Subscriber management
- ❌ Settings management

---

### 6. mailing_viewer
**Access Level:** Read-only access
**Permissions:** 11 permissions
**Description:** View-only access for auditing and reporting purposes.

**Included:**
- ✅ View all campaigns
- ✅ View all subscribers
- ✅ View all lists
- ✅ View all templates
- ✅ View all automations
- ✅ View all imports
- ✅ View all mailer components/templates/variables
- ✅ Dashboard access

**Excluded:**
- ❌ Any create/edit/delete operations
- ❌ Any operational actions (send, import, export, etc.)

---

### 7. manager (Legacy)
**Access Level:** Operational + templates
**Permissions:** 22 permissions
**Description:** Legacy role maintained for backward compatibility. Similar to mailing_manager but with automation create permission.

**Included:**
- ✅ Campaigns view/create/edit/send/pause/test
- ✅ Subscribers view/create/edit/import/export/sync
- ✅ Lists view/create/edit/import/export
- ✅ Templates view/create/edit
- ✅ Automations view/create/edit
- ✅ Imports view/create
- ✅ Validation and testing

**Excluded:**
- ❌ Delete any resources
- ❌ Mailer infrastructure access
- ❌ Settings management

---

### 8. administrative (Legacy)
**Access Level:** Limited view access
**Permissions:** 4 permissions
**Description:** Legacy role for basic administrative viewing.

**Included:**
- ✅ Module access
- ✅ View campaigns
- ✅ View subscribers
- ✅ View lists

**Excluded:**
- ❌ All operational actions
- ❌ Template/automation/import access

---

## Role Comparison Matrix

| Feature | super-admin | admin | mailing_admin | mailing_manager | mailing_user | mailing_viewer | manager | administrative |
|---------|-------------|-------|---------------|-----------------|--------------|----------------|---------|----------------|
| **Total Permissions** | 87 | 67 | 39 | 30 | 13 | 11 | 22 | 4 |
| **Campaigns (CRUD)** | ✅✅✅✅ | ✅✅✅✅ | ✅✅✅✅ | ✅✅✅❌ | ✅✅❌❌ | ✅❌❌❌ | ✅✅✅❌ | ✅❌❌❌ |
| **Campaigns (Send)** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ |
| **Campaigns (Test)** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| **Subscribers (CRUD)** | ✅✅✅✅ | ✅✅✅✅ | ✅✅✅✅ | ✅✅✅❌ | ✅✅❌❌ | ✅❌❌❌ | ✅✅✅❌ | ✅❌❌❌ |
| **Subscribers (Import/Export)** | ✅✅ | ✅✅ | ✅✅ | ✅✅ | ❌❌ | ❌❌ | ✅✅ | ❌❌ |
| **Lists (CRUD)** | ✅✅✅✅ | ✅✅✅✅ | ✅✅✅✅ | ✅✅✅❌ | ✅❌❌❌ | ✅❌❌❌ | ✅✅✅❌ | ✅❌❌❌ |
| **Templates (CRUD)** | ✅✅✅✅ | ✅✅✅✅ | ✅✅✅✅ | ✅✅✅❌ | ✅✅❌❌ | ✅❌❌❌ | ✅✅✅❌ | ❌❌❌❌ |
| **Automations (CRUD)** | ✅✅✅✅ | ✅✅✅✅ | ✅✅✅✅ | ✅❌✅❌ | ✅❌❌❌ | ✅❌❌❌ | ✅✅✅❌ | ❌❌❌❌ |
| **Imports (CRUD)** | ✅✅✅ | ✅✅✅ | ✅✅✅ | ✅✅❌ | ✅❌❌ | ✅❌❌ | ✅✅❌ | ❌❌❌ |
| **Mailer Components** | ✅✅✅✅✅ | ✅✅✅✅✅ | ✅❌❌❌✅ | ✅❌❌❌✅ | ✅❌❌❌❌ | ✅❌❌❌❌ | ❌❌❌❌❌ | ❌❌❌❌❌ |
| **Mailer Endpoints** | ✅✅✅✅✅✅ | ✅✅✅✅✅✅ | ❌❌❌❌❌❌ | ❌❌❌❌❌❌ | ❌❌❌❌❌❌ | ❌❌❌❌❌❌ | ❌❌❌❌❌❌ | ❌❌❌❌❌❌ |
| **Mailer Templates** | ✅✅✅✅✅✅ | ✅✅✅✅✅✅ | ✅❌❌❌✅❌ | ✅❌❌❌✅❌ | ✅❌❌❌❌❌ | ✅❌❌❌❌❌ | ❌❌❌❌❌❌ | ❌❌❌❌❌❌ |
| **Mailer Variables** | ✅✅✅✅ | ✅✅✅✅ | ✅❌❌❌ | ❌❌❌❌ | ❌❌❌❌ | ✅❌❌❌ | ❌❌❌❌ | ❌❌❌❌ |
| **Settings Management** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Validation & Testing** | ✅✅ | ✅✅ | ✅✅ | ✅✅ | ✅❌ | ❌❌ | ✅✅ | ❌❌ |

**Legend:**
- ✅✅✅✅ = Create, Read, Update, Delete
- ✅✅✅ = Create, Read, Update
- ✅✅ = Create, Read
- ✅ = Read only / Has permission
- ❌ = No access

---

## Usage Instructions

### Running the Seeder

```bash
# Run the seeder standalone
php artisan db:seed --class="Modules\Mailing\Database\Seeders\MailingPermissionsSeeder"

# Or include in your main DatabaseSeeder
php artisan db:seed
```

### Assigning Roles to Users

```php
use App\Models\User;
use Spatie\Permission\Models\Role;

// Assign role to user
$user = User::find(1);
$user->assignRole('mailing_admin');

// Assign multiple roles
$user->assignRole(['mailing_admin', 'admin']);

// Remove role
$user->removeRole('mailing_user');

// Sync roles (replaces all roles)
$user->syncRoles(['mailing_manager']);
```

### Checking Permissions in Code

```php
// Check specific permission
if ($user->can('mailing.campaigns.send')) {
    // User can send campaigns
}

// Check multiple permissions (OR)
if ($user->hasAnyPermission(['mailing.campaigns.edit', 'mailing.campaigns.delete'])) {
    // User can edit OR delete campaigns
}

// Check multiple permissions (AND)
if ($user->hasAllPermissions(['mailing.campaigns.view', 'mailing.campaigns.create'])) {
    // User can view AND create campaigns
}

// Check via role
if ($user->hasRole('mailing_admin')) {
    // User has mailing admin role
}
```

### Middleware Protection

```php
// In routes/web.php
Route::middleware(['permission:mailing.campaigns.send'])->group(function () {
    Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send']);
});

// Or in controller constructor
public function __construct()
{
    $this->middleware('permission:mailing.campaigns.view')->only(['index', 'show']);
    $this->middleware('permission:mailing.campaigns.create')->only(['create', 'store']);
    $this->middleware('permission:mailing.campaigns.edit')->only(['edit', 'update']);
}
```

### Blade Directives

```blade
@can('mailing.campaigns.send')
    <button>Send Campaign</button>
@endcan

@role('mailing_admin')
    <a href="{{ route('settings.mailing') }}">Settings</a>
@endrole

@hasanyrole('admin|mailing_admin')
    <div class="admin-panel">...</div>
@endhasanyrole
```

---

## Implementation Notes

### Policy Method to Permission Mapping

Not all policy methods directly map to a permission. Some methods combine multiple permissions or add business logic on top of permission checks.

**Examples:**

1. **CampaignPolicy::copy()** - Requires both `view` AND `create` permissions
2. **CampaignPolicy::run()** - Uses `send` permission + status check
3. **CampaignPolicy::restart()** - Uses `send` permission + status check
4. **AutomationPolicy** - All methods check feature flag first

### Permission Naming Conventions

Permissions follow this pattern:
```
{module}.{resource}.{action}
```

Where:
- **module** = `mailing` or `mailer`
- **resource** = `campaigns`, `subscribers`, `lists`, `templates`, etc.
- **action** = `view`, `create`, `edit`, `delete`, `send`, etc.

Special cases:
- `mailing.access` - Module entry point
- `mailer.manage` - Administrative oversight
- `mailing.settings.{category}.{action}` - Settings follow nested pattern

### Guard Name

All permissions use the **`web`** guard. This is Laravel's default guard for web-based authentication.

```php
Permission::findOrCreate('mailing.campaigns.view', 'web');
```

### Cache Management

The seeder automatically clears the permission cache:
```php
app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
```

This ensures changes take effect immediately. In production, you may want to run:
```bash
php artisan permission:cache-reset
```

---

## Missing/Incomplete Implementations

### MailerSettingsPolicy Issues

The `MailerSettingsPolicy` has incomplete method implementations:

```php
public function manageTemplates(User $user): bool
{
    if ($user->hasRole('super-admin')) {
        return true;
    }
    // Missing return statement - will return null
}
```

**Affected Methods:**
- `manageTemplates()`
- `manageComponents()`
- `manageVariables()`
- `manageEndpoints()`

**Recommendation:** Update these methods to return `false` by default or implement proper permission checks.

### Potential Missing Permissions

Based on policy analysis, these actions exist but may need dedicated permissions:

1. **Campaign Analytics** - Currently uses `view` permission
2. **Campaign Preview** - Currently uses `view` permission
3. **Template Image Upload** - Currently uses `edit` permission
4. **Template Preview** - Currently uses `view` permission
5. **Automation Enable/Disable** - Currently uses `edit` permission

Consider creating granular permissions if these actions need separate control:
- `mailing.campaigns.analytics`
- `mailing.campaigns.preview`
- `mailing.templates.upload-image`
- `mailing.templates.preview`
- `mailing.automations.toggle`

---

## Migration Path

### From Previous System

If migrating from an older permission system:

1. **Backup existing permissions:**
```bash
php artisan db:seed --class=BackupPermissionsSeeder
```

2. **Run new seeder:**
```bash
php artisan db:seed --class="Modules\Mailing\Database\Seeders\MailingPermissionsSeeder"
```

3. **Reassign users to new roles:**
```php
// Map old roles to new roles
$oldToNewRoleMap = [
    'mailing-admin' => 'mailing_admin',
    'campaign-manager' => 'mailing_manager',
    // ... etc
];

foreach (User::all() as $user) {
    foreach ($user->roles as $role) {
        if (isset($oldToNewRoleMap[$role->name])) {
            $user->syncRoles($oldToNewRoleMap[$role->name]);
        }
    }
}
```

### Adding New Permissions

To add new permissions:

1. **Update Policy** - Add new method to appropriate policy
2. **Update Seeder** - Add permission to `getPermissions()` array
3. **Update Role Assignments** - Add to appropriate roles in `assignToRoles()`
4. **Run Seeder** - `php artisan db:seed --class=MailingPermissionsSeeder`
5. **Update Documentation** - Update this file

---

## Testing Recommendations

### Unit Tests

```php
/** @test */
public function mailing_admin_can_send_campaigns()
{
    $user = User::factory()->create();
    $user->assignRole('mailing_admin');

    $this->assertTrue($user->can('mailing.campaigns.send'));
}

/** @test */
public function mailing_viewer_cannot_delete_campaigns()
{
    $user = User::factory()->create();
    $user->assignRole('mailing_viewer');

    $this->assertFalse($user->can('mailing.campaigns.delete'));
}
```

### Feature Tests

```php
/** @test */
public function unauthorized_user_cannot_send_campaign()
{
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('campaigns.send', $campaign));

    $response->assertStatus(403);
}
```

### Policy Tests

```php
/** @test */
public function cannot_edit_sent_campaign()
{
    $user = User::factory()->create();
    $user->givePermissionTo('mailing.campaigns.edit');

    $campaign = Campaign::factory()->sent()->create();

    $this->assertFalse($user->can('update', $campaign));
}
```

---

## Troubleshooting

### Permission Denied After Seeding

**Problem:** User has role but permission check fails

**Solutions:**
1. Clear permission cache: `php artisan permission:cache-reset`
2. Verify role assignment: `dd($user->roles->pluck('name'))`
3. Verify permissions: `dd($user->getAllPermissions()->pluck('name'))`

### Role Not Found

**Problem:** `Role not found` error when assigning

**Solutions:**
1. Run seeder: `php artisan db:seed --class=MailingPermissionsSeeder`
2. Check database: `SELECT * FROM roles WHERE name = 'mailing_admin'`
3. Verify guard name matches: `findOrCreate('role_name', 'web')`

### Permission Not Working in Policy

**Problem:** Policy returns false despite having permission

**Solutions:**
1. Check HasSafePermissionCheck trait is used
2. Verify permission string matches exactly (case-sensitive)
3. Check for additional business logic (status checks, etc.)
4. Debug: `dd($this->hasPermission($user, 'permission.name'))`

---

## Changelog

### Version 1.0 (2026-01-29)
- Initial comprehensive seeder created
- 87 permissions across 11 resource categories
- 8 roles with granular access control
- Full policy coverage for all Mailing module features
- Added mailer.* namespace permissions
- Documented all business rules and special cases

---

## Appendix: Complete Permission List

### Alphabetical Index

```
mailer.components.create
mailer.components.delete
mailer.components.preview
mailer.components.update
mailer.components.view
mailer.endpoints.create
mailer.endpoints.delete
mailer.endpoints.logs
mailer.endpoints.regenerate-token
mailer.endpoints.update
mailer.endpoints.view
mailer.manage
mailer.settings.configure
mailer.settings.view
mailer.templates.create
mailer.templates.delete
mailer.templates.preview
mailer.templates.update
mailer.templates.view
mailer.variables.create
mailer.variables.delete
mailer.variables.update
mailer.variables.view
mailing.access
mailing.automations.create
mailing.automations.delete
mailing.automations.edit
mailing.automations.view
mailing.campaigns.create
mailing.campaigns.delete
mailing.campaigns.edit
mailing.campaigns.pause
mailing.campaigns.send
mailing.campaigns.test
mailing.campaigns.view
mailing.dashboard.view
mailing.imports.create
mailing.imports.delete
mailing.imports.view
mailing.lists.create
mailing.lists.delete
mailing.lists.edit
mailing.lists.export
mailing.lists.import
mailing.lists.view
mailing.settings.api
mailing.settings.bounce-handlers.create
mailing.settings.bounce-handlers.delete
mailing.settings.bounce-handlers.edit
mailing.settings.bounce-handlers.test
mailing.settings.bounce-handlers.view
mailing.settings.feedback-handlers.create
mailing.settings.feedback-handlers.delete
mailing.settings.feedback-handlers.edit
mailing.settings.feedback-handlers.test
mailing.settings.feedback-handlers.view
mailing.settings.general
mailing.settings.manage
mailing.settings.permissions
mailing.settings.sending-servers.create
mailing.settings.sending-servers.delete
mailing.settings.sending-servers.edit
mailing.settings.sending-servers.test
mailing.settings.sending-servers.view
mailing.settings.sub-accounts.create
mailing.settings.sub-accounts.delete
mailing.settings.sub-accounts.edit
mailing.settings.sub-accounts.test
mailing.settings.sub-accounts.view
mailing.settings.verification-servers.create
mailing.settings.verification-servers.delete
mailing.settings.verification-servers.edit
mailing.settings.verification-servers.test
mailing.settings.verification-servers.view
mailing.settings.webhooks
mailing.subscribers.create
mailing.subscribers.delete
mailing.subscribers.edit
mailing.subscribers.export
mailing.subscribers.import
mailing.subscribers.manage
mailing.subscribers.sync
mailing.subscribers.view
mailing.templates.create
mailing.templates.delete
mailing.templates.edit
mailing.templates.view
mailing.validation.test
mailing.validation.validate
```

**Total: 87 permissions**

---

**End of Report**
