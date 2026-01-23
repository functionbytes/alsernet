# Mailrelay Permissions - Standard Spatie Pattern

## Architecture (Simple & Standard)

```
Spatie Permission (Standard)
├── Role (super-admin, manager, user)
├── Permission (mailrelay.campaigns.view, mailrelay.subscribers.create, etc.)
└── RoleHasPermission (pivot)

User Model
├── hasRole()  [from Spatie]
├── hasPermissionTo()  [from Spatie]
└── can()  [Laravel native + Spatie]
```

**NO custom entities, NO ValidatorGroups**

---

## Permission Naming Convention

```
mailrelay.{resource}.{action}

Examples:
- mailrelay.campaigns.view
- mailrelay.campaigns.create
- mailrelay.campaigns.update
- mailrelay.campaigns.delete
- mailrelay.subscribers.view
- mailrelay.subscribers.manage
- mailrelay.imports.create
- mailrelay.settings.manage
```

---

## Implementation Steps

### 1. Create Seeder

File: `modules/Mailrelay/database/seeders/MailrelayPermissionsSeeder.php`

```php
<?php

namespace Modules\Mailrelay\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MailrelayPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = $this->getPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Assign to roles
        $this->assignToRoles();
    }

    private function getPermissions(): array
    {
        return [
            // Campaigns
            'mailrelay.campaigns.view',
            'mailrelay.campaigns.create',
            'mailrelay.campaigns.update',
            'mailrelay.campaigns.delete',
            'mailrelay.campaigns.send',
            'mailrelay.campaigns.duplicate',
            'mailrelay.campaigns.analytics',

            // Subscribers
            'mailrelay.subscribers.view',
            'mailrelay.subscribers.create',
            'mailrelay.subscribers.update',
            'mailrelay.subscribers.delete',
            'mailrelay.subscribers.manage',
            'mailrelay.subscribers.import',
            'mailrelay.subscribers.export',

            // Imports
            'mailrelay.imports.create',
            'mailrelay.imports.view',
            'mailrelay.imports.process',
            'mailrelay.imports.delete',

            // Lists
            'mailrelay.lists.view',
            'mailrelay.lists.create',
            'mailrelay.lists.update',
            'mailrelay.lists.delete',

            // Validation
            'mailrelay.validation.test',
            'mailrelay.validation.validate',

            // Settings (Admin only)
            'mailrelay.settings.general',
            'mailrelay.settings.api',
            'mailrelay.settings.templates',
            'mailrelay.settings.groups',
            'mailrelay.settings.custom-fields',
            'mailrelay.settings.automations',
            'mailrelay.settings.webhooks',
            'mailrelay.settings.permissions',
            'mailrelay.settings.manage',

            // Dashboard
            'mailrelay.access',
            'mailrelay.dashboard.view',
        ];
    }

    private function assignToRoles(): void
    {
        // Super admin - all permissions
        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $superAdmin->givePermissionTo(Permission::all());

        // Manager - operational + templates
        $manager = Role::findOrCreate('manager', 'web');
        $manager->givePermissionTo([
            'mailrelay.access',
            'mailrelay.dashboard.view',
            'mailrelay.campaigns.view',
            'mailrelay.campaigns.create',
            'mailrelay.campaigns.update',
            'mailrelay.campaigns.send',
            'mailrelay.campaigns.duplicate',
            'mailrelay.campaigns.analytics',
            'mailrelay.subscribers.view',
            'mailrelay.subscribers.create',
            'mailrelay.subscribers.update',
            'mailrelay.subscribers.import',
            'mailrelay.subscribers.export',
            'mailrelay.imports.create',
            'mailrelay.imports.view',
            'mailrelay.imports.process',
            'mailrelay.lists.view',
            'mailrelay.lists.create',
            'mailrelay.lists.update',
            'mailrelay.validation.test',
            'mailrelay.validation.validate',
            'mailrelay.settings.templates',
        ]);

        // Administrative - limited access
        $administrative = Role::findOrCreate('administrative', 'web');
        $administrative->givePermissionTo([
            'mailrelay.access',
            'mailrelay.campaigns.view',
            'mailrelay.subscribers.view',
            'mailrelay.lists.view',
        ]);
    }
}
```

### 2. Update MailrelayServiceProvider

Register Gates dynamically:

```php
protected function registerGates(): void
{
    // Dynamic gate handler for all mailrelay permissions
    Gate::before(function ($user, $ability) {
        // Super-admin bypass
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Check if permission exists
        if (str_starts_with($ability, 'mailrelay.')) {
            return $user->hasPermissionTo($ability);
        }

        return null; // Let other gates handle
    });
}
```

### 3. Usage in Controllers

**Before authorization:**
```php
Gate::authorize('mailrelay.campaigns.view');
```

**In views:**
```blade
@can('mailrelay.campaigns.create')
    <button>Create Campaign</button>
@endcan
```

**In middleware:**
```php
Route::middleware('can:mailrelay.campaigns.create')
    ->post('campaigns', [CampaignController::class, 'store']);
```

**In PHP:**
```php
if (auth()->user()->can('mailrelay.campaigns.delete')) {
    // Delete logic
}

if (auth()->user()->hasPermissionTo('mailrelay.settings.manage')) {
    // Settings logic
}
```

---

## Comparison: Document vs Mailrelay

| Aspect | Document Module | Mailrelay Module |
|--------|-----------------|------------------|
| **Permission Table** | DocumentPermission (custom) | permission (Spatie standard) |
| **Group Table** | DocumentValidatorGroup | ❌ None (no groups) |
| **Permission Names** | approve-documents, upload-files | mailrelay.campaigns.view |
| **User Permissions** | Through ValidatorGroups | Direct role/permission |
| **Gate Pattern** | Gate::before() + Gate::define() | Gate::before() only |
| **Caching** | Manual Redis cache | Spatie built-in cache |
| **Complexity** | High (teams/groups) | Low (simple RBAC) |

---

## Benefits of Standard Spatie Pattern for Mailrelay

1. **Simplicity** - No custom entities, no extra migrations
2. **Standard** - Matches Laravel conventions and other modules (Role)
3. **Maintenance** - Uses proven Spatie package
4. **Performance** - Spatie handles caching
5. **Integration** - Works with Laravel's native `can()` helper
6. **Consistency** - Same as Role module

---

## Testing Permissions

```php
// Check permission
auth()->user()->hasPermissionTo('mailrelay.campaigns.create')

// Check role
auth()->user()->hasRole('manager')

// Check if can perform action
auth()->user()->can('mailrelay.campaigns.view')

// In tinker
User::find(1)->givePermissionTo('mailrelay.campaigns.create')
Role::findOrCreate('custom-role')->givePermissionTo('mailrelay.campaigns.view')
```

