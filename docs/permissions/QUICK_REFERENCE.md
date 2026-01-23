# Document Permissions Quick Reference

## Execution

```bash
# Single run
php artisan db:seed --class=Database\\Seeders\\Documents\\DocumentPermissionsSeeder

# With full migration
php artisan migrate:fresh --seed
```

## All 47 Permissions

### Documents (12)
- `documents.view` - Ver documentos
- `documents.view_all` - Ver todos los documentos
- `documents.create` - Crear documentos
- `documents.update` - Actualizar documentos
- `documents.delete` - Eliminar documentos
- `documents.manage` - Gestionar documentos
- `documents.approve_stage` - Aprobar etapas
- `documents.reject_stage` - Rechazar etapas
- `documents.assign` - Asignar documentos
- `documents.export` - Exportar documentos
- `documents.import` - Importar documentos
- `documents.bulk_update` - Actualización masiva

### Files (4)
- `documents.files.create` - Cargar archivos
- `documents.files.update` - Actualizar archivos
- `documents.files.delete` - Eliminar archivos
- `documents.files.download` - Descargar archivos

### Notes (3)
- `documents.notes.create` - Crear notas
- `documents.notes.update` - Actualizar notas
- `documents.notes.delete` - Eliminar notas

### Types (4)
- `document_types.view` - Ver tipos
- `document_types.create` - Crear tipos
- `document_types.update` - Actualizar tipos
- `document_types.delete` - Eliminar tipos

### Groups (5)
- `document_groups.view` - Ver grupos
- `document_groups.create` - Crear grupos
- `document_groups.update` - Actualizar grupos
- `document_groups.delete` - Eliminar grupos
- `document_groups.configure` - Configurar grupos

### Conditions (4)
- `document_conditions.view` - Ver condiciones
- `document_conditions.create` - Crear condiciones
- `document_conditions.update` - Actualizar condiciones
- `document_conditions.delete` - Eliminar condiciones

### SLA Policies (4)
- `document_sla_policies.view` - Ver políticas
- `document_sla_policies.create` - Crear políticas
- `document_sla_policies.update` - Actualizar políticas
- `document_sla_policies.delete` - Eliminar políticas

### Storage (3)
- `document_storage.view` - Ver almacenamiento
- `document_storage.update` - Actualizar almacenamiento
- `document_storage.test` - Probar almacenamiento

### Settings (3)
- `document_settings.view` - Ver configuración
- `document_settings.update` - Actualizar configuración
- `document_settings.reset` - Restablecer configuración

### Blockades (5)
- `document_blockades.view` - Ver bloqueos
- `document_blockades.create` - Crear bloqueos
- `document_blockades.update` - Actualizar bloqueos
- `document_blockades.delete` - Eliminar bloqueos
- `document_blockades.sync` - Sincronizar bloqueos

## Roles

| Role | Count | Key Permissions |
|------|-------|-----------------|
| **super-admin** | All | `documents.*`, `document_types.*`, etc. |
| **manager** | 12 | View + Configuration permissions |
| **administrative** | 20 | CRUD + Stage approval |

## Usage Patterns

### Controller
```php
$this->authorize('documents.create');
```

### Blade
```blade
@can('documents.update')
    Edit button
@endcan
```

### Livewire
```php
$this->authorize('documents.delete');
```

### Route
```php
->middleware('can:documents.view')
```

## Common Tinker Commands

```bash
# Check all permissions
Permission::all();

# Count permissions
Permission::count();

# Filter by prefix
Permission::where('name', 'like', 'documents.%')->get();

# Get role permissions
Role::findByName('administrative')->permissions;

# Check user permission
$user->hasPermissionTo('documents.create');

# Give user permission
$user->givePermissionTo('documents.delete');

# Assign role to user
$user->assignRole('administrative');
```

## File Locations

- **Seeder**: `/database/seeders/Documents/CreateDocumentPermissionsSeeder.php`
- **Guide**: `/docs/permissions/document-permissions-seeder.md`
- **Examples**: `/docs/permissions/document-permissions-usage-examples.md`
- **Setup**: `/docs/permissions/IMPLEMENTATION_GUIDE.md`
- **Module README**: `/database/seeders/Documents/README.md`

## Add New Permission

1. Edit `/database/seeders/Documents/CreateDocumentPermissionsSeeder.php`
2. Add to `definePermissions()` method
3. Run: `php artisan db:seed --class=...CreateDocumentPermissionsSeeder`

## Clear Cache

```bash
php artisan cache:clear
```

## Test Permission

```bash
php artisan tinker
$user = User::first();
$user->assignRole('administrative');
$user->can('documents.create');  # true
```
