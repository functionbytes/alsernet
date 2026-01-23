# Role Module

Role and Permission Management module for Alsernet. Provides comprehensive RBAC (Role-Based Access Control) and ACL (Access Control List) functionality for managing user roles, permissions, and access control.

## Features

- **Role Management** - Create, read, update, delete user roles
- **Permission Management** - Define and manage granular permissions
- **Role Permissions** - Assign permissions to roles
- **User Roles** - Assign roles to users
- **ACL Traits** - HasRolesAndPermissions trait for models
- **Middleware** - CheckRolesAndPermissions and RoleMiddleware for protecting routes
- **Blade Directives** - Permission-based blade directives via PermissionBladeServiceProvider
- **Console Commands** - CLI tools for managing roles and permissions
- **Helper Functions** - PermissionHelper for common permission operations

## Installation

The module is automatically loaded as part of the Alsernet application. No additional installation required.

## Configuration

Role configuration is stored in `config/role.php` and can be accessed via:

```php
config('role.model.role')           // Role model class
config('role.model.permission')     // Permission model class
config('role.guards')               // Guard configuration
```

## Usage

### Assigning Roles to Users

```php
use Spatie\Permission\Models\Role;

$user = User::find(1);
$role = Role::findByName('admin');

$user->assignRole($role);
// or
$user->assignRole('admin');
```

### Assigning Permissions to Roles

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$role = Role::findByName('admin');
$permission = Permission::findByName('edit-users');

$role->givePermissionTo($permission);
```

### Checking Permissions

```php
// Check if user has permission
if ($user->hasPermissionTo('edit-users')) {
    // ...
}

// Check if user has role
if ($user->hasRole('admin')) {
    // ...
}
```

### Using Middleware

```php
Route::get('/users', [UserController::class, 'index'])
    ->middleware('check.roles.permissions:manager');
```

### Blade Directives

```blade
@can('edit-users')
    <button>Edit User</button>
@endcan

@role('admin')
    <div>Admin content</div>
@endrole
```

### Using the Trait

```php
use Modules\Role\Traits\Acl\HasRolesAndPermissions;

class CustomUser extends Model
{
    use HasRolesAndPermissions;
}
```

### Using the Helper

```php
use Modules\Role\Helpers\PermissionHelper;

$permissions = PermissionHelper::getAll();
$userPermissions = PermissionHelper::getUserPermissions($userId);
```

## Routes

### Manager Routes (`/manager/settings/roles/`)

- `GET /` - List all roles
- `GET /create` - Show create role form
- `POST /store` - Store new role
- `GET /{role}/show` - View role details
- `GET /{role}/edit` - Edit role form
- `POST /{role}/update` - Update role
- `DELETE /{role}` - Delete role
- `GET /{role}/permissions` - Show role permissions
- `POST /{role}/permissions` - Update role permissions
- `GET /{role}/users` - Show users with role
- `POST /{role}/users/assign` - Assign users to role
- `DELETE /{role}/users/{user}` - Remove user from role
- `POST /{role}/duplicate` - Duplicate a role

### Manager Routes (`/manager/settings/permissions/`)

- `GET /` - List all permissions
- `GET /create` - Show create permission form
- `POST /store` - Store new permission
- `GET /edit/{id}` - Edit permission form
- `POST /update` - Update permission
- `GET /destroy/{id}` - Delete permission

## Console Commands

### Create Roles

```bash
php artisan create-roles
```

### List Roles

```bash
php artisan list-roles
```

### Create Permissions

```bash
php artisan create-permissions
```

### List Permissions

```bash
php artisan list-permissions
```

### Assign Role to User

```bash
php artisan assign-role {user_id} {role}
```

### Assign Permission to Role

```bash
php artisan assign-permission {role} {permission}
```

### Fix Media Permissions

```bash
php artisan fix-media-permissions
```

## Architecture

```
Modules/Role/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Managers/Settings/Roles/
│   │   │       ├── RoleController.php        - Role CRUD operations
│   │   │       └── PermissionController.php  - Permission CRUD operations
│   │   ├── Requests/
│   │   │   └── Systems/RoleRequest.php       - Role form validation
│   │   └── Middleware/
│   │       ├── CheckRolesAndPermissions.php  - Permission checking middleware
│   │       └── RoleMiddleware.php            - Role middleware
│   ├── Traits/
│   │   └── Acl/HasRolesAndPermissions.php   - Model role/permission trait
│   ├── Helpers/
│   │   └── PermissionHelper.php              - Permission helper functions
│   ├── Console/
│   │   └── Commands/
│   │       ├── CreateRolesCommand.php
│   │       ├── CreatePermissionsCommand.php
│   │       ├── AssignRoleCommand.php
│   │       ├── AssignPermissionCommand.php
│   │       ├── ListRolesCommand.php
│   │       ├── ListPermissionsCommand.php
│   │       └── FixMediaPermissions.php
│   └── Providers/
│       ├── RoleServiceProvider.php           - Module bootstrap
│       └── PermissionBladeServiceProvider.php - Blade directives
├── config/
│   └── role.php                              - Configuration
├── routes/
│   └── managers.php                          - Admin routes
└── README.md                                 - Documentation
```

## Dependencies

- **Spatie Laravel Permission** - Role and permission management
- **Laravel Framework** - Core framework

## Integration Points

- **Spatie Permission Models** - Role and Permission from Spatie
- **User Model** - Uses HasRolesAndPermissions trait
- **Middleware Stack** - Integrated role/permission checking
- **Blade Templates** - Permission-based directives
- **Service Container** - Dependency injection
- **Console Commands** - Artisan command infrastructure

## Testing

Test role assignment and permissions:

```bash
php artisan tinker

$user = User::find(1);
$user->assignRole('admin');
$user->hasRole('admin'); // true
$user->hasPermissionTo('edit-users'); // true/false based on permissions
```

## Contributing

When adding new role/permission features:

1. Add endpoints to `RoleController` or `PermissionController`
2. Create form requests for validation in `app/Http/Requests/Systems/`
3. Add routes in `routes/managers.php`
4. Update this README with new features

## License

Proprietary - Alsernet
