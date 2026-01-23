# Arquitectura Completa del Sistema de Documentos

## 📋 Índice
1. [Flujo General del Proceso](#flujo-general-del-proceso)
2. [Estados y Transiciones](#estados-y-transiciones)
3. [Integración Prestashop](#integración-prestashop)
4. [Componentes del Sistema](#componentes-del-sistema)
5. [Notificaciones y Emails](#notificaciones-y-emails)
6. [Seguimiento de Movimientos](#seguimiento-de-movimientos)
7. [Cambios en Modelos](#cambios-en-modelos)
8. [APIs y Endpoints](#apis-y-endpoints)

---

## Flujo General del Proceso

### 1. FASE INICIAL: ORDEN PAGADA EN PRESTASHOP

```
┌─────────────────────┐
│  Prestashop Order   │
│    Payer Clicks     │
│   "Confirm Order"   │
└──────────┬──────────┘
           │
           ├─→ Order Status = Paid ✓
           │
           └─→ [Webhook] /api/documents/process
               action=request
               ↓
         ┌─────────────────────┐
         │ Create Document     │
         ├─────────────────────┤
         │ State: PENDING      │
         │ Source: api         │ ← Origen: Prestashop API
         │ Status: Awaiting    │
         │ SLA Policy: Default │
         └─────────────────────┘
```

**Datos Recibidos de Prestashop:**
```json
{
  "order_id": 12345,
  "order_reference": "ABCD1234E",
  "customer_id": 5678,
  "customer_name": "Juan Pérez",
  "customer_email": "juan@example.com",
  "customer_dni": "12345678A",
  "customer_company": "Armas Sport",
  "customer_phone": "555-1234",
  "products": [
    {
      "id": 99,
      "name": "Rifle Mauser",
      "reference": "MAUSER-22",
      "quantity": 1,
      "price": 450.00,
      "features": {
        "23": "263660"  // Feature 23 = RIFLE (263660)
      }
    }
  ]
}
```

**Acción Automática:**
1. ✓ Se crea documento con `source = 'api'`
2. ✓ Se detecta tipo automático (rifle) por features
3. ✓ Se establece estado = `PENDING`
4. ✓ Se obtiene política SLA por defecto
5. ✓ Se **dispara evento** `DocumentCreated`
6. ✓ Listener envía **email inicial** (sincrónico)
7. ✓ Se agenda **job de recordatorio** (asincrónico, +7 días)

---

### 2. FASE EMAIL INICIAL

```
┌──────────────────────────────────────┐
│   DocumentCreated Event Fired       │
└──────────────────┬───────────────────┘
                   │
                   └─→ SendDocumentUploadNotification Listener
                       │
                       ├─→ 📧 Email: "Envíanos tus documentos"
                       │   - Link con UID del documento
                       │   - Token de validación
                       │   - Documentos requeridos (DNI frente/dorso, Licencia)
                       │   - Redirige a: https://alsernet.test/document/{uid}/upload
                       │
                       ├─→ 🔔 Notificación en panel
                       │   - "Nuevo documento solicitado"
                       │
                       └─→ 📝 Log en document_actions
                           action_type: 'email_initial_request'
                           performed_by_type: 'system'
                           metadata: {emails: ["juan@example.com"], ...}
```

**Email Template Variables:**
```blade
{{ $document->uid }}           // Token único
{{ $document->customer_name }} // Juan Pérez
{{ $document->documentType }}  // rifle
{{ $document->requiredDocs }}  // Array de documentos
{{ $uploadUrl }}              // https://...document/{uid}/upload
```

---

### 3. FASE FORMULARIO DE CARGA

```
┌─────────────────────────────────────────┐
│  Cliente Abre Link (desde email)        │
│  https://alsernet.test/document/{uid}   │
└──────────────┬──────────────────────────┘
               │
               ├─→ [Validar Token]
               │   - ¿Existe documento con este UID?
               │   - ¿Está en estado válido? (PENDING, AWAITING)
               │
               └─→ SI → Mostrar Formulario
                   │
                   ├─ Campo 1: DNI Cara Frontal
                   │  └─ [Subir archivo]
                   │
                   ├─ Campo 2: DNI Cara Dorsal
                   │  └─ [Subir archivo]
                   │
                   ├─ Campo 3: Licencia de Armas
                   │  └─ [Subir archivo]
                   │
                   └─ [Enviar Documentos]
```

**Validación en Subida:**
```php
// Validar cada archivo
- Max size: 10MB
- Tipos: PDF, JPG, PNG
- Escaneo antivirus (si está habilitado)
- OCR (opcional)

// Registrar qué se subió
uploaded_documents = [
    ['name' => 'DNI Frente', 'file_id' => 1234, 'uploaded_at' => now()],
    ['name' => 'DNI Dorso', 'file_id' => 1235, 'uploaded_at' => now()],
    ['name' => 'Licencia', 'file_id' => 1236, 'uploaded_at' => now()]
]
```

**Estados Posibles Después de Subida:**
```
┌─────────────────────────────────────┐
│   ¿Subió TODOS los documentos?      │
├─────────────────────────────────────┤
│                                     │
│ NO  → Estado = INCOMPLETE           │
│       "Le faltan: Licencia"         │
│       Mostrar: "Vuelve a intentar"  │
│                                     │
│ YES → Estado = AWAITING_DOCUMENTS   │
│       "Documentos recibidos"        │
│       Msg: "Estamos revisando..."   │
│                                     │
└─────────────────────────────────────┘

Transición guardada en:
- document_status_histories
  from_status_id: PENDING → to_status_id: INCOMPLETE/AWAITING
  changed_by: NULL (customer)
  reason: "Customer uploaded files"
```

**Acción Automática:**
```php
[DocumentUploaded Event Fired]
  │
  └─→ SendDocumentUploadConfirmation
      │
      └─→ 📧 Email: "Documentos recibidos"
          "Gracias! Estamos revisando tu información..."

      └─→ 📝 Log: action_type = 'documents_uploaded'
```

---

### 4. FASE REVISIÓN Y GESTIÓN (Panel Admin)

```
┌─────────────────────────────────────────────────────┐
│         Gerente/Admin abre Panel                    │
│   /administrative/documents/manage/{uid}            │
└────────────────────┬────────────────────────────────┘
                     │
        ┌────────────┴────────────────┐
        │                             │
        ▼                             ▼
    VER FALTANTES               VER OPCIONES
    ┌─────────────────┐        ┌──────────────────┐
    │ ¿Le faltan docs?│        │ 📧 Enviar Email  │
    │                 │        │ 📝 Agregar Nota  │
    │ DNI: ✓          │        │ 🔄 Cambiar Estado│
    │ Licencia: ✗     │        │ 📤 Cargar Manual │
    │                 │        │ 📋 Ver Historial│
    └─────────────────┘        └──────────────────┘
```

#### **Opción A: SOLICITAR DOCUMENTOS FALTANTES**

```
Admin hace clic: "Solicitar documentos faltantes"
│
├─→ Email automático:
│   "Te falta: Licencia de Armas"
│   "Link para completar: [URL]"
│
├─→ Estado NO cambia (sigue INCOMPLETE)
│
└─→ Cambio rastreado:
    document_actions
    action_type: 'email_missing_documents'
    metadata: {missing: ['Licencia'], ...}
```

#### **Opción B: CARGAR MANUALMENTE (Admin)**

```
Admin carga documentos directamente:
│
├─→ POST /administrative/documents/{uid}/admin-upload
│   Files: [Licencia.pdf]
│   Source: 'manual' ← Origen: Carga Manual
│
├─→ Validar y guardar archivos
│
├─→ Actualizar:
│   uploaded_documents = [..., {name: 'Licencia', file_id: 2000, ...}]
│
├─→ Revisar: ¿Completo ahora?
│   SI → Estado = APPROVED (listo para gestionar)
│   NO → Estado = INCOMPLETE (falta más)
│
└─→ Rastrear:
    document_actions
    action_type: 'admin_documents_uploaded'
    performed_by: user_id (admin)
    performed_by_type: 'admin'
    metadata: {files: ['Licencia.pdf'], ...}
```

#### **Opción C: ENVIAR RECORDATORIO (+7 días)**

```
Si pasan 7 días sin completar:

Automático (por job/cron):
  └─→ Verificar documentos incompletos
      └─→ SI → Enviar email recordatorio
          │
          ├─→ 📧 "Recordatorio: Completa tus documentos"
          │   "Te quedan documentos pendientes..."
          │
          └─→ 📝 Log: action_type = 'email_reminder'
              metadata: {remind_date: '2025-12-17', ...}

También Manual:
  Admin clic: "Enviar Recordatorio"
  └─→ Mismo flujo (email + log)
```

#### **Opción D: ENVIAR EMAIL PERSONALIZADO**

```
Admin clic: "Enviar Email Personalizado"
│
├─→ Abrir modal/formulario
│   Asunto: [Campo de texto]
│   Cuerpo: [Editor WYSIWYG]
│   Variables: {name}, {dni}, {order_id}, etc
│
├─→ Enviar email
│
└─→ Rastrear:
    document_actions
    action_type: 'email_custom'
    performed_by: user_id
    metadata: {
        subject: "...",
        body: "...",
        email_to: "juan@example.com"
    }
```

#### **Opción E: AGREGAR NOTA INTERNA**

```
Admin clic: "Agregar Nota"
│
├─→ document_notes
    created_by: user_id
    content: "Cliente llamó diciendo que carga mañana"
    is_internal: true (no visible para cliente)
│
├─→ Rastrear:
    document_actions
    action_type: 'note_added'
    performed_by: user_id
│
└─→ Visible solo en panel admin
```

---

### 5. FASE APROBACIÓN Y COMPLETACIÓN

```
┌────────────────────────────────────────────┐
│  Admin: "Revisar documentos"               │
│  - Verificar DNI legible ✓                │
│  - Verificar Licencia válida ✓            │
│  - Verificar datos consistentes ✓         │
└──────────────┬───────────────────────────┘
               │
      ┌────────┴────────┐
      │                 │
      ▼                 ▼
   APROBAR          RECHAZAR
   ┌──────────┐    ┌──────────┐
   │ APPROVED │    │ REJECTED │
   └────┬─────┘    └────┬─────┘
        │               │
        ├─→ Email OK    ├─→ Email: "Documentos rechazados"
        │               │   Razón: "DNI vencido"
        │               │   Próximo paso: "Carga DNI válido"
        │               │
        └─→ Log OK      └─→ Log: action_type = 'status_changed'
                            from: AWAITING
                            to: REJECTED
                            reason: "DNI vencido"
```

**Transición de Estado:**
```sql
INSERT INTO document_status_histories
(document_id, from_status_id, to_status_id, changed_by, reason, created_at)
VALUES
(123, 4, 5, user_id, "Documentos verificados correctamente", NOW());
-- from: AWAITING → to: APPROVED
```

**Email de Aprobación:**
```
Asunto: "✓ Tus documentos han sido aprobados"

Contenido:
"¡Excelente! Hemos verificado tus documentos
y todo está en orden.

Próximo paso: Procesaremos tu pedido en breve.

Referencia de orden: ABCD1234E"
```

---

### 6. FASE COMPLETACIÓN

```
┌─────────────────────────────┐
│  Admin finaliza gestión     │
│  Status: APPROVED           │
│  Admin: "Marcar completado" │
└────────────┬────────────────┘
             │
        [DocumentCompleted Event]
             │
             ├─→ Estado = COMPLETED
             │
             ├─→ 📧 Email: "Documentos completados"
             │   "Tu proceso ha sido completado"
             │
             ├─→ Actualizar order status en Prestashop
             │   status = "documents_completed"
             │
             └─→ 📝 Log: action_type = 'status_changed'
                 from: APPROVED
                 to: COMPLETED
```

---

## Estados y Transiciones

### Máquina de Estados

```
┌─────────────┐
│   PENDING   │  Estado inicial: Documento solicitado
└──────┬──────┘
       │
       ├─→ [documentos faltantes en subida]
       │   └─→ INCOMPLETE ────┐
       │                      │
       │   [todos los docs]   │
       └─────────────────→ AWAITING_DOCUMENTS
                             │
                             ├─→ [admin rechaza]
                             │   └─→ REJECTED
                             │
                             ├─→ [admin aprueba]
                             │   └─→ APPROVED ─→ COMPLETED
                             │
                             └─→ [solicita más info]
                                 └─→ INCOMPLETE (ciclo)
```

### Tabla: `document_statuses`

| Key | Label | Description | Color | Icon | Order |
|-----|-------|-------------|-------|------|-------|
| pending | Pendiente | Documento recién creado | #6c757d | circle | 1 |
| incomplete | Incompleto | Faltan documentos | #ffc107 | alert-circle | 2 |
| awaiting_documents | Esperando | Esperando aprobación | #17a2b8 | hourglass | 3 |
| approved | Aprobado | Documentos OK | #28a745 | check-circle | 4 |
| completed | Completado | Procesado | #20c997 | badge-check | 5 |
| rejected | Rechazado | No válidos | #dc3545 | x-circle | 6 |
| cancelled | Cancelado | Cancelado | #6c757d | ban | 7 |

### Tabla: `document_status_transitions`

Transiciones permitidas:

| From | To | Permission | Requires All Docs | Notes |
|------|----|-----------|----|-------|
| PENDING | INCOMPLETE | - | No | Cliente sube parcial |
| PENDING | AWAITING | - | No | Admin marca listo |
| PENDING | CANCELLED | - | No | Cancelar solicitud |
| INCOMPLETE | AWAITING | - | Yes | Todos los docs OK |
| INCOMPLETE | REJECTED | documents.reject | No | Admin rechaza |
| INCOMPLETE | CANCELLED | - | No | |
| AWAITING | APPROVED | documents.approve | Yes | Admin aprueba |
| AWAITING | INCOMPLETE | - | No | Pide más info |
| AWAITING | CANCELLED | - | No | |
| APPROVED | COMPLETED | documents.complete | Yes | Finalizar |
| APPROVED | REJECTED | documents.reject | No | |
| REJECTED | AWAITING | - | No | Reenviar docs |
| REJECTED | CANCELLED | - | No | |

---

## Integración Prestashop

### Flujo de Datos

```
┌──────────────────────────────┐
│  Prestashop eCommerce        │
│  - Order Management          │
│  - Product Management        │
│  - Customer Management       │
└───────────┬──────────────────┘
            │
            ├─→ [Order Status = Paid]
            │   └─→ Webhook POST
            │       /api/documents/process
            │       action=request
            │
            ├─ Datos enviados:
            │  - order_id, order_reference
            │  - customer_id, name, email, dni
            │  - products array con features
            │
            └─→ [Síncrono/Asíncrono]
                Respuesta JSON:
                {
                  "success": true,
                  "document_uid": "ABC123XYZ",
                  "status": "pending",
                  "upload_url": "..."
                }
```

### Campos Denormalizados

**Por qué denormalizamos:**
- Velocidad de consultas en panel
- Datos históricos (cliente cambia de email)
- No necesitamos actualizar si Prestashop cambia
- Datos de auditoría congelados

**Datos Copiados:**
```php
$document->order_id           // ID de Prestashop
$document->order_reference    // ABCD1234E
$document->order_date         // 2025-12-10
$document->customer_id        // ID Prestashop
$document->customer_firstname // Juan
$document->customer_lastname  // Pérez
$document->customer_email     // juan@example.com
$document->customer_dni       // 12345678A
$document->customer_company   // Armas Sport
$document->customer_cellphone // 555-1234
```

### Detección Automática de Tipo

```
Prestashop Product Features:
┌──────────────────────────────┐
│ Feature ID 23 = Tipo de Arma │
├──────────────────────────────┤
│ 263658 = DNI                 │
│ 263659 = ESCOPETA            │
│ 263660 = RIFLE               │
│ 263661 = CORTA               │
└──────────────────────────────┘

Lógica de Detección:
1. Iterar productos en order
2. Buscar Feature 23
3. Mapear feature_id a tipo
4. Tomar el más restrictivo:
   - Si hay RIFLE → tipo = RIFLE
   - Si no, ESCOPETA → tipo = ESCOPETA
   - Si no, CORTA → tipo = CORTA
   - Si no → tipo = GENERAL
```

---

## Componentes del Sistema

### 1. Modelos Eloquent

#### DocumentStatus
```php
class DocumentStatus extends Model {
    public function documents()      // Documentos en este estado
    public function statusHistories() // Cambios a este estado
    public function transitionsFrom() // Transiciones salientes
    public function transitionsTo()   // Transiciones entrantes
}
```

#### Document
```php
class Document extends Model {
    // Relaciones
    public function status()              // Estado actual
    public function statusHistories()     // Cambios históricos
    public function slaPolicy()           // Política SLA asignada
    public function slaBreaches()         // Incumplimientos SLA
    public function actions()             // Auditoría completa
    public function notes()               // Notas internas/externas
    public function products()            // Productos del pedido

    // Métodos clave
    public function getRequiredDocuments()
    public function getMissingDocuments()
    public function allDocumentsUploaded()
    public function canTransitionTo(DocumentStatus $target)
}
```

#### DocumentStatusHistory
```php
class DocumentStatusHistory extends Model {
    public function document()    // Document
    public function fromStatus()  // DocumentStatus (nullable)
    public function toStatus()    // DocumentStatus
    public function changedBy()   // User (nullable - customer = null)
}
```

#### DocumentStatusTransition
```php
class DocumentStatusTransition extends Model {
    public function fromStatus()  // DocumentStatus
    public function toStatus()    // DocumentStatus
    public function canTransition($user) // Check permissions
}
```

#### DocumentAction
```php
class DocumentAction extends Model {
    // Auditría de TODO
    action_type: enum (email, upload, status_change, note, etc)
    action_name: string
    description: text
    metadata: json
    performed_by: user_id (nullable)
    performed_by_type: enum (admin, customer, system)
}
```

#### DocumentNote
```php
class DocumentNote extends Model {
    public function document()   // Document
    public function createdBy()  // User
    // is_internal: true = solo admin, false = visible cliente
}
```

#### DocumentSlaPolicy
```php
class DocumentSlaPolicy extends Model {
    // Times in minutes
    upload_request_time
    review_time
    approval_time

    // Business hours
    business_hours_only
    business_hours: json
    timezone

    // Escalation
    enable_escalation
    escalation_threshold_percent

    // Type multipliers
    document_type_multipliers: json
}
```

#### DocumentSlaBreach
```php
class DocumentSlaBreach extends Model {
    public function document()
    public function slaPolicy()
    // breach_type: upload_request | review | approval
    // escalated, escalated_at
    // resolved, resolved_at
}
```

### 2. Tabla: `request_documents` (Campos Nuevos)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| status_id | FK | Referencia a document_statuses |
| sla_policy_id | FK | Referencia a document_sla_policies |
| source | enum | Origen: api, manual, erp, email, etc |
| last_action_at | timestamp | Último movimiento |
| completed_at | timestamp | Cuándo se completó |

### 3. Servicios

#### DocumentStatusService
```php
class DocumentStatusService {
    public function changeStatus(Document $doc, DocumentStatus $newStatus, $reason)
    public function getValidTransitions(Document $doc)
    public function canTransition(Document $doc, DocumentStatus $target)
    public function getHistory(Document $doc)
}
```

#### DocumentActionService
```php
class DocumentActionService {
    public function logStatusChange($doc, $from, $to, $reason)
    public function logEmailSent($doc, $type, $metadata)
    public function logDocumentUpload($doc, $files, $by_type)
    public function logAdminAction($doc, $action, $user)
    public function getTimeline($doc)
}
```

#### DocumentMailService
```php
class DocumentMailService {
    public function sendUploadNotification(Document $doc)
    public function sendReminder(Document $doc)
    public function sendMissingDocs(Document $doc, array $missing)
    public function sendApprovalEmail(Document $doc)
    public function sendRejectionEmail(Document $doc, $reason)
    public function sendCustomEmail(Document $doc, $subject, $body)
}
```

---

## Notificaciones y Emails

### Tabla: Email Trigger Points

| Trigger | Estado Anterior | Estado Nuevo | Email Type | Sent By |
|---------|-----------------|--------------|-----------|---------|
| Order pagada | N/A | PENDING | `document_initial_request` | System |
| Cliente sube docs | PENDING | AWAITING | `document_upload_confirmation` | System |
| Admin rechaza | AWAITING | REJECTED | `document_rejected` | System |
| Admin aprueba | AWAITING | APPROVED | `document_approved` | System |
| Admin completado | APPROVED | COMPLETED | `document_completed` | System |
| Recordatorio manual | ANY | ANY | `document_reminder` | Admin |
| Solicitar faltantes | ANY | ANY | `document_missing_docs` | Admin |
| Email personalizado | ANY | ANY | `document_custom` | Admin |

### Email Templates

#### 1. Initial Request Email
```
Asunto: "Necesitamos tus documentos para procesar tu pedido"

Contenido Variables:
- {{ $document->customer_firstname }}
- {{ $document->order_reference }}
- {{ $uploadUrl }}
- {{ $requiredDocuments }}  // Array renderizado
- {{ $deadline }}  // +7 días

Botón: "Subir Documentos"
```

#### 2. Missing Documents Email
```
Asunto: "Te faltan documentos"

Variables:
- {{ implode(', ', $missing) }}  // Licencia, DNI dorso
- {{ $uploadUrl }}

Contenido: "Completa cargando: Licencia de Armas"
```

#### 3. Reminder Email
```
Asunto: "Recordatorio: Completa tus documentos"

Variables:
- {{ $daysRemaining }}  // Cuántos días quedan
- {{ $missing }}  // Qué falta
- {{ $uploadUrl }}

Tono: Cortés pero urgente
```

---

## Seguimiento de Movimientos

### Tabla: `document_actions`

**Propósito:** Auditría completa de TODO lo que ocurra

```php
public function logAction($document, $type, $name, $description, $metadata = [], $user = null, $userType = 'system') {
    DocumentAction::create([
        'document_id' => $document->id,
        'action_type' => $type,        // email_sent, upload, status_change, note, etc
        'action_name' => $name,        // "Correo inicial enviado"
        'description' => $description, // Descripción legible
        'metadata' => $metadata,       // JSON adicional
        'performed_by' => $user,       // user_id o null
        'performed_by_type' => $userType, // admin, customer, system
        'created_at' => now()
    ]);
}
```

### Tipos de Acciones a Rastrear

```
EMAIL ACTIONS:
- email_initial_request
- email_reminder
- email_missing_documents
- email_custom
- email_approval
- email_rejection
- email_completion

DOCUMENT ACTIONS:
- documents_uploaded        // Cliente sube
- admin_documents_uploaded  // Admin carga manual
- document_deleted
- document_verified

STATUS CHANGES:
- status_changed           // Cambio de estado
  metadata: {from: 'PENDING', to: 'AWAITING', reason: '...'}

ADMIN ACTIONS:
- note_added
- note_edited
- note_deleted
- source_changed

SYSTEM ACTIONS:
- sla_breach_detected      // SLA incumplido
- escalation_triggered     // Escalamiento automático
- auto_reminder_sent       // Recordatorio automático
- document_created         // Nuevo documento
```

### Visualización en Panel

**Timeline del Documento:**
```
Timeline View:
[📝 2025-12-10 10:30] Documento creado desde API (Prestashop)
[📧 2025-12-10 10:32] Email inicial enviado a juan@example.com
[📤 2025-12-12 14:15] Cliente sube: DNI Frente, DNI Dorso
[⚠️  2025-12-12 14:16] Documento incompleto - Falta: Licencia
[📧 2025-12-12 14:18] Email solicitando: Licencia de Armas
[📤 2025-12-13 09:00] Cliente sube: Licencia.pdf
[✓  2025-12-13 09:02] Documento completado (Awaiting Review)
[👤 2025-12-13 10:00] Admin revisa: Todo correcto
[✔️  2025-12-13 10:05] APROBADO por Juan López (admin)
[📧 2025-12-13 10:06] Email: Documentos aprobados
[🏁 2025-12-14 15:30] COMPLETADO - Listo para procesar
```

---

## Cambios en Modelos

### Modelo: Document

**Agregar Campos:**
```php
// Ya existen pero nuevos usos:
$document->status_id        // FK a document_statuses
$document->sla_policy_id    // FK a document_sla_policies
$document->source           // origen: api, manual, erp, etc

// Nuevos campos opcionales:
$document->completed_at     // timestamp
$document->last_action_at   // timestamp
$document->sla_breach_count // int contador
```

**Agregar Relaciones:**
```php
public function status(): BelongsTo {
    return $this->belongsTo(DocumentStatus::class);
}

public function statusHistories(): HasMany {
    return $this->hasMany(DocumentStatusHistory::class);
}

public function slaPolicy(): BelongsTo {
    return $this->belongsTo(DocumentSlaPolicy::class);
}

public function slaBreaches(): HasMany {
    return $this->hasMany(DocumentSlaBreach::class);
}
```

**Agregar Métodos:**
```php
public function canTransitionTo(DocumentStatus $status): bool
public function transitionTo(DocumentStatus $status, $reason = null, $user = null)
public function changeStatus(DocumentStatus $newStatus, $reason, $user = null)
public function markCompleted()
public function getSlaDeadline()
public function checkSlaBreaches()
public function getTimeline()
public function getOriginLabel(): string // "API Prestashop", "Carga Manual", etc
```

### Modelo: DocumentConfiguration

**Agregar Campo:**
```php
$table->foreignId('default_sla_policy_id')
      ->nullable()
      ->constrained('document_sla_policies')
      ->setOnDelete('set null');
```

**Agregar Método:**
```php
public function defaultSlaPolicy(): BelongsTo {
    return $this->belongsTo(DocumentSlaPolicy::class, 'default_sla_policy_id');
}
```

---

## APIs y Endpoints

### Administrative Routes

```php
Route::prefix('documents')->group(function () {
    // List & Management
    Route::get('/', 'DocumentsController@index');                    // All
    Route::get('/pending', 'DocumentsController@pending');           // Pending only
    Route::get('/history', 'DocumentsController@history');           // Completed
    Route::get('/manage/{uid}', 'DocumentsController@manage');       // Detail view
    Route::get('/view/{uid}', 'DocumentsController@show');           // Public view

    // Status Management
    Route::post('/status/{uid}/change', 'DocumentsController@changeStatus');

    // Email Notifications
    Route::post('/{uid}/send-notification', 'DocumentsController@sendNotification');
    Route::post('/{uid}/send-reminder', 'DocumentsController@sendReminder');
    Route::post('/{uid}/send-missing', 'DocumentsController@sendMissing');
    Route::post('/{uid}/send-custom-email', 'DocumentsController@sendCustomEmail');

    // Document Management
    Route::post('/{uid}/admin-upload', 'DocumentsController@adminUpload');
    Route::post('/{uid}/add-note', 'DocumentsController@addNote');
    Route::get('/{uid}/missing-documents', 'DocumentsController@getMissing');

    // Sync/Import
    Route::get('/sync/all', 'DocumentsController@syncAll');
    Route::post('/sync/by-order', 'DocumentsController@syncByOrder');
    Route::get('/sync/from-erp', 'DocumentsController@syncErp');
});
```

### API Routes

```php
Route::prefix('api/documents')->group(function () {
    // Webhooks from Prestashop
    Route::post('/process', 'Api\DocumentsController@process');
    Route::post('/order-paid', 'Api\DocumentsController@orderPaid');

    // Client Upload
    Route::post('/upload', 'Api\DocumentsController@upload');
    Route::post('/confirm', 'Api\DocumentsController@confirm');

    // Status Queries
    Route::get('/status/{uid}', 'Api\DocumentsController@status');
    Route::get('/missing/{uid}', 'Api\DocumentsController@missing');

    // Sync
    Route::get('/sync/by-order/{orderId}', 'Api\DocumentsController@syncByOrder');
});
```

### Webhook Format

**POST /api/documents/process**
```json
{
  "action": "request",
  "order_id": 12345,
  "order_reference": "ABCD1234E",
  "order_date": "2025-12-10",
  "customer_id": 5678,
  "customer_firstname": "Juan",
  "customer_lastname": "Pérez",
  "customer_email": "juan@example.com",
  "customer_dni": "12345678A",
  "customer_company": "Armas Sport",
  "customer_phone": "555-1234",
  "products": [
    {
      "id": 99,
      "name": "Rifle Mauser",
      "reference": "MAUSER-22",
      "quantity": 1,
      "price": 450.00,
      "features": {
        "23": "263660"
      }
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "document_uid": "ABC123XYZ",
  "status": "pending",
  "upload_url": "https://alsernet.test/document/ABC123XYZ/upload",
  "required_documents": [
    "DNI Cara Frontal",
    "DNI Cara Dorsal",
    "Licencia de Armas"
  ]
}
```

---

## Decisiones de Arquitectura

### 1. ¿Por qué Denormalizamos?
- ✓ Consultas instantáneas en panel
- ✓ Datos históricos congelados
- ✓ No dependemos de Prestashop
- ✓ Auditría precisa

### 2. ¿Por qué Múltiples Tablas de Estado?
- ✓ `document_statuses` - Configuración
- ✓ `document_status_histories` - Auditría
- ✓ `document_status_transitions` - Reglas

Mejor que tener TODO en un enum inmutable.

### 3. ¿Por qué `source` como Enum?
- ✓ Fácil de filtrar/reportar
- ✓ Consistencia
- ✓ Extensible (podemos agregar valores)

### 4. ¿Por qué Events/Listeners?
- ✓ Desacoplamiento
- ✓ Fácil de extender
- ✓ Testing más simple

### 5. ¿Por qué SLAs Separadas?
- ✓ Flexible según tipo documento
- ✓ Fácil de cambiar tiempos
- ✓ Escalamientos automáticos

---

## Implementación Roadmap

### FASE 1: Base (Ya Completada)
- [x] Crear modelos DocumentStatus*
- [x] Crear modelos DocumentSlaPolicy*
- [x] Agregar relaciones a Document
- [x] Crear controlador DocumentSlaPoliciesController
- [x] Crear vistas SLA policies
- [x] Agregar rutas

### FASE 2: Estado y Transiciones
- [ ] Implementar DocumentStatusService
- [ ] Agregar método changeStatus() en Document
- [ ] Validar transiciones en controladores
- [ ] Actualizar vistas para mostrar estado actual

### FASE 3: Auditría Completa
- [ ] Implementar DocumentActionService mejorado
- [ ] Agregar logging a TODOS los endpoints
- [ ] Crear vista Timeline
- [ ] Integrar documento_notes en UI

### FASE 4: Notificaciones Avanzadas
- [ ] Crear todas las mailables (approval, rejection, completion)
- [ ] Agregar job para recordatorios automáticos
- [ ] Crear template de email personalizado
- [ ] Integrar con queue system

### FASE 5: SLA y Escalamientos
- [ ] Crear job para detectar SLA breaches
- [ ] Implementar escalamientos automáticos
- [ ] Crear reportes de SLA compliance
- [ ] Dashboard de SLA metrics

### FASE 6: Integraciones
- [ ] Mejorar sincronización Prestashop
- [ ] Webhook de estado actualizado
- [ ] Exportar datos a ERP
- [ ] Integración con Google Drive/S3

---

## Configuración Recomendada

### Global Document Settings (settings tabla)

```
documents.enable_initial_request = true
documents.initial_request_days_to_remind = 7
documents.reminder_email_enabled = true
documents.auto_escalation_enabled = true
documents.escalation_threshold_percent = 80
documents.business_hours_only = true
documents.timezone = "America/Mexico_City"

documents.enable_customer_login = false  // Para upload sin login
documents.require_email_verification = false
documents.antivirus_scan_enabled = false
documents.ocr_enabled = false
```

---

## Testing Strategy

```gherkin
Scenario: Cliente completa documentos correctamente
  Given: Documento en estado PENDING
  When:  Cliente sube todos los documentos
  Then:  Estado cambia a AWAITING_DOCUMENTS
  And:   Email de confirmación se envía
  And:   Acción se registra en timeline

Scenario: Cliente sube documentos incompletos
  Given: Documento requiere 3 docs
  When:  Cliente sube solo 2 docs
  Then:  Estado permanece INCOMPLETE
  And:   Email indica qué falta

Scenario: Admin rechaza documentos
  Given: Documento en AWAITING
  When:  Admin clic rechazar + razón
  Then:  Estado cambia a REJECTED
  And:   Email con razón se envía
  And:   Cliente puede reenviar

Scenario: SLA Vencido
  Given: Documento con SLA 24h
  And:   Sin cambios en 24h
  Then:  Se crea DocumentSlaBreach
  And:   Email de escalamiento (si habilitado)
```

---

## Conclusión

Este sistema proporciona:

✅ **Trazabilidad Completa** - Cada acción auditada
✅ **Flexibilidad** - Estados y transiciones configurables
✅ **Automatización** - Emails y recordatorios automáticos
✅ **SLA Compliance** - Tracking de tiempos de servicio
✅ **Integración Prestashop** - Sincronización automática
✅ **Experiencia Usuario** - Notificaciones claras

**Próximo paso:** Implementar Fase 2 (Estado y Transiciones)

---

*Documento creado: 2025-12-10*
*Versión: 1.0*
*Status: Propuesta de Arquitectura*
