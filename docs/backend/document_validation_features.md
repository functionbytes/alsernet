# Sistema de Validación de Documentos - Nuevas Funcionalidades

## 📋 Descripción General

El sistema de validación de documentos ha sido expandido con tres características principales:

1. **Validación por Etapas (Steps)** - Flujos de validación multi-etapa configurables
2. **Perfiles de Validadores** - Asignación inteligente de validadores con múltiples prioridades
3. **Archivos Adicionales** - Carga de archivos complementarios durante la validación
4. **Validaciones por Grupo** - Configuraciones específicas para cada grupo validador

---

## 1. Validación por Etapas (Validation Stages)

### Concepto

Los documentos pueden pasar por múltiples **etapas de validación** consecutivas. Cada etapa es manejada por un **Validator Group** diferente.

### Estructura en Base de Datos

#### Tabla `document_types`
```sql
- validation_stages (JSON): Array de configuración de etapas
- current_stage (INT): Etapa actual del documento
- total_stages (INT): Total de etapas configuradas
```

#### Ejemplo de JSON en `validation_stages`

```json
[
  {
    "key": "documentacion",
    "order": 1,
    "conditions": {
      "requires_physical_documents": true
    }
  },
  {
    "key": "revisione_tecnica",
    "order": 2,
    "conditions": {
      "requires_financing": true
    }
  },
  {
    "key": "aprobacion_final",
    "order": 3,
    "conditions": {}
  }
]
```

### Modelos Relacionados

#### `DocumentType` (app/Models/Document/DocumentType.php)

```php
// Obtener etapas de validación
$stages = $documentType->getValidationStages();
// Retorna: ['documentacion', 'revisione_tecnica', 'aprobacion_final']

// Obtener etapas con condiciones completas
$stagesWithConditions = $documentType->getValidationStagesWithConditions();
// Retorna: Array con key, order y conditions
```

#### `Document` (app/Models/Document/Document.php)

```php
// Propiedades de control de etapas
$document->current_stage;           // INT: Etapa actual (1, 2, 3...)
$document->total_stages;            // INT: Total de etapas
$document->current_validator_group; // VARCHAR: Clave del grupo actual
$document->validation_status;       // VARCHAR: pending, in_progress, completed
```

#### `DocumentValidationHistory` (app/Models/Document/DocumentValidationHistory.php)

```php
// Registro de validaciones por etapa
$history = DocumentValidationHistory::where('document_id', $docId)->get();

foreach ($history as $record) {
    $record->stage_number;      // INT: Número de etapa (1, 2, 3...)
    $record->validator_group;   // VARCHAR: Clave del grupo validador
    $record->validator_user_id; // INT: ID del usuario validador
    $record->action;            // VARCHAR: approved, rejected, pending_revision
    $record->comments;          // TEXT: Comentarios del validador
    $record->validated_at;      // TIMESTAMP: Cuándo se validó
}
```

### Flujo de Validación

```
Documento Creado (current_stage = 1)
        ↓
   Asignar a Grupo "documentacion"
        ↓
   Validador revisa documentos (DocumentValidationHistory)
        ↓
   ¿Aprobado? → SI → Pasar a Stage 2 (current_stage = 2)
        ↓
        NO → Rechazar con comentarios
        ↓
   Asignar a Grupo "revisione_tecnica"
        ↓
   ... Repetir ciclo ...
        ↓
   Final: validation_status = 'completed'
```

### Implementación en Controlador

```php
// En DocumentsController
public function advanceValidationStage(Document $document, $action = 'approve')
{
    $currentStage = $document->current_stage;
    $documentType = $document->type;
    $stages = $documentType->getValidationStages();

    // Registrar acción de validación
    DocumentValidationHistory::create([
        'document_id' => $document->id,
        'stage_number' => $currentStage,
        'validator_group' => $document->current_validator_group,
        'validator_user_id' => auth()->id(),
        'action' => $action,
        'comments' => request('comments'),
        'validated_at' => now(),
    ]);

    if ($action === 'approve') {
        if ($currentStage < count($stages)) {
            // Avanzar a siguiente etapa
            $nextGroup = $stages[$currentStage]; // Next group key
            $document->update([
                'current_stage' => $currentStage + 1,
                'current_validator_group' => $nextGroup,
                'validation_status' => 'in_progress',
            ]);
        } else {
            // Última etapa completada
            $document->update([
                'validation_status' => 'completed',
                'validation_completed_at' => now(),
            ]);
        }
    }
}
```

---

## 2. Perfiles de Validadores (Validator Groups)

### Concepto

Un **ValidatorGroup** es un grupo de validadores que pueden tener **múltiples perfiles de prioridad**:

- **Primary**: Validadores principales que se asignan primero
- **Backup**: Validadores de respaldo si los principales no están disponibles

### Estructura en Base de Datos

#### Tabla `validator_groups`
```sql
- id (BIGINT PRIMARY KEY)
- uid (VARCHAR UNIQUE): Identificador único
- name (VARCHAR): Nombre del grupo
- key (VARCHAR): Clave única (ej: 'documentacion', 'revisione_tecnica')
- description (TEXT): Descripción
- assignment_mode (VARCHAR): 'round_robin', 'load_balanced', 'first_available'
- is_default (BOOLEAN): Grupo por defecto
- is_active (BOOLEAN): Grupo activo
- sort_order (INT): Orden de visualización
```

#### Tabla `validator_group_user`
```sql
- validator_group_id (BIGINT FK)
- user_id (BIGINT FK)
- priority (VARCHAR): 'primary' o 'backup'
- created_at (TIMESTAMP)
```

### Modelos

#### `ValidatorGroup` (app/Models/Validation/ValidatorGroup.php)

```php
// Obtener validadores por prioridad
$primaryUsers = $group->primaryUsers()->get();
$backupUsers = $group->backupUsers()->get();
$allUsers = $group->users()->get();

// Asignación inteligente de usuarios
$nextUser = $group->getNextUser('Document');
// Usa assignment_mode para determinar quién asignar

// Métodos útiles
$group->findDefault();              // Obtener grupo por defecto
$group->findByKey('documentacion'); // Obtener por clave
$group->getActiveOrdered();         // Obtener todos activos
$group->getByKeysInOrder(['doc', 'revision', 'aprobacion']);
```

### Modos de Asignación

#### 1. **Round Robin**
```php
// Los validadores se asignan en rotación
// Usuario 1 → Usuario 2 → Usuario 3 → Usuario 1 ...
$group->assignment_mode = 'round_robin';
```

#### 2. **Load Balanced**
```php
// Se asigna al validador con menos tareas pendientes
$group->assignment_mode = 'load_balanced';
```

#### 3. **First Available**
```php
// Se asigna al primer validador disponible
$group->assignment_mode = 'first_available';
```

### Implementación

```php
// En un Job o Controller
$document = Document::find($docId);
$group = ValidatorGroup::findByKey($document->current_validator_group);

// Obtener siguiente usuario según modo de asignación
$assignedUser = $group->getNextUser('Document');

// Asignar documento
$document->update([
    'assigned_user_id' => $assignedUser->id,
    'validation_status' => 'awaiting_validation',
]);

// Verificar si usuario puede validar
if ($group->canUserValidate(auth()->user())) {
    // Permitir validación
}
```

---

## 3. Archivos Adicionales (Additional Attachments)

### Concepto

Además de los documentos requeridos configurados en `document_type_requirements`, los documentos pueden tener **archivos adicionales** cargados durante cualquier etapa de validación.

### Estructura en Base de Datos

#### Tabla `documents`
```sql
- additional_attachments (JSON): Metadatos de archivos adicionales

Ejemplo:
[
  {
    "id": 12345,
    "name": "Nota_importante.pdf",
    "size": 512000,
    "mime_type": "application/pdf",
    "uploaded_by": 42,
    "uploaded_at": "2025-12-21T10:30:00",
    "stage": 2,
    "description": "Documentación complementaria"
  }
]
```

### Almacenamiento de Archivos

Usa **Spatie MediaLibrary** con colección `additional_attachments`:

```php
// Cargar archivo adicional
$document->addMedia(request()->file('file'))
    ->withCustomProperties([
        'stage' => $document->current_stage,
        'uploaded_by' => auth()->id(),
        'description' => request('description'),
    ])
    ->toMediaCollection('additional_attachments');

// Obtener archivos adicionales
$additionalFiles = $document->getMedia('additional_attachments');

// Sincronizar JSON después de cambios
$document->syncAdditionalAttachments();
$document->save();
```

### Validación en Controlador

```php
// En DocumentsController@store (upload handler)
if ($request->hasFile('additional_attachments')) {
    $uploadedCount = 0;

    foreach ($request->file('additional_attachments') as $file) {
        // Validar tipo de archivo
        $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'png'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            continue;
        }

        // Cargar archivo
        $document->addMedia($file)
            ->withCustomProperties([
                'stage' => $document->current_stage,
                'uploaded_by' => auth()->id(),
                'description' => $request->input('description'),
                'document_type' => 'additional_attachment',
            ])
            ->toMediaCollection('additional_attachments');

        $uploadedCount++;
    }

    return response()->json([
        'success' => true,
        'message' => "$uploadedCount archivos cargados",
        'total_attachments' => $document->getMedia('additional_attachments')->count(),
    ]);
}
```

### Acciones de Validación

Enum `ValidationAction` define qué acciones puede hacer cada validador:

```php
// En app/Enums/Document/ValidationAction.php
case UPLOAD_DOCUMENTS = 'upload_documents';
case ACCESS_ADDITIONAL_FILES = 'access_additional_files';
case REQUEST_CLARIFICATIONS = 'request_clarifications';
case APPROVE_STAGE = 'approve_stage';
case REJECT_STAGE = 'reject_stage';
```

---

## 4. Validaciones por Grupo (Group Configurations)

### Concepto

Cada **ValidatorGroup** puede tener **configuraciones específicas** que determinan:

- Qué tipos de validaciones puede hacer
- Requisitos de aprobación
- Límites de tiempo
- Acciones permitidas

### Estructura en Base de Datos

#### Tabla `validator_group_configurations`
```sql
- id (BIGINT PRIMARY KEY)
- validator_group_id (BIGINT FK): Referencia al grupo
- key (VARCHAR): Clave de configuración
- label (VARCHAR): Etiqueta para UI
- description (TEXT): Descripción
- value (BOOLEAN): Valor de la configuración
- category (VARCHAR): Categoría (ej: 'validations', 'permissions', 'limits')
- order (INT): Orden de visualización
- is_active (BOOLEAN): Activo o no
```

#### Tabla `validator_group_configuration_histories`
```sql
- id (BIGINT PRIMARY KEY)
- validator_group_id (BIGINT FK)
- user_id (BIGINT FK): Quién hizo el cambio
- change_type (VARCHAR): 'created', 'updated', 'deleted'
- old_value (JSON): Valor anterior
- new_value (JSON): Nuevo valor
- changed_at (TIMESTAMP)
```

### Modelos

#### `ValidatorGroupConfiguration` (app/Models/Validation/ValidatorGroupConfiguration.php)

```php
// Obtener configuraciones de un grupo
$configs = ValidatorGroupConfiguration::getGroupConfigurations($groupId);

// Por categoría
$validationConfigs = ValidatorGroupConfiguration::getGroupConfigurations(
    $groupId,
    'validations'
);

// Verificar si una configuración está habilitada
$canApprove = ValidatorGroupConfiguration::isEnabled($groupId, 'can_approve');
$canReject = ValidatorGroupConfiguration::isEnabled($groupId, 'can_reject');
```

### Ejemplo de Configuraciones

```
GRUPO: "documentacion" (Revisión de Documentos)
├── validations
│   ├── check_completeness (Verificar completitud)
│   ├── verify_authenticity (Verificar autenticidad)
│   └── validate_formats (Validar formatos)
├── permissions
│   ├── can_approve (Puede aprobar)
│   ├── can_reject (Puede rechazar)
│   └── can_request_changes (Puede solicitar cambios)
└── limits
    ├── max_review_time_hours (24)
    └── require_multiple_approvals (true)

GRUPO: "revisione_tecnica" (Revisión Técnica)
├── validations
│   ├── check_technical_requirements (Verificar requisitos técnicos)
│   ├── validate_specifications (Validar especificaciones)
│   └── check_calculations (Verificar cálculos)
├── permissions
│   ├── can_approve (Puede aprobar)
│   └── can_request_technical_review (Puede solicitar revisión técnica)
└── limits
    ├── max_review_time_hours (48)
```

### Historial de Cambios

```php
// Registro automático de cambios en configuración
ValidatorGroupConfigurationHistory::create([
    'validator_group_id' => $group->id,
    'user_id' => auth()->id(),
    'change_type' => 'updated',
    'old_value' => $oldConfig,
    'new_value' => $newConfig,
    'changed_at' => now(),
]);

// Obtener historial de un grupo
$history = $group->configurationHistory()
    ->latest('changed_at')
    ->paginate(50);
```

---

## Flujo Completo de Ejemplo

### Caso de Uso: Validación de Documento Financiero

```
1. Cliente sube documento tipo "Financiamiento"
   - Document creado con type_id → DocumentType
   - validation_stages = ['documentacion', 'revisione_tecnica', 'aprobacion_final']
   - current_stage = 1
   - validation_status = 'pending'

2. Sistema busca ValidatorGroup con key 'documentacion'
   - Group tiene 3 PRIMARY users + 2 BACKUP users
   - assignment_mode = 'load_balanced'
   - Sistema asigna al usuario con menos tareas

3. Validador revisa documentos
   - Puede cargar additional_attachments si necesita
   - Lee configuraciones del grupo:
     * can_approve: true
     * can_reject: true
     * check_completeness: true

4. Validador aprueba
   - DocumentValidationHistory.create():
     * stage_number = 1
     * action = 'approved'
     * validator_user_id = 5

5. Sistema avanza a Stage 2
   - current_stage = 2
   - Busca ValidatorGroup 'revisione_tecnica'
   - Asigna a siguiente usuario en el grupo
   - validation_status = 'in_progress'

6. Repite proceso para stages 2 y 3

7. Documento completado
   - validation_status = 'completed'
   - validation_completed_at = now()
   - DocumentValidationHistory completo con todas las etapas
```

---

## Consultas Útiles

### Obtener documentos en validación

```php
// Documentos esperando validación
$pending = Document::where('validation_status', 'pending')
    ->orWhere('validation_status', 'in_progress')
    ->get();

// Documentos asignados a usuario actual
$assignedToMe = Document::where('assigned_user_id', auth()->id())
    ->where('validation_status', 'in_progress')
    ->get();

// Documentos por etapa
$stage1Docs = Document::where('current_stage', 1)
    ->with(['type', 'validationHistory'])
    ->get();
```

### Obtener historial de validación completo

```php
$document = Document::find($docId);

$completePath = DocumentValidationHistory::where('document_id', $docId)
    ->orderBy('stage_number')
    ->orderBy('created_at')
    ->get();

// Mostrar flujo
foreach ($completePath as $step) {
    echo "Stage {$step->stage_number}: "
        . "{$step->validator->name} - {$step->action} "
        . "({$step->validated_at})";
}
```

### Cambiar configuración de grupo y registrar historial

```php
$group = ValidatorGroup::find($groupId);

DB::transaction(function () use ($group) {
    // Guardar valor anterior
    $oldValue = $group->configurations()->where('key', 'can_approve')->first();

    // Actualizar
    $oldValue->update(['value' => false]);

    // Registrar cambio
    ValidatorGroupConfigurationHistory::create([
        'validator_group_id' => $group->id,
        'user_id' => auth()->id(),
        'change_type' => 'updated',
        'old_value' => ['can_approve' => true],
        'new_value' => ['can_approve' => false],
        'changed_at' => now(),
    ]);
});
```

---

## Enums y Constantes

### ValidationAction

```php
// app/Enums/Document/ValidationAction.php
UPLOAD_DOCUMENTS = 'upload_documents'
ACCESS_ADDITIONAL_FILES = 'access_additional_files'
REQUEST_CLARIFICATIONS = 'request_clarifications'
APPROVE_STAGE = 'approve_stage'
REJECT_STAGE = 'reject_stage'
```

### Validation Status

```php
pending           // Pendiente de validación
in_progress       // En proceso de validación
completed         // Validación completada
rejected          // Rechazado en alguna etapa
awaiting_revision // Esperando revisión por solicitante
```

---

## Tests

```php
// tests/Feature/DocumentValidationTest.php

public function test_document_advances_through_validation_stages()
{
    $document = Document::factory()->create([
        'current_stage' => 1,
        'validation_status' => 'in_progress',
    ]);

    $group = ValidatorGroup::findByKey('documentacion');

    // Validar Stage 1
    DocumentValidationHistory::create([
        'document_id' => $document->id,
        'stage_number' => 1,
        'validator_group' => 'documentacion',
        'action' => 'approved',
        'validator_user_id' => auth()->id(),
    ]);

    $document->update([
        'current_stage' => 2,
        'current_validator_group' => 'revisione_tecnica',
    ]);

    $this->assertEquals(2, $document->current_stage);
    $this->assertEquals('revisione_tecnica', $document->current_validator_group);
}

public function test_validator_group_respects_assignment_mode()
{
    $group = ValidatorGroup::create([
        'name' => 'Test Group',
        'key' => 'test',
        'assignment_mode' => 'round_robin',
    ]);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $group->users()->attach([$user1->id, $user2->id], ['priority' => 'primary']);

    $assigned1 = $group->getNextUser();
    $this->assertEquals($user1->id, $assigned1->id);
}
```

---

## API Endpoints Recomendados

```
GET  /manager/documents/{id}/validation-history
GET  /manager/documents/{id}/validation-stages
POST /manager/documents/{id}/advance-validation
POST /manager/documents/{id}/upload-additional-files
GET  /manager/validator-groups/{id}/configurations
POST /manager/validator-groups/{id}/configurations
GET  /manager/validator-groups/{id}/configuration-history
```

---

**Última actualización:** 21 de Diciembre de 2025
