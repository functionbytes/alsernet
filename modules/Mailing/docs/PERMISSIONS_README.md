# Mailing Module Permissions Documentation

**Complete permissions system for the Mailing module based on Spatie Permission**

---

## Quick Navigation

### 📖 Documentation Files

| File | Pages | Purpose | Audience |
|------|-------|---------|----------|
| **[PERMISSIONS_QUICK_REFERENCE.md](./PERMISSIONS_QUICK_REFERENCE.md)** | 5 | Fast lookup, common tasks | All developers |
| **[PERMISSIONS_SEEDER_REPORT.md](./PERMISSIONS_SEEDER_REPORT.md)** | 37 | Complete reference | Architects, leads |
| **[PERMISSIONS_ROLE_HIERARCHY.md](./PERMISSIONS_ROLE_HIERARCHY.md)** | 12 | Visual guides, role selection | Product managers, admins |
| **[PERMISSIONS_IMPLEMENTATION_SUMMARY.md](./PERMISSIONS_IMPLEMENTATION_SUMMARY.md)** | 8 | Technical overview | DevOps, architects |

---

## At a Glance

### System Overview

```
87 Permissions → 8 Roles → Policy-Based Authorization
```

### Roles Available

```
super-admin (87)    → Complete system control
admin (67)          → Platform management
mailing_admin (39)  → Full mailing operations
mailing_manager (30) → Campaign management
mailing_user (13)   → Content creation
mailing_viewer (11) → Read-only access
manager (22)        → Legacy operational
administrative (4)  → Legacy viewer
```

### Permission Categories

```
Core Operations (39)
├── Campaigns (7)
├── Subscribers (8)
├── Lists (6)
├── Templates (4)
├── Automations (4)
├── Imports (3)
├── Validation (2)
└── Access (2)

Mailer Infrastructure (28)
├── Components (5)
├── Endpoints (6)
├── Templates (6)
├── Variables (4)
└── Settings (2)

System Settings (30)
├── General (5)
├── Sending Servers (5)
├── Bounce Handlers (5)
├── Feedback Handlers (5)
├── Sub-Accounts (5)
└── Verification Servers (5)
```

---

## Getting Started

### 1. Run the Seeder

```bash
php artisan db:seed --class="Modules\Mailing\Database\Seeders\MailingPermissionsSeeder"
```

### 2. Assign a Role to User

```php
use App\Models\User;

$user = User::find(1);
$user->assignRole('mailing_admin');
```

### 3. Check Permission

```php
if ($user->can('mailing.campaigns.send')) {
    // User can send campaigns
}
```

### 4. Protect Routes

```php
Route::middleware(['permission:mailing.campaigns.send'])
    ->post('/campaigns/{campaign}/send', [CampaignController::class, 'send']);
```

---

## Common Tasks

### View All Permissions
See [PERMISSIONS_QUICK_REFERENCE.md](./PERMISSIONS_QUICK_REFERENCE.md) - Section "Permission Categories"

### Choose the Right Role
See [PERMISSIONS_ROLE_HIERARCHY.md](./PERMISSIONS_ROLE_HIERARCHY.md) - Section "Role Selection Guide"

### Understand Permission Flow
See [PERMISSIONS_ROLE_HIERARCHY.md](./PERMISSIONS_ROLE_HIERARCHY.md) - Section "Permission Flow Examples"

### Troubleshoot Issues
See [PERMISSIONS_SEEDER_REPORT.md](./PERMISSIONS_SEEDER_REPORT.md) - Section "Troubleshooting"

### Migrate from Old System
See [PERMISSIONS_SEEDER_REPORT.md](./PERMISSIONS_SEEDER_REPORT.md) - Section "Migration Path"

### Write Tests
See [PERMISSIONS_SEEDER_REPORT.md](./PERMISSIONS_SEEDER_REPORT.md) - Section "Testing Recommendations"

---

## Document Purpose Guide

### When to Use Each Document

#### 🔍 Quick Reference
**Use when you need:**
- Fast permission lookup
- Code examples for common tasks
- Quick role comparison
- Troubleshooting shortcuts

**Best for:** Daily development, quick checks

---

#### 📚 Complete Report
**Use when you need:**
- Detailed permission descriptions
- Full business rule explanations
- Policy method mappings
- Migration planning
- Testing strategies
- Alphabetical permission index

**Best for:** Architecture decisions, training, deep dives

---

#### 📊 Role Hierarchy Guide
**Use when you need:**
- Visual role relationships
- Feature access matrices
- Role selection guidance
- Permission flow diagrams
- Role combination strategies

**Best for:** Product planning, user management, admin training

---

#### ⚙️ Implementation Summary
**Use when you need:**
- Technical overview
- Statistics and metrics
- Known issues
- Performance considerations
- Implementation checklist

**Best for:** Code reviews, audits, status reports

---

## Key Features

### ✅ Comprehensive Coverage
- 87 permissions covering all mailing features
- 11 policy files fully mapped
- 80+ policy methods covered

### ✅ Hierarchical Roles
- 8 roles from viewer to super-admin
- Clear progression path
- No permission conflicts

### ✅ Business Logic Integration
- Status-based restrictions (can't edit sent campaigns)
- Feature flags (automation enable/disable)
- Combined permission checks (copy requires view + create)

### ✅ Production Ready
- Laravel 12 compatible
- Spatie Permission 6.x
- Cache management included
- Code formatted with Pint

### ✅ Extensively Documented
- 60+ pages of documentation
- Code examples throughout
- Visual diagrams
- Troubleshooting guides

---

## Permission Naming Convention

All permissions follow this pattern:

```
{module}.{resource}.{action}

Examples:
mailing.campaigns.send
mailing.subscribers.import
mailer.components.create
```

**Modules:**
- `mailing` - Core mailing functionality
- `mailer` - Infrastructure and configuration

**Resources:**
- `campaigns`, `subscribers`, `lists`, `templates`, `automations`, `imports`
- `components`, `endpoints`, `templates`, `variables`
- `settings.*` - Configuration areas

**Actions:**
- `view`, `create`, `edit`, `delete` - Standard CRUD
- `send`, `pause`, `test` - Campaign operations
- `import`, `export`, `sync` - Data operations
- `manage`, `configure` - Administrative

---

## Role Selection Quick Guide

### Need to send campaigns? → mailing_manager or higher
### Need to delete resources? → mailing_admin or higher
### Need settings access? → admin or super-admin
### Just creating content? → mailing_user
### Read-only reporting? → mailing_viewer

---

## File Locations

### Seeder
```
modules/Mailing/database/seeders/MailingPermissionsSeeder.php
```

### Policies
```
modules/Mailing/app/Policies/
├── CampaignPolicy.php
├── SubscriberPolicy.php
├── MailListPolicy.php
├── TemplatePolicy.php
├── AutomationPolicy.php
├── ImportPolicy.php
├── MailerComponentPolicy.php
├── MailerEndpointPolicy.php
├── MailerTemplatePolicy.php
├── MailerVariablePolicy.php
└── MailerSettingsPolicy.php
```

### Documentation
```
modules/Mailing/docs/
├── PERMISSIONS_README.md (this file)
├── PERMISSIONS_QUICK_REFERENCE.md
├── PERMISSIONS_SEEDER_REPORT.md
├── PERMISSIONS_ROLE_HIERARCHY.md
└── PERMISSIONS_IMPLEMENTATION_SUMMARY.md
```

---

## Support & Resources

### Internal Documentation
- [Quick Reference](./PERMISSIONS_QUICK_REFERENCE.md) - Fast lookup
- [Complete Report](./PERMISSIONS_SEEDER_REPORT.md) - Full details
- [Role Hierarchy](./PERMISSIONS_ROLE_HIERARCHY.md) - Visual guides
- [Implementation Summary](./PERMISSIONS_IMPLEMENTATION_SUMMARY.md) - Technical overview

### External Resources
- [Spatie Permission Documentation](https://spatie.be/docs/laravel-permission)
- [Laravel Authorization](https://laravel.com/docs/authorization)
- [Laravel Policies](https://laravel.com/docs/authorization#creating-policies)

---

## Statistics

| Metric | Value |
|--------|-------|
| Total Permissions | 87 |
| Total Roles | 8 |
| Policy Files | 11 |
| Policy Methods | 80+ |
| Documentation Pages | 60+ |
| Code Examples | 50+ |

---

## Version History

### v1.0 (2026-01-29)
- Initial comprehensive permissions system
- 87 permissions across 14 categories
- 8 roles with clear hierarchy
- Complete documentation suite
- Based on Spatie Permission 6.x
- Laravel 12 compatible

---

## Quick Commands

```bash
# Run seeder
php artisan db:seed --class="Modules\Mailing\Database\Seeders\MailingPermissionsSeeder"

# Clear permission cache
php artisan permission:cache-reset

# List all permissions
php artisan tinker --execute="Permission::all()->pluck('name')->dd();"

# List all roles with permission counts
php artisan tinker --execute="Role::withCount('permissions')->get(['name', 'permissions_count'])->dd();"

# Check user permissions
php artisan tinker --execute="User::find(1)->getAllPermissions()->pluck('name')->dd();"
```

---

## Maintenance

### Adding New Permission
1. Add to policy method
2. Add to `getPermissions()` array in seeder
3. Add to appropriate roles in `assignToRoles()`
4. Run seeder
5. Update documentation

### Modifying Role
1. Update `assignToRoles()` method
2. Run seeder
3. Update role hierarchy documentation
4. Notify affected users

### Testing Changes
1. Run seeder in testing environment
2. Execute unit tests
3. Execute feature tests
4. Verify policy checks
5. Clear cache

---

## Need Help?

### Common Issues

**Permission denied after seeding**
→ See [Troubleshooting Guide](./PERMISSIONS_SEEDER_REPORT.md#troubleshooting)

**Role not found**
→ Run seeder: `php artisan db:seed --class=MailingPermissionsSeeder`

**Permission not working in policy**
→ Check HasSafePermissionCheck trait is used

**Cache issues**
→ Run: `php artisan permission:cache-reset`

### Documentation Questions

- **"Which role should I use?"** → [Role Hierarchy Guide](./PERMISSIONS_ROLE_HIERARCHY.md#role-selection-guide)
- **"What permissions exist?"** → [Quick Reference](./PERMISSIONS_QUICK_REFERENCE.md#permission-categories)
- **"How do I check permissions?"** → [Quick Reference](./PERMISSIONS_QUICK_REFERENCE.md#checking-permissions)
- **"What are the business rules?"** → [Complete Report](./PERMISSIONS_SEEDER_REPORT.md#permissions-breakdown-by-category)

---

**Last Updated:** 2026-01-29
**Maintained By:** Development Team
**Status:** ✅ Production Ready
