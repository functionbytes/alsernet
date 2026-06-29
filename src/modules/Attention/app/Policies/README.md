# Attention Module - Policies & Permissions

Este directorio contiene el sistema de permisos basado en Laravel Policies para el módulo Attention.

## Archivos en este Sistema

```
modules/Attention/
├── app/
│   ├── Policies/
│   │   ├── AttentionPolicy.php          ← Policy principal
│   │   └── README.md                    ← Este archivo
│   ├── Http/Middleware/
│   │   └── CheckAttentionPermission.php ← Middleware personalizado
│   ├── Traits/
│   │   └── HasAttentionPermissions.php  ← Helpers para User model
│   └── Providers/
│       ├── AttentionServiceProvider.php ← Registra el policy
│       └── BladeDirectivesServiceProvider.php ← Directivas Blade
├── database/seeders/
│   └── AttentionPermissionsSeeder.php   ← Crea permisos y roles
├── PERMISSIONS.md                        ← Documentación completa
└── PERMISSIONS_QUICK_REFERENCE.md       ← Guía rápida
```

## AttentionPolicy.php

Contiene 14 métodos de autorización:

### Permisos Básicos
- `viewAny()` - Ver listado de peticiones
- `view()` - Ver peticiones específico
- `create()` - Crear peticiones
- `update()` - Actualizar peticiones
- `delete()` - Eliminar peticiones

### Permisos Avanzados
- `manage()` - Gestión completa
- `assign()` - Asignar a usuario/departamento
- `changeStatus()` - Cambiar estado
- `resolve()` - Resolver peticiones
- `close()` - Cerrar peticiones

### Permisos Especiales
- `sendEmail()` - Enviar emails
- `manageNotes()` - Gestionar notas
- `viewHistory()` - Ver historial completo
- `viewAll()` - Ver todos sin filtros

## Uso Básico

### En Controladores

```php
// Verificar permiso automáticamente (lanza 403 si falla)
$this->authorize('update', $attention);

// Verificar sin excepción
if (auth()->user()->can('update', $attention)) {
    // Permitido
}
```

### En Vistas

```blade
@can('update', $attention)
    <button>Editar</button>
@endcan
```

### En Rutas

```php
Route::middleware('can:update,attention')
    ->put('/attention/{uid}', [AttentionController::class, 'update']);
```

## Lógica de Autorización

### Jerarquía de Acceso

1. **Super Admin** → Acceso completo a todo
2. **Usuario Asignado** → Puede gestionar su peticiones asignado
3. **Miembro del Departamento** → Puede gestionar peticiones del departamento
4. **Creador** → Puede ver y actualizar (con limitaciones)
5. **Permisos Específicos** → Usuarios con permisos de Spatie

### Ejemplo: ¿Quién puede actualizar un peticiones?

```php
public function update(User $user, Attention $attention): bool
{
    // Super settings
    if ($this->isSuperAdmin($user)) {
        return true;
    }

    // No se pueden actualizar peticiones cerrados
    if ($attention->status === 'closed') {
        return false;
    }

    // Creador (si no está resuelto)
    if ($attention->user_id === $user->id && $attention->status !== 'resolved') {
        return true;
    }

    // Usuario asignado
    if ($attention->assigned_user_id === $user->id) {
        return true;
    }

    // Miembro del departamento con permiso
    if ($this->belongsToAttentionDepartment($user, $attention) &&
        $user->hasPermissionTo('attention.update')) {
        return true;
    }

    return false;
}
```

## Instalación

### 1. Ejecutar Seeder

```bash
php artisan db:seed --class=Modules\\Attention\\Database\\Seeders\\AttentionPermissionsSeeder
```

Esto crea:
- 17 permisos
- 5 roles (super-admin, attention-admin, attention-manager, attention-agent, attention-user)

### 2. Agregar Trait al User Model

```php
// app/Models/User.php
use Modules\Attention\App\Traits\HasAttentionPermissions;

class User extends Authenticatable
{
    use HasAttentionPermissions;
}
```

### 3. Registrar Middleware (Opcional)

```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    'attention.permission' => \Modules\Attention\App\Http\Middleware\CheckAttentionPermission::class,
];
```

### 4. El Policy ya está registrado

```php
// modules/Attention/app/Providers/AttentionServiceProvider.php
Gate::policy(Attention::class, AttentionPolicy::class);
```

## Roles Creados Automáticamente

| Rol | Descripción | Permisos |
|-----|-------------|----------|
| `super-admin` | Super administrador | Todos |
| `attention-admin` | Administrador del módulo | Todos los de attention |
| `attention-manager` | Supervisor de peticiones | Ver todo, gestionar, asignar, resolver, cerrar |
| `attention-agent` | Agente de atención | Ver asignados, actualizar, resolver |
| `attention-user` | Usuario básico | Ver propios, crear |

## Asignar Roles

```php
// Asignar rol a usuario
$user->assignRole('attention-agent');

// Verificar rol
if ($user->hasRole('attention-settings')) {
    // Es settings
}

// Dar permiso específico
$user->givePermissionTo('attention.view-all');
```

## Helper Methods

El trait `HasAttentionPermissions` agrega estos métodos al modelo User:

```php
// Verificar permisos
$user->canManageAttention($attention)
$user->canViewAttention($attention)
$user->canUpdateAttention($attention)
$user->canDeleteAttention($attention)
$user->canResolveAttention($attention)
$user->canCloseAttention($attention)

// Verificar relaciones
$user->isAssignedTo($attention)
$user->isAttentionCreator($attention)
$user->belongsToAttentionDepartment($attention)

// Obtener peticiones accesibles
$user->accessibleAttentions() // Query Builder

// Obtener departamentos
$user->attentionDepartments() // Relación
```

## Restricciones por Estado

```php
// Estados disponibles
pending → in_progress → resolved → closed

// Restricciones
- No se pueden actualizar peticiones cerrados
- Solo peticiones resueltos se pueden cerrar
- Creadores no pueden actualizar peticiones resueltos
- No se puede resolver un peticiones ya resuelto o cerrado
```

## Ejemplos Completos

Ver archivos de ejemplo:
- **Controlador:** `app/Http/Controllers/AttentionControllerExample.php`
- **Vista:** `resources/views/examples/show-with-permissions.blade.php`

## Documentación

- **Documentación Completa:** `modules/Attention/PERMISSIONS.md`
- **Guía Rápida:** `modules/Attention/PERMISSIONS_QUICK_REFERENCE.md`

## Debugging

```php
// Ver permisos de un usuario
dd([
    'roles' => auth()->user()->getRoleNames(),
    'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
    'can_view' => auth()->user()->can('view', $attention),
    'can_update' => auth()->user()->can('update', $attention),
    'is_assigned' => auth()->user()->isAssignedTo($attention),
]);
```

## Comandos Útiles

```bash
# Limpiar cache de permisos
php artisan permission:cache-reset

# Limpiar todo el cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Ejecutar seeder nuevamente
php artisan db:seed --class=Modules\\Attention\\Database\\Seeders\\AttentionPermissionsSeeder
```

---

**Creado:** 2026-02-08
**Última Actualización:** 2026-02-08
