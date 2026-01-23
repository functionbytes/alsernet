# 📊 Resumen Completo: Implementación Helpdesk Team, Attributes, Statuses y Views

## ✅ COMPLETADO (Listo para usar)

### 1. Migraciones Database (5 archivos)
- ✅ `create_helpdesk_agent_settings_table.php`
- ✅ `create_helpdesk_groups_table.php`
- ✅ `create_helpdesk_group_user_table.php`
- ✅ `create_helpdesk_attributes_table.php`
- ✅ `create_helpdesk_attributables_table.php`

### 2. Modelos (3 archivos)
- ✅ `app/Models/Helpdesk/AgentSettings.php`
- ✅ `app/Models/Helpdesk/Group.php`
- ✅ `app/Models/Helpdesk/CustomAttribute.php`

### 3. Controladores (2 archivos)
- ✅ `app/Http/Controllers/Managers/Helpdesk/Settings/TeamController.php`
- ✅ `app/Http/Controllers/Managers/Helpdesk/Settings/AttributesController.php`

### 4. Rutas
- ✅ Registradas en `routes/managers.php` (líneas 1356-1382)

### 5. Modelo User
- ✅ Relaciones `agentSettings()`, `groups()`, `conversations()` agregadas
- ✅ Método `acceptsConversations()` implementado

### 6. Vista Inicial
- ✅ `resources/views/managers/views/settings/helpdesk/team/members.blade.php`

---

## 🔨 PENDIENTE DE COMPLETAR

### Paso 1: Ejecutar Migraciones

```bash
/opt/homebrew/Cellar/php/8.4.4/bin/php artisan migrate --path=database/migrations/helpdesk --database=helpdesk
```

### Paso 2: Crear Vistas Restantes (6 archivos)

Los archivos están en: `resources/views/managers/views/settings/helpdesk/`

#### A) Team Views

**1. `team/member-edit.blade.php`** (~400 líneas)
```blade
Contenido: Formulario completo de edición de agente con:
- Información básica (nombre, email, rol)
- Límites de asignación (assignment_limit)
- Disponibilidad (accepts_conversations: yes/no/working_hours)
- Configurador de horarios laborales (working_hours JSON)
- Selector de grupos con prioridades (primary/backup)
```

**2. `team/groups.blade.php`** (~250 líneas)
```blade
Contenido: Tabla de grupos con:
- Lista de grupos con miembros
- Badge de grupo por defecto
- Indicador de modo de asignación
- Botones editar/eliminar
- Contador de miembros online (opcional con Reverb)
```

**3. `team/group-create.blade.php`** (~350 líneas)
```blade
Contenido: Formulario de creación de grupo:
- Nombre del grupo
- Modo de asignación (round_robin, load_balanced, manual)
- Checkbox "Grupo por defecto"
- Selector de miembros con drag & drop
- Asignación de prioridades (primary/backup) por miembro
```

**4. `team/group-edit.blade.php`** (~350 líneas)
```blade
Contenido: Similar a group-create pero con datos pre-cargados
```

#### B) Attributes Views

**5. `attributes/index.blade.php`** (~300 líneas)
```blade
Contenido: Tabla de atributos personalizados:
- Filtros por tipo (conversation/customer/ticket)
- Filtros por formato (text/select/number/etc)
- Filtros por estado (active/inactive)
- Badge de atributo interno
- Toggle activo/inactivo
- Botones editar/eliminar
```

**6. `attributes/create.blade.php`** (~450 líneas)
```blade
Contenido: Formulario dinámico de creación:
- Nombre y key (auto-generado)
- Tipo de entidad (conversation/customer/ticket)
- Formato de campo (text, textarea, number, switch, rating, select, checkboxGroup, date)
- Checkbox "Requerido"
- Permisos (userCanView, userCanEdit, agentCanEdit)
- Descripción agente
- Nombre y descripción para cliente
- Constructor de opciones (para select/checkboxGroup)
- JavaScript para mostrar/ocultar campos según formato seleccionado
```

**7. `attributes/edit.blade.php`** (~450 líneas)
```blade
Contenido: Similar a create pero:
- Datos pre-cargados
- Restricciones para atributos internos
- Toggle de estado activo/inactivo
```

### Paso 3: Crear Políticas (2 archivos)

**`app/Policies/Helpdesk/GroupPolicy.php`**
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

**`app/Policies/Helpdesk/CustomAttributePolicy.php`**
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

Registrar en `app/Providers/AuthServiceProvider.php`:
```php
use App\Models\Helpdesk\Group;
use App\Models\Helpdesk\CustomAttribute;
use App\Policies\Helpdesk\GroupPolicy;
use App\Policies\Helpdesk\CustomAttributePolicy;

protected $policies = [
    Group::class => GroupPolicy::class,
    CustomAttribute::class => CustomAttributePolicy::class,
];
```

---

## 🆕 NUEVOS MÓDULOS: STATUSES Y VIEWS

### Módulo 1: Conversation Statuses

**Ruta:** `/admin/helpdesk/settings/statuses`

#### Migración
```bash
/opt/homebrew/Cellar/php/8.4.4/bin/php artisan make:migration create_helpdesk_conversation_statuses_table --path=database/migrations/helpdesk
```

**Esquema:**
```php
Schema::connection('helpdesk')->create('helpdesk_conversation_statuses', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('color', 7)->default('#000000'); // Hex color
    $table->integer('order')->default(0);
    $table->boolean('default')->default(false);
    $table->boolean('active')->default(true);
    $table->timestamps();

    $table->index('order');
    $table->index(['active', 'order']);
});
```

#### Modelo: `app/Models/Helpdesk/ConversationStatus.php`
```php
<?php

namespace App\Models\Helpdesk;

use Illuminate\Database\Eloquent\Model;

class ConversationStatus extends Model
{
    protected $connection = 'helpdesk';
    protected $table = 'helpdesk_conversation_statuses';

    protected $fillable = ['name', 'color', 'order', 'default', 'active'];

    protected $casts = [
        'order' => 'integer',
        'default' => 'boolean',
        'active' => 'boolean',
    ];

    public static function getDefault(): ?self
    {
        return static::where('default', true)->where('active', true)->first();
    }

    public static function getOrdered()
    {
        return static::where('active', true)->orderBy('order')->get();
    }
}
```

#### Controlador: `app/Http/Controllers/Managers/Helpdesk/Settings/StatusesController.php`
```php
<?php

namespace App\Http\Controllers\Managers\Helpdesk\Settings;

use App\Http\Controllers\Controller;
use App\Models\Helpdesk\ConversationStatus;
use Illuminate\Http\Request;

class StatusesController extends Controller
{
    public function index()
    {
        $statuses = ConversationStatus::orderBy('order')->get();
        return view('theme.views.backups.helpdesk.statuses.index', compact('statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:helpdesk_conversation_statuses,name',
            'color' => 'required|regex:/^#[0-9A-F]{6}$/i',
            'order' => 'required|integer|min:0',
            'default' => 'nullable|boolean',
        ]);

        if ($validated['default'] ?? false) {
            ConversationStatus::where('default', true)->update(['default' => false]);
        }

        ConversationStatus::create($validated);

        return back()->with('success', 'Estado creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $status = ConversationStatus::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:helpdesk_conversation_statuses,name,' . $id,
            'color' => 'required|regex:/^#[0-9A-F]{6}$/i',
            'order' => 'required|integer|min:0',
            'default' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ]);

        if ($validated['default'] ?? false) {
            ConversationStatus::where('id', '!=', $id)->update(['default' => false]);
        }

        $status->update($validated);

        return back()->with('success', 'Estado actualizado correctamente');
    }

    public function destroy($id)
    {
        $status = ConversationStatus::findOrFail($id);

        if ($status->default) {
            return back()->with('error', 'No se puede eliminar el estado por defecto');
        }

        $status->delete();

        return back()->with('success', 'Estado eliminado correctamente');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'statuses' => 'required|array',
            'statuses.*.id' => 'required|exists:helpdesk_conversation_statuses,id',
            'statuses.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['statuses'] as $statusData) {
            ConversationStatus::where('id', $statusData['id'])->update(['order' => $statusData['order']]);
        }

        return response()->json(['success' => true]);
    }
}
```

#### Vista: `resources/views/managers/views/settings/helpdesk/statuses/index.blade.php`
```blade
Contenido: Tabla con drag & drop para reordenar estados
- Color picker para cada estado
- Toggle activo/inactivo
- Indicador de estado por defecto
- Botones editar/eliminar
- Soporte para Sortable.js para drag & drop
```

#### Rutas (agregar en routes/managers.php):
```php
// Statuses Settings
Route::prefix('statuses')->name('statuses.')->group(function () {
    Route::get('/', [StatusesController::class, 'index'])->name('index');
    Route::post('/', [StatusesController::class, 'store'])->name('store');
    Route::put('{id}', [StatusesController::class, 'update'])->name('update');
    Route::delete('{id}', [StatusesController::class, 'destroy'])->name('destroy');
    Route::post('reorder', [StatusesController::class, 'reorder'])->name('reorder');
});
```

---

### Módulo 2: Conversation Views (Vistas Guardadas)

**Ruta:** `/admin/helpdesk/views`

#### Migración
```bash
/opt/homebrew/Cellar/php/8.4.4/bin/php artisan make:migration create_helpdesk_conversation_views_table --path=database/migrations/helpdesk
```

**Esquema:**
```php
Schema::connection('helpdesk')->create('helpdesk_conversation_views', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('icon')->nullable();
    $table->json('filters'); // Filtros JSON: status, priority, assigned_to, etc.
    $table->boolean('public')->default(false); // Visible para todos los agentes
    $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // Owner si es privada
    $table->integer('order')->default(0);
    $table->timestamps();

    $table->index('public');
    $table->index(['public', 'order']);
    $table->index('user_id');
});
```

#### Modelo: `app/Models/Helpdesk/ConversationView.php`
```php
<?php

namespace App\Models\Helpdesk;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ConversationView extends Model
{
    protected $connection = 'helpdesk';
    protected $table = 'helpdesk_conversation_views';

    protected $fillable = ['name', 'slug', 'icon', 'filters', 'public', 'user_id', 'order'];

    protected $casts = [
        'filters' => 'array',
        'public' => 'boolean',
        'order' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getPublicViews()
    {
        return static::where('public', true)->orderBy('order')->get();
    }

    public static function getUserViews($userId)
    {
        return static::where('user_id', $userId)->orderBy('order')->get();
    }

    public function applyFilters($query)
    {
        foreach ($this->filters as $key => $value) {
            if ($key === 'status') {
                $query->whereIn('status_id', (array) $value);
            } elseif ($key === 'assigned_to') {
                if ($value === 'me') {
                    $query->where('assigned_to', auth()->id());
                } elseif ($value === 'unassigned') {
                    $query->whereNull('assigned_to');
                } else {
                    $query->where('assigned_to', $value);
                }
            } elseif ($key === 'priority') {
                $query->where('priority', $value);
            } elseif ($key === 'created_after') {
                $query->where('created_at', '>=', $value);
            } elseif ($key === 'created_before') {
                $query->where('created_at', '<=', $value);
            }
            // Agregar más filtros según necesidad
        }

        return $query;
    }
}
```

#### Controlador: `app/Http/Controllers/Managers/Helpdesk/ViewsController.php`
```php
<?php

namespace App\Http\Controllers\Managers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Models\Helpdesk\ConversationView;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ViewsController extends Controller
{
    public function index()
    {
        $publicViews = ConversationView::getPublicViews();
        $userViews = ConversationView::getUserViews(auth()->id());

        return view('theme.views.helpdesk.views.index', compact('publicViews', 'userViews'));
    }

    public function create()
    {
        return view('theme.views.helpdesk.views.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'filters' => 'required|array',
            'public' => 'nullable|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['user_id'] = ($validated['public'] ?? false) ? null : auth()->id();

        ConversationView::create($validated);

        return redirect()->route('manager.helpdesk.views.index')
            ->with('success', 'Vista creada correctamente');
    }

    public function edit($id)
    {
        $view = ConversationView::findOrFail($id);

        // Solo el dueño o admin puede editar
        if ($view->user_id && $view->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        return view('theme.views.helpdesk.views.edit', compact('view'));
    }

    public function update(Request $request, $id)
    {
        $view = ConversationView::findOrFail($id);

        if ($view->user_id && $view->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'filters' => 'required|array',
            'public' => 'nullable|boolean',
        ]);

        $validated['user_id'] = ($validated['public'] ?? false) ? null : $view->user_id;

        $view->update($validated);

        return redirect()->route('manager.helpdesk.views.index')
            ->with('success', 'Vista actualizada correctamente');
    }

    public function destroy($id)
    {
        $view = ConversationView::findOrFail($id);

        if ($view->user_id && $view->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $view->delete();

        return back()->with('success', 'Vista eliminada correctamente');
    }
}
```

#### Rutas (agregar en routes/managers.php):
```php
// Conversation Views
Route::prefix('views')->name('views.')->group(function () {
    Route::get('/', [ViewsController::class, 'index'])->name('index');
    Route::get('create', [ViewsController::class, 'create'])->name('create');
    Route::post('/', [ViewsController::class, 'store'])->name('store');
    Route::get('{id}/edit', [ViewsController::class, 'edit'])->name('edit');
    Route::put('{id}', [ViewsController::class, 'update'])->name('update');
    Route::delete('{id}', [ViewsController::class, 'destroy'])->name('destroy');
});
```

---

## 📋 Checklist Final

### Team & Attributes
- [x] Migraciones creadas
- [x] Modelos creados
- [x] Controladores creados
- [x] Rutas registradas
- [x] Modelo User actualizado
- [x] Vista members index creada
- [ ] Vista member-edit
- [ ] Vista groups index
- [ ] Vista group-create
- [ ] Vista group-edit
- [ ] Vista attributes index
- [ ] Vista attributes create
- [ ] Vista attributes edit
- [ ] Políticas creadas
- [ ] Migración ejecutada

### Statuses
- [ ] Migración creada
- [ ] Modelo creado
- [ ] Controlador creado
- [ ] Rutas registradas
- [ ] Vista index con drag & drop
- [ ] Política creada
- [ ] Migración ejecutada

### Views
- [ ] Migración creada
- [ ] Modelo creado
- [ ] Controlador creado
- [ ] Rutas registradas
- [ ] Vista index (públicas y privadas)
- [ ] Vista create con constructor de filtros
- [ ] Vista edit
- [ ] Política creada
- [ ] Migración ejecutada

---

## 🧪 Testing Rápido

```bash
# 1. Ejecutar migraciones
/opt/homebrew/Cellar/php/8.4.4/bin/php artisan migrate --path=database/migrations/helpdesk --database=helpdesk

# 2. Crear datos de prueba
/opt/homebrew/Cellar/php/8.4.4/bin/php artisan tinker

# Crear grupo
$group = App\Models\Helpdesk\Group::create([
    'name' => 'Soporte Técnico',
    'assignment_mode' => 'load_balanced',
    'default' => true
]);

# Asignar agente al grupo
$group->users()->attach(1, ['conversation_priority' => 'primary']);

# Crear atributo
$attr = App\Models\Helpdesk\CustomAttribute::create([
    'name' => 'Prioridad',
    'key' => 'prioridad',
    'type' => 'conversation',
    'format' => 'select',
    'permission' => 'agentCanEdit',
    'config' => [
        'options' => [
            ['name' => 'Alta', 'value' => 'high'],
            ['name' => 'Media', 'value' => 'medium'],
            ['name' => 'Baja', 'value' => 'low']
        ]
    ]
]);

# Crear estado
$status = App\Models\Helpdesk\ConversationStatus::create([
    'name' => 'Abierto',
    'color' => '#3B82F6',
    'order' => 1,
    'default' => true
]);

# Crear vista
$view = App\Models\Helpdesk\ConversationView::create([
    'name' => 'Mis Tickets',
    'slug' => 'mis-tickets',
    'icon' => 'ti-inbox',
    'filters' => ['assigned_to' => 'me', 'status' => [1, 2]],
    'public' => false,
    'user_id' => 1
]);
```

---

¿Necesitas que te ayude a crear alguna de las vistas específicas o prefieres continuar tú mismo siguiendo este documento?
