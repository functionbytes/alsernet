# Plan de Implementación Detallado

## Índice
1. [Cambios en Modelos](#cambios-en-modelos)
2. [Nuevos Services](#nuevos-services)
3. [Actualización de Controllers](#actualización-de-controllers)
4. [Creación de Mailables](#creación-de-mailables)
5. [Eventos y Listeners](#eventos-y-listeners)
6. [Jobs y Scheduling](#jobs-y-scheduling)
7. [Cambios en Vistas](#cambios-en-vistas)
8. [Testing](#testing)
9. [Checklist de Implementación](#checklist-de-implementación)

---

## Cambios en Modelos

### 1. Document Model

**Archivo:** `app/Models/Order/Document.php`

**Cambios Necesarios:**

```php
<?php

namespace App\Models\Order;

use App\Models\Document\DocumentSlaBreach;use App\Models\Document\DocumentSlaPolicy;use App\Models\Document\DocumentStatus;use App\Models\Document\DocumentStatusHistory;use App\Models\Document\DocumentStatusTransition;class Document extends Model implements HasMedia {
    // ... existing code ...

    protected $fillable = [
        // ... existing fields ...
        'status_id',           // ← NUEVO
        'sla_policy_id',       // ← NUEVO
        'source',              // ← YA EXISTE, mejorar
        'completed_at',        // ← NUEVO (nullable)
        'last_action_at',      // ← NUEVO (nullable)
    ];

    protected function casts(): array {
        return [
            // ... existing casts ...
            'completed_at' => 'datetime',
            'last_action_at' => 'datetime',
        ];
    }

    // ============ RELACIONES NUEVAS ============

    /**
     * Estado actual del documento
     */
    public function status(): BelongsTo {
        return $this->belongsTo(DocumentStatus::class, 'status_id');
    }

    /**
     * Historial completo de cambios de estado
     */
    public function statusHistories(): HasMany {
        return $this->hasMany(DocumentStatusHistory::class)
            ->orderByDesc('created_at');
    }

    /**
     * Política SLA aplicada
     */
    public function slaPolicy(): BelongsTo {
        return $this->belongsTo(DocumentSlaPolicy::class, 'sla_policy_id');
    }

    /**
     * Incumplimientos de SLA
     */
    public function slaBreaches(): HasMany {
        return $this->hasMany(DocumentSlaBreach::class)
            ->orderByDesc('created_at');
    }

    // ============ MÉTODOS DE GESTIÓN DE ESTADO ============

    /**
     * Cambiar estado del documento
     *
     * @param DocumentStatus $newStatus
     * @param string $reason
     * @param User|null $user
     * @return bool
     * @throws InvalidStateTransitionException
     */
    public function changeStatus(
        DocumentStatus $newStatus,
        ?string $reason = null,
        ?User $user = null
    ): bool {
        // Validar transición
        if (!$this->canTransitionTo($newStatus)) {
            throw new InvalidStateTransitionException(
                "Cannot transition from {$this->status->key} to {$newStatus->key}"
            );
        }

        // Guardar en historial
        $oldStatus = $this->status;

        DocumentStatusHistory::create([
            'document_id' => $this->id,
            'from_status_id' => $oldStatus?->id,
            'to_status_id' => $newStatus->id,
            'changed_by' => $user?->id,
            'reason' => $reason,
            'metadata' => [
                'user_type' => $user ? 'admin' : 'system',
                'old_status' => $oldStatus?->key,
                'new_status' => $newStatus->key,
            ]
        ]);

        // Actualizar documento
        $this->update([
            'status_id' => $newStatus->id,
            'last_action_at' => now(),
            'completed_at' => $newStatus->key === 'completed' ? now() : $this->completed_at,
        ]);

        // Registrar en auditoría
        app(DocumentActionService::class)->logStatusChange(
            $this,
            $oldStatus,
            $newStatus,
            $reason,
            $user
        );

        return true;
    }

    /**
     * Verificar si puede cambiar a un estado
     */
    public function canTransitionTo(DocumentStatus $target): bool {
        if (!$this->status) {
            return false;
        }

        $transition = DocumentStatusTransition::where([
            'from_status_id' => $this->status_id,
            'to_status_id' => $target->id,
            'is_active' => true,
        ])->first();

        if (!$transition) {
            return false;
        }

        // Si requiere todos los docs, verificar
        if ($transition->requires_all_documents_uploaded && !$this->allDocumentsUploaded()) {
            return false;
        }

        return true;
    }

    /**
     * Obtener transiciones válidas
     */
    public function getValidTransitions(): Collection {
        return DocumentStatusTransition::where([
            'from_status_id' => $this->status_id,
            'is_active' => true,
        ])->with('toStatus')->get()->pluck('toStatus');
    }

    /**
     * Marcar como completado
     */
    public function markCompleted(?User $user = null): bool {
        $completedStatus = DocumentStatus::where('key', 'completed')->first();
        return $this->changeStatus($completedStatus, 'Documento completado', $user);
    }

    /**
     * Obtener deadline SLA
     */
    public function getSlaDeadline(): ?Carbon {
        if (!$this->slaPolicy) {
            return null;
        }

        // Usar el tiempo más largo según estado actual
        $minutes = match($this->status?->key) {
            'pending', 'awaiting_documents' => $this->slaPolicy->approval_time,
            'incomplete' => $this->slaPolicy->review_time,
            default => $this->slaPolicy->approval_time,
        };

        $multiplier = $this->slaPolicy->getMultiplierForDocumentType($this->type);
        $totalMinutes = (int)($minutes * $multiplier);

        return $this->created_at->addMinutes($totalMinutes);
    }

    /**
     * Verificar incumplimientos de SLA
     */
    public function checkSlaBreaches(): void {
        if (!$this->slaPolicy || in_array($this->status?->key, ['completed', 'rejected', 'cancelled'])) {
            return;
        }

        $deadline = $this->getSlaDeadline();
        if (!$deadline || now()->isBefore($deadline)) {
            return;
        }

        // Ya incumplido, crear registro
        if (!$this->slaBreaches()->where('resolved', false)->exists()) {
            $breach = $this->slaBreaches()->create([
                'sla_policy_id' => $this->sla_policy_id,
                'breach_type' => $this->getBreaChType(),
                'minutes_over' => $deadline->diffInMinutes(now()),
                'escalated' => false,
            ]);

            // Disparar evento de escalamiento
            event(new SlaBreachDetected($this, $breach));
        }
    }

    /**
     * Obtener etiqueta del origen
     */
    public function getOriginLabel(): string {
        return match($this->source) {
            'api' => 'API Prestashop',
            'manual' => 'Carga Manual',
            'erp' => 'Importación ERP',
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
            'wp' => 'WordPress',
            default => $this->source ?? 'Desconocido',
        };
    }

    /**
     * Obtener timeline completo
     */
    public function getTimeline(): Collection {
        return $this->actions()
            ->orderByDesc('created_at')
            ->with(['performedByUser'])
            ->get();
    }

    // ... resto del código existente ...
}
```

### 2. DocumentStatus Model

**Archivo:** `app/Models/Order/DocumentStatus.php` (Ya creado)

**Mejoras:**

```php
<?php

namespace App\Models\Order;

class DocumentStatus extends Model {

    protected $table = 'document_statuses';

    protected $fillable = [
        'key', 'label', 'description', 'color', 'icon', 'is_active', 'order'
    ];

    protected function casts(): array {
        return ['is_active' => 'boolean', 'order' => 'integer'];
    }

    // ============ MÉTODOS ÚTILES ============

    /**
     * Obtener estado por key
     */
    public static function getByKey(string $key): ?self {
        return static::where('key', $key)->first();
    }

    /**
     * Obtener todos activos ordenados
     */
    public static function getActive(): Collection {
        return static::where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Badge HTML
     */
    public function badge(): string {
        return sprintf(
            '<span class="badge" style="background-color: %s;">%s</span>',
            $this->color,
            $this->label
        );
    }

    /**
     * Icon HTML
     */
    public function icon(): string {
        return sprintf('<i class="ti ti-%s"></i>', $this->icon);
    }
}
```

### 3. DocumentStatusHistory Model

**Archivo:** `app/Models/Order/DocumentStatusHistory.php` (Ya creado)

**Ya está bien, solo asegurar:**

```php
public function scopeForDocument($query, int $documentId) {
    return $query->where('document_id', $documentId)->orderByDesc('created_at');
}

public function scopeRecent($query) {
    return $query->orderByDesc('created_at');
}
```

### 4. DocumentSlaPolicy Model

**Archivo:** `app/Models/Order/DocumentSlaPolicy.php` (Ya creado)

**Ya está bien completado.**

### 5. DocumentConfiguration Model

**Archivo:** `app/Models/Order/DocumentConfiguration.php`

**Cambios:**

```php
<?php

namespace App\Models\Order;

use App\Models\Document\DocumentSlaPolicy;class DocumentConfiguration extends Model {
    // ... existing code ...

    protected $fillable = [
        // ... existing ...
        'default_sla_policy_id', // ← NUEVO
    ];

    protected function casts(): array {
        return [
            // ... existing ...
            'default_sla_policy_id' => 'integer',
        ];
    }

    /**
     * Política SLA por defecto para este tipo
     */
    public function defaultSlaPolicy(): BelongsTo {
        return $this->belongsTo(DocumentSlaPolicy::class, 'default_sla_policy_id');
    }

    /**
     * Obtener política SLA a usar
     */
    public function getSlaPolicy(): DocumentSlaPolicy {
        return $this->defaultSlaPolicy ?? DocumentSlaPolicy::where('is_default', true)->first();
    }
}
```

---

## Nuevos Services

### 1. DocumentStatusService

**Archivo:** `app/Services/Documents/DocumentStatusService.php` (CREAR NUEVO)

```php
<?php

namespace App\Services\Documents;

use App\Models\Document\Document;use App\Models\Document\DocumentStatus;use App\Models\Document\DocumentStatusHistory;use App\Models\Document\DocumentStatusTransition;use App\Models\User;use Carbon\Carbon;use Illuminate\Database\Eloquent\Collection;

class DocumentStatusService {

    /**
     * Cambiar estado con validación
     */
    public function changeStatus(
        Document $document,
        DocumentStatus $newStatus,
        ?string $reason = null,
        ?User $user = null
    ): DocumentStatusHistory {
        // Validar transición
        if (!$this->canTransition($document, $newStatus, $user)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$document->status->key} to {$newStatus->key}"
            );
        }

        // Usar método del modelo
        $document->changeStatus($newStatus, $reason, $user);

        // Retornar el último historial creado
        return $document->statusHistories()->latest()->first();
    }

    /**
     * Verificar si puede transicionar
     */
    public function canTransition(
        Document $document,
        DocumentStatus $target,
        ?User $user = null
    ): bool {
        // Obtener transición válida
        $transition = DocumentStatusTransition::where([
            'from_status_id' => $document->status_id,
            'to_status_id' => $target->id,
            'is_active' => true,
        ])->first();

        if (!$transition) {
            return false;
        }

        // Verificar requerimientos
        if ($transition->requires_all_documents_uploaded && !$document->allDocumentsUploaded()) {
            return false;
        }

        // Verificar permisos
        if ($transition->permission && $user) {
            if (!$user->can($transition->permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtener transiciones válidas
     */
    public function getValidTransitions(Document $document): Collection {
        return $document->getValidTransitions();
    }

    /**
     * Obtener historial
     */
    public function getHistory(Document $document): Collection {
        return $document->statusHistories()
            ->with('fromStatus', 'toStatus', 'changedBy')
            ->get();
    }

    /**
     * Obtener último cambio
     */
    public function getLastChange(Document $document): ?DocumentStatusHistory {
        return $document->statusHistories()->latest()->first();
    }

    /**
     * Cuánto tiempo en estado actual
     */
    public function getTimeInCurrentStatus(Document $document): Carbon {
        $lastChange = $this->getLastChange($document);
        return $lastChange?->created_at ?? $document->created_at;
    }
}
```

### 2. DocumentActionService (Mejorado)

**Archivo:** `app/Services/Documents/DocumentActionService.php` (ACTUALIZAR)

```php
<?php

namespace App\Services\Documents;

use App\Models\Document\Document;use App\Models\Document\DocumentAction;use App\Models\Document\DocumentStatus;use App\Models\User;

class DocumentActionService {

    /**
     * Registrar cambio de estado
     */
    public function logStatusChange(
        Document $document,
        ?DocumentStatus $fromStatus,
        DocumentStatus $toStatus,
        ?string $reason = null,
        ?User $user = null
    ): DocumentAction {
        return $this->log(
            $document,
            'status_changed',
            "Estado cambió a: {$toStatus->label}",
            "Cambio de estado: {$fromStatus?->label} → {$toStatus->label}",
            [
                'from_status' => $fromStatus?->key,
                'to_status' => $toStatus->key,
                'reason' => $reason,
            ],
            $user,
            'system'
        );
    }

    /**
     * Registrar carga de documentos
     */
    public function logDocumentUpload(
        Document $document,
        array $files,
        User $user,
        string $type = 'customer'
    ): DocumentAction {
        return $this->log(
            $document,
            'documents_uploaded',
            "{$type === 'admin' ? 'Admin' : 'Cliente'} subió documentos",
            sprintf("Se cargaron %d archivo(s)", count($files)),
            [
                'files' => $files,
                'file_count' => count($files),
            ],
            $user,
            $type === 'admin' ? 'admin' : 'customer'
        );
    }

    /**
     * Registrar email enviado
     */
    public function logEmailSent(
        Document $document,
        string $emailType,
        array $metadata = [],
        ?User $user = null
    ): DocumentAction {
        $labels = [
            'email_initial_request' => 'Email inicial enviado',
            'email_reminder' => 'Email recordatorio enviado',
            'email_missing_documents' => 'Email documentos faltantes enviado',
            'email_approval' => 'Email aprobación enviado',
            'email_rejection' => 'Email rechazo enviado',
            'email_custom' => 'Email personalizado enviado',
        ];

        return $this->log(
            $document,
            'email_sent',
            $labels[$emailType] ?? 'Email enviado',
            "Tipo: {$emailType}",
            $metadata + ['email_type' => $emailType],
            $user,
            'system'
        );
    }

    /**
     * Registrar nota
     */
    public function logNoteAdded(
        Document $document,
        string $noteContent,
        User $user,
        bool $isInternal = true
    ): DocumentAction {
        return $this->log(
            $document,
            'note_added',
            'Nota ' . ($isInternal ? 'interna' : 'pública') . ' agregada',
            $noteContent,
            [
                'is_internal' => $isInternal,
                'note_preview' => substr($noteContent, 0, 50),
            ],
            $user,
            'admin'
        );
    }

    /**
     * Registrar acción genérica
     */
    private function log(
        Document $document,
        string $actionType,
        string $actionName,
        string $description,
        array $metadata = [],
        ?User $user = null,
        string $userType = 'system'
    ): DocumentAction {
        return DocumentAction::create([
            'document_id' => $document->id,
            'action_type' => $actionType,
            'action_name' => $actionName,
            'description' => $description,
            'metadata' => $metadata,
            'performed_by' => $user?->id,
            'performed_by_type' => $userType,
            'created_at' => now(),
        ]);
    }

    /**
     * Obtener timeline
     */
    public function getTimeline(Document $document) {
        return $document->actions()
            ->orderByDesc('created_at')
            ->with('performedBy')
            ->get();
    }
}
```

### 3. DocumentMailService (Mejorado)

**Archivo:** `app/Services/Documents/DocumentMailService.php` (ACTUALIZAR)

```php
<?php

namespace App\Services\Documents;

use App\Jobs\SendDocumentEmailJob;use App\Mail\Documents\{DocumentApprovalMail,DocumentCompletionMail,DocumentCustomMail,DocumentInitialRequestMail,DocumentMissingDocumentsMail,DocumentRejectionMail,DocumentReminderMail,};use App\Models\Document\Document;use Illuminate\Support\Facades\Mail;

class DocumentMailService {

    /**
     * Enviar solicitud inicial
     */
    public function sendInitialRequest(Document $document, bool $queue = false): void {
        $mail = new DocumentInitialRequestMail($document);

        if ($queue) {
            Mail::queue($mail);
        } else {
            Mail::send($mail);
        }

        // Log
        app(DocumentActionService::class)->logEmailSent(
            $document,
            'email_initial_request',
            ['email_to' => $document->customer_email]
        );
    }

    /**
     * Enviar recordatorio
     */
    public function sendReminder(Document $document, bool $queue = false): void {
        $mail = new DocumentReminderMail($document);

        if ($queue) {
            Mail::queue($mail);
        } else {
            Mail::send($mail);
        }

        app(DocumentActionService::class)->logEmailSent(
            $document,
            'email_reminder',
            ['email_to' => $document->customer_email]
        );
    }

    /**
     * Enviar documentos faltantes
     */
    public function sendMissingDocuments(Document $document, array $missing): void {
        $mail = new DocumentMissingDocumentsMail($document, $missing);
        Mail::send($mail);

        app(DocumentActionService::class)->logEmailSent(
            $document,
            'email_missing_documents',
            [
                'email_to' => $document->customer_email,
                'missing' => $missing,
            ]
        );
    }

    /**
     * Enviar aprobación
     */
    public function sendApproval(Document $document): void {
        $mail = new DocumentApprovalMail($document);
        Mail::send($mail);

        app(DocumentActionService::class)->logEmailSent(
            $document,
            'email_approval',
            ['email_to' => $document->customer_email]
        );
    }

    /**
     * Enviar rechazo
     */
    public function sendRejection(Document $document, string $reason): void {
        $mail = new DocumentRejectionMail($document, $reason);
        Mail::send($mail);

        app(DocumentActionService::class)->logEmailSent(
            $document,
            'email_rejection',
            [
                'email_to' => $document->customer_email,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Enviar completación
     */
    public function sendCompletion(Document $document): void {
        $mail = new DocumentCompletionMail($document);
        Mail::send($mail);

        app(DocumentActionService::class)->logEmailSent(
            $document,
            'email_custom',
            ['email_to' => $document->customer_email]
        );
    }

    /**
     * Enviar email personalizado
     */
    public function sendCustomEmail(
        Document $document,
        string $subject,
        string $body
    ): void {
        $mail = new DocumentCustomMail($document, $subject, $body);
        Mail::send($mail);

        app(DocumentActionService::class)->logEmailSent(
            $document,
            'email_custom',
            [
                'email_to' => $document->customer_email,
                'subject' => $subject,
                'body_preview' => substr($body, 0, 50),
            ]
        );
    }
}
```

---

## Actualización de Controllers

### 1. DocumentsController (Administrative)

**Archivo:** `app/Http/Controllers/Administratives/Documents/DocumentsController.php`

**Cambios Necesarios:**

```php
<?php

namespace App\Http\Controllers\Administratives\Documents;

use App\Models\Document\Document;use App\Models\Document\DocumentStatus;use App\Services\Documents\DocumentActionService;use App\Services\Documents\DocumentMailService;use App\Services\Documents\DocumentStatusService;

class DocumentsController extends Controller {

    // ============ INYECTAR SERVICES ============

    public function __construct(
        private DocumentStatusService $statusService,
        private DocumentActionService $actionService,
        private DocumentMailService $mailService,
    ) {}

    // ============ MÉTODOS DE ESTADO ============

    /**
     * Cambiar estado del documento
     */
    public function changeStatus(Request $request, Document $document) {
        $newStatus = DocumentStatus::findOrFail($request->status_id);
        $reason = $request->reason;

        try {
            $this->statusService->changeStatus(
                $document,
                $newStatus,
                $reason,
                auth()->user()
            );

            return redirect()->back()->with('success', 'Estado actualizado');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Enviar email inicial (manual)
     */
    public function sendNotification(Document $document) {
        $this->mailService->sendInitialRequest($document);
        return redirect()->back()->with('success', 'Email enviado');
    }

    /**
     * Enviar recordatorio
     */
    public function sendReminder(Document $document) {
        $this->mailService->sendReminder($document);
        return redirect()->back()->with('success', 'Recordatorio enviado');
    }

    /**
     * Solicitar documentos faltantes
     */
    public function sendMissing(Document $document) {
        $missing = $document->getMissingDocuments();

        if (empty($missing)) {
            return redirect()->back()->withErrors('No hay documentos faltantes');
        }

        $this->mailService->sendMissingDocuments($document, $missing);
        return redirect()->back()->with('success', 'Email enviado');
    }

    /**
     * Enviar email personalizado
     */
    public function sendCustomEmail(Request $request, Document $document) {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
        ]);

        $this->mailService->sendCustomEmail(
            $document,
            $validated['subject'],
            $validated['body']
        );

        return redirect()->back()->with('success', 'Email enviado');
    }

    /**
     * Cargar documentos manualmente
     */
    public function adminUpload(Request $request, Document $document) {
        $validated = $request->validate([
            'files.*' => 'file|max:10240',
        ]);

        $files = [];
        foreach ($request->file('files', []) as $file) {
            $path = $file->store("documents/{$document->uid}");
            $files[] = ['name' => $file->getClientOriginalName(), 'path' => $path];
        }

        // Guardar en documentos subidos
        $uploaded = $document->uploaded_documents ?? [];
        $uploaded = array_merge($uploaded, $files);
        $document->update(['uploaded_documents' => $uploaded]);

        // Log
        $this->actionService->logDocumentUpload(
            $document,
            $files,
            auth()->user(),
            'admin'
        );

        // Actualizar estado si está completo
        if ($document->allDocumentsUploaded()) {
            $awaitingStatus = DocumentStatus::where('key', 'awaiting_documents')->first();
            $document->changeStatus($awaitingStatus, 'Admin cargó documentos', auth()->user());
        }

        return redirect()->back()->with('success', 'Documentos cargados');
    }

    /**
     * Agregar nota
     */
    public function addNote(Request $request, Document $document) {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'is_internal' => 'boolean',
        ]);

        $document->notes()->create([
            'created_by' => auth()->id(),
            'content' => $validated['content'],
            'is_internal' => $validated['is_internal'] ?? true,
        ]);

        $this->actionService->logNoteAdded(
            $document,
            $validated['content'],
            auth()->user(),
            $validated['is_internal'] ?? true
        );

        return redirect()->back()->with('success', 'Nota agregada');
    }

    /**
     * Ver detalles del documento (mejorado)
     */
    public function manage(Document $document) {
        return view('administratives.documents.manage', [
            'document' => $document,
            'timeline' => $document->getTimeline(),
            'validTransitions' => $document->getValidTransitions(),
            'slaDeadline' => $document->getSlaDeadline(),
            'slaBreaches' => $document->slaBreaches,
            'notes' => $document->notes()->orderByDesc('created_at')->get(),
        ]);
    }

    // ============ RESTO DE MÉTODOS EXISTENTES ============
    // ... mantener código existente ...
}
```

### 2. DocumentSlaPoliciesController

**Archivo:** `app/Http/Controllers/Managers/Settings/DocumentSlaPoliciesController.php` (Ya creado)

**Verificar que esté completo y funcionando.**

---

## Creación de Mailables

### 1. DocumentInitialRequestMail

**Archivo:** `app/Mail/Documents/DocumentInitialRequestMail.php` (CREAR NUEVO)

```php
<?php

namespace App\Mail\Documents;

use App\Models\Document\Document;use Illuminate\Bus\Queueable;use Illuminate\Mail\Mailable;use Illuminate\Queue\SerializesModels;

class DocumentInitialRequestMail extends Mailable {
    use Queueable, SerializesModels;

    public function __construct(public Document $document) {}

    public function envelope() {
        return new Envelope(
            subject: "Necesitamos tus documentos para procesar tu pedido",
        );
    }

    public function content() {
        $uploadUrl = route('document.upload.form', $this->document->uid);

        return new Content(
            view: 'emails.documents.initial-request',
            with: [
                'document' => $this->document,
                'uploadUrl' => $uploadUrl,
                'requiredDocuments' => $this->document->getRequiredDocuments(),
            ],
        );
    }
}
```

### 2. DocumentReminderMail

**Archivo:** `app/Mail/Documents/DocumentReminderMail.php` (CREAR NUEVO)

```php
<?php

namespace App\Mail\Documents;

use App\Models\Document\Document;use Illuminate\Bus\Queueable;use Illuminate\Mail\Mailable;use Illuminate\Queue\SerializesModels;

class DocumentReminderMail extends Mailable {
    use Queueable, SerializesModels;

    public function __construct(public Document $document) {}

    public function envelope() {
        return new Envelope(
            subject: "Recordatorio: Completa tus documentos",
        );
    }

    public function content() {
        $uploadUrl = route('document.upload.form', $this->document->uid);
        $missing = $this->document->getMissingDocuments();

        return new Content(
            view: 'emails.documents.reminder',
            with: [
                'document' => $this->document,
                'uploadUrl' => $uploadUrl,
                'missing' => $missing,
            ],
        );
    }
}
```

### 3. DocumentApprovalMail, RejectionMail, CompletionMail

Crear archivos similares en `app/Mail/Documents/`.

---

## Eventos y Listeners

### 1. DocumentStatusChangedEvent

**Archivo:** `app/Events/Documents/DocumentStatusChanged.php` (CREAR NUEVO)

```php
<?php

namespace App\Events\Documents;

use App\Models\Document\Document;use App\Models\Document\DocumentStatus;use Illuminate\Foundation\Events\Dispatchable;use Illuminate\Queue\SerializesModels;

class DocumentStatusChanged {
    use Dispatchable, SerializesModels;

    public function __construct(
        public Document $document,
        public DocumentStatus $oldStatus,
        public DocumentStatus $newStatus,
        public ?string $reason = null,
    ) {}
}
```

### 2. Listeners

**Archivo:** `app/Listeners/Documents/SendApprovalEmail.php` (CREAR)

```php
<?php

namespace App\Listeners\Documents;

use App\Events\Document\DocumentStatusChanged;
use App\Services\Documents\DocumentMailService;

class SendApprovalEmail {
    public function handle(DocumentStatusChanged $event) {
        if ($event->newStatus->key === 'approved') {
            app(DocumentMailService::class)->sendApproval($event->document);
        }
    }
}
```

Crear listeners similares para: Rejection, Completion, etc.

---

## Jobs y Scheduling

### 1. SendDocumentReminderJob

**Archivo:** `app/Jobs/Documents/SendDocumentReminderJob.php` (CREAR NUEVO)

```php
<?php

namespace App\Jobs\Documents;

use App\Models\Document\Document;use App\Services\Documents\DocumentMailService;use Illuminate\Bus\Queueable;use Illuminate\Contracts\Queue\ShouldQueue;use Illuminate\Foundation\Bus\Dispatchable;use Illuminate\Queue\InteractsWithQueue;use Illuminate\Queue\SerializesModels;

class SendDocumentReminderJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(DocumentMailService $mailService) {
        // Encontrar documentos incompletos con 7+ días
        Document::where('created_at', '<=', now()->subDays(7))
            ->whereIn('status_id', [1, 2]) // PENDING, INCOMPLETE
            ->whereDoesntHave('statusHistories', function ($q) {
                $q->where('created_at', '>=', now()->subDays(1));
            })
            ->each(function ($document) use ($mailService) {
                $mailService->sendReminder($document, queue: true);
            });
    }
}
```

### 2. CheckSlaBreachesJob

**Archivo:** `app/Jobs/Documents/CheckSlaBreachesJob.php` (CREAR NUEVO)

```php
<?php

namespace App\Jobs\Documents;

use App\Models\Document\Document;use Illuminate\Bus\Queueable;use Illuminate\Contracts\Queue\ShouldQueue;use Illuminate\Foundation\Bus\Dispatchable;use Illuminate\Queue\InteractsWithQueue;use Illuminate\Queue\SerializesModels;

class CheckSlaBreachesJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle() {
        Document::whereNotIn('status_id', [5, 6, 7]) // No completed, rejected, cancelled
            ->with('slaPolicy')
            ->get()
            ->each(fn($doc) => $doc->checkSlaBreaches());
    }
}
```

### 3. Scheduler

**Archivo:** `bootstrap/app.php` (Actualizar)

```php
$schedule->job(SendDocumentReminderJob::class)
    ->dailyAt('09:00')
    ->onOneServer()
    ->name('document-reminders');

$schedule->job(CheckSlaBreachesJob::class)
    ->everyHour()
    ->onOneServer()
    ->name('check-sla-breaches');
```

---

## Cambios en Vistas

### 1. Vista: manage.blade.php (Mejorada)

**Archivo:** `resources/views/administratives/documents/manage.blade.php`

**Agregar secciones:**

```blade
<!-- ESTADO ACTUAL + TRANSICIONES -->
<div class="card mb-4">
    <div class="card-header">
        <h6>Estado Actual</h6>
    </div>
    <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-3">
            {!! $document->status->icon() !!}
            {!! $document->status->badge() !!}
        </div>

        @if($validTransitions->count() > 0)
            <h6 class="mb-2">Posibles Acciones:</h6>
            <div class="btn-group flex-wrap">
                @foreach($validTransitions as $transition)
                    <form method="POST" action="{{ route('administrative.documents.change-status', $document) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="status_id" value="{{ $transition->id }}">
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            {{ $transition->label }}
                        </button>
                    </form>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- SLA Y DEADLINES -->
@if($slaDeadline)
    <div class="card mb-4">
        <div class="card-header">
            <h6>SLA Compliance</h6>
        </div>
        <div class="card-body">
            <p>Deadline: <strong>{{ $slaDeadline->format('Y-m-d H:i') }}</strong></p>
            <div class="progress">
                <div class="progress-bar {{ now()->isAfter($slaDeadline) ? 'bg-danger' : 'bg-success' }}"
                     style="width: {{ min(100, ($slaDeadline->diffInSeconds(now()) / $slaDeadline->diffInSeconds($document->created_at)) * 100) }}%">
                </div>
            </div>
        </div>
    </div>
@endif

<!-- DOCUMENTOS FALTANTES -->
<div class="card mb-4">
    <div class="card-header">
        <h6>Documentos</h6>
    </div>
    <div class="card-body">
        @forelse($document->getRequiredDocuments() as $doc)
            <div class="mb-2">
                <span>{{ $doc }}</span>
                @if(in_array($doc, $document->getUploadedDocuments()))
                    <span class="badge bg-success">✓ Subido</span>
                @else
                    <span class="badge bg-warning">✗ Faltante</span>
                @endif
            </div>
        @empty
            <p class="text-muted">Sin documentos requeridos</p>
        @endforelse
    </div>
</div>

<!-- TIMELINE -->
<div class="card mb-4">
    <div class="card-header">
        <h6>Timeline de Actividades</h6>
    </div>
    <div class="card-body">
        @foreach($timeline as $action)
            <div class="mb-3 pb-3 border-bottom">
                <p class="small text-muted mb-1">
                    {{ $action->created_at->format('Y-m-d H:i') }}
                </p>
                <p class="mb-1">
                    <strong>{{ $action->action_name }}</strong>
                </p>
                <p class="text-muted small">{{ $action->description }}</p>
                @if($action->performed_by)
                    <small class="text-muted">Por: {{ $action->performedBy->name }}</small>
                @endif
            </div>
        @endforeach
    </div>
</div>

<!-- ACCIONES RÁPIDAS -->
<div class="card">
    <div class="card-header">
        <h6>Acciones</h6>
    </div>
    <div class="card-body">
        <div class="btn-group-vertical w-100">
            <form method="POST" action="{{ route('administrative.documents.send-notification', $document) }}">
                @csrf
                <button type="submit" class="btn btn-light text-start">📧 Enviar Solicitud Inicial</button>
            </form>

            <form method="POST" action="{{ route('administrative.documents.send-reminder', $document) }}">
                @csrf
                <button type="submit" class="btn btn-light text-start">🔔 Enviar Recordatorio</button>
            </form>

            <form method="POST" action="{{ route('administrative.documents.send-missing', $document) }}">
                @csrf
                <button type="submit" class="btn btn-light text-start">⚠️ Documentos Faltantes</button>
            </form>

            <button type="button" class="btn btn-light text-start" data-bs-toggle="modal" data-bs-target="#customEmailModal">
                ✉️ Email Personalizado
            </button>

            <button type="button" class="btn btn-light text-start" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                📝 Agregar Nota
            </button>

            <button type="button" class="btn btn-light text-start" data-bs-toggle="modal" data-bs-target="#adminUploadModal">
                📤 Cargar Manualmente
            </button>
        </div>
    </div>
</div>
```

---

## Testing

### 1. Feature Test: Document Status Changes

**Archivo:** `tests/Feature/Documents/DocumentStatusTest.php` (CREAR NUEVO)

```php
<?php

namespace Tests\Feature\Documents;

use App\Models\Document\Document;use App\Models\Document\DocumentStatus;use App\Models\User;use Tests\TestCase;

class DocumentStatusTest extends TestCase {

    public function test_can_change_document_status() {
        $document = Document::factory()->create();
        $newStatus = DocumentStatus::where('key', 'awaiting_documents')->first();

        $this->actingAs(User::factory()->create())
            ->post(route('administrative.documents.change-status', $document), [
                'status_id' => $newStatus->id,
                'reason' => 'Documentos revisados',
            ])
            ->assertRedirect();

        $this->assertEquals($newStatus->id, $document->refresh()->status_id);
    }

    public function test_cannot_transition_invalid_states() {
        $document = Document::factory()->create();
        $document->status_id = DocumentStatus::where('key', 'completed')->first()->id;
        $document->save();

        $invalidStatus = DocumentStatus::where('key', 'pending')->first();

        $this->assertFalse($document->canTransitionTo($invalidStatus));
    }

    public function test_status_change_creates_history() {
        $document = Document::factory()->create();
        $newStatus = DocumentStatus::where('key', 'awaiting_documents')->first();

        $document->changeStatus($newStatus, 'Test reason');

        $this->assertCount(1, $document->statusHistories);
    }
}
```

### 2. Unit Test: DocumentStatusService

**Archivo:** `tests/Unit/Services/DocumentStatusServiceTest.php` (CREAR NUEVO)

```php
<?php

namespace Tests\Unit\Services;

use App\Models\Document\Document;use App\Models\Document\DocumentStatus;use App\Services\Documents\DocumentStatusService;use Tests\TestCase;

class DocumentStatusServiceTest extends TestCase {

    public function test_can_transition_valid_states() {
        $service = app(DocumentStatusService::class);
        $document = Document::factory()->create();
        $newStatus = DocumentStatus::where('key', 'awaiting_documents')->first();

        $this->assertTrue($service->canTransition($document, $newStatus));
    }

    public function test_gets_valid_transitions() {
        $service = app(DocumentStatusService::class);
        $document = Document::factory()->create();

        $transitions = $service->getValidTransitions($document);

        $this->assertNotEmpty($transitions);
    }
}
```

---

## Checklist de Implementación

```
FASE 1: MODELOS Y MIGRACIONES
- [x] Migraciones creadas
- [x] Modelos creados
- [ ] Migraciones ejecutadas
- [ ] Relaciones verificadas

FASE 2: SERVICES
- [ ] DocumentStatusService creado
- [ ] DocumentActionService mejorado
- [ ] DocumentMailService mejorado

FASE 3: CONTROLLERS
- [ ] DocumentsController actualizado
- [ ] Endpoints agregados

FASE 4: MAILABLES
- [ ] DocumentInitialRequestMail creado
- [ ] DocumentReminderMail creado
- [ ] DocumentApprovalMail creado
- [ ] DocumentRejectionMail creado
- [ ] DocumentCompletionMail creado

FASE 5: EVENTOS
- [ ] DocumentStatusChanged creado
- [ ] Listeners creados y registrados
- [ ] Eventos disparados correctamente

FASE 6: JOBS
- [ ] SendDocumentReminderJob creado
- [ ] CheckSlaBreachesJob creado
- [ ] Scheduler configurado

FASE 7: VISTAS
- [ ] manage.blade.php actualizado
- [ ] Timeline implementado
- [ ] Acciones rápidas implementadas

FASE 8: TESTING
- [ ] Tests unitarios escritos
- [ ] Tests de feature escritos
- [ ] Coverage > 80%

FASE 9: DOCUMENTACIÓN
- [ ] Código documentado
- [ ] API documentada
- [ ] Guías de usuario creadas

FASE 10: DEPLOYMENT
- [ ] Code review completado
- [ ] QA testeo completado
- [ ] Producción desplegado
```

---

*Documento creado: 2025-12-10*
*Versión: 1.0 - Plan Detallado*
