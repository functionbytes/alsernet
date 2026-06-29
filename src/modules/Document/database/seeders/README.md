# Document Module Seeders

This directory contains all database seeders for the Document module.

## Seeders Overview (16 total)

### Configuration & Reference Data
- `DocumentStatusSeeder` - Document statuses (pending, approved, rejected, etc.)
- `DocumentStatusTransitionSeeder` - Allowed status transitions and permissions
- `DocumentTypeSeeder` - Document types (corta, rifle, escopeta, balines, dni, general)
- `DocumentGroupSeeder` - Document grouping categories
- `DocumentLoadSeeder` - Load types (manual, on_demand, scheduled, automated)
- `DocumentSyncSeeder` - Sync types (none, prestashop, erp, api, email_imap)
- `DocumentSourceSeeder` - Document sources (manual, email, whatsapp, prestashop, api)
- `DocumentUploadTypeSeeder` - Upload types (automatic, manual)

### Validation & Rules
- `DocumentValidationConditionSeeder` - Validation conditions (is_weapon, is_dni_only, requires_financing)
- `DocumentValidatorGroupSeeder` - Validator groups (documentation_team, licenses_team, accounting_team)
- `DocumentValidatorGroupConfigurationSeeder` - Validator group configurations and rules

### Configuration & Application Settings
- `DocumentConfigurationSeeder` - Document type configurations
- `DocumentSettingsSeeder` - Application settings for documents (email, SLA, general)

### Email & Communication
- `DocumentEmailTemplateSeeder` - Email templates with translations
- `MigrateDocumentTemplatesSeeder` - Legacy/migration document templates

### Permissions & Access Control
- `DocumentPermissionsSeeder` - Spatie permission definitions and role assignments

## Quick Start

### Run All Document Seeders
```bash
php artisan db:seed --class=Database\\Seeders\\DatabaseSeeder
```

### Fresh Migration with All Seeders
```bash
php artisan migrate:fresh --seed
```

## Seeder Execution Order

Seeders should be run in this order (managed by DatabaseSeeder.php):

1. **DocumentPermissionsSeeder** - Permissions first
2. **DocumentStatusSeeder** - Base statuses
3. **DocumentStatusTransitionSeeder** - Depends on statuses
4. **DocumentValidationConditionSeeder** - Validation rules
5. **DocumentValidatorGroupSeeder** - Validator groups
6. **DocumentValidatorGroupConfigurationSeeder** - Depends on validator groups
7. **DocumentTypeSeeder** - Document types
8. **DocumentGroupSeeder** - Document grouping
9. **DocumentLoadSeeder** - Load types
10. **DocumentSyncSeeder** - Sync types
11. **DocumentSourceSeeder** - Source types
12. **DocumentUploadTypeSeeder** - Upload types
13. **DocumentConfigurationSeeder** - Type configurations
14. **DocumentSettingsSeeder** - Application settings
15. **DocumentEmailTemplateSeeder** - Email templates
16. **MigrateDocumentTemplatesSeeder** - Legacy templates (optional)

## Important Notes

- **All seeders are idempotent**: Safe to run multiple times
- **Use firstOrCreate/updateOrCreate**: Prevents duplicate entries
- **All seeders tested**: Validated for correct execution order
- **No destructive operations**: Seeders only insert/update data

## Key Features

- ✅ Complete document workflow setup
- ✅ Validation stages with multiple teams
- ✅ Email notifications system ready
- ✅ RBAC permissions configured
- ✅ SLA monitoring enabled by default
- ✅ Support for weapon licenses, DNI validation, financing

## Regeneration

All seeders were regenerated with:
- Cleaner code structure
- Improved documentation
- Fixed idempotency issues
- Proper error handling
- Better organization

Generated: 2026-01-03
