# Arquitectura Polimórfica de Validación Multi-Etapa

## Objetivo

Crear un sistema de validación flexible que pueda utilizarse con diferentes entidades:
- **Documents** - Documentación de pedidos
- **Tickets** - Tickets de soporte/helpdesk
- **ReturnRequests** - Solicitudes de devolución
- **Otros** - Cualquier entidad futura que requiera validación por etapas

## Estructura de Base de Datos Propuesta

### 1. `validator_groups` (Reemplaza document_groups)

Grupos de validadores genéricos que pueden usarse para cualquier tipo de validación.

```sql
CREATE TABLE validator_groups (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uid VARCHAR(36) UNIQUE NOT NULL,
    name VARCHAR(191) NOT NULL,
    key VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL,
    assignment_mode ENUM('manual', 'round_robin', 'load_balanced') DEFAULT 'manual',
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Ejemplo de datos:
-- key: 'documentacion', 'licencias', 'contabilidad', 'soporte_nivel_1', 'soporte_nivel_2'
```

### 2. `validator_group_user` (Pivote)

Relación muchos a muchos entre grupos y usuarios.

```sql
CREATE TABLE validator_group_user (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    validator_group_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    priority ENUM('primary', 'backup') DEFAULT 'primary',
    created_at TIMESTAMP NULL,

    FOREIGN KEY (validator_group_id) REFERENCES validator_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_user (validator_group_id, user_id)
);
```

### 3. `validation_workflows` (NUEVO)

Define flujos de validación reutilizables.

```sql
CREATE TABLE validation_workflows (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uid VARCHAR(36) UNIQUE NOT NULL,
    name VARCHAR(191) NOT NULL,
    key VARCHAR(100) UNIQUE NOT NULL,           -- Ej: 'document_dni', 'document_arma', 'ticket_standard'
    description TEXT NULL,
    entity_type VARCHAR(100) NOT NULL,          -- Ej: 'document', 'ticket', 'return_request'
    conditions JSON NULL,                        -- Condiciones para seleccionar este workflow
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Ejemplo de conditions JSON:
-- Para documents: {"sale_type": "arma", "requires_financing": true}
-- Para tickets: {"priority": "high", "category": "technical"}
```

### 4. `validation_workflow_stages` (NUEVO)

Define las etapas de cada workflow.

```sql
CREATE TABLE validation_workflow_stages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    workflow_id BIGINT UNSIGNED NOT NULL,
    validator_group_id BIGINT UNSIGNED NOT NULL,
    stage_number INT UNSIGNED NOT NULL,          -- 1, 2, 3...
    name VARCHAR(191) NOT NULL,                  -- Nombre descriptivo de la etapa
    description TEXT NULL,
    is_optional BOOLEAN DEFAULT FALSE,           -- Etapa puede saltarse
    skip_conditions JSON NULL,                   -- Condiciones para saltar etapa
    auto_approve_conditions JSON NULL,           -- Condiciones para auto-aprobar
    sla_hours INT UNSIGNED NULL,                 -- SLA en horas para esta etapa
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (workflow_id) REFERENCES validation_workflows(id) ON DELETE CASCADE,
    FOREIGN KEY (validator_group_id) REFERENCES validator_groups(id),
    UNIQUE KEY unique_workflow_stage (workflow_id, stage_number)
);

-- Ejemplo de skip_conditions:
-- {"requires_financing": false} - Saltar etapa de contabilidad si no requiere financiación
```

### 5. `validations` (Reemplaza document_validation_history)

Historial de validaciones **polimórfico**.

```sql
CREATE TABLE validations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uid VARCHAR(36) UNIQUE NOT NULL,

    -- Relación polimórfica
    validatable_id BIGINT UNSIGNED NOT NULL,
    validatable_type VARCHAR(100) NOT NULL,      -- App\Models\Document\Document, App\Models\Ticket, etc.

    -- Workflow y etapa
    workflow_id BIGINT UNSIGNED NULL,
    stage_id BIGINT UNSIGNED NULL,
    stage_number INT UNSIGNED NOT NULL,

    -- Quién validó
    validator_group_key VARCHAR(100) NOT NULL,
    validator_user_id BIGINT UNSIGNED NULL,

    -- Acción realizada
    action ENUM('approved', 'rejected', 'returned', 'skipped', 'auto_approved') NOT NULL,
    comments TEXT NULL,
    internal_notes TEXT NULL,                    -- Notas solo para admins

    -- Metadata adicional
    metadata JSON NULL,                          -- Datos específicos de la validación

    -- Timestamps
    validated_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (workflow_id) REFERENCES validation_workflows(id) ON DELETE SET NULL,
    FOREIGN KEY (stage_id) REFERENCES validation_workflow_stages(id) ON DELETE SET NULL,
    FOREIGN KEY (validator_user_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_validatable (validatable_type, validatable_id),
    INDEX idx_workflow (workflow_id),
    INDEX idx_validator (validator_user_id)
);
```

### 6. Campos en entidades validables (Documents, Tickets, etc.)

Cada entidad que use validación tendrá estos campos:

```sql
-- Agregar a documents, tickets, return_requests, etc:
ALTER TABLE {entity_table} ADD COLUMN validation_workflow_id BIGINT UNSIGNED NULL;
ALTER TABLE {entity_table} ADD COLUMN validation_status VARCHAR(50) DEFAULT 'pending';
ALTER TABLE {entity_table} ADD COLUMN current_validation_stage INT UNSIGNED DEFAULT 0;
ALTER TABLE {entity_table} ADD COLUMN total_validation_stages INT UNSIGNED DEFAULT 0;
ALTER TABLE {entity_table} ADD COLUMN current_validator_group_id BIGINT UNSIGNED NULL;
ALTER TABLE {entity_table} ADD COLUMN assigned_validator_id BIGINT UNSIGNED NULL;
ALTER TABLE {entity_table} ADD COLUMN validation_started_at TIMESTAMP NULL;
ALTER TABLE {entity_table} ADD COLUMN validation_completed_at TIMESTAMP NULL;
```

---

## Modelos y Trait

### Trait: `HasValidationWorkflow`

```php
<?php

namespace App\Traits;

use App\Models\Validation\Validation;use App\Models\Validation\ValidationWorkflow;use app\Validation\ValidatorGroup;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasValidationWorkflow
{
    // ==========================================
    // RELACIONES
    // ==========================================

    public function validations(): MorphMany
    {
        return $this->morphMany(Validation::class, 'validatable');
    }

    public function validationWorkflow(): BelongsTo
    {
        return $this->belongsTo(ValidationWorkflow::class, 'validation_workflow_id');
    }

    public function currentValidatorGroup(): BelongsTo
    {
        return $this->belongsTo(ValidatorGroup::class, 'current_validator_group_id');
    }

    public function assignedValidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_validator_id');
    }

    // ==========================================
    // INICIALIZACIÓN
    // ==========================================

    /**
     * Inicializa el workflow de validación para esta entidad
     */
    public function initializeValidationWorkflow(?string $workflowKey = null): bool
    {
        $workflow = $workflowKey
            ? ValidationWorkflow::findByKey($workflowKey)
            : $this->determineWorkflow();

        if (!$workflow) {
            return false;
        }

        $stages = $workflow->stages()->ordered()->get();
        $applicableStages = $this->filterApplicableStages($stages);

        $this->validation_workflow_id = $workflow->id;
        $this->total_validation_stages = $applicableStages->count();
        $this->current_validation_stage = 1;
        $this->validation_status = 'pending';
        $this->validation_started_at = now();

        // Asignar primer grupo validador
        $firstStage = $applicableStages->first();
        if ($firstStage) {
            $this->current_validator_group_id = $firstStage->validator_group_id;
        }

        return $this->save();
    }

    /**
     * Determina qué workflow usar basado en condiciones de la entidad
     * DEBE ser implementado por cada modelo
     */
    abstract protected function determineWorkflow(): ?ValidationWorkflow;

    /**
     * Filtra etapas aplicables según condiciones
     */
    protected function filterApplicableStages($stages)
    {
        return $stages->filter(function ($stage) {
            if (!$stage->is_optional) {
                return true;
            }

            // Evaluar skip_conditions
            if ($stage->skip_conditions) {
                return !$this->evaluateConditions($stage->skip_conditions);
            }

            return true;
        })->values();
    }

    // ==========================================
    // ACCIONES DE VALIDACIÓN
    // ==========================================

    /**
     * Aprobar la etapa actual
     */
    public function approveCurrentStage(
        $validator,
        ?string $comments = null,
        ?int $assignToUserId = null
    ): bool {
        if (!$this->canBeValidated()) {
            return false;
        }

        // Registrar aprobación
        $this->validations()->create([
            'workflow_id' => $this->validation_workflow_id,
            'stage_id' => $this->getCurrentStageId(),
            'stage_number' => $this->current_validation_stage,
            'validator_group_key' => $this->currentValidatorGroup?->key,
            'validator_user_id' => $validator->id,
            'action' => 'approved',
            'comments' => $comments,
            'validated_at' => now(),
        ]);

        // ¿Es la última etapa?
        if ($this->current_validation_stage >= $this->total_validation_stages) {
            return $this->completeValidation();
        }

        // Avanzar a siguiente etapa
        return $this->advanceToNextStage($assignToUserId);
    }

    /**
     * Rechazar en la etapa actual
     */
    public function rejectValidation($validator, string $reason): bool
    {
        if (!$this->canBeValidated()) {
            return false;
        }

        $this->validations()->create([
            'workflow_id' => $this->validation_workflow_id,
            'stage_id' => $this->getCurrentStageId(),
            'stage_number' => $this->current_validation_stage,
            'validator_group_key' => $this->currentValidatorGroup?->key,
            'validator_user_id' => $validator->id,
            'action' => 'rejected',
            'comments' => $reason,
            'validated_at' => now(),
        ]);

        $this->validation_status = 'rejected';
        $this->validation_completed_at = now();
        $this->current_validator_group_id = null;
        $this->assigned_validator_id = null;

        return $this->save();
    }

    /**
     * Devolver a una etapa anterior
     */
    public function returnToStage(int $targetStage, $validator, string $reason): bool
    {
        if ($targetStage < 1 || $targetStage >= $this->current_validation_stage) {
            return false;
        }

        $this->validations()->create([
            'workflow_id' => $this->validation_workflow_id,
            'stage_id' => $this->getCurrentStageId(),
            'stage_number' => $this->current_validation_stage,
            'validator_group_key' => $this->currentValidatorGroup?->key,
            'validator_user_id' => $validator->id,
            'action' => 'returned',
            'comments' => $reason,
            'metadata' => ['returned_to_stage' => $targetStage],
            'validated_at' => now(),
        ]);

        $this->current_validation_stage = $targetStage;
        $this->validation_status = 'in_validation';
        $this->current_validator_group_id = $this->getValidatorGroupForStage($targetStage);
        $this->assigned_validator_id = null;

        return $this->save();
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function canBeValidated(): bool
    {
        return in_array($this->validation_status, ['pending', 'in_validation']);
    }

    public function isValidationComplete(): bool
    {
        return $this->validation_status === 'approved';
    }

    public function isValidationRejected(): bool
    {
        return $this->validation_status === 'rejected';
    }

    protected function advanceToNextStage(?int $assignToUserId = null): bool
    {
        $this->current_validation_stage++;
        $this->validation_status = 'in_validation';
        $this->current_validator_group_id = $this->getValidatorGroupForStage($this->current_validation_stage);
        $this->assigned_validator_id = $assignToUserId;

        return $this->save();
    }

    protected function completeValidation(): bool
    {
        $this->validation_status = 'approved';
        $this->validation_completed_at = now();
        $this->current_validator_group_id = null;
        $this->assigned_validator_id = null;

        return $this->save();
    }

    protected function getValidatorGroupForStage(int $stageNumber): ?int
    {
        $stage = $this->validationWorkflow
            ?->stages()
            ->where('stage_number', $stageNumber)
            ->first();

        return $stage?->validator_group_id;
    }

    protected function getCurrentStageId(): ?int
    {
        return $this->validationWorkflow
            ?->stages()
            ->where('stage_number', $this->current_validation_stage)
            ->first()
            ?->id;
    }

    protected function evaluateConditions(array $conditions): bool
    {
        foreach ($conditions as $field => $expectedValue) {
            if ($this->{$field} !== $expectedValue) {
                return false;
            }
        }
        return true;
    }
}
```

---

## Implementación en Modelos

### Document

```php
<?php

namespace App\Models\Document;

use App\Models\Validation\ValidationWorkflow;
use App\Traits\HasValidationWorkflow;

class Document extends Model
{
    use HasValidationWorkflow;

    protected function determineWorkflow(): ?ValidationWorkflow
    {
        $saleType = $this->getSaleType();

        if ($saleType === 'arma') {
            $key = $this->requires_financing
                ? 'document_arma_financing'
                : 'document_arma';
        } else {
            $key = $this->requires_financing
                ? 'document_dni_financing'
                : 'document_dni';
        }

        return ValidationWorkflow::findByKey($key);
    }
}
```

### Ticket (Ejemplo futuro)

```php
<?php

namespace App\Models\Helpdesk;

use App\Models\Validation\ValidationWorkflow;
use App\Traits\HasValidationWorkflow;

class Ticket extends Model
{
    use HasValidationWorkflow;

    protected function determineWorkflow(): ?ValidationWorkflow
    {
        // Tickets técnicos requieren escalado
        if ($this->category === 'technical' && $this->priority === 'high') {
            return ValidationWorkflow::findByKey('ticket_escalation');
        }

        return ValidationWorkflow::findByKey('ticket_standard');
    }
}
```

---

## Workflows Predefinidos

### Para Documentos

| Workflow Key | Etapas |
|--------------|--------|
| `document_dni` | 1. Documentación |
| `document_dni_financing` | 1. Documentación → 2. Contabilidad |
| `document_arma` | 1. Documentación → 2. Licencias |
| `document_arma_financing` | 1. Documentación → 2. Licencias → 3. Contabilidad |

### Para Tickets (Ejemplo)

| Workflow Key | Etapas |
|--------------|--------|
| `ticket_standard` | 1. Soporte Nivel 1 |
| `ticket_escalation` | 1. Soporte Nivel 1 → 2. Soporte Nivel 2 → 3. Desarrollo |

---

## Migración de Datos Existentes

```php
// 1. Migrar document_groups a validator_groups
DB::statement('INSERT INTO validator_groups (uid, name, key, ...)
               SELECT uuid(), name, key, ... FROM document_groups');

// 2. Crear workflows basados en combinaciones existentes
// document_dni, document_arma, etc.

// 3. Migrar document_validation_history a validations
DB::statement('INSERT INTO validations (validatable_id, validatable_type, ...)
               SELECT document_id, "App\\Models\\Document\\Document", ...
               FROM document_validation_history');

// 4. Actualizar documents con nuevos campos
// validation_workflow_id, current_validator_group_id, etc.
```

---

## Ventajas de Esta Arquitectura

1. **Reutilizable**: El mismo sistema funciona para documentos, tickets, devoluciones
2. **Configurable**: Workflows definidos en BD, no en código
3. **Extensible**: Fácil agregar nuevas entidades con validación
4. **Auditable**: Historial completo de todas las validaciones
5. **Flexible**: Etapas opcionales, condiciones de salto, auto-aprobación
6. **SLA-Ready**: Soporte para tiempos límite por etapa

---

## Próximos Pasos

1. [ ] Crear migraciones para nuevas tablas
2. [ ] Crear modelos: `ValidatorGroup`, `ValidationWorkflow`, `ValidationWorkflowStage`, `Validation`
3. [ ] Implementar Trait `HasValidationWorkflow`
4. [ ] Crear seeders para workflows predefinidos
5. [ ] Migrar datos existentes de `document_groups` y `document_validation_history`
6. [ ] Actualizar modelo `Document` para usar el nuevo sistema
7. [ ] Actualizar controladores para usar el Trait
8. [ ] Crear tests unitarios y de integración
