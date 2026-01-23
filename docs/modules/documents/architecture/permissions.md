# Sistema de Validación Multi-Etapa con Permisos por Etapa

## Resumen

Se ha implementado un sistema completo de **validación multi-etapa dinámico** para documentos, donde:

1. **El número de etapas (1, 2 o 3)** se determina dinámicamente basado en:
   - El tipo de documento (`document_types.validation_stages`)
   - Las condiciones del documento (`conditions` evaluadas en runtime)

2. **Las acciones permitidas** varían según la etapa actual:
   - **Documentación (Etapa 1):** Validar, rechazar, enviar emails
   - **Licencias (Etapa 2):** Solo confirmar y pasar a siguiente
   - **Contabilidad (Etapa 3):** Aprobar finalmente, rechazar, enviar emails

3. **Las notificaciones** se envían de forma diferenciada según la etapa:
   - Etapa 1: Email al cliente + Notificación a validadores siguiente
   - Etapa 2: Solo notificación interna a siguiente etapa
   - Etapa 3: Email final al cliente + Notificación stakeholders

---

## Archivos Creados

### 1. Enum de Acciones (`app/Enums/Document/ValidationAction.php`)

Define todas las posibles acciones en la validación:

```php
enum ValidationAction: string
{
    case APPROVE = 'approve';                      // Aprobar etapa
    case REJECT = 'reject';                        // Rechazar
    case SEND_APPROVAL_EMAIL = 'send_approval_email';  // Email aprobación
    case ADD_COMMENT = 'add_comment';              // Comentarios
    case REQUEST_ADDITIONAL_DOCS = 'request_additional_docs';  // Solicitar docs
    case MOVE_TO_NEXT_STAGE = 'move_to_next_stage';  // Pasar sin aprobar
    case ACCESS_ADDITIONAL_FILES = 'access_additional_files';  // Acceso archivos
}
```

### 2. Configuración de Permisos (`config/validation-permissions.php`)

Define qué acciones están permitidas en cada etapa:

```php
'documentacion' => [
    'order' => 1,
    'allowed_actions' => [
        'approve',                  // ✅ Validar
        'reject',                   // ✅ Rechazar
        'send_approval_email',      // ✅ Enviar email
        'add_comment',              // ✅ Comentarios
        'request_additional_docs',  // ✅ Solicitar docs
    ],
    'restricted_actions' => [
        'move_to_next_stage',       // ❌ No puede saltarse
        'access_additional_files',  // ❌ No aplica
    ],
],

'licencias' => [
    'order' => 2,
    'allowed_actions' => [
        'approve',                  // ✅ Confirmar y pasar
        'add_comment',              // ✅ Comentarios
        'request_additional_docs',  // ✅ Solicitar docs
    ],
    'restricted_actions' => [
        'reject',                   // ❌ No puede rechazar
        'send_approval_email',      // ❌ No envía emails
    ],
],

'contabilidad' => [
    'order' => 3,
    'allowed_actions' => [
        'approve',                  // ✅ Aprobación final
        'reject',                   // ✅ Rechazar final
        'send_approval_email',      // ✅ Email final
        'access_additional_files',  // ✅ Archivos adicionales
        'add_comment',              // ✅ Comentarios
        'request_additional_docs',  // ✅ Solicitar docs
    ],
],
```

### 3. Servicio de Permisos (`app/Services/Documents/ValidationPermissionService.php`)

Servicio que centraliza toda la lógica de permisos:

```php
$permissionService = app(ValidationPermissionService::class);

// Verificar si acción es permitida en etapa actual
$canApprove = $permissionService->canApprove($document);
$canReject = $permissionService->canReject($document);
$canSendEmail = $permissionService->canSendApprovalEmail($document);

// Obtener acciones permitidas
$allowedActions = $permissionService->getAllowedActionsForDocument($document);

// Obtener etapa
$stageName = $permissionService->getStageLabelByKey($document->current_validator_group);
$stageDesc = $permissionService->getStageDescriptionByKey($document->current_validator_group);
```

### 4. Métodos en el Trait `HasValidationWorkflow`

Se agregaron métodos para verificar permisos y estado de etapa:

```php
// Verificar acciones permitidas
$document->canPerformValidationAction(ValidationAction::APPROVE);
$document->getAllowedValidationActions();

// Información de etapa
$document->isFirstValidationStage();
$document->isLastValidationStage();
$document->isIntermediateValidationStage();
$document->getCurrentStageLabel();
$document->getCurrentStageDescription();
```

### 5. Evento de Validación (`app/Events/Documents/DocumentValidationStageApproved.php`)

Se dispara cuando se aprueba una etapa:

```php
event(new DocumentValidationStageApproved(
    document: $document,
    approvedBy: $user,
    stageNumber: 1,
    stageKey: 'documentacion',
    isFinalApproval: false,
    comments: 'Documentos verificados correctamente'
));
```

### 6. Listener de Notificaciones (`app/Listeners/Documents/SendStageNotifications.php`)

Maneja notificaciones diferenciadas por etapa:

- **Etapa 1:** Envía email de aprobación al cliente + notifica siguiente etapa
- **Etapa 2:** Solo notifica siguiente etapa (sin email al cliente)
- **Etapa 3:** Envía email final de aprobación + notifica stakeholders

---

## Cómo Usar

### En un Controlador

```php
use App\Enums\Document\ValidationAction;
use App\Services\Documents\ValidationPermissionService;

class DocumentValidationController extends Controller
{
    public function __construct(
        private ValidationPermissionService $permissionService
    ) {}

    public function approve(Document $document)
    {
        // Verificar permisos
        if (! $this->permissionService->canApprove($document)) {
            return response()->json([
                'error' => 'No puedes aprobar en esta etapa',
                'current_stage' => $document->getCurrentStageLabel(),
            ], 403);
        }

        // Aprobar con opción de enviar email
        $sendEmail = request()->boolean('send_email');

        if (! $sendEmail || ! $this->permissionService->canSendApprovalEmail($document)) {
            // Si no puedes enviar email en esta etapa, se ignora
            $sendEmail = false;
        }

        $document->approveCurrentStage(
            comments: request()->input('comments'),
            validator: auth()->user(),
            shouldSendEmail: $sendEmail
        );

        return response()->json([
            'message' => 'Documento aprobado',
            'current_stage' => $document->current_stage,
            'total_stages' => $document->total_stages,
            'is_complete' => $document->isApproved(),
        ]);
    }

    public function reject(Document $document)
    {
        // Verificar permisos
        if (! $this->permissionService->canReject($document)) {
            return response()->json([
                'error' => 'No puedes rechazar en esta etapa',
                'stage' => $document->getCurrentStageLabel(),
            ], 403);
        }

        $document->rejectValidation(
            comments: request()->input('comments'),
            validator: auth()->user()
        );

        return response()->json(['message' => 'Documento rechazado']);
    }
}
```

### En una Vista/Componente

```blade
@if ($document->canPerformValidationAction(ValidationAction::APPROVE))
    <button type="button" class="btn btn-success">
        ✓ Aprobar {{ $document->getCurrentStageLabel() }}
    </button>
@endif

@if ($document->canPerformValidationAction(ValidationAction::REJECT))
    <button type="button" class="btn btn-danger">
        ✗ Rechazar
    </button>
@endif

@if ($document->canPerformValidationAction(ValidationAction::SEND_APPROVAL_EMAIL))
    <div class="form-check">
        <input type="checkbox" name="send_email" id="send_email">
        <label for="send_email">
            📧 Enviar email de aprobación
        </label>
    </div>
@endif

@if ($document->canPerformValidationAction(ValidationAction::ACCESS_ADDITIONAL_FILES))
    <section class="additional-files">
        <h5>📎 Archivos Adicionales</h5>
        <!-- Mostrar archivos adicionales -->
    </section>
@endif
```

---

## Flujo de Trabajo: Ejemplo Completo

### Documento tipo "rifle" con financiamiento (3 etapas)

```
┌─────────────────────────────────────────────────────────────────┐
│ ETAPA 1: DOCUMENTACIÓN (Validador: User1)                       │
├─────────────────────────────────────────────────────────────────┤
│ ✅ Acciones Permitidas:                                          │
│   - Validar documentos → Pasar a etapa 2                         │
│   - Rechazar completamente                                      │
│   - Enviar email al cliente                                     │
│   - Solicitar documentos adicionales                            │
│   - Agregar comentarios internos                                │
│                                                                 │
│ ❌ Acciones Bloqueadas:                                          │
│   - Acceder a archivos adicionales (aplica etapa 3)            │
│                                                                 │
│ 📧 Notificaciones:                                              │
│   - Si se aprueba: Email a cliente + Notif a siguientes        │
└─────────────────────────────────────────────────────────────────┘
         ↓ approveCurrentStage(shouldSendEmail: true)

┌─────────────────────────────────────────────────────────────────┐
│ ETAPA 2: LICENCIAS (Validador: User2)                           │
├─────────────────────────────────────────────────────────────────┤
│ ✅ Acciones Permitidas:                                          │
│   - Confirmar y pasar a etapa 3 (no es "aprobación completa")  │
│   - Solicitar documentos adicionales                            │
│   - Agregar comentarios internos                                │
│                                                                 │
│ ❌ Acciones Bloqueadas:                                          │
│   - Rechazar (solo etapas 1 y 3 pueden rechazar)               │
│   - Enviar email al cliente                                     │
│   - Acceder a archivos adicionales                              │
│                                                                 │
│ 📧 Notificaciones:                                              │
│   - Si se aprueba: Solo notificación interna (SIN email)       │
└─────────────────────────────────────────────────────────────────┘
         ↓ approveCurrentStage(shouldSendEmail: false)

┌─────────────────────────────────────────────────────────────────┐
│ ETAPA 3: CONTABILIDAD (Validador: User3)                        │
├─────────────────────────────────────────────────────────────────┤
│ ✅ Acciones Permitidas:                                          │
│   - APROBACIÓN FINAL (completa validación)                      │
│   - Rechazar completamente                                      │
│   - Enviar email de aprobación final al cliente                 │
│   - Acceder y revisar archivos adicionales                      │
│   - Solicitar documentos adicionales                            │
│   - Agregar comentarios internos                                │
│                                                                 │
│ 📧 Notificaciones:                                              │
│   - Si se aprueba: Email final al cliente + Notif stakeholders │
│   - Si se rechaza: Email de rechazo                             │
└─────────────────────────────────────────────────────────────────┘
         ↓ approveCurrentStage(shouldSendEmail: true)
         ✅ DOCUMENTO COMPLETAMENTE APROBADO
```

---

## Reglas y Restricciones

Se implementan varias reglas de negocio:

```php
'rules' => [
    // Solo etapas 1 y 3 pueden enviar emails
    'send_approval_email_only_first_last' => true,

    // Solo etapas 1 y 3 pueden rechazar
    'reject_only_first_last' => true,

    // Etapas intermedias solo avanzan, no aprueban completamente
    'intermediate_stages_advance_only' => true,

    // Archivos adicionales solo en etapa final
    'additional_files_final_stage_only' => true,
],
```

---

## Método Modificado: approveCurrentStage()

```php
public function approveCurrentStage(
    ?string $comments = null,
    ?User $validator = null,
    bool $shouldSendEmail = false  // Nuevo parámetro
): bool
{
    // Verifica permisos según etapa actual
    if (! $this->canPerformValidationAction(ValidationAction::APPROVE)) {
        return false; // Acción rechazada
    }

    // ... resto de lógica ...

    // Envía email solo si:
    // 1. Usuario solicitó enviar ($shouldSendEmail = true)
    // 2. La etapa permite enviar (send_approval_email en allowed_actions)
    if ($sendEmailThisStage) {
        event(new DocumentValidationStageApproved(...));
    }
}
```

---

## Próximos Pasos (TODO)

1. **Implementar controlador actualizado:**
   - Usar `ValidationPermissionService` en validaciones
   - Pasar `shouldSendEmail` parámetro a `approveCurrentStage()`

2. **Implementar listener completo:**
   - Conectar `DocumentMailService` para enviar emails
   - Implementar `NotificationService` para notificaciones internas

3. **Actualizar vistas/formularios:**
   - Mostrar/ocultar botones según permisos actuales
   - Mostrar archivo adicionales solo en última etapa

4. **Testing:**
   - Crear tests para cada etapa y sus permisos
   - Validar flujo completo de validación

---

## Integración con DocumentType

Para usar el nuevo sistema, configura los `validation_stages` en `document_types`:

```php
// En DatabaseSeeder o migraciones
DocumentType::create([
    'slug' => 'rifle',
    'name' => 'Rifle',
    'validation_stages' => [
        [
            'key' => 'documentacion',
            'order' => 1,
            'conditions' => [], // Siempre se ejecuta
        ],
        [
            'key' => 'licencias',
            'order' => 2,
            'conditions' => ['is_weapon' => true],
        ],
        [
            'key' => 'contabilidad',
            'order' => 3,
            'conditions' => ['requires_financing' => true],
        ],
    ],
]);
```

---

## Beneficios del Sistema

✅ **Claridad:** Cada etapa tiene responsabilidades claras
✅ **Seguridad:** Permisos centralizados y fáciles de auditar
✅ **Flexibilidad:** Cambiar permisos sin modificar controladores
✅ **Notificaciones:** Diferentes notificaciones según etapa
✅ **Trazabilidad:** Auditoría completa de quién hace qué en cada etapa
✅ **Escalabilidad:** Fácil agregar nuevas etapas o acciones
