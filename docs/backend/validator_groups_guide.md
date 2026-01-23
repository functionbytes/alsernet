# Guía de Grupos Validadores (Validator Groups)

## 📚 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Estructura de Base de Datos](#estructura-de-base-de-datos)
3. [Modelos de Eloquent](#modelos-de-eloquent)
4. [Gestión de Validadores](#gestión-de-validadores)
5. [Configuraciones por Grupo](#configuraciones-por-grupo)
6. [Historial de Cambios](#historial-de-cambios)
7. [Ejemplos Prácticos](#ejemplos-prácticos)

---

## Introducción

Los **Grupos Validadores** son la estructura central para administrar quiénes validan los documentos y bajo qué condiciones. Cada grupo puede tener:

- **Múltiples validadores** con diferentes niveles de prioridad
- **Configuraciones específicas** que definen qué pueden hacer
- **Historial completo** de cambios de configuración
- **Modos de asignación** automática (Round Robin, Load Balanced, etc.)

---

## Estructura de Base de Datos

### Tabla: `validator_groups`

```sql
CREATE TABLE validator_groups (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uid VARCHAR(255) UNIQUE,
    name VARCHAR(255),
    key VARCHAR(255) UNIQUE,
    description TEXT,
    assignment_mode VARCHAR(50) DEFAULT 'round_robin', -- round_robin, load_balanced, first_available
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX (is_active),
    INDEX (key),
    INDEX (sort_order)
);
```

### Tabla: `validator_group_user` (Tabla Pivote)

```sql
CREATE TABLE validator_group_user (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    validator_group_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    priority VARCHAR(50) DEFAULT 'primary', -- primary, backup
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE (validator_group_id, user_id),
    FOREIGN KEY (validator_group_id) REFERENCES validator_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Tabla: `validator_group_configurations`

```sql
CREATE TABLE validator_group_configurations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    validator_group_id BIGINT UNSIGNED,
    key VARCHAR(255),
    label VARCHAR(255),
    description TEXT,
    value BOOLEAN DEFAULT TRUE,
    category VARCHAR(100), -- validations, permissions, limits
    order INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE (validator_group_id, key),
    FOREIGN KEY (validator_group_id) REFERENCES validator_groups(id) ON DELETE CASCADE,
    INDEX (category),
    INDEX (is_active)
);
```

### Tabla: `validator_group_configuration_histories`

```sql
CREATE TABLE validator_group_configuration_histories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    validator_group_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    key VARCHAR(255),
    change_type VARCHAR(50), -- created, updated, deleted
    old_value JSON,
    new_value JSON,
    changed_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (validator_group_id) REFERENCES validator_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (validator_group_id),
    INDEX (changed_at)
);
```

---

## Modelos de Eloquent

### ValidatorGroup

```php
namespace App\Models\Validation;

use app\Library\Traits\HasUid;use App\Models\User;use app\Validation\ValidatorGroupConfiguration;use app\Validation\ValidatorGroupConfigurationHistory;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsToMany;use Illuminate\Database\Eloquent\Relations\HasMany;

class ValidatorGroup extends Model
{
    use HasUid;

    protected $table = 'validator_groups';

    protected $fillable = [
        'name',
        'key',
        'description',
        'assignment_mode',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // === RELATIONSHIPS ===

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'validator_group_user',
            'validator_group_id',
            'user_id'
        )->withPivot('priority', 'created_at');
    }

    public function primaryUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('priority', 'primary');
    }

    public function backupUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('priority', 'backup');
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(ValidatorGroupConfiguration::class);
    }

    public function configurationHistory(): HasMany
    {
        return $this->hasMany(ValidatorGroupConfigurationHistory::class);
    }

    // === FINDERS ===

    public static function findDefault(): ?self
    {
        return self::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public static function findByKey(string $key): ?self
    {
        return self::where('key', $key)
            ->where('is_active', true)
            ->first();
    }

    public static function getActiveOrdered()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    // === ASSIGNMENT LOGIC ===

    public function getNextUser(?string $entityType = null): ?User
    {
        $users = $this->primaryUsers()->get();

        if ($users->isEmpty()) {
            $users = $this->backupUsers()->get();
        }

        if ($users->isEmpty()) {
            return null;
        }

        return match ($this->assignment_mode) {
            'round_robin' => $this->getNextUserRoundRobin($users),
            'load_balanced' => $this->getNextUserLoadBalanced($users, $entityType),
            default => $users->first(),
        };
    }

    // === PERMISSIONS ===

    public function hasUser(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        return $this->users()->where('users.id', $userId)->exists();
    }

    public function canUserValidate(User|int $user): bool
    {
        return $this->is_active && $this->hasUser($user);
    }
}
```

### ValidatorGroupConfiguration

```php
namespace App\Models\Validation;

use app\Validation\ValidatorGroup;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidatorGroupConfiguration extends Model
{
    protected $fillable = [
        'validator_group_id',
        'key',
        'label',
        'description',
        'value',
        'category',
        'order',
        'is_active',
    ];

    protected $casts = [
        'value' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function validatorGroup(): BelongsTo
    {
        return $this->belongsTo(ValidatorGroup::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public static function getGroupConfigurations(
        int $groupId,
        ?string $category = null
    ) {
        $query = self::where('validator_group_id', $groupId)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('order');

        if ($category) {
            $query->where('category', $category);
        }

        return $query->get();
    }

    public static function isEnabled(int $groupId, string $key): bool
    {
        return self::where('validator_group_id', $groupId)
            ->where('key', $key)
            ->where('is_active', true)
            ->value('value') ?? false;
    }
}
```

### ValidatorGroupConfigurationHistory

```php
namespace App\Models\Validation;

use App\Models\User;use app\Validation\ValidatorGroup;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidatorGroupConfigurationHistory extends Model
{
    protected $fillable = [
        'validator_group_id',
        'user_id',
        'key',
        'change_type',
        'old_value',
        'new_value',
        'changed_at',
    ];

    protected $casts = [
        'old_value' => 'json',
        'new_value' => 'json',
        'changed_at' => 'datetime',
    ];

    public function validatorGroup(): BelongsTo
    {
        return $this->belongsTo(ValidatorGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## Gestión de Validadores

### Agregar Validador a un Grupo

```php
// Forma 1: Usando el método attach
$group = ValidatorGroup::find($groupId);
$group->users()->attach($userId, ['priority' => 'primary']);

// Forma 2: En una transacción con historial
DB::transaction(function () use ($group, $userId) {
    $group->users()->attach($userId, ['priority' => 'primary']);

    ValidatorGroupConfigurationHistory::create([
        'validator_group_id' => $group->id,
        'user_id' => auth()->id(),
        'key' => 'user_assignment',
        'change_type' => 'created',
        'old_value' => null,
        'new_value' => [
            'user_id' => $userId,
            'priority' => 'primary',
        ],
        'changed_at' => now(),
    ]);
});
```

### Cambiar Prioridad de Validador

```php
// De PRIMARY a BACKUP
$group = ValidatorGroup::find($groupId);
$group->users()->updateExistingPivot($userId, ['priority' => 'backup']);

// Registrar cambio
ValidatorGroupConfigurationHistory::create([
    'validator_group_id' => $group->id,
    'user_id' => auth()->id(),
    'key' => 'priority_change',
    'change_type' => 'updated',
    'old_value' => ['priority' => 'primary', 'user_id' => $userId],
    'new_value' => ['priority' => 'backup', 'user_id' => $userId],
    'changed_at' => now(),
]);
```

### Remover Validador de un Grupo

```php
$group = ValidatorGroup::find($groupId);
$group->users()->detach($userId);

// Registrar cambio
ValidatorGroupConfigurationHistory::create([
    'validator_group_id' => $group->id,
    'user_id' => auth()->id(),
    'key' => 'user_removal',
    'change_type' => 'deleted',
    'old_value' => [
        'user_id' => $userId,
        'name' => User::find($userId)->name,
    ],
    'new_value' => null,
    'changed_at' => now(),
]);
```

---

## Configuraciones por Grupo

### Crear Configuración

```php
ValidatorGroupConfiguration::create([
    'validator_group_id' => $groupId,
    'key' => 'can_approve',
    'label' => 'Puede Aprobar',
    'description' => 'Permite al grupo aprobar documentos',
    'value' => true,
    'category' => 'permissions',
    'order' => 1,
    'is_active' => true,
]);
```

### Actualizar Configuración

```php
$config = ValidatorGroupConfiguration::where('validator_group_id', $groupId)
    ->where('key', 'can_approve')
    ->first();

$oldValue = $config->value;

$config->update(['value' => false]);

// Registrar cambio
ValidatorGroupConfigurationHistory::create([
    'validator_group_id' => $groupId,
    'user_id' => auth()->id(),
    'key' => 'can_approve',
    'change_type' => 'updated',
    'old_value' => ['value' => $oldValue],
    'new_value' => ['value' => false],
    'changed_at' => now(),
]);
```

### Verificar Configuración

```php
// Verificar si habilitada
if (ValidatorGroupConfiguration::isEnabled($groupId, 'can_approve')) {
    // Permitir acción
}

// Obtener todas las configuraciones
$configs = ValidatorGroupConfiguration::getGroupConfigurations($groupId);

// Por categoría
$permissions = ValidatorGroupConfiguration::getGroupConfigurations(
    $groupId,
    'permissions'
);
```

---

## Historial de Cambios

### Obtener Historial

```php
$group = ValidatorGroup::find($groupId);

// Últimos 50 cambios
$history = $group->configurationHistory()
    ->with('user:id,name,email')
    ->latest('changed_at')
    ->paginate(50);

// Filtrar por tipo de cambio
$updates = $group->configurationHistory()
    ->where('change_type', 'updated')
    ->latest('changed_at')
    ->get();

// Cambios en últimas 24 horas
$recentChanges = $group->configurationHistory()
    ->where('changed_at', '>=', now()->subDay())
    ->orderBy('changed_at', 'desc')
    ->get();
```

### Mostrar Cambios (en Blade)

```blade
@foreach ($history as $change)
    <div class="history-item">
        <strong>{{ $change->user->name }}</strong>
        <span class="badge">{{ ucfirst($change->change_type) }}</span>

        <p class="text-muted">{{ $change->changed_at->diffForHumans() }}</p>

        @if ($change->change_type === 'updated')
            <p>
                <strong>{{ $change->key }}</strong><br>
                De: <code>{{ json_encode($change->old_value) }}</code><br>
                A: <code>{{ json_encode($change->new_value) }}</code>
            </p>
        @endif
    </div>
@endforeach
```

---

## Ejemplos Prácticos

### Crear Grupo Completo

```php
// Crear grupo
$group = ValidatorGroup::create([
    'name' => 'Revisión Técnica',
    'key' => 'revision_tecnica',
    'description' => 'Grupo responsable de revisar especificaciones técnicas',
    'assignment_mode' => 'load_balanced',
    'is_default' => false,
    'is_active' => true,
    'sort_order' => 2,
]);

// Agregar usuarios PRIMARY
$primaryUsers = User::whereIn('id', [1, 2, 3])->get();
foreach ($primaryUsers as $user) {
    $group->users()->attach($user->id, ['priority' => 'primary']);
}

// Agregar usuarios BACKUP
$backupUsers = User::whereIn('id', [4, 5])->get();
foreach ($backupUsers as $user) {
    $group->users()->attach($user->id, ['priority' => 'backup']);
}

// Crear configuraciones
$configs = [
    ['key' => 'can_approve', 'label' => 'Puede Aprobar', 'category' => 'permissions'],
    ['key' => 'can_reject', 'label' => 'Puede Rechazar', 'category' => 'permissions'],
    ['key' => 'check_calculations', 'label' => 'Verificar Cálculos', 'category' => 'validations'],
    ['key' => 'max_review_hours', 'label' => 'Máximo 48 Horas', 'category' => 'limits'],
];

foreach ($configs as $config) {
    ValidatorGroupConfiguration::create([
        'validator_group_id' => $group->id,
        'key' => $config['key'],
        'label' => $config['label'],
        'category' => $config['category'],
        'value' => true,
        'order' => 1,
        'is_active' => true,
    ]);
}
```

### Asignar Documento a Grupo

```php
$document = Document::find($docId);
$group = ValidatorGroup::findByKey('documentacion');

// Obtener próximo validador
$assignedUser = $group->getNextUser('Document');

if ($assignedUser) {
    $document->update([
        'assigned_user_id' => $assignedUser->id,
        'current_validator_group' => $group->key,
        'validation_status' => 'in_progress',
    ]);

    // Opcional: Enviar notificación
    $assignedUser->notify(new DocumentAssignedNotification($document));
}
```

### Validar Permisos Antes de Actuar

```php
// En un Controller o Policy
public function approve(Document $document)
{
    $user = auth()->user();
    $group = ValidatorGroup::findByKey($document->current_validator_group);

    // Verificar que usuario pertenece al grupo
    if (!$group->canUserValidate($user)) {
        abort(403, 'No tienes permisos para validar en este grupo');
    }

    // Verificar configuración de grupo
    if (!ValidatorGroupConfiguration::isEnabled($group->id, 'can_approve')) {
        abort(403, 'Este grupo no tiene permiso para aprobar');
    }

    // Proceder con aprobación
    // ...
}
```

### Panel de Configuración de Grupo

```php
// En Controller
public function showConfiguration(ValidatorGroup $group)
{
    return view('manager.validator-groups.configuration', [
        'group' => $group->load('users', 'configurations.validatorGroup'),
        'primaryUsers' => $group->primaryUsers()->get(),
        'backupUsers' => $group->backupUsers()->get(),
        'configurations' => ValidatorGroupConfiguration::getGroupConfigurations($group->id),
        'history' => $group->configurationHistory()
            ->with('user:id,name,email')
            ->latest('changed_at')
            ->limit(20)
            ->get(),
        'allUsers' => User::where('is_active', true)
            ->orderBy('name')
            ->get(),
    ]);
}
```

---

## Tests

```php
// tests/Unit/ValidatorGroupTest.php

public function test_group_respects_round_robin_assignment()
{
    $group = ValidatorGroup::factory()->create([
        'assignment_mode' => 'round_robin',
    ]);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $group->users()->attach([$user1->id, $user2->id], ['priority' => 'primary']);

    $assigned = $group->getNextUser();
    $this->assertNotNull($assigned);
}

public function test_can_track_configuration_changes()
{
    $group = ValidatorGroup::factory()->create();

    $config = ValidatorGroupConfiguration::create([
        'validator_group_id' => $group->id,
        'key' => 'test_key',
        'value' => true,
    ]);

    ValidatorGroupConfigurationHistory::create([
        'validator_group_id' => $group->id,
        'user_id' => auth()->id(),
        'key' => 'test_key',
        'change_type' => 'created',
        'old_value' => null,
        'new_value' => ['value' => true],
        'changed_at' => now(),
    ]);

    $this->assertEquals(1, $group->configurationHistory()->count());
}
```

---

**Última actualización:** 21 de Diciembre de 2025
