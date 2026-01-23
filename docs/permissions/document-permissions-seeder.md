# Document Permissions Seeder Guide

## Overview

The `CreateDocumentPermissionsSeeder` manages all document-related permissions for the application using Spatie Laravel Permission package.

**Location**: `/database/seeders/Documents/CreateDocumentPermissionsSeeder.php`

## Running the Seeder

### Single Execution
```bash
php artisan db:seed --class=Database\\Seeders\\Documents\\DocumentPermissionsSeeder
```

### In DatabaseSeeder
Add to your main `DatabaseSeeder.php`:
```php
public function run(): void
{
    $this->call([
        // ... other seeders
        Documents\CreateDocumentPermissionsSeeder::class,
    ]);
}
```

### Full Reset
```bash
php artisan migrate:fresh --seed
```

## Permission Structure

### Organized by Feature Area

The seeder defines permissions grouped by document features:

#### **Core Documents** (`documents.*`)
- `documents.view` - View documents
- `documents.view_all` - View all documents
- `documents.create` - Create documents
- `documents.update` - Update documents
- `documents.delete` - Delete documents
- `documents.manage` - Manage documents (admin)
- `documents.approve_stage` - Approve document stages
- `documents.reject_stage` - Reject document stages
- `documents.assign` - Assign documents
- `documents.export` - Export documents
- `documents.import` - Import documents
- `documents.bulk_update` - Bulk update documents

#### **Files** (`documents.files.*`)
- `documents.files.create` - Upload files
- `documents.files.update` - Update files
- `documents.files.delete` - Delete files
- `documents.files.download` - Download files

#### **Notes** (`documents.notes.*`)
- `documents.notes.create` - Create notes
- `documents.notes.update` - Update notes
- `documents.notes.delete` - Delete notes

#### **Document Types** (`document_types.*`)
- `document_types.view` - View types
- `document_types.create` - Create types
- `document_types.update` - Update types
- `document_types.delete` - Delete types

#### **Document Groups** (`document_groups.*`)
- `document_groups.view` - View groups
- `document_groups.create` - Create groups
- `document_groups.update` - Update groups
- `document_groups.delete` - Delete groups
- `document_groups.configure` - Configure groups

#### **Validation Conditions** (`document_conditions.*`)
- `document_conditions.view` - View conditions
- `document_conditions.create` - Create conditions
- `document_conditions.update` - Update conditions
- `document_conditions.delete` - Delete conditions

#### **SLA Policies** (`document_sla_policies.*`)
- `document_sla_policies.view` - View policies
- `document_sla_policies.create` - Create policies
- `document_sla_policies.update` - Update policies
- `document_sla_policies.delete` - Delete policies

#### **Storage Configuration** (`document_storage.*`)
- `document_storage.view` - View storage settings
- `document_storage.update` - Update settings
- `document_storage.test` - Test connections

#### **Settings** (`document_settings.*`)
- `document_settings.view` - View settings
- `document_settings.update` - Update settings
- `document_settings.reset` - Reset settings

#### **Blockades** (`document_blockades.*`)
- `document_blockades.view` - View blockades
- `document_blockades.create` - Create blockades
- `document_blockades.update` - Update blockades
- `document_blockades.delete` - Delete blockades
- `document_blockades.sync` - Sync blockades

## Role Assignments

### Super Admin
**Permissions**: All document permissions via wildcard (`documents.*`, `document_types.*`, etc.)

**Use case**: Full administrative access to all document features.

### Manager
**Permissions**: View and configuration only
- `documents.view`, `documents.view_all`
- Configuration permissions for all features
- Reporting and export

**Use case**: Oversight and configuration, no direct document editing.

### Administrative
**Permissions**: CRUD operations and validation
- Full document lifecycle management
- File and note management
- Stage approvals/rejections
- View-only for configuration areas

**Use case**: Day-to-day document management and processing.

## Adding New Permissions

### Step 1: Update `definePermissions()` Method

```php
private function definePermissions(): array
{
    return [
        // ... existing permissions ...

        // New feature
        'documents.new_feature.view' => 'View new feature',
        'documents.new_feature.manage' => 'Manage new feature',
    ];
}
```

### Step 2: Assign to Roles (Optional)

Update `assignPermissionsToRoles()` if roles need the new permission:

```php
$this->assignToRole('administrative', [
    // ... existing permissions ...
    'documents.new_feature.view',
]);
```

### Step 3: Run Seeder

```bash
php artisan db:seed --class=Database\\Seeders\\Documents\\DocumentPermissionsSeeder
```

## Using Permissions in Code

### In Controllers
```php
public function store(Request $request)
{
    if (!auth()->user()->can('documents.create')) {
        abort(403);
    }
    // ... create document
}
```

### In Blade Templates
```blade
@can('documents.update')
    <button>Edit Document</button>
@endcan
```

### In Middleware
```php
Route::post('/documents', [DocumentController::class, 'store'])
    ->middleware('can:documents.create');
```

### In Livewire
```php
public function saveDocument()
{
    $this->authorize('documents.update');
    // ... save logic
}
```

## Wildcard Permissions

The seeder supports wildcard patterns for bulk permission assignment:

```php
$this->assignToRole('super-admin', [
    'documents.*',           // All document permissions
    'document_types.*',      // All document type permissions
]);
```

This automatically resolves to all permissions starting with that prefix.

## Maintenance

### Viewing Current Permissions
```php
// In tinker
Permission::all();

// Filter by prefix
Permission::where('name', 'like', 'documents.%')->get();
```

### Checking Role Permissions
```php
$role = Role::findByName('administrative');
$role->permissions; // Get all permissions for role
```

### Revoking Permissions
```php
$role->revokePermissionTo('documents.delete');
```

## Best Practices

1. **Naming Convention**: Use dot notation following feature structure
   - ✅ `documents.files.create`
   - ❌ `files_create_document`

2. **Keep Descriptions Clear**: Make descriptions understandable for non-technical users

3. **Group Related Permissions**: Organize by feature area in the array

4. **Use Wildcards Wisely**: Reserve wildcard access for super-admin roles

5. **Test New Permissions**: Always test CRUD operations after adding new permissions

6. **Document Changes**: Update this file when adding significant permission groups

## Troubleshooting

### "Permission not found" Error
```php
// Make sure permission exists
php artisan tinker
Permission::findByName('documents.view');

// Recreate if missing
php artisan db:seed --class=Database\\Seeders\\Documents\\CreateDocumentPermissionsSeeder
```

### Role Not Getting Permissions
```php
// Clear cached permissions
app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

// Reassign
php artisan db:seed --class=Database\\Seeders\\Documents\\CreateDocumentPermissionsSeeder
```

### Permissions Cache Issues
```bash
php artisan cache:clear
php artisan optimize:clear
```

## Integration with Other Seeders

This seeder is independent and can run:
- **In isolation**: `php artisan db:seed --class=...CreateDocumentPermissionsSeeder`
- **With other seeders**: Add to `DatabaseSeeder.php` call array
- **In fresh migration**: `php artisan migrate:fresh --seed`

## Related Documentation

- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [Laravel Authorization](https://laravel.com/docs/authorization)
- [Database Seeders Guide](../../database/seeders.md)
