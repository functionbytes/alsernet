# Document Module - Spatie Permissions Architecture

## Complete Analysis

### 1. Permission Hierarchy (3 Levels)

```
Spatie Permission (Global)
    ├── Role (super-admin, manager, user)
    ├── Permission (authenticated action)
    └── RoleHasPermission (pivot)

Document Module Permissions (Scoped)
    ├── DocumentPermission (module-specific permissions)
    ├── DocumentValidatorGroup (groups for validators)
    ├── DocumentValidatorGroupPermission (pivot)
    └── DocumentValidatorGroupUser (pivot)

User -> Has Spatie Permissions + Has Document Groups
```

### 2. Entity Models

**DocumentPermission.php** (Module-specific)
- `id`, `name` (unique), `label`, `category`, `description`
- `is_active`, `sort_order`
- Relationships: `validatorGroups()` (BelongsToMany)
- Helper methods: `findByName()`, `findOrCreateByName()`

**DocumentValidatorGroup.php** (Module-specific)
- `id`, `name`, `key`, `description`
- `assignment_mode` (round_robin, load_balanced)
- `is_default`, `is_active`, `sort_order`
- Relationships:
  - `users()` - Many users (with priority: primary/backup)
  - `permissions()` - Many permissions (BelongsToMany)
- Methods:
  - `hasPermission(string)` - Check if group has permission
  - `givePermissionTo(string|DocumentPermission)` - Add permission
  - `revokePermissionFrom()` - Remove permission
  - `syncPermissions(array)` - Sync multiple permissions

**User Model** (Extended with Trait)
- Adds: `documentValidatorGroups()` relationship
- Adds: `HasDocumentPermissions` trait with 15+ permission methods

### 3. Database Structure

```sql
-- Document Module Custom Permissions
document_permissions
  ├── id (PK)
  ├── name (UNIQUE) - e.g., 'approve-documents'
  ├── label - e.g., 'Aprobar documentos'
  ├── category - e.g., 'validation'
  ├── description
  ├── is_active (BOOLEAN)
  ├── sort_order (INTEGER)
  └── timestamps

-- Validator Groups
document_validator_groups
  ├── id (PK)
  ├── uid (UUID)
  ├── name - e.g., 'Initial Validators'
  ├── key - e.g., 'initial-validators'
  ├── assignment_mode - 'round_robin' or 'load_balanced'
  ├── is_default (BOOLEAN)
  ├── is_active (BOOLEAN)
  ├── sort_order (INTEGER)
  └── timestamps

-- Group ↔ Permission Many-to-Many
document_validator_group_permission
  ├── id (PK)
  ├── validator_group_id (FK) → document_validator_groups
  ├── permission_id (FK) → document_permissions
  ├── UNIQUE(validator_group_id, permission_id)
  └── timestamps

-- Group ↔ User Many-to-Many
document_validator_group_user
  ├── id (PK)
  ├── validator_group_id (FK) → document_validator_groups
  ├── user_id (FK) → users
  ├── priority - 'primary' or 'backup'
  └── created_at
```

### 4. Gate Authorization Pattern

**DocumentsServiceProvider.php**
```php
protected function registerGates(): void
{
    // Explicit static gates for common actions
    Gate::define('configure-documents', fn ($user) => $settingsPolicy->configure($user));
    Gate::define('view-document-settings', fn ($user) => $settingsPolicy->viewSettings($user));
    // ... more specific gates

    // DYNAMIC gate handler for ALL other permissions
    Gate::before(function ($user, $ability) {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Check if this ability exists in DocumentPermission table
        if (class_exists('Modules\Document\Entities\DocumentPermission')) {
            $permission = DocumentPermission::where('name', $ability)->first();

            if ($permission) {
                // Get user's validator groups
                $userGroups = DocumentValidatorGroup::whereHas('users', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->get();

                // Check if any group has this permission
                foreach ($userGroups as $group) {
                    if ($group->permissions()->where('name', $ability)->exists()) {
                        return true;
                    }
                }

                return false;
            }
        }

        return null; // Let other gates handle it
    });
}
```

### 5. Permission Service (Centralized Logic)

**PermissionService.php**
```php
- can(User $user, string $action, ?string $profile): bool
  Checks super-admin, then Spatie permission, then specific logic

- getAvailableActions(User $user): array
  Returns all actions the user can perform

- getEmailActionsConfig(User $user): array
  Returns email action configuration merged from all groups

- isInValidatorGroup(User $user): bool
  Checks if user belongs to any active group

- getUserValidatorGroups(User $user): Collection
  Returns all groups user belongs to

- buildPermissionName(string $action, ?string $profile): string
  Formats permission names (e.g., 'administrative.documents.approve')
```

### 6. User Trait Methods (HasDocumentPermissions)

High-level permission checking on User:
```php
$user->documentValidatorGroups()  // Get all groups user belongs to
$user->canInDocumentModule('approve-documents')  // Check permission
$user->hasDocumentPermission('approve-documents')  // Alias
$user->canDocument('email-actions')  // Check component
$user->canActionDocumentComponent('email-actions', 'send-reminder')  // Action on component
$user->canEditDocumentField('status')  // Field-level permissions
$user->getDocumentGroupPermissions()  // Get all permissions across groups
$user->belongsToAnyDocumentGroup()  // Check if in any group
```

### 7. Usage in Controllers

```php
// Using Policy
Gate::authorize('approve-documents');  // Uses SettingsPolicy

// Using Service
$service = app(PermissionService::class);
if ($service->can($user, 'approve', 'administrative')) {
    // Allow action
}

// Using Trait on User
if (auth()->user()->can('administrative.documents.approve')) {
    // Spatie Permission check
}

if (auth()->user()->canInDocumentModule('approve-documents')) {
    // Module-scoped check
}
```

### 8. Seeding Permissions

**ModulePermissionsSeeder.php**
- Creates Spatie permissions for each module
- E.g., `modules.view.documents`, `modules.view.emails`, etc.

**DocumentPermissionSeeder.php**
- Creates DocumentPermission records scoped to Document module
- E.g., `approve-documents`, `reject-documents`, `upload-files`

---

## Key Architectural Decisions

### Why Two Permission Systems?

1. **Spatie Permission** - Global system-wide permissions
   - Pro: Centralized, integrates with roles
   - Con: Rigid for module-specific needs

2. **DocumentPermission + ValidatorGroup** - Module-specific
   - Pro: Flexible grouping, team-based assignments
   - Con: Requires dual check

**Result**: Users have BOTH global permissions AND group-scoped permissions
- Super-admin bypasses everything
- Other users need BOTH Spatie permission AND group permission to access

### Caching Strategy

DocumentValidatorGroup uses Redis caching:
```php
Cache::remember(
    "group_{$id}_has_permission_{$name}",
    3600,  // 1 hour
    fn () => $group->permissions()
        ->where('name', $name)
        ->where('is_active', true)
        ->exists()
);
```

Cache keys cleared when:
- Permission added to group
- Permission removed from group
- Permissions synced
- Group saved

### Gate::before() vs Gate::define()

**Document's approach:**
- Define specific gates: `configure-documents`, `manage-types`, etc.
- Use `Gate::before()` for dynamic permission checking
- `Gate::before()` runs FIRST on every gate check
- If returns true/false, stops execution
- If returns null, continues to defined gates

**Advantage:**
- Avoids Closure serialization in route cache
- Prevents duplicate permission definitions
- Single source of truth (DocumentPermission table)

---

## Implementation Checklist for Mailrelay

- [ ] Create MailrelayPermission entity
- [ ] Create MailrelayPermissionGroup entity
- [ ] Create migrations for both + pivot tables
- [ ] Create MailrelayPermissionService
- [ ] Create HasMailrelayPermissions trait
- [ ] Update MailrelayServiceProvider to use Gate::before()
- [ ] Create seeders for initial permissions
- [ ] Update User model to use trait
- [ ] Update controllers to use service/trait
- [ ] Test permission checks

