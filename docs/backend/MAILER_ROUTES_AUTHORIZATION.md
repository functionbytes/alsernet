# Mailer Module Routes & Authorization

## Overview

All Mailer module settings routes (`manager.settings.mailers.*`) have been configured with proper authorization using Laravel Gates and a dedicated `MailerSettingsPolicy`.

## Authorization System

### Gates Registered

The following gates are registered in `MailerServiceProvider`:

- `configure-mailers` - General mailer configuration access
- `view-mailer-settings` - View mailer settings pages
- `manage-mailer-templates` - Create/edit/delete email templates
- `manage-mailer-components` - Create/edit/delete email components (headers, footers, etc.)
- `manage-mailer-variables` - Create/edit/delete dynamic variables
- `manage-mailer-endpoints` - Create/edit/delete API email endpoints

### Authorization Policy

**File:** `modules/Mailer/app/Policies/MailerSettingsPolicy.php`

All gate checks verify:
1. Super-admin users always have access
2. Manager users require specific Spatie permissions in format: `manager.mailers.{action}`
3. Default deny-all for other roles

## Routes Protected

### Web Routes (GET - Views)
**File:** `modules/Mailer/routes/web.php`

```
├── templates/*                 [can:manage-mailer-templates]
│   ├── index, create, edit
│   ├── preview, preview-ajax
│   └── variables, variables-by-module
├── components/*                [can:manage-mailer-components]
│   ├── index, create, edit
│   ├── preview, preview-ajax
│   └── variables
├── variables/*                 [can:manage-mailer-variables]
│   ├── index, create, edit
│   └── by-module
└── endpoints/*                 [can:manage-mailer-endpoints]
    ├── index, create, edit
    ├── documentation
    └── logs
```

### API Routes (POST, PUT, DELETE - Mutations)
**File:** `modules/Mailer/routes/api/settings.php`

```
├── templates/*                 [can:manage-mailer-templates]
│   ├── store, update, destroy
│   ├── toggle-status
│   ├── send-test
│   └── format-html
├── components/*                [can:manage-mailer-components]
│   ├── store, update, destroy
│   └── duplicate
├── variables/*                 [can:manage-mailer-variables]
│   ├── store, update, destroy
│   └── toggle-status
└── endpoints/*                 [can:manage-mailer-endpoints]
    ├── store, update, destroy
    └── regenerate-token
```

## Blade File Route References

All 37 route references found in blade files are covered:

### Email Templates
- ✅ manager.settings.mailers.templates.index
- ✅ manager.settings.mailers.templates.create
- ✅ manager.settings.mailers.templates.edit
- ✅ manager.settings.mailers.templates.store
- ✅ manager.settings.mailers.templates.update
- ✅ manager.settings.mailers.templates.destroy
- ✅ manager.settings.mailers.templates.toggle-status
- ✅ manager.settings.mailers.templates.send-test
- ✅ manager.settings.mailers.templates.preview
- ✅ manager.settings.mailers.templates.preview-ajax
- ✅ manager.settings.mailers.templates.variables

### Email Components
- ✅ manager.settings.mailers.components.index
- ✅ manager.settings.mailers.components.create
- ✅ manager.settings.mailers.components.edit
- ✅ manager.settings.mailers.components.store
- ✅ manager.settings.mailers.components.update
- ✅ manager.settings.mailers.components.destroy
- ✅ manager.settings.mailers.components.duplicate
- ✅ manager.settings.mailers.components.preview
- ✅ manager.settings.mailers.components.preview-ajax
- ✅ manager.settings.mailers.components.variables

### Email Variables
- ✅ manager.settings.mailers.variables.index
- ✅ manager.settings.mailers.variables.create
- ✅ manager.settings.mailers.variables.edit
- ✅ manager.settings.mailers.variables.store
- ✅ manager.settings.mailers.variables.update
- ✅ manager.settings.mailers.variables.destroy
- ✅ manager.settings.mailers.variables.toggle-status
- ✅ manager.settings.mailers.variables-by-module

### Email Endpoints
- ✅ manager.settings.mailers.endpoints.index
- ✅ manager.settings.mailers.endpoints.create
- ✅ manager.settings.mailers.endpoints.edit
- ✅ manager.settings.mailers.endpoints.store
- ✅ manager.settings.mailers.endpoints.update
- ✅ manager.settings.mailers.endpoints.destroy
- ✅ manager.settings.mailers.endpoints.regenerate-token
- ✅ manager.settings.mailers.endpoints.logs

## Required Permissions

To enable access to these routes, ensure users have one or more of these Spatie permissions:

```
manager.mailers.configure
manager.mailers.manage-templates
manager.mailers.manage-components
manager.mailers.manage-variables
manager.mailers.manage-endpoints
```

## Route Organization

### Before (Mixed)
All routes (GET, POST, PUT, DELETE) were in a single `web.php` file.

### After (Separated)
- **web.php**: Only GET routes for rendering views
- **api/settings.php**: POST/PUT/PATCH/DELETE routes for data operations
- Both files loaded with same prefix/name in ServiceProvider

## Testing Authorization

Users without proper permissions will receive a 403 Forbidden response when accessing:
- GET /manager/settings/mailers/*
- POST /manager/settings/mailers/*/

## Implementation Details

1. **MailerSettingsPolicy** - Gate-based authorization without needing models
2. **Middleware** - Applied at route group level using `can:` middleware
3. **Fallback** - Super-admin role bypasses all permission checks
4. **Consistency** - All routes in web.php and api/settings.php are protected

## Related Files

- `modules/Mailer/app/Policies/MailerSettingsPolicy.php` - Authorization logic
- `modules/Mailer/app/Providers/MailerServiceProvider.php` - Gate registration
- `modules/Mailer/routes/web.php` - View routes with middleware
- `modules/Mailer/routes/api/settings.php` - API routes with middleware
