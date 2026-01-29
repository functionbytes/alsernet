# Mailing Module Permissions - Quick Reference

**Last Updated:** 2026-01-29

## Quick Stats

- **Total Permissions:** 87
- **Total Roles:** 8
- **Resource Categories:** 14
- **Seeder:** `modules/Mailing/database/seeders/MailingPermissionsSeeder.php`

---

## Roles at a Glance

| Role | Permissions | Best For |
|------|-------------|----------|
| **super-admin** | 87 | System administrators |
| **admin** | 67 | Mailing platform administrators |
| **mailing_admin** | 39 | Dedicated mailing managers |
| **mailing_manager** | 30 | Campaign managers |
| **mailing_user** | 13 | Content creators |
| **mailing_viewer** | 11 | Auditors, reporters |
| **manager** (legacy) | 22 | Legacy operational role |
| **administrative** (legacy) | 4 | Legacy view-only role |

---

## Permission Categories

### Core Operations (39 permissions)
- Campaigns: 7 permissions
- Subscribers: 8 permissions
- Mail Lists: 6 permissions
- Templates: 4 permissions
- Automations: 4 permissions
- Imports: 3 permissions
- Validation: 2 permissions
- Module Access: 2 permissions

### Mailer Infrastructure (23 permissions)
- Components: 5 permissions
- Endpoints: 6 permissions
- Templates: 6 permissions
- Variables: 4 permissions
- Settings: 2 permissions

### System Settings (30 permissions)
- General Settings: 5 permissions
- Sending Servers: 5 permissions
- Bounce Handlers: 5 permissions
- Feedback Handlers: 5 permissions
- Sub-Accounts: 5 permissions
- Verification Servers: 5 permissions

---

## Common Use Cases

### Assigning Roles

```php
// Single role
$user->assignRole('mailing_admin');

// Multiple roles
$user->assignRole(['mailing_manager', 'admin']);

// Sync (replace all)
$user->syncRoles(['mailing_viewer']);
```

### Checking Permissions

```php
// In controller
if ($user->can('mailing.campaigns.send')) {
    // Allow send
}

// In policy
return $this->hasPermission($user, 'mailing.campaigns.view');

// In Blade
@can('mailing.campaigns.create')
    <button>Create Campaign</button>
@endcan
```

### Route Protection

```php
Route::middleware(['permission:mailing.campaigns.send'])
    ->post('/campaigns/{campaign}/send', [CampaignController::class, 'send']);
```

---

## Role Capabilities Comparison

### Feature Access Matrix

| Feature | mailing_admin | mailing_manager | mailing_user | mailing_viewer |
|---------|---------------|-----------------|--------------|----------------|
| Send Campaigns | ✅ | ✅ | ❌ | ❌ |
| Create Campaigns | ✅ | ✅ | ✅ | ❌ |
| Delete Campaigns | ✅ | ❌ | ❌ | ❌ |
| Manage Subscribers | ✅ | ✅ | ❌ | ❌ |
| Import/Export | ✅ | ✅ | ❌ | ❌ |
| Manage Automations | ✅ | Edit only | View only | View only |
| Mailer Components | View only | View only | View only | View only |
| Settings | ❌ | ❌ | ❌ | ❌ |

---

## Permission Namespaces

### mailing.* (Core module)
```
mailing.access
mailing.dashboard.view
mailing.campaigns.*
mailing.subscribers.*
mailing.lists.*
mailing.templates.*
mailing.automations.*
mailing.imports.*
mailing.settings.*
mailing.validation.*
```

### mailer.* (Infrastructure)
```
mailer.components.*
mailer.endpoints.*
mailer.templates.*
mailer.variables.*
mailer.settings.*
mailer.manage
```

---

## Special Business Rules

### Campaigns
- Cannot edit sent campaigns (even with `edit` permission)
- Cannot delete sent/sending campaigns (even with `delete` permission)
- `send` permission covers: send, run, restart, resend
- `pause` permission only works on queuing/queued/sending/scheduled campaigns

### Automations
- All permissions check feature flag: `mailing.features.automations.enabled`
- Can only delete active/inactive automations

### Imports
- Cannot delete processing imports (even with `delete` permission)

### Mailer Settings
- Currently hardcoded to super-admin role only
- Regular permission checks not implemented

---

## Running the Seeder

```bash
# Standalone
php artisan db:seed --class="Modules\Mailing\Database\Seeders\MailingPermissionsSeeder"

# Clear cache after changes
php artisan permission:cache-reset
```

---

## Troubleshooting

### Permission Denied
1. Clear cache: `php artisan permission:cache-reset`
2. Check role: `dd($user->roles->pluck('name'))`
3. Check permissions: `dd($user->getAllPermissions()->pluck('name'))`

### Role Not Found
1. Run seeder: `php artisan db:seed --class=MailingPermissionsSeeder`
2. Verify guard: All permissions use `web` guard

---

## For Detailed Information

See: [PERMISSIONS_SEEDER_REPORT.md](./PERMISSIONS_SEEDER_REPORT.md)

- Complete permission list (87 permissions)
- Detailed role breakdowns
- Policy method mappings
- Migration guides
- Testing recommendations
