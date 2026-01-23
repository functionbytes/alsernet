# Sistema de Validación Multi-Etapa para Documentos

**Versión:** 1.0
**Fecha:** 2025-12-19
**Autor:** Sistema de Gestión de Documentos

## Índice

1. [Descripción General](#descripción-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Grupos de Validación](#grupos-de-validación)
4. [Flujos de Trabajo](#flujos-de-trabajo)
5. [Modelos y Relaciones](#modelos-y-relaciones)
6. [Migraciones de Base de Datos](#migraciones-de-base-de-datos)
7. [API de Métodos](#api-de-métodos)
8. [Ejemplos de Uso](#ejemplos-de-uso)
9. [Integración con Blockades](#integración-con-blockades)
10. [Diagramas de Flujo](#diagramas-de-flujo)

---

## Descripción General

El sistema de validación multi-etapa permite que los documentos pasen por diferentes niveles de aprobación según:

- **Tipo de producto**: DNI vs. Armas (ESCOPETA, RIFLE, CORTA)
- **Requisito de financiación**: Si el documento requiere aprobación financiera

### Características Principales

- ✅ Validación por etapas con grupos de usuarios asignados
- ✅ Flujos dinámicos según tipo de producto
- ✅ Historial completo de validaciones (audit trail)
- ✅ Capacidad de aprobar, rechazar o devolver documentos
- ✅ Integración con sistema de blockades de productos

---

## Arquitectura del Sistema

### Componentes Principales

```
┌─────────────────────────────────────────────────────────────┐
│                        DOCUMENTO                             │
│  - requires_financing: boolean                               │
│  - validation_status: pending/in_validation/approved/rejected│
│  - current_stage: 1, 2, 3                                   │
│  - total_stages: 1, 2, 3                                    │
│  - current_validator_group: documentacion/licencias/...     │
├─────────────────────────────────────────────────────────────┤
│                    PRODUCTOS                                 │
│  - Detecta blockades (DNI, ESCOPETA, RIFLE, CORTA)         │
│  - Determina tipo de venta (getSaleType)                   │
└─────────────────────────────────────────────────────────────┘
         │                                  │
         ▼                                  ▼
┌──────────────────────┐         ┌──────────────────────┐
│  DocumentGroup       │         │ ValidationHistory    │
│  (Grupos validación) │         │ (Historial auditoría)│
├──────────────────────┤         ├──────────────────────┤
│ - documentacion      │         │ - stage_number       │
│ - licencias          │         │ - validator_group    │
│ - contabilidad       │         │ - action (approved)  │
└──────────────────────┘         │ - comments           │
         │                        │ - validated_at       │
         ▼                        └──────────────────────┘
┌──────────────────────┐
│  Users (Validadores) │
│  (Pivot: priority)   │
└──────────────────────┘
```

---

## Grupos de Validación

### Tabla: `document_groups`

Los grupos se identifican mediante un campo único `key` para referencias programáticas.

| ID | Nombre | Key | Descripción | Orden |
|----|--------|-----|-------------|-------|
| 2 | Documentacion | `documentacion` | Primera etapa: validación documental | 2 |
| 1 | Licencias | `licencias` | Segunda etapa: validación de permisos (solo armas) | 1 |
| 3 | Contabilidad | `contabilidad` | Tercera etapa: aprobación financiera | 3 |

### Modelo: `DocumentGroup`

```php
namespace App\Models\Document;

class DocumentGroup extends Model
{
    protected $fillable = [
        'name',
        'key',              // Identificador único
        'description',
        'assignment_mode',  // manual, round_robin, load_balanced
        'is_default',
        'is_active',
        'order',
    ];

    // Relaciones
    public function users(): BelongsToMany;

    // Métodos útiles
    public static function findByKey(string $key): ?self;
    public function getNextUser(): ?User;
}
```

### Asignación de Usuarios a Grupos

Los usuarios se asignan a grupos mediante la tabla pivot `document_group_user`:

```php
// Asignar usuario al grupo de documentación
$group = DocumentGroup::findByKey('documentacion');
$group->users()->attach($userId, ['priority' => 'primary']);

// Obtener usuarios del grupo
$users = $group->users;
$primaryUsers = $group->primaryUsers()->get();
```

---

## Flujos de Trabajo

### Matriz de Flujos

| Tipo Producto | Financiación | Total Etapas | Flujo |
|---------------|--------------|--------------|-------|
| **DNI** | ❌ No | 1 | documentacion → ✅ APROBADO |
| **DNI** | ✅ Sí | 2 | documentacion → contabilidad → ✅ APROBADO |
| **Armas** (ESCOPETA/RIFLE/CORTA) | ❌ No | 2 | documentacion → licencias → ✅ APROBADO |
| **Armas** (ESCOPETA/RIFLE/CORTA) | ✅ Sí | 3 | documentacion → licencias → contabilidad → ✅ APROBADO |

### Lógica de Determinación de Etapas

```php
/**
 * Document::determineWorkflowStages()
 *
 * Lógica:
 * - DNI sin financiación: 1 etapa (documentacion)
 * - DNI con financiación: 2 etapas (documentacion → contabilidad)
 * - Armas sin financiación: 2 etapas (documentacion → licencias)
 * - Armas con financiación: 3 etapas (documentacion → licencias → contabilidad)
 */
public function determineWorkflowStages(): int
{
    $saleType = $this->getSaleType(); // 'dni', 'escopeta', 'rifle', 'corta'
    $stages = 1; // Default: documentacion only

    $isWeapon = in_array($saleType, [
        DocumentProductBlockade::TYPE_ESCOPETA,
        DocumentProductBlockade::TYPE_RIFLE,
        DocumentProductBlockade::TYPE_CORTA,
    ]);

    if ($isWeapon) {
        $stages = 2; // documentacion + licencias

        if ($this->requires_financing) {
            $stages = 3; // + contabilidad
        }
    } else {
        // DNI inventaries
        if ($this->requires_financing) {
            $stages = 2; // documentacion + contabilidad
        }
    }

    return $stages;
}
```

### Mapeo de Etapas a Grupos

```php
/**
 * Document::getValidatorGroupForStage(int $stage)
 *
 * Retorna el grupo validador para cada etapa según tipo de producto
 */
public function getValidatorGroupForStage(int $stage): ?string
{
    // Etapa 1 siempre es documentacion
    if ($stage === 1) {
        return 'documentacion';
    }

    $isWeapon = in_array($this->getSaleType(), [
        DocumentProductBlockade::TYPE_ESCOPETA,
        DocumentProductBlockade::TYPE_RIFLE,
        DocumentProductBlockade::TYPE_CORTA,
    ]);

    // Etapa 2
    if ($stage === 2) {
        if ($isWeapon) {
            return 'licencias'; // Armas → licencias
        } else {
            return $this->requires_financing ? 'contabilidad' : null; // DNI → contabilidad
        }
    }

    // Etapa 3 (solo armas con financiación)
    if ($stage === 3) {
        return ($isWeapon && $this->requires_financing) ? 'contabilidad' : null;
    }

    return null;
}
```

---

## Modelos y Relaciones

### 1. Document (Documento Principal)

**Tabla:** `documents`

#### Campos de Workflow

```php
protected $fillable = [
    // ... campos existentes ...

    // Workflow fields
    'requires_financing',          // boolean - Requiere financiación
    'validation_status',           // string - Estado: pending, in_validation, approved, rejected
    'current_stage',               // integer - Etapa actual (1-3)
    'total_stages',                // integer - Total de etapas (1-3)
    'current_validator_group',     // string - Grupo actual: documentacion, licencias, contabilidad
    'validation_started_at',       // timestamp
    'validation_completed_at',     // timestamp
];

protected $casts = [
    'requires_financing' => 'boolean',
    'current_stage' => 'integer',
    'total_stages' => 'integer',
    'validation_started_at' => 'datetime',
    'validation_completed_at' => 'datetime',
];
```

#### Relaciones

```php
// Historial de validaciones
public function validationHistory(): HasMany
{
    return $this->hasMany(DocumentValidationHistory::class);
}

// Productos del documento
public function products(): HasMany
{
    return $this->hasMany(DocumentProduct::class);
}
```

### 2. DocumentValidationHistory (Historial de Validaciones)

**Tabla:** `document_validation_history`

```php
class DocumentValidationHistory extends Model
{
    protected $table = 'document_validation_history';

    protected $fillable = [
        'document_id',
        'stage_number',          // 1, 2, 3
        'validator_group',       // documentacion, licencias, contabilidad
        'validator_user_id',     // Usuario que validó
        'action',                // approved, rejected, returned
        'comments',              // Comentarios del validador
        'validated_at',          // Timestamp de validación
    ];

    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
            'stage_number' => 'integer',
        ];
    }

    // Relaciones
    public function document(): BelongsTo;
    public function validator(): BelongsTo; // User
}
```

### 3. DocumentProductBlockade (Bloqueos de Productos)

**Tabla:** `document_product_blockades`

```php
class DocumentProductBlockade extends Model
{
    const TYPE_DNI = 'dni';
    const TYPE_ESCOPETA = 'escopeta';
    const TYPE_RIFLE = 'rifle';
    const TYPE_CORTA = 'corta';

    protected $fillable = [
        'source_id',             // ID del ERP/PrestaShop
        'product_id',            // ID producto simple (nullable)
        'product_attribute_id',  // ID combinación producto (nullable)
        'blockade_type',         // dni, escopeta, rifle, corta
    ];

    // Verificar si existe blockade
    public static function hasBlockade(
        ?int $idProduct = null,
        ?int $idProductAttribute = null,
        ?string $blockadeType = null
    ): bool;
}
```

---

## Migraciones de Base de Datos

### Migración 1: Campos de Workflow en Documents

**Archivo:** `2025_12_19_140458_add_workflow_fields_to_documents_table.php`

```php
Schema::table('documents', function (Blueprint $table) {
    // Financing requirement
    $table->boolean('requires_financing')->default(false)->after('type');

    // Workflow tracking
    $table->string('validation_status', 50)->default('pending')->after('requires_financing');
    $table->unsignedInteger('current_stage')->default(1)->after('validation_status');
    $table->unsignedInteger('total_stages')->default(1)->after('current_stage');
    $table->string('current_validator_group', 100)->nullable()->after('total_stages');
    $table->timestamp('validation_started_at')->nullable()->after('current_validator_group');
    $table->timestamp('validation_completed_at')->nullable()->after('validation_started_at');

    // Index for queries
    $table->index(['validation_status', 'current_validator_group'], 'idx_validation_queue');
});
```

### Migración 2: Tabla de Historial de Validaciones

**Archivo:** `2025_12_19_140558_create_document_validation_history_table.php`

```php
Schema::create('document_validation_history', function (Blueprint $table) {
    $table->id();
    $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
    $table->unsignedInteger('stage_number'); // Stage 1, 2, 3
    $table->string('validator_group', 100); // documentacion, licencias, contabilidad
    $table->foreignId('validator_user_id')->nullable()->constrained('users')->onDelete('set null');
    $table->string('action', 50); // approved, rejected, returned
    $table->text('comments')->nullable();
    $table->timestamp('validated_at');
    $table->timestamps();

    // Indexes
    $table->index(['document_id', 'stage_number'], 'idx_document_stage');
    $table->index('validator_user_id', 'idx_validator');
});
```

### Migración 3: Campo Key en Document Groups

**Archivo:** `2025_12_19_152704_add_key_to_document_groups_table.php`

```php
// Agregar campo key para identificadores únicos
Schema::table('document_groups', function (Blueprint $table) {
    $table->string('key', 100)->nullable()->after('name');
});

// Actualizar grupos existentes con keys
$existingGroups = DB::table('document_groups')->get();
foreach ($existingGroups as $group) {
    $key = Str::slug($group->name);
    DB::table('document_groups')
        ->where('id', $group->id)
        ->update(['key' => $key]);
}

// Agregar constraint UNIQUE
Schema::table('document_groups', function (Blueprint $table) {
    $table->unique('key');
});

// Crear grupos de workflow
$workflowGroups = [
    ['name' => 'Administrativos', 'key' => 'administrative', 'order' => 10],
    ['name' => 'Gerencia', 'key' => 'manager', 'order' => 20],
    ['name' => 'Contabilidad Workflow', 'key' => 'accounting', 'order' => 30],
];
```

---

## API de Métodos

### Métodos de Inicialización

#### `initializeWorkflow()`

Inicializa el workflow de validación para un documento.

```php
$document->initializeWorkflow();

// Establece:
// - total_stages: según tipo de producto y financiación
// - current_stage: 1
// - validation_status: 'pending'
// - current_validator_group: 'documentacion'
// - validation_started_at: now()
```

### Métodos de Validación

#### `approveCurrentStage(User $validator, ?string $comments = null): bool`

Aprueba la etapa actual y avanza a la siguiente.

```php
$success = $document->approveCurrentStage($user, 'Todo correcto');

// Crea registro en document_validation_history
// Incrementa current_stage o marca como approved si es última etapa
```

#### `rejectDocument(User $validator, string $reason): bool`

Rechaza el documento en la etapa actual.

```php
$success = $document->rejectDocument($user, 'Falta DNI frontal');

// Crea registro en document_validation_history
// Marca validation_status = 'rejected'
// Detiene el workflow
```

#### `returnToStage(int $targetStage, User $validator, string $reason): bool`

Devuelve el documento a una etapa anterior.

```php
$success = $document->returnToStage(1, $user, 'Revisar documentación');

// Crea registro en document_validation_history
// Actualiza current_stage al valor especificado
// Actualiza current_validator_group
```

### Métodos de Consulta

#### `canUserValidate(User $user): bool`

Verifica si un usuario puede validar el documento en la etapa actual.

```php
if ($document->canUserValidate($user)) {
    // Mostrar botones de aprobar/rechazar
}

// Verifica:
// - Estado del documento (pending o in_validation)
// - Pertenencia del usuario al grupo actual
```

#### `getSaleType(): ?string`

Obtiene el tipo de venta basado en los blockades de productos.

```php
$saleType = $document->getSaleType();
// Retorna: 'dni', 'escopeta', 'rifle', 'corta', o null
```

#### `getAllBlockadeTypes(): array`

Obtiene todos los tipos de blockade únicos para productos del documento.

```php
$blockades = $document->getAllBlockadeTypes();
// Ejemplo: ['dni', 'escopeta']
```

---

## Ejemplos de Uso

### Ejemplo 1: Crear Documento desde ERP con Workflow

```php
use App\Models\Document\Document;

// Crear documento desde orden ERP
$document = Document::create([
    'order_id' => 12345,
    'customer_email' => 'cliente@example.com',
    'requires_financing' => true, // Cliente solicita financiación
    // ... otros campos
]);

// Agregar productos desde ERP
$this->createDocumentProductsFromErpData($document, $orderData);

// Inicializar workflow (IMPORTANTE: llamar después de agregar productos)
$document->initializeWorkflow();

// Estado inicial:
// - validation_status: 'pending'
// - current_stage: 1
// - current_validator_group: 'documentacion'
// - total_stages: depende del tipo de productos
```

### Ejemplo 2: Flujo de Validación Completo (DNI con Financiación)

```php
// Documento DNI con financiación
$document = Document::find(1);
$document->getSaleType(); // 'dni'
$document->requires_financing; // true
$document->total_stages; // 2

// ETAPA 1: Grupo Documentación
$userDocumentacion = User::find(10); // Usuario del grupo documentacion

if ($document->canUserValidate($userDocumentacion)) {
    $document->approveCurrentStage($userDocumentacion, 'Documentos verificados correctamente');
}

// Ahora el documento está en:
// - current_stage: 2
// - current_validator_group: 'contabilidad'
// - validation_status: 'in_validation'

// ETAPA 2: Grupo Contabilidad
$userContabilidad = User::find(20); // Usuario del grupo contabilidad

if ($document->canUserValidate($userContabilidad)) {
    $document->approveCurrentStage($userContabilidad, 'Financiación aprobada por 12 meses');
}

// Documento aprobado:
// - validation_status: 'approved'
// - validation_completed_at: 2025-12-19 15:30:00
// - current_validator_group: null
```

### Ejemplo 3: Flujo de Validación Completo (Rifle con Financiación)

```php
// Documento de rifle con financiación
$document = Document::find(2);
$document->getSaleType(); // 'rifle'
$document->requires_financing; // true
$document->total_stages; // 3

// ETAPA 1: Documentación
$userDoc = User::find(10);
$document->approveCurrentStage($userDoc, 'Documentos OK');

// current_stage: 2, current_validator_group: 'licencias'

// ETAPA 2: Licencias
$userLic = User::find(15);
$document->approveCurrentStage($userLic, 'Licencia tipo D válida hasta 2030');

// current_stage: 3, current_validator_group: 'contabilidad'

// ETAPA 3: Contabilidad
$userCont = User::find(20);
$document->approveCurrentStage($userCont, 'Crédito aprobado');

// validation_status: 'approved'
```

### Ejemplo 4: Rechazar Documento

```php
$userDocumentacion = User::find(10);

// Revisar documento
if ($document->canUserValidate($userDocumentacion)) {
    $document->rejectDocument(
        $userDocumentacion,
        'Falta DNI trasera. Cliente debe cargar ambas caras del DNI.'
    );
}

// Documento rechazado:
// - validation_status: 'rejected'
// - validation_completed_at: now()
// - Se creó registro en document_validation_history con action='rejected'
```

### Ejemplo 5: Devolver a Etapa Anterior

```php
$userLicencias = User::find(15);

// En etapa 2 (licencias), detecta problema con documentación
if ($document->current_stage === 2) {
    $document->returnToStage(
        1, // Volver a etapa 1 (documentacion)
        $userLicencias,
        'La licencia está borrosa, solicitar nueva foto'
    );
}

// Documento devuelto:
// - current_stage: 1
// - current_validator_group: 'documentacion'
// - validation_status: 'in_validation'
```

### Ejemplo 6: Consultar Historial de Validaciones

```php
$document = Document::find(1);

// Obtener historial completo
$history = $document->validationHistory()
    ->with('validator')
    ->orderBy('validated_at', 'asc')
    ->get();

foreach ($history as $entry) {
    echo "Etapa {$entry->stage_number} ({$entry->validator_group})\n";
    echo "Acción: {$entry->action}\n";
    echo "Validador: {$entry->validator->name}\n";
    echo "Fecha: {$entry->validated_at}\n";
    echo "Comentarios: {$entry->comments}\n";
    echo "---\n";
}

// Salida ejemplo:
// Etapa 1 (documentacion)
// Acción: approved
// Validador: Juan Pérez
// Fecha: 2025-12-19 10:00:00
// Comentarios: Documentos correctos
// ---
// Etapa 2 (licencias)
// Acción: approved
// Validador: María García
// Fecha: 2025-12-19 11:30:00
// Comentarios: Licencia tipo D vigente
// ---
```

---

## Integración con Blockades

### Detección Automática de Tipo de Venta

El sistema utiliza la tabla `document_product_blockades` para determinar el tipo de venta:

```php
// En DocumentsController al crear documento desde ERP
private function createDocumentProductsFromErpData(Document $document, array $orderData): void
{
    foreach ($resources as $linea) {
        $articulo = $linea['articulo'] ?? [];
        $idArticulo = $articulo['idarticulo'] ?? null;

        // Detectar blockades usando source_id (id_origen del ERP)
        if ($idArticulo) {
            $blockades = DocumentProductBlockade::where('source_id', $idArticulo)
                ->pluck('blockade_type')
                ->toArray();

            if (!empty($blockades)) {
                Log::info('Product blockades found', [
                    'document_uid' => $document->uid,
                    'id_articulo' => $idArticulo,
                    'blockades' => $blockades,
                ]);
            }
        }

        // Crear producto del documento
        $document->products()->create([
            'product_id' => $idArticulo,
            'product_name' => $name,
            'quantity' => $quantity,
            'price' => $unitPrice,
        ]);
    }

    // Después de agregar productos, inicializar workflow
    $document->initializeWorkflow();
}
```

### Sincronización de Blockades desde PrestaShop

```bash
# Comando Artisan para sincronizar blockades
php artisan migrate:product-blockades

# Resultado:
# Synced 1,620 blockades from PrestaShop:
# - DNI: 490
# - ESCOPETA: 274
# - RIFLE: 750
# - CORTA: 106
```

**Archivo:** `app/Console/Commands/MigrateProductBlockades.php`

```php
public function handle()
{
    $blockadeLabels = [
        'dni' => DocumentProductBlockade::TYPE_DNI,
        'escopeta' => DocumentProductBlockade::TYPE_ESCOPETA,
        'rifle' => DocumentProductBlockade::TYPE_RIFLE,
        'corta' => DocumentProductBlockade::TYPE_CORTA,
    ];

    $totalMigrated = 0;

    // Migrate combinations (aalv_combinaciones_import)
    $totalMigrated += $this->migrateFromTable(
        'aalv_combinaciones_import',
        'id_origen',
        'id_product_attribute',
        $blockadeLabels,
        'combination'
    );

    // Migrate simple inventaries (aalv_combinacionunica_import)
    $totalMigrated += $this->migrateFromTable(
        'aalv_combinacionunica_import',
        'id_origen',
        'id_product',
        $blockadeLabels,
        'product'
    );

    $this->info("Total migrated: {$totalMigrated} blockades");
}
```

---

## Diagramas de Flujo

### Diagrama 1: Flujo General de Validación

```
┌─────────────────────────────────────────┐
│  NUEVO DOCUMENTO                         │
│  - Importado desde ERP                   │
│  - Productos agregados                   │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  initializeWorkflow()                    │
│  - Detecta tipo de venta (blockades)    │
│  - Determina total_stages                │
│  - Establece current_stage = 1           │
│  - Asigna current_validator_group        │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  ETAPA 1: DOCUMENTACION                 │
│  - Usuario del grupo "documentacion"    │
│  - Revisa documentos cargados           │
└──────────────┬──────────────────────────┘
               │
         ┌─────┴─────┐
         │           │
         ▼           ▼
    [APROBAR]    [RECHAZAR]
         │           │
         │           └──────────────────────┐
         │                                  │
         ▼                                  ▼
┌──────────────────┐              ┌──────────────────┐
│ ¿Es DNI?         │              │ DOCUMENTO        │
└────┬─────────┬───┘              │ RECHAZADO        │
     │ SI      │ NO (arma)        │ - Status:rejected│
     │         │                  └──────────────────┘
     │         ▼
     │    ┌─────────────────────────────────┐
     │    │  ETAPA 2: LICENCIAS             │
     │    │  - Usuario del grupo "licencias"│
     │    │  - Valida permisos/licencias    │
     │    └──────────────┬──────────────────┘
     │                   │
     │              [APROBAR]
     │                   │
     ▼                   ▼
┌──────────────┐  ┌──────────────┐
│¿Financing?   │  │¿Financing?   │
└──┬───────┬───┘  └──┬───────┬───┘
   │ NO    │ SI      │ NO    │ SI
   │       │         │       │
   ▼       ▼         ▼       ▼
[APROBADO] │    [APROBADO]   │
           │                 │
           └─────────┬───────┘
                     │
                     ▼
         ┌───────────────────────────┐
         │ ETAPA 3: CONTABILIDAD     │
         │ - Aprobación financiera   │
         └──────────┬────────────────┘
                    │
               [APROBAR]
                    │
                    ▼
            ┌──────────────┐
            │  APROBADO    │
            │  COMPLETO    │
            └──────────────┘
```

### Diagrama 2: Matriz de Decisión de Etapas

```
┌──────────────┬─────────────┬──────────────┬─────────────────────────────┐
│ Tipo Producto│ Financiación│ Total Stages │ Flujo                       │
├──────────────┼─────────────┼──────────────┼─────────────────────────────┤
│ DNI          │ ❌ No       │      1       │ [1] documentacion           │
│              │             │              │  └─> APROBADO               │
├──────────────┼─────────────┼──────────────┼─────────────────────────────┤
│ DNI          │ ✅ Sí       │      2       │ [1] documentacion           │
│              │             │              │  └─> [2] contabilidad       │
│              │             │              │        └─> APROBADO         │
├──────────────┼─────────────┼──────────────┼─────────────────────────────┤
│ ESCOPETA     │ ❌ No       │      2       │ [1] documentacion           │
│ RIFLE        │             │              │  └─> [2] licencias          │
│ CORTA        │             │              │        └─> APROBADO         │
├──────────────┼─────────────┼──────────────┼─────────────────────────────┤
│ ESCOPETA     │ ✅ Sí       │      3       │ [1] documentacion           │
│ RIFLE        │             │              │  └─> [2] licencias          │
│ CORTA        │             │              │        └─> [3] contabilidad │
│              │             │              │              └─> APROBADO   │
└──────────────┴─────────────┴──────────────┴─────────────────────────────┘
```

### Diagrama 3: Estados del Documento

```
                    ┌──────────────┐
                    │   PENDING    │ (inicial)
                    │ current_stage│
                    │      = 1     │
                    └──────┬───────┘
                           │
                           ▼
                  ┌─────────────────┐
                  │ IN_VALIDATION   │
                  │ current_stage   │
                  │   incrementa    │
                  └─────┬───────────┘
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        ▼               ▼               ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│  APPROVED   │  │  REJECTED   │  │  RETURNED   │
│ (completed) │  │ (completed) │  │ (vuelve a   │
│             │  │             │  │  etapa X)   │
└─────────────┘  └─────────────┘  └──────┬──────┘
                                          │
                                          ▼
                                  ┌─────────────────┐
                                  │ IN_VALIDATION   │
                                  │ (reinicia ciclo)│
                                  └─────────────────┘
```

---

## Queries Útiles para Reportes

### 1. Documentos Pendientes por Grupo

```php
// Obtener documentos pendientes para el grupo "documentacion"
$pendingDocs = Document::where('validation_status', 'in_validation')
    ->where('current_validator_group', 'documentacion')
    ->with(['inventaries', 'customer'])
    ->orderBy('created_at', 'asc')
    ->get();
```

### 2. Estadísticas de Validación por Usuario

```sql
SELECT
    u.name as validator_name,
    dvh.validator_group,
    dvh.action,
    COUNT(*) as total_actions,
    AVG(TIMESTAMPDIFF(HOUR, d.created_at, dvh.validated_at)) as avg_hours_to_validate
FROM document_validation_history dvh
JOIN users u ON dvh.validator_user_id = u.id
JOIN documents d ON dvh.document_id = d.id
WHERE dvh.validated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY u.id, dvh.validator_group, dvh.action
ORDER BY validator_name, validator_group;
```

### 3. Documentos con Mayor Tiempo en Validación

```php
$slowDocuments = Document::where('validation_status', 'in_validation')
    ->whereNotNull('validation_started_at')
    ->orderByRaw('TIMESTAMPDIFF(HOUR, validation_started_at, NOW()) DESC')
    ->limit(20)
    ->get();
```

### 4. Tasa de Aprobación por Tipo de Producto

```sql
SELECT
    d.type,
    d.requires_financing,
    COUNT(*) as total_documents,
    SUM(CASE WHEN d.validation_status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN d.validation_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    ROUND(100.0 * SUM(CASE WHEN d.validation_status = 'approved' THEN 1 ELSE 0 END) / COUNT(*), 2) as approval_rate
FROM documents d
WHERE d.validation_status IN ('approved', 'rejected')
GROUP BY d.type, d.requires_financing;
```

---

## Notas de Implementación

### Consideraciones Importantes

1. **Inicialización del Workflow**: SIEMPRE llamar `initializeWorkflow()` después de agregar productos al documento.

2. **Detección de Blockades**: El sistema depende de que los productos tengan blockades configurados en `document_product_blockades`.

3. **Asignación de Usuarios a Grupos**: Asegurarse de que cada grupo de validación tenga al menos un usuario asignado.

4. **Permisos**: Implementar middleware o policies para verificar que solo usuarios del grupo correcto puedan validar documentos.

5. **Notificaciones**: Considerar implementar notificaciones cuando un documento avanza a la siguiente etapa.

6. **Métricas**: El campo `validation_started_at` permite calcular SLAs y tiempos de respuesta.

### Próximos Pasos Sugeridos

- [ ] Implementar controladores para validación (approve/reject endpoints)
- [ ] Crear vistas para cada grupo de validadores
- [ ] Agregar notificaciones por email/push cuando documento cambia de etapa
- [ ] Dashboard con KPIs de validación (tiempo promedio, tasa de aprobación)
- [ ] Filtros avanzados en listado de documentos (por etapa, grupo, estado)
- [ ] Políticas de autorización (Policy) para verificar permisos de validación
- [ ] Tests unitarios y de integración para el workflow

---

## Changelog

### Versión 1.0 (2025-12-19)

- ✅ Implementación inicial del sistema de validación multi-etapa
- ✅ Creación de modelos `Document`, `DocumentValidationHistory`, `DocumentGroup`
- ✅ Migraciones de base de datos para workflow y grupos
- ✅ Métodos de inicialización, aprobación, rechazo y devolución
- ✅ Integración con sistema de blockades de productos
- ✅ Sincronización de blockades desde PrestaShop
- ✅ Documentación completa del sistema

---

## Contacto y Soporte

Para preguntas o sugerencias sobre el sistema de validación de documentos, contactar al equipo de desarrollo.

**Documentación generada automáticamente** - 2025-12-19
