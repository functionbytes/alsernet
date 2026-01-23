# Document Module Routes & Authorization

## Overview

All Document module settings routes (`manager.settings.documents.*`) have been configured with proper authorization using Laravel Gates and a dedicated `SettingsPolicy`.

## Authorization System

### Gates Registered

The following gates are registered in `DocumentsServiceProvider`:

- `configure-documents` - General document configuration access
- `view-document-settings` - View document settings pages
- `manage-document-types` - Create/edit/delete document types
- `manage-document-conditions` - Create/edit/delete validation conditions
- `manage-document-sla-policies` - Create/edit/delete SLA policies
- `manage-document-groups` - Create/edit/delete validator groups
- `manage-document-blockades` - Create/edit/delete product blockades

### Authorization Policy

**File:** `modules/Document/app/Policies/SettingsPolicy.php`

All gate checks verify:
1. Super-admin users always have access
2. Manager users require specific Spatie permissions in format: `manager.documents.{action}`
3. Default deny-all for other roles

## Routes Protected

### Web Routes (GET - Views)
**File:** `modules/Document/routes/web.php`

```
Middleware: can:view-document-settings
├── documents/                              [configurations]
├── documents/configurations/*              [global, storage, etc]
├── documents/types/*          [can:manage-document-types]
├── documents/conditions/*      [can:manage-document-conditions]
├── documents/sla-policies/*    [can:manage-document-sla-policies]
├── documents/groups/*          [can:manage-document-groups]
├── documents/settings/*
└── documents/blockades/*       [can:manage-document-blockades]
```

### API Routes (POST, PUT, DELETE - Mutations)
**File:** `modules/Document/routes/api/settings.php`

```
Middleware: can:view-document-settings (inherited)
├── documents/configurations/*              [update operations]
├── documents/types/*           [can:manage-document-types]
├── documents/conditions/*       [can:manage-document-conditions]
├── documents/sla-policies/*     [can:manage-document-sla-policies]
├── documents/groups/*           [can:manage-document-groups]
├── documents/settings/*         [can:view-document-settings]
└── documents/blockades/*        [can:manage-document-blockades]
```

## Blade File Route References

All 40 route references found in blade files are covered:

### Document Types
- ✅ manager.settings.documents.types.index
- ✅ manager.settings.documents.types.create
- ✅ manager.settings.documents.types.edit
- ✅ manager.settings.documents.types.store
- ✅ manager.settings.documents.types.update
- ✅ manager.settings.documents.types.destroy
- ✅ manager.settings.documents.types.toggle-active

### Validation Conditions
- ✅ manager.settings.documents.conditions.index
- ✅ manager.settings.documents.conditions.create
- ✅ manager.settings.documents.conditions.edit
- ✅ manager.settings.documents.conditions.store
- ✅ manager.settings.documents.conditions.update
- ✅ manager.settings.documents.conditions.destroy
- ✅ manager.settings.documents.conditions.toggle-active

### SLA Policies
- ✅ manager.settings.documents.sla-policies.index
- ✅ manager.settings.documents.sla-policies.create
- ✅ manager.settings.documents.sla-policies.edit
- ✅ manager.settings.documents.sla-policies.store
- ✅ manager.settings.documents.sla-policies.update
- ✅ manager.settings.documents.sla-policies.toggle
- ✅ manager.settings.documents.sla-policies.destroy

### Validator Groups
- ✅ manager.settings.documents.groups.index
- ✅ manager.settings.documents.groups.create
- ✅ manager.settings.documents.groups.edit
- ✅ manager.settings.documents.groups.store
- ✅ manager.settings.documents.groups.update
- ✅ manager.settings.documents.groups.toggle
- ✅ manager.settings.documents.groups.destroy
- ✅ manager.settings.documents.groups.update-configuration
- ✅ manager.settings.documents.groups.configuration

### Configurations
- ✅ manager.settings.documents.configurations
- ✅ manager.settings.documents.configurations.global
- ✅ manager.settings.documents.configurations.storage
- ✅ manager.settings.documents.configurations.update
- ✅ manager.settings.documents.configurations.storage.update

### Document Settings
- ✅ manager.settings.documents.settings.update

### Product Blockades
- ✅ manager.settings.documents.blockades.sync
- ✅ manager.settings.documents.blockades.store
- ✅ manager.settings.documents.blockades.store-bulk
- ✅ manager.settings.documents.blockades.save-labels
- ✅ manager.settings.documents.blockades.destroy

## Required Permissions

To enable access to these routes, ensure users have one or more of these Spatie permissions:

```
manager.documents.configure
manager.documents.manage-types
manager.documents.manage-conditions
manager.documents.manage-sla-policies
manager.documents.manage-groups
manager.documents.manage-blockades
```

## Testing Authorization

Users without proper permissions will receive a 403 Forbidden response when accessing:
- GET /manager/settings/documents/*
- POST /manager/settings/documents/*/

## Implementation Details

1. **SettingsPolicy** - Gate-based authorization without needing models
2. **Middleware** - Applied at route group level using `can:` middleware
3. **Fallback** - Super-admin role bypasses all permission checks
4. **Consistency** - All routes in web.php and api.php are protected

## Related Files

- `modules/Document/app/Policies/SettingsPolicy.php` - Authorization logic
- `modules/Document/app/Providers/DocumentsServiceProvider.php` - Gate registration
- `modules/Document/routes/web.php` - View routes with middleware
- `modules/Document/routes/api/settings.php` - API routes with middleware
- `modules/Document/app/Services/PermissionService.php` - Permission resolution
