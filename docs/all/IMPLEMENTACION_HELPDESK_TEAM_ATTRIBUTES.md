# Implementación Completa: Team & Attributes para Helpdesk

## 📋 Estado Actual

### ✅ Completado
1. ✅ Migraciones creadas (5 archivos)
2. ✅ Modelos creados (3 archivos)
3. ✅ Controladores creados (2 archivos)
4. ✅ Vista Team Members Index creada

### 🔨 Archivos Pendientes

#### Vistas Blade (7 archivos)
1. `resources/views/managers/views/settings/helpdesk/team/member-edit.blade.php`
2. `resources/views/managers/views/settings/helpdesk/team/groups.blade.php`
3. `resources/views/managers/views/settings/helpdesk/team/group-create.blade.php`
4. `resources/views/managers/views/settings/helpdesk/team/group-edit.blade.php`
5. `resources/views/managers/views/settings/helpdesk/attributes/index.blade.php`
6. `resources/views/managers/views/settings/helpdesk/attributes/create.blade.php`
7. `resources/views/managers/views/settings/helpdesk/attributes/edit.blade.php`

#### Rutas (1 archivo)
- Agregar rutas en `routes/managers.php`

#### Políticas (2 archivos)
- `app/Policies/Helpdesk/GroupPolicy.php`
- `app/Policies/Helpdesk/CustomAttributePolicy.php`

#### Navegación (1 archivo)
- Actualizar `resources/views/managers/includes/nav.blade.php`

---

## 🚀 Próximos Pasos

### Paso 1: Registrar Rutas

Agregar en `routes/managers.php` dentro del grupo `Route::prefix('helpdesk')`:

```php
// TEAM SETTINGS
Route::prefix('backups/team')->name('manager.helpdesk.backups.team.')->group(function () {
    Route::get('members', [TeamController::class, 'membersIndex'])->name('members');
    Route::get('members/{id}/edit', [TeamController::class, 'memberEdit'])->name('member.edit');
    Route::put('members/{id}', [TeamController::class, 'memberUpdate'])->name('member.update');

    Route::get('groups', [TeamController::class, 'groupsIndex'])->name('groups');
    Route::get('groups/create', [TeamController::class, 'groupCreate'])->name('group.create');
    Route::post('groups', [TeamController::class, 'groupStore'])->name('group.store');
    Route::get('groups/{id}/edit', [TeamController::class, 'groupEdit'])->name('group.edit');
    Route::put('groups/{id}', [TeamController::class, 'groupUpdate'])->name('group.update');
    Route::delete('groups/{id}', [TeamController::class, 'groupDestroy'])->name('group.destroy');
});

// ATTRIBUTES SETTINGS
Route::prefix('backups/attributes')->name('manager.helpdesk.backups.attributes.')->group(function () {
    Route::get('/', [AttributesController::class, 'index'])->name('index');
    Route::get('create', [AttributesController::class, 'create'])->name('create');
    Route::post('/', [AttributesController::class, 'store'])->name('store');
    Route::get('{id}/edit', [AttributesController::class, 'edit'])->name('edit');
    Route::put('{id}', [AttributesController::class, 'update'])->name('update');
    Route::delete('{id}', [AttributesController::class, 'destroy'])->name('destroy');
    Route::post('{id}/toggle', [AttributesController::class, 'toggleActive'])->name('toggle');
});
```

### Paso 2: Agregar imports en el controlador de rutas

```php
use App\Http\Controllers\Managers\Helpdesk\Settings\TeamController;
use App\Http\Controllers\Managers\Helpdesk\Settings\AttributesController;
```

### Paso 3: Ejecutar Migraciones

```bash
/opt/homebrew/Cellar/php/8.4.4/bin/php artisan migrate --path=database/migrations/helpdesk --database=helpdesk
```

### Paso 4: Actualizar modelo User

Agregar relaciones en `app/Models/User.php`:

```php
use App\Models\Helpdesk\AgentSettings;
use App\Models\Helpdesk\Group;

public function agentSettings()
{
    return $this->hasOne(AgentSettings::class);
}

public function groups()
{
    return $this->belongsToMany(Group::class, 'helpdesk_group_user')
        ->withPivot('conversation_priority')
        ->withTimestamps(['created_at']);
}

public function acceptsConversations(): bool
{
    return $this->agentSettings?->acceptsConversationsNow() ?? false;
}
```

---

## 📁 Código de Archivos Pendientes

### 1. member-edit.blade.php (Vista de Edición de Miembro)

Características:
- Formulario de edición de agente
- Configuración de disponibilidad
- Límites de asignación
- Horarios de trabajo
- Asignación a grupos

**Tamaño estimado:** ~400 líneas

### 2. groups.blade.php (Lista de Grupos)

Características:
- Tabla de grupos con miembros
- Indicadores de grupo por defecto
- Modo de asignación
- Botones de editar/eliminar

**Tamaño estimado:** ~200 líneas

### 3. group-create.blade.php y group-edit.blade.php

Características:
- Formulario de creación/edición de grupo
- Selector de miembros con prioridades (primary/backup)
- Configuración de modo de asignación
- Checkbox de grupo por defecto

**Tamaño estimado:** ~300 líneas c/u

### 4. attributes/index.blade.php

Características:
- Tabla de atributos personalizados
- Filtros por tipo, formato, estado
- Toggle activo/inactivo
- Indicadores de atributos internos

**Tamaño estimado:** ~250 líneas

### 5. attributes/create.blade.php y edit.blade.php

Características:
- Formulario dinámico según tipo de atributo
- Configuración de opciones para select/checkbox
- Permisos granulares
- Validación de nombres únicos

**Tamaño estimado:** ~400 líneas c/u

---

## 🎨 Componentes JavaScript Requeridos

### Working Hours Component
```javascript
// resources/js/components/working-hours.js
// Componente para configurar horarios laborales
```

### Group Members Selector
```javascript
// resources/js/components/group-members-selector.js
// Selector de miembros con drag & drop y prioridades
```

### Attribute Config Builder
```javascript
// resources/js/components/attribute-config-builder.js
// Constructor dinámico de opciones para atributos
```

---

## 🔐 Políticas de Autorización

### GroupPolicy.php

```php
<?php

namespace App\Policies\Helpdesk;

use App\Models\User;
use App\Models\Helpdesk\Group;

class GroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['helpdesk.manage', 'helpdesk.view']);
    }

    public function view(User $user, Group $group): bool
    {
        return $user->hasAnyPermission(['helpdesk.manage', 'helpdesk.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('helpdesk.manage');
    }

    public function update(User $user, Group $group): bool
    {
        return $user->hasPermission('helpdesk.manage');
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->hasPermission('helpdesk.manage') && !$group->default;
    }
}
```

### CustomAttributePolicy.php

```php
<?php

namespace App\Policies\Helpdesk;

use App\Models\User;
use App\Models\Helpdesk\CustomAttribute;

class CustomAttributePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['helpdesk.manage', 'helpdesk.view']);
    }

    public function view(User $user, CustomAttribute $attribute): bool
    {
        return $user->hasAnyPermission(['helpdesk.manage', 'helpdesk.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('helpdesk.manage');
    }

    public function update(User $user, CustomAttribute $attribute): bool
    {
        return $user->hasPermission('helpdesk.manage');
    }

    public function delete(User $user, CustomAttribute $attribute): bool
    {
        return $user->hasPermission('helpdesk.manage') && !$attribute->internal;
    }
}
```

---

## 📝 Navegación

Agregar en el menú de Helpdesk Settings:

```blade
<!-- En resources/views/managers/includes/nav.blade.php -->
<li class="nav-item">
    <a class="nav-link" href="{{ route('manager.helpdesk.settings.team.members') }}">
        <i class="ti ti-users"></i>
        <span>Equipo</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link" href="{{ route('manager.helpdesk.settings.attributes.index') }}">
        <i class="ti ti-forms"></i>
        <span>Atributos</span>
    </a>
</li>
```

---

## ✅ Checklist de Implementación

- [x] Crear migraciones
- [x] Crear modelos
- [x] Crear controladores
- [x] Crear vista members index
- [ ] Crear vista member-edit
- [ ] Crear vistas de groups (index, create, edit)
- [ ] Crear vistas de attributes (index, create, edit)
- [ ] Registrar rutas
- [ ] Crear políticas
- [ ] Actualizar navegación
- [ ] Ejecutar migraciones
- [ ] Probar funcionalidad completa

---

## 🧪 Testing

```bash
# Verificar migraciones
/opt/homebrew/Cellar/php/8.4.4/bin/php artisan migrate:status --database=helpdesk

# Crear grupo de prueba
/opt/homebrew/Cellar/php/8.4.4/bin/php artisan tinker
>>> $group = App\Models\Helpdesk\Group::create(['name' => 'Soporte', 'assignment_mode' => 'round_robin'])
>>> $group->users()->attach(1, ['conversation_priority' => 'primary'])

# Crear atributo de prueba
>>> $attr = App\Models\Helpdesk\CustomAttribute::create([
    'name' => 'Prioridad',
    'key' => 'prioridad',
    'type' => 'conversation',
    'format' => 'select',
    'permission' => 'agentCanEdit',
    'config' => ['options' => [['name' => 'Alta', 'value' => 'high'], ['name' => 'Media', 'value' => 'medium']]]
])
```

---

¿Quieres que continúe creando las vistas restantes ahora?
