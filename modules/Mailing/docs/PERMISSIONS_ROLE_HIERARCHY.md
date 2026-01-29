# Mailing Module Role Hierarchy

**Visual guide to understanding role permissions and relationships**

---

## Role Hierarchy Pyramid

```
                    ┌─────────────────┐
                    │  super-admin    │  87 permissions
                    │  (Complete)     │  Everything
                    └────────┬────────┘
                             │
                    ┌────────┴────────┐
                    │     admin       │  67 permissions
                    │  (Platform)     │  All operational + settings
                    └────────┬────────┘
                             │
                    ┌────────┴────────┐
                    │  mailing_admin  │  39 permissions
                    │  (Module Lead)  │  Full mailing ops
                    └────────┬────────┘
                             │
                    ┌────────┴────────┐
                    │ mailing_manager │  30 permissions
                    │ (Operations)    │  Campaign/subscriber mgmt
                    └────────┬────────┘
                             │
                    ┌────────┴────────┐
                    │  mailing_user   │  13 permissions
                    │  (Creator)      │  View + Create only
                    └────────┬────────┘
                             │
                    ┌────────┴────────┐
                    │ mailing_viewer  │  11 permissions
                    │  (Read-only)    │  View everything
                    └─────────────────┘
```

---

## Permission Accumulation

### super-admin → 87 permissions
```
✅ All mailing.* permissions (57)
✅ All mailer.* permissions (28)
✅ All settings.* permissions (30)
✅ Super-admin exclusives (mailer.settings.*)
```

### admin → 67 permissions
```
✅ All operational permissions
✅ All settings management
✅ All mailer infrastructure
❌ Super-admin mailer settings
```

### mailing_admin → 39 permissions
```
✅ All campaigns (CRUD + operations)
✅ All subscribers (CRUD + operations)
✅ All lists (CRUD + import/export)
✅ All templates (CRUD)
✅ All automations (CRUD)
✅ All imports (CRUD)
✅ View mailer components/templates/variables
❌ Mailer infrastructure management
❌ Settings
```

### mailing_manager → 30 permissions
```
✅ Campaigns (CRUD - delete)
✅ Subscribers (full operational)
✅ Lists (CRUD - delete)
✅ Templates (CRU - delete)
✅ Automations (RU - create/delete)
✅ Imports (CR - delete)
❌ Delete campaigns/lists/templates
❌ Mailer infrastructure
❌ Settings
```

### mailing_user → 13 permissions
```
✅ Campaigns (view + create + test)
✅ Subscribers (view + create)
✅ Lists (view only)
✅ Templates (view + create)
✅ Automations (view only)
✅ Imports (view only)
❌ Edit/delete anything
❌ Send campaigns (except test)
❌ Import/export operations
```

### mailing_viewer → 11 permissions
```
✅ View campaigns
✅ View subscribers
✅ View lists
✅ View templates
✅ View automations
✅ View imports
✅ View mailer components/templates/variables
❌ Any create/edit/delete
❌ Any operational actions
```

---

## Feature Matrix by Role

### Campaigns Management

```
                     View Create Edit Delete Send Pause Test
super-admin           ✅    ✅    ✅    ✅    ✅    ✅    ✅
admin                 ✅    ✅    ✅    ✅    ✅    ✅    ✅
mailing_admin         ✅    ✅    ✅    ✅    ✅    ✅    ✅
mailing_manager       ✅    ✅    ✅    ❌    ✅    ✅    ✅
mailing_user          ✅    ✅    ❌    ❌    ❌    ❌    ✅
mailing_viewer        ✅    ❌    ❌    ❌    ❌    ❌    ❌
```

### Subscribers Management

```
                     View Create Edit Delete Manage Import Export Sync
super-admin           ✅    ✅    ✅    ✅     ✅     ✅     ✅     ✅
admin                 ✅    ✅    ✅    ✅     ✅     ✅     ✅     ✅
mailing_admin         ✅    ✅    ✅    ✅     ✅     ✅     ✅     ✅
mailing_manager       ✅    ✅    ✅    ❌     ✅     ✅     ✅     ✅
mailing_user          ✅    ✅    ❌    ❌     ❌     ❌     ❌     ❌
mailing_viewer        ✅    ❌    ❌    ❌     ❌     ❌     ❌     ❌
```

### Templates Management

```
                     View Create Edit Delete
super-admin           ✅    ✅    ✅    ✅
admin                 ✅    ✅    ✅    ✅
mailing_admin         ✅    ✅    ✅    ✅
mailing_manager       ✅    ✅    ✅    ❌
mailing_user          ✅    ✅    ❌    ❌
mailing_viewer        ✅    ❌    ❌    ❌
```

### Mailer Infrastructure

```
                     Components Endpoints Templates Variables Settings
                     (CRUD)     (CRUD+)   (CRUD+)   (CRUD)    (Config)
super-admin           ✅✅✅✅✅   ✅✅✅✅✅✅  ✅✅✅✅✅✅  ✅✅✅✅    ✅✅
admin                 ✅✅✅✅✅   ✅✅✅✅✅✅  ✅✅✅✅✅✅  ✅✅✅✅    ❌❌
mailing_admin         ✅❌❌❌✅   ❌❌❌❌❌❌  ✅❌❌❌✅❌  ✅❌❌❌    ❌❌
mailing_manager       ✅❌❌❌✅   ❌❌❌❌❌❌  ✅❌❌❌✅❌  ❌❌❌❌    ❌❌
mailing_user          ✅❌❌❌❌   ❌❌❌❌❌❌  ✅❌❌❌❌❌  ❌❌❌❌    ❌❌
mailing_viewer        ✅❌❌❌❌   ❌❌❌❌❌❌  ✅❌❌❌❌❌  ✅❌❌❌    ❌❌
```

Legend:
- ✅✅✅✅✅ = View + Create + Update + Delete + Preview
- ✅✅✅✅✅✅ = View + Create + Update + Delete + Logs + Regenerate Token
- ✅✅ = Configure + View

---

## Permission Ownership by Category

### mailing.* namespace (57 permissions)

```
Campaigns (7)         │ Subscribers (8)      │ Lists (6)
────────────────────  │ ──────────────────── │ ───────────────────
view                  │ view                 │ view
create                │ create               │ create
edit                  │ edit                 │ edit
delete                │ delete               │ delete
send                  │ manage               │ import
pause                 │ import               │ export
test                  │ export               │
                      │ sync                 │

Templates (4)         │ Automations (4)      │ Imports (3)
────────────────────  │ ──────────────────── │ ───────────────────
view                  │ view                 │ view
create                │ create               │ create
edit                  │ edit                 │ delete
delete                │ delete               │

Settings (30)         │ General (2)          │ Validation (2)
────────────────────  │ ──────────────────── │ ───────────────────
general               │ access               │ test
api                   │ dashboard.view       │ validate
webhooks              │                      │
permissions           │                      │
manage                │                      │
sending-servers (5)   │                      │
bounce-handlers (5)   │                      │
feedback-handlers (5) │                      │
sub-accounts (5)      │                      │
verification (5)      │                      │
```

### mailer.* namespace (28 permissions)

```
Components (5)        │ Endpoints (6)        │ Templates (6)
────────────────────  │ ──────────────────── │ ───────────────────
view                  │ view                 │ view
create                │ create               │ create
update                │ update               │ update
delete                │ delete               │ delete
preview               │ logs                 │ preview
                      │ regenerate-token     │ manage

Variables (4)         │ Settings (2)
────────────────────  │ ────────────────────
view                  │ configure
create                │ view
update                │
delete                │
```

---

## Role Selection Guide

### Choose **super-admin** when:
- System administrator
- Full platform control needed
- Configure core mailer infrastructure
- Emergency access required

### Choose **admin** when:
- Platform administrator
- Manage all mailing operations
- Configure sending servers, bounce handlers
- Full operational + settings access needed

### Choose **mailing_admin** when:
- Dedicated mailing team lead
- Complete campaign/subscriber control
- No need for system settings
- Responsible for mailing strategy

### Choose **mailing_manager** when:
- Campaign manager
- Day-to-day operations
- Send campaigns, manage subscribers
- Should not delete critical resources

### Choose **mailing_user** when:
- Content creator
- Draft campaigns and templates
- Add subscribers
- No send or edit permissions

### Choose **mailing_viewer** when:
- Auditor/reporter
- Read-only access needed
- Monitor campaign performance
- No operational responsibilities

---

## Common Role Combinations

### Platform Admin
```php
$user->assignRole(['admin', 'super-admin']);
// Full control over entire platform
```

### Mailing Department Head
```php
$user->assignRole(['mailing_admin', 'manager']);
// Complete mailing control + legacy operational access
```

### Campaign Specialist
```php
$user->assignRole('mailing_manager');
// Send campaigns, manage subscribers, no delete
```

### Marketing Content Creator
```php
$user->assignRole('mailing_user');
// Create campaigns/templates, test emails
```

### Analytics/Reporting Team
```php
$user->assignRole('mailing_viewer');
// View all data, export reports, no modifications
```

---

## Permission Flow Examples

### Sending a Campaign

```
User attempts to send campaign
           ↓
Check: mailing.campaigns.send
           ↓
    Has permission?
    ┌────────┴────────┐
   YES                NO
    ↓                 ↓
Policy: CampaignPolicy@send    403 Forbidden
    ↓
Business Rule: Campaign not sent?
    ┌────────┴────────┐
   YES                NO
    ↓                 ↓
Allow send           Block (already sent)
```

### Deleting a Campaign

```
User attempts to delete campaign
           ↓
Check: mailing.campaigns.delete
           ↓
    Has permission?
    ┌────────┴────────┐
   YES                NO
    ↓                 ↓
Policy: CampaignPolicy@delete  403 Forbidden
    ↓
Business Rule: Not sent AND not sending?
    ┌────────┴────────┐
   YES                NO
    ↓                 ↓
Allow delete         Block (sent/sending)
```

### Viewing Mailer Settings

```
User attempts to view settings
           ↓
Policy: MailerSettingsPolicy@viewSettings
           ↓
Has super-admin role?
    ┌────────┴────────┐
   YES                NO
    ↓                 ↓
Allow view           Block (hardcoded)
```

---

## Migration Examples

### Promote User to Manager

```php
$user = User::find($userId);

// Remove lower role
$user->removeRole('mailing_user');

// Assign manager role
$user->assignRole('mailing_manager');

// Verify
dd($user->getAllPermissions()->count()); // Should be 30
```

### Demote User to Viewer

```php
$user = User::find($userId);

// Replace all roles with viewer
$user->syncRoles(['mailing_viewer']);

// Verify
dd($user->can('mailing.campaigns.send')); // Should be false
```

### Grant Temporary Admin Access

```php
// Add admin role without removing current roles
$user->assignRole('admin');

// Later: revoke
$user->removeRole('admin');
```

---

## Role Dependencies

### Roles that should NOT be combined:

```
❌ mailing_admin + mailing_manager
   (Redundant: admin includes all manager permissions)

❌ mailing_manager + mailing_user
   (Redundant: manager includes all user permissions)

❌ mailing_user + mailing_viewer
   (Redundant: user includes all viewer permissions)
```

### Roles that CAN be combined:

```
✅ admin + mailing_admin
   (Platform admin + mailing specialization)

✅ super-admin + admin
   (Maximum access + platform management)

✅ mailing_manager + administrative
   (Operational + legacy viewing)
```

---

## For Implementation Details

- **Complete permission list:** See [PERMISSIONS_SEEDER_REPORT.md](./PERMISSIONS_SEEDER_REPORT.md)
- **Quick reference:** See [PERMISSIONS_QUICK_REFERENCE.md](./PERMISSIONS_QUICK_REFERENCE.md)
- **Seeder code:** `modules/Mailing/database/seeders/MailingPermissionsSeeder.php`
