# Document Permissions Seeder - Implementation Guide

## Overview

A complete, production-ready Spatie Laravel Permission seeder for document management with:
- 47 granular permissions across 9 feature areas
- 3 role levels (super-admin, manager, administrative)
- Editable & incremental structure
- Full documentation and usage examples

## Files Created

### 1. Seeder Implementation
**File**: `/database/seeders/Documents/CreateDocumentPermissionsSeeder.php`

- Fully functional and production-ready
- 47 permissions defined across 9 feature areas
- 3 role assignments with appropriate permissions
- Wildcard support for role assignment
- Idempotent (safe to run multiple times)
- PHPDoc blocks and inline comments

### 2. Documentation Files
**Location**: `/docs/permissions/`

- `document-permissions-seeder.md` - Complete seeder guide with all permissions listed
- `document-permissions-usage-examples.md` - 10+ practical code examples
- `IMPLEMENTATION_GUIDE.md` - This file

### 3. Module README
**File**: `/database/seeders/Documents/README.md`

- Overview of all Document seeders
- Quick start guide
- Integration instructions

### 4. Integration
**File**: `/database/seeders/DatabaseSeeder.php` (Updated)

- Added import for `CreateDocumentPermissionsSeeder`
- Registered in PHASE 7 (Roles & Permissions)
- Properly ordered for dependency management

## Quick Start

### 1. Run the Seeder

**Individual execution:**
```bash
php artisan db:seed --class=Database\\Seeders\\Documents\\DocumentPermissionsSeeder
```

**Full database seeding:**
```bash
php artisan db:seed
```

**Fresh migration with seeding:**
```bash
php artisan migrate:fresh --seed
```

### 2. Verify Permissions Created

```bash
php artisan tinker
Permission::count();              # Should show 47 (or more with other seeders)
Permission::where('name', 'like', 'documents.%')->count();  # Should show 19
Role::all();                      # Should show super-admin, manager, administrative
```

### 3. Check Role Permissions

```bash
php artisan tinker

# Check what permissions a role has
$role = Role::findByName('administrative');
$role->permissions;

# Check if a user has a permission
$user = User::first();
$user->hasPermissionTo('documents.create');  # Returns true/false
```

## Permission Structure

### Organized by Feature Area (47 Total)

| Feature | Permissions | Purpose |
|---------|-------------|---------|
| **documents** | 12 | Core document operations |
| **documents.files** | 4 | File management within documents |
| **documents.notes** | 3 | Note creation and management |
| **document_types** | 4 | Document type configuration |
| **document_groups** | 5 | Document grouping and organization |
| **document_conditions** | 4 | Validation conditions |
| **document_sla_policies** | 4 | SLA policy management |
| **document_storage** | 3 | Storage configuration |
| **document_settings** | 3 | General settings |
| **document_blockades** | 5 | Document blockades and synchronization |

## Role Assignments

### Super Admin
- **Assigned**: All document permissions via wildcard patterns
- **Use case**: Full administrative access
- **Permissions**: `documents.*`, `document_types.*`, etc.

### Manager
- **Assigned**: 12 view and configuration permissions
- **Use case**: Oversight without direct editing
- **Permissions**: View all features, configure groups

### Administrative
- **Assigned**: 20 CRUD and operation permissions
- **Use case**: Day-to-day document management
- **Permissions**: Create, update, approve/reject stages

## Adding New Permissions

### Step 1: Define the Permission

Edit `/database/seeders/Documents/CreateDocumentPermissionsSeeder.php`, method `definePermissions()`:

```php
private function definePermissions(): array
{
    return [
        // ... existing permissions ...

        // New feature
        'documents.archive' => 'Archive documents',
        'documents.unarchive' => 'Unarchive documents',
    ];
}
```

### Step 2: Assign to Roles (Optional)

Update `assignPermissionsToRoles()` method:

```php
$this->assignToRole('manager', [
    // ... existing permissions ...
    'documents.archive',      // Add new permissions to appropriate roles
]);
```

### Step 3: Run the Seeder

```bash
php artisan db:seed --class=Database\\Seeders\\Documents\\DocumentPermissionsSeeder
```

The seeder uses `findOrCreate()`, so it's safe to run multiple times.

## Usage in Application

### In Controllers

```php
public function destroy(Document $document)
{
    // Method 1: Using authorize()
    $this->authorize('documents.delete');

    $document->delete();
    return back()->with('success', 'Document deleted');
}
```

### In Blade Templates

```blade
@can('documents.update')
    <a href="{{ route('documents.edit', $document) }}">Edit</a>
@endcan

@cannot('documents.delete')
    <p>You don't have permission to delete</p>
@endcannot
```

### In Livewire Components

```php
public function save()
{
    $this->authorize('documents.update');
    // ... save logic
}
```

### In Middleware

```php
Route::post('documents', [DocumentController::class, 'store'])
    ->middleware('can:documents.create');
```

## Testing Permissions

### Create a Test User

```bash
php artisan tinker

$user = User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
]);

$user->assignRole('administrative');
```

### Test Permission Check

```bash
auth()->login($user);
auth()->user()->can('documents.create');     # Returns true
auth()->user()->can('document_settings.update');  # Returns false
```

### Run Feature Tests

```bash
php artisan test tests/Feature/DocumentPermissionTest.php
```

## Common Tasks

### Check Current Permissions
```bash
php artisan tinker
Permission::all();
```

### Clear Permission Cache
```bash
php artisan cache:clear
app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
```

### Assign Permission to Role
```bash
php artisan tinker
$role = Role::findByName('manager');
$role->givePermissionTo('documents.delete');
```

### Remove Permission from Role
```bash
php artisan tinker
$role = Role::findByName('manager');
$role->revokePermissionTo('documents.delete');
```

### Assign Role to User
```bash
php artisan tinker
$user = User::find(1);
$user->assignRole('administrative');
```

## Troubleshooting

### Error: "Class not found"
```bash
# Clear autoloader cache
composer dump-autoload
php artisan cache:clear
```

### Permissions not working
```bash
# Clear permission cache
php artisan cache:clear

# Re-run seeder
php artisan db:seed --class=Database\\Seeders\\Documents\\DocumentPermissionsSeeder
```

### "Access denied" in production
```bash
# Make sure to migrate and seed in production
php artisan migrate --env=production
php artisan db:seed --class=Database\\Seeders\\Documents\\DocumentPermissionsSeeder --env=production
```

## Feature Checklist

- [x] 47 granular permissions defined
- [x] 3 role levels configured
- [x] Wildcard support for bulk assignment
- [x] Idempotent (safe to run multiple times)
- [x] Proper dependency ordering in DatabaseSeeder
- [x] PHPDoc blocks and comments
- [x] Spatie Laravel Permission best practices
- [x] Complete documentation with examples
- [x] Usage examples for controllers, Blade, Livewire
- [x] Testing examples

## Best Practices

1. **Keep Role Assignments Clear**: Document why each role has specific permissions

2. **Incremental Expansion**: Add permissions as features are needed, not all at once

3. **Test Thoroughly**: Always test permission checks before deployment

4. **Use Meaningful Names**: Follow the `resource.action` convention

5. **Cache Management**: Clear cache when permissions change in production

6. **Audit Trail**: Consider logging permission changes for compliance

7. **Regular Review**: Periodically review and adjust role permissions

## Related Files

- Seeder: `/database/seeders/Documents/CreateDocumentPermissionsSeeder.php`
- Guide: `/docs/permissions/document-permissions-seeder.md`
- Examples: `/docs/permissions/document-permissions-usage-examples.md`
- Module README: `/database/seeders/Documents/README.md`
- Database Seeder: `/database/seeders/DatabaseSeeder.php`

## Support

For issues or questions:
1. Check `/docs/permissions/document-permissions-usage-examples.md` for code examples
2. Review Spatie documentation: https://spatie.be/docs/laravel-permission
3. Check Laravel authorization: https://laravel.com/docs/authorization

## Version History

- **v1.0** - Initial release with 47 permissions across 9 feature areas
  - 3 role levels (super-admin, manager, administrative)
  - Complete documentation and examples
  - Wildcard support for bulk assignment
