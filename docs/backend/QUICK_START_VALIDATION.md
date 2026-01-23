# Quick Start - Sistema de Validación de Documentos

> 📌 Esta guía es un resumen rápido. Para detalles completos, ver:
> - `docs/backend/document_validation_features.md` - Funcionalidades de validación
> - `docs/backend/validator_groups_guide.md` - Gestión de grupos validadores

---

## 🚀 Flujo Básico en 5 Pasos

### 1. Crear un Documento con Etapas

```php
// Crear DocumentType con etapas de validación
$documentType = DocumentType::create([
    'slug' => 'financing',
    'label' => 'Documento de Financiamiento',
    'icon' => 'fas fa-file-invoice-dollar',
    'color' => '#90bb13',
    'is_active' => true,
    'validation_stages' => [
        [
            'key' => 'documentacion',
            'order' => 1,
            'conditions' => []
        ],
        [
            'key' => 'revisione_tecnica',
            'order' => 2,
            'conditions' => ['requires_financing' => true]
        ],
        [
            'key' => 'aprobacion_final',
            'order' => 3,
            'conditions' => []
        ]
    ]
]);
```

### 2. Crear Grupos Validadores

```php
// Grupo: Revisión de Documentos
$docGroup = ValidatorGroup::create([
    'name' => 'Revisión de Documentos',
    'key' => 'documentacion',
    'assignment_mode' => 'round_robin',
    'is_default' => true,
    'is_active' => true,
    'sort_order' => 1
]);

// Agregar validadores PRIMARY
$docGroup->users()->attach([1, 2, 3], ['priority' => 'primary']);

// Agregar validadores BACKUP
$docGroup->users()->attach([4, 5], ['priority' => 'backup']);

// Grupo: Revisión Técnica
$techGroup = ValidatorGroup::create([
    'name' => 'Revisión Técnica',
    'key' => 'revisione_tecnica',
    'assignment_mode' => 'load_balanced',
    'is_active' => true,
    'sort_order' => 2
]);

$techGroup->users()->attach([6, 7], ['priority' => 'primary']);
```

### 3. Cargar Documento del Usuario

```php
// En DocumentsController
$document = Document::create([
    'uid' => \Str::uuid(),
    'type_id' => $documentType->id,
    'customer_email' => 'client@example.com',
    'current_stage' => 1,
    'total_stages' => 3,
    'current_validator_group' => 'documentacion',
    'validation_status' => 'pending',
]);

// Subir documentos requeridos
$document->addMedia(request()->file('document'))
    ->toMediaCollection('documents');
```

### 4. Asignar a Validador

```php
// Sistema busca grupo actual
$group = ValidatorGroup::findByKey($document->current_validator_group);

// Obtiene siguiente validador (según assignment_mode)
$validator = $group->getNextUser('Document');

// Asigna documento
$document->update([
    'assigned_user_id' => $validator->id,
    'validation_status' => 'in_progress'
]);
```

### 5. Validador Revisa y Avanza

```php
// Validador carga archivos adicionales si necesita
$document->addMedia(request()->file('extra_doc'))
    ->withCustomProperties([
        'stage' => $document->current_stage,
        'uploaded_by' => auth()->id(),
        'description' => 'Documentación complementaria'
    ])
    ->toMediaCollection('additional_attachments');

// Validador aprueba o rechaza
if ($request->input('action') === 'approve') {

    // Registrar validación
    DocumentValidationHistory::create([
        'document_id' => $document->id,
        'stage_number' => $document->current_stage,
        'validator_group' => $document->current_validator_group,
        'validator_user_id' => auth()->id(),
        'action' => 'approved',
        'comments' => $request->input('comments'),
        'validated_at' => now()
    ]);

    // Avanzar a siguiente etapa
    $stages = $document->type->getValidationStages();

    if ($document->current_stage < count($stages)) {
        // Más etapas pendientes
        $nextGroup = $stages[$document->current_stage];

        $document->update([
            'current_stage' => $document->current_stage + 1,
            'current_validator_group' => $nextGroup,
            'validation_status' => 'in_progress'
        ]);
    } else {
        // Completado
        $document->update([
            'validation_status' => 'completed',
            'validation_completed_at' => now()
        ]);
    }
}
```

---

## 📊 Consultas Rápidas

### Obtener Documentos Pendientes de Validación

```php
// Documentos esperando que un usuario específico valide
$myDocuments = Document::where('assigned_user_id', auth()->id())
    ->where('validation_status', 'in_progress')
    ->with(['type', 'validationHistory'])
    ->orderByDesc('created_at')
    ->paginate();
```

### Ver Historial Completo de Validación

```php
$document = Document::find($docId);

$history = DocumentValidationHistory::where('document_id', $docId)
    ->with('validator')
    ->orderBy('stage_number')
    ->orderBy('created_at')
    ->get();

// En Blade
@foreach($history as $step)
    <div class="validation-step">
        <h6>Etapa {{ $step->stage_number }} - {{ $step->validator_group }}</h6>
        <p>{{ $step->validator->name }} - {{ $step->action }}</p>
        <small>{{ $step->comments }}</small>
        <time>{{ $step->validated_at->format('d/m/Y H:i') }}</time>
    </div>
@endforeach
```

### Obtener Documentos por Grupo Validador

```php
// Documentos en etapa "documentacion"
$docGroupDocs = Document::where('current_validator_group', 'documentacion')
    ->where('validation_status', 'in_progress')
    ->get();

// Documentos completados
$completed = Document::where('validation_status', 'completed')
    ->with(['type', 'validationHistory'])
    ->get();
```

---

## ⚙️ Configuraciones de Grupo (Qué Puede Hacer Cada Grupo)

### Crear Configuración

```php
$group = ValidatorGroup::findByKey('documentacion');

ValidatorGroupConfiguration::create([
    'validator_group_id' => $group->id,
    'key' => 'can_approve',
    'label' => 'Puede Aprobar Documentos',
    'category' => 'permissions',
    'value' => true,
    'order' => 1,
    'is_active' => true
]);
```

### Verificar Configuración

```php
if (ValidatorGroupConfiguration::isEnabled($group->id, 'can_approve')) {
    // Permitir botón de aprobación
}
```

### Configuraciones Recomendadas por Grupo

```php
// GRUPO: Documentación
[
    'can_approve' => true,
    'can_reject' => true,
    'can_request_changes' => true,
    'check_completeness' => true,
    'verify_signatures' => true
]

// GRUPO: Revisión Técnica
[
    'can_approve' => true,
    'can_reject' => true,
    'check_calculations' => true,
    'verify_specs' => true,
    'requires_specialist_approval' => true
]

// GRUPO: Aprobación Final
[
    'can_approve' => true,
    'can_reject' => true,
    'final_authority' => true,
    'can_override_previous' => true
]
```

---

## 📝 Archivos Adicionales (Additional Attachments)

### Subir Archivo Adicional

```php
// El validador sube documentos complementarios
if ($request->hasFile('additional_file')) {
    $document->addMedia($request->file('additional_file'))
        ->withCustomProperties([
            'stage' => $document->current_stage,
            'uploaded_by' => auth()->id(),
            'description' => $request->input('description'),
            'type' => 'additional_attachment'
        ])
        ->toMediaCollection('additional_attachments');

    // Sincronizar JSON
    $document->syncAdditionalAttachments();
    $document->save();
}
```

### Obtener Archivos Adicionales

```php
$additionalFiles = $document->getMedia('additional_attachments');

@foreach($additionalFiles as $file)
    <div class="file-item">
        <a href="{{ $file->getUrl() }}">{{ $file->file_name }}</a>
        <small>{{ $file->getCustomProperty('description') }}</small>
    </div>
@endforeach
```

---

## 🔄 Historial de Cambios en Grupos

### Cambiar Configuración y Registrar

```php
DB::transaction(function () {
    $group = ValidatorGroup::find($groupId);

    $config = ValidatorGroupConfiguration::where('validator_group_id', $groupId)
        ->where('key', 'can_approve')
        ->first();

    $oldValue = $config->value;
    $config->update(['value' => false]);

    // Registrar cambio
    ValidatorGroupConfigurationHistory::create([
        'validator_group_id' => $group->id,
        'user_id' => auth()->id(),
        'key' => 'can_approve',
        'change_type' => 'updated',
        'old_value' => ['value' => $oldValue],
        'new_value' => ['value' => false],
        'changed_at' => now()
    ]);
});
```

### Ver Historial

```php
$group = ValidatorGroup::find($groupId);

$history = $group->configurationHistory()
    ->with('user:id,name')
    ->latest('changed_at')
    ->paginate(20);

@foreach($history as $change)
    <tr>
        <td>{{ $change->user->name }}</td>
        <td>{{ $change->key }}</td>
        <td>
            @if($change->change_type === 'updated')
                {{ $change->old_value['value'] ?? 'N/A' }} → {{ $change->new_value['value'] ?? 'N/A' }}
            @endif
        </td>
        <td>{{ $change->changed_at->diffForHumans() }}</td>
    </tr>
@endforeach
```

---

## 🎯 Casos de Uso Comunes

### Caso 1: Documento Rechazado en Etapa 1

```php
DocumentValidationHistory::create([
    'document_id' => $document->id,
    'stage_number' => 1,
    'validator_group' => 'documentacion',
    'validator_user_id' => auth()->id(),
    'action' => 'rejected',
    'comments' => 'Faltan documentos: DNI frente y dorso',
    'validated_at' => now()
]);

$document->update([
    'validation_status' => 'awaiting_revision',
    // El cliente sube documentos faltantes
]);

// Cuando cliente sube documentos, vuelve a estado pending para etapa 1
$document->update([
    'validation_status' => 'pending',
    'current_stage' => 1
]);
```

### Caso 2: Validador No Disponible

```php
$group = ValidatorGroup::findByKey('documentacion');

// Método 1: Asignar a siguiente PRIMARY
$primaryUsers = $group->primaryUsers()->get();
if ($primaryUsers->count() > 1) {
    // Rotar a siguiente
}

// Método 2: Asignar a BACKUP
$backupUser = $group->backupUsers()->first();
if ($backupUser) {
    $document->update(['assigned_user_id' => $backupUser->id]);
}
```

### Caso 3: Cambiar Modo de Asignación

```php
$group = ValidatorGroup::find($groupId);

// De Round Robin a Load Balanced
$group->update(['assignment_mode' => 'load_balanced']);

// Registrar
ValidatorGroupConfigurationHistory::create([
    'validator_group_id' => $group->id,
    'user_id' => auth()->id(),
    'key' => 'assignment_mode',
    'change_type' => 'updated',
    'old_value' => ['mode' => 'round_robin'],
    'new_value' => ['mode' => 'load_balanced'],
    'changed_at' => now()
]);
```

---

## 🧪 Tests Básicos

```php
// tests/Feature/DocumentValidationTest.php

public function test_document_advances_through_stages()
{
    $type = DocumentType::factory()->create([
        'validation_stages' => [
            ['key' => 'doc1', 'order' => 1],
            ['key' => 'doc2', 'order' => 2],
        ]
    ]);

    $doc = Document::factory()->create([
        'type_id' => $type->id,
        'current_stage' => 1
    ]);

    // Aprobación
    DocumentValidationHistory::create([
        'document_id' => $doc->id,
        'stage_number' => 1,
        'action' => 'approved'
    ]);

    // Avanzar
    $doc->update(['current_stage' => 2]);

    $this->assertEquals(2, $doc->fresh()->current_stage);
}

public function test_validator_group_assignment()
{
    $group = ValidatorGroup::factory()->create();
    $user = User::factory()->create();

    $group->users()->attach($user->id, ['priority' => 'primary']);

    $this->assertTrue($group->canUserValidate($user));
}
```

---

## 📚 Archivos Clave

| Archivo | Propósito |
|---------|-----------|
| `app/Models/Document/DocumentType.php` | Definición de tipos de documento con etapas |
| `app/Models/Document/Document.php` | Documento principal con control de validación |
| `app/Models/Document/DocumentValidationHistory.php` | Registro de cada validación por etapa |
| `app/Models/Validation/ValidatorGroup.php` | Grupos de validadores |
| `app/Models/Validation/ValidatorGroupConfiguration.php` | Configuraciones de grupo |
| `app/Models/Validation/ValidatorGroupConfigurationHistory.php` | Historial de cambios |
| `app/Http/Controllers/Administratives/Documents/DocumentsController.php` | Controlador de documentos |

---

**Última actualización:** 21 de Diciembre de 2025
