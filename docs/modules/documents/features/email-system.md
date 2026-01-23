# Document Email System Integration with Mailers

Complete guide for integrating the document management system with the existing mailers email template infrastructure. This document explains how email templates, language support, and document configurations work together.

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Email Template Structure](#email-template-structure)
3. [Global Configuration Mapping](#global-configuration-mapping)
4. [Document Type Configuration](#document-type-configuration)
5. [SLA Policy Integration](#sla-policy-integration)
6. [Language-Aware Email Implementation](#language-aware-email-implementation)
7. [Email Trigger Points](#email-trigger-points)
8. [Template Variables](#template-variables)
9. [Implementation Workflow](#implementation-workflow)
10. [Testing & Troubleshooting](#testing--troubleshooting)

---

## System Overview

### Architecture Diagram

```
Prestashop Order Payment
        ↓
   Document Requested (Status: PENDING)
        ↓
   Email Trigger (Depends on Settings)
        ↓
   Language Selection (customer lang_id)
        ↓
   Template Lookup (EmailTemplate table)
        ↓
   Variable Substitution
        ↓
   Layout Application (Header/Footer/Wrapper)
        ↓
   Send via Queue (SendDocumentEmailJob)
```

### Key Components

| Component | Storage | Language Support | Purpose |
|-----------|---------|------------------|---------|
| **Global Settings** | Settings table | No (centralized) | System-wide behavior toggles and message defaults |
| **Email Templates** | email_templates table | Yes (lang_id FK) | Template content with variable placeholders |
| **Email Layouts** | layouts table | Yes (lang_id FK) | Header/footer/wrapper components |
| **Document Types** | request_document_configurations | No (centralized) | Required documents per document type |
| **SLA Policies** | document_sla_policies | No (centralized) | Time targets and business hours |
| **Document Status** | document_statuses | No (centralized) | State definitions (pending, awaiting, approved, etc.) |

---

## Email Template Structure

### Database Tables

#### email_templates Table
```sql
CREATE TABLE email_templates (
    id BIGINT PRIMARY KEY,
    uid VARCHAR(255) UNIQUE,
    key VARCHAR(255),           -- template_key for lookup
    subject VARCHAR(255),       -- Email subject line
    content LONGTEXT,           -- Email HTML content with {PLACEHOLDERS}
    layout_id BIGINT NULLABLE,  -- FK to layouts for header/footer
    lang_id BIGINT,             -- FK to langs table for language
    module VARCHAR(100),        -- 'documents', 'orders', 'notifications', 'core'
    is_enabled BOOLEAN,         -- Can be disabled per language
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY unique_template_per_lang (key, lang_id, module),
    FOREIGN KEY (layout_id) REFERENCES layouts(id) ON DELETE SET NULL,
    FOREIGN KEY (lang_id) REFERENCES langs(id) ON DELETE CASCADE
);
```

#### Document Email Keys
These keys must be created in the mailers UI for each language:

```
Key                          | Module    | Usage
────────────────────────────────────────────────────────────────
document_initial_request     | documents | Customer receives order payment → request to upload docs
document_missing_documents   | documents | Admin requests specific missing docs from customer
document_reminder            | documents | Automatic 7-day reminder if docs not uploaded
document_approved            | documents | Admin approved all documents
document_rejected            | documents | Admin rejected documents (needs changes)
document_completed           | documents | Document processing completed, order ready
```

#### layouts Table
```sql
CREATE TABLE layouts (
    id BIGINT PRIMARY KEY,
    alias VARCHAR(255) UNIQUE,  -- 'email_template_header', 'email_template_wrapper', etc.
    type VARCHAR(50),           -- 'partial', 'layout', 'component'
    content LONGTEXT,           -- HTML with {CONTENT} slot for template injection
    lang_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (lang_id) REFERENCES langs(id) ON DELETE CASCADE
);
```

**System Protected Layouts** (cannot be deleted):
- `email_template_header` - Company logo and header
- `email_template_footer` - Links and footer text
- `email_template_wrapper` - Main layout with {CONTENT} slot

### Template Rendering Process

```php
// Pseudo-code: TemplateRendererService::renderEmailTemplate()

1. Fetch EmailTemplate record:
   SELECT * FROM email_templates
   WHERE key = 'document_initial_request'
     AND lang_id = $userLangId
     AND module = 'documents'
     AND is_enabled = true

2. If not found, fallback to default language:
   SELECT * FROM email_templates
   WHERE key = 'document_initial_request'
     AND lang_id = Lang::first()->id
     AND module = 'documents'
     AND is_enabled = true

3. Replace variables in template content:
   - {CUSTOMER_NAME} → customer's full name
   - {ORDER_REFERENCE} → Prestashop order reference
   - {UPLOAD_LINK} → portal URL with document token
   - {EXPIRATION_DATE} → deadline formatted to user's language
   - {COMPANY_NAME} → system company name

4. If layout_id exists, apply layout:
   - Fetch Layout by layout_id
   - Replace {CONTENT} in layout with processed template
   - Apply header and footer

5. Return final HTML ready to send
```

---

## Global Configuration Mapping

### Settings Table Structure

Global document settings are stored as key-value pairs in the Settings table with `documents.` prefix.

#### Configuration Keys

| Key | Type | Default | Purpose |
|-----|------|---------|---------|
| `documents.enable_initial_request` | yes/no | yes | Send initial request email when document created |
| `documents.initial_request_message` | text | NULL | Custom message in initial request email |
| `documents.enable_reminder` | yes/no | yes | Enable automatic 7-day reminder |
| `documents.reminder_days` | integer | 7 | Days before sending reminder (1-90) |
| `documents.reminder_message` | text | NULL | Custom message in reminder email |
| `documents.enable_missing_docs` | yes/no | yes | Enable request for specific missing documents |
| `documents.missing_docs_message` | text | NULL | Custom message when requesting missing docs |

### Trigger Flow

```
Document Created (status_id = PENDING)
├─ enable_initial_request = yes?
│  └─ Queue SendDocumentInitialRequestJob
│     ├─ Language: customer.lang_id
│     ├─ Template: document_initial_request
│     ├─ Custom Message: initial_request_message
│     └─ Send Email
│
├─ Schedule Reminder (if enable_reminder = yes)
│  └─ Queue SendDocumentReminderJob after reminder_days
│     ├─ Language: customer.lang_id
│     ├─ Template: document_reminder
│     ├─ Custom Message: reminder_message
│     └─ Send Email
│
└─ Register Status Transition Watch
   └─ Listen for status changes to trigger emails
```

### Admin Actions

```
Admin requests missing documents
├─ enable_missing_docs = yes?
│  └─ Validate selected documents exist in DocumentConfiguration
│     └─ Queue SendMissingDocumentsJob
│        ├─ Language: customer.lang_id
│        ├─ Template: document_missing_documents
│        ├─ Variables:
│        │  - {MISSING_DOCUMENTS} → bulleted list of requested docs
│        │  - {REQUEST_REASON} → admin note/reason
│        ├─ Custom Message: missing_docs_message
│        └─ Send Email

Admin approves documents
├─ Status Change: AWAITING_DOCUMENTS → APPROVED
│  └─ Queue SendDocumentApprovedJob
│     ├─ Language: customer.lang_id
│     ├─ Template: document_approved
│     └─ Send Email

Admin rejects documents
├─ Status Change: AWAITING_DOCUMENTS → INCOMPLETE
│  └─ Queue SendDocumentRejectedJob
│     ├─ Language: customer.lang_id
│     ├─ Template: document_rejected
│     ├─ Variables:
│     │  - {REJECTION_REASON} → required admin reason
│     ├─ Custom Message: (from SLA policy or admin note)
│     └─ Send Email

Admin marks completed
├─ Status Change: APPROVED → COMPLETED
│  └─ Queue SendDocumentCompletedJob
│     ├─ Language: customer.lang_id
│     ├─ Template: document_completed
│     └─ Send Email
```

---

## Document Type Configuration

### Configuration Storage

Document types are stored in `request_document_configurations` table with document-specific requirements.

#### Table Structure
```sql
CREATE TABLE request_document_configurations (
    id BIGINT PRIMARY KEY,
    document_type VARCHAR(50) UNIQUE,      -- 'corta', 'rifle', 'escopeta', 'dni', 'general'
    document_type_label VARCHAR(100),      -- 'Armas Cortas', 'Rifles', etc.
    required_documents JSON,               -- {"doc_1": "DNI - Cara delantera", "doc_2": "Licencia", ...}
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Default Types

```php
[
    'corta' => [
        'label' => 'Armas Cortas',
        'documents' => [
            'dni_frontal' => 'DNI - Cara delantera',
            'dni_trasera' => 'DNI - Cara trasera',
            'licencia' => 'Licencia de armas cortas (tipo B)',
        ],
    ],
    'rifle' => [
        'label' => 'Rifles',
        'documents' => [
            'dni_frontal' => 'DNI - Cara delantera',
            'dni_trasera' => 'DNI - Cara trasera',
            'licencia' => 'Licencia de armas largas rayadas (tipo D)',
        ],
    ],
    'escopeta' => [
        'label' => 'Escopetas',
        'documents' => [
            'dni_frontal' => 'DNI - Cara delantera',
            'dni_trasera' => 'DNI - Cara trasera',
            'licencia' => 'Licencia de escopeta (tipo E)',
        ],
    ],
    'dni' => [
        'label' => 'Solo DNI',
        'documents' => [
            'dni_frontal' => 'DNI - Cara delantera',
            'dni_trasera' => 'DNI - Cara trasera',
        ],
    ],
    'general' => [
        'label' => 'General',
        'documents' => [
            'documento' => 'Pasaporte o carnet de conducir (ambas caras)',
        ],
    ],
]
```

### Integration with Email Templates

Email templates can reference document types to display required documents:

```html
<!-- In document_initial_request template -->
<h3>Documentos Requeridos para {DOCUMENT_TYPE_LABEL}</h3>
<ul>
    {REQUIRED_DOCUMENTS_LIST}
</ul>

<!-- Rendered example for 'corta' type -->
<h3>Documentos Requeridos para Armas Cortas</h3>
<ul>
    <li>DNI - Cara delantera</li>
    <li>DNI - Cara trasera</li>
    <li>Licencia de armas cortas (tipo B)</li>
</ul>
```

### Missing Documents Request

When admin requests missing documents, the email includes:

```php
// DocumentMailService::sendMissingDocumentsRequest()

$document = Document::find($documentId);
$missingDocs = $request->input('missing_documents'); // ['dni_frontal', 'licencia']

$requiredDocsMap = DocumentConfiguration::getByType($document->document_type)
    ->required_documents;

$missingDocuments = array_map(function($key) use ($requiredDocsMap) {
    return $requiredDocsMap[$key] ?? $key;
}, $missingDocs);

// Variables for template
$variables = [
    'MISSING_DOCUMENTS' => implode("\n", array_map(fn($doc) => "• $doc", $missingDocuments)),
    'REQUEST_REASON' => $request->input('reason', ''),
];
```

---

## SLA Policy Integration

### SLA Policy Table Structure

```sql
CREATE TABLE document_sla_policies (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    description TEXT,

    -- Time targets in minutes
    upload_request_time INT,      -- Max time to request documents (required)
    review_time INT NULLABLE,     -- Max time to review (optional)
    approval_time INT,            -- Max time to approve/reject (required)

    -- Business hours
    business_hours_only BOOLEAN,  -- Only count business hours?
    business_hours JSON,          -- {"Mon": "09:00-17:00", "Tue": "09:00-17:00", ...}
    timezone VARCHAR(255),        -- 'America/Mexico_City', 'Europe/Madrid', etc.

    -- Document type multipliers
    document_type_multipliers JSON, -- {"corta": 0.75, "rifle": 1.0, "dni": 0.5, ...}

    -- Escalation
    enable_escalation BOOLEAN,
    escalation_threshold_percent INT, -- 80 = escalate at 80% of SLA time
    escalation_recipients JSON,   -- Emails to notify on escalation

    -- Status
    active BOOLEAN,
    is_default BOOLEAN,           -- Only one policy can be default
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Email Impact on SLA

**SLA Timelines** are affected by:

1. **Document Type Multiplier**
   ```
   Final SLA = Base SLA × Document Type Multiplier

   Example:
   - Base approval_time: 1440 minutes (24 hours)
   - Document type 'dni': 0.5 multiplier
   - Final SLA: 1440 × 0.5 = 720 minutes (12 hours)
   ```

2. **Business Hours Calculation**
   ```
   If business_hours_only = true:
   - Only count Mon-Fri 09:00-17:00 (or configured hours)
   - Weekends and after-hours don't count

   If business_hours_only = false:
   - Count 24/7 including weekends
   ```

3. **Escalation Triggers**
   ```
   Escalation Email Sent When:
   - Time Elapsed ≥ (SLA × escalation_threshold_percent / 100)
   - Example: 1440 min × 80% = 1152 min elapsed
   - Sent to: escalation_recipients (JSON array of emails)
   ```

### SLA Breach Detection & Email

**Scheduler Job** runs hourly to check for SLA breaches:

```php
// CheckSlaBreachesJob (runs via Laravel Scheduler)

// 1. Find all AWAITING_DOCUMENTS documents past approval_time
// 2. Find all INCOMPLETE documents past upload_request_time
// 3. Find all documents approaching escalation threshold

foreach ($documents as $doc) {
    // Calculate elapsed time
    $elapsedMinutes = $doc->created_at->diffInMinutes(now());

    // Get SLA policy with multiplier
    $sla = DocumentSlaPolicy::find($doc->sla_policy_id);
    $multiplier = $sla->getMultiplierForDocumentType($doc->document_type);
    $finalSla = $sla->approval_time * $multiplier;

    // Check if breached
    if ($elapsedMinutes > $finalSla) {
        // Create breach record
        DocumentSlaBreach::create([
            'document_id' => $doc->id,
            'sla_policy_id' => $doc->sla_policy_id,
            'breach_type' => 'approval',
            'minutes_over' => $elapsedMinutes - $finalSla,
        ]);

        // Send escalation email if enabled
        if ($sla->enable_escalation && !$doc->slaBreaches()->first()?->escalated) {
            SendDocumentEscalationEmailJob::dispatch($doc, $sla);
        }
    }
}
```

### Automatic Reminder Job

**SendDocumentReminderJob** runs based on global settings:

```php
// Runs daily - checks for documents needing reminders

$reminderDays = (int)Setting::get('documents.reminder_days', 7);
$reminderEnabled = Setting::get('documents.enable_reminder', 'yes') === 'yes';

if (!$reminderEnabled) {
    return; // Skip if disabled
}

// Find documents awaiting > $reminderDays
$documents = Document::where('status_id', DocumentStatus::AWAITING_DOCUMENTS()->id)
    ->where('created_at', '<', now()->subDays($reminderDays))
    ->whereDoesntHave('statusHistories', function($q) {
        // Don't send if already sent a reminder
        $q->where('action_type', 'reminder_sent');
    })
    ->get();

foreach ($documents as $doc) {
    SendDocumentReminderEmailJob::dispatch($doc);
}
```

---

## Language-Aware Email Implementation

### Language Selection Priority

Email language is determined by:

```php
// Priority order for language selection:
1. Customer's lang_id field (if exists in customers table)
2. Document's assigned team/group lang preference (if applicable)
3. System default language (Lang::first()->id)

// Implementation in DocumentMailService:
private function getTemplateLanguageId(Document $document): int
{
    // 1. Check customer language
    if ($document->customer?->lang_id) {
        return $document->customer->lang_id;
    }

    // 2. Check document team/group preference
    if ($document->team?->lang_id) {
        return $document->team->lang_id;
    }

    // 3. Default to system default language
    return Lang::first()->id;
}
```

### Template Lookup with Fallback

```php
// Implementation in DocumentEmailFactory::getTemplate()

public function getTemplate(
    string $templateKey,
    Document $document,
    ?int $forceLangId = null
): ?EmailTemplate
{
    $langId = $forceLangId ?? $this->getTemplateLanguageId($document);

    // Try exact language match
    $template = EmailTemplate::where('key', $templateKey)
        ->where('module', 'documents')
        ->where('lang_id', $langId)
        ->where('is_enabled', true)
        ->first();

    // Fallback to default language if not found
    if (!$template) {
        $template = EmailTemplate::where('key', $templateKey)
            ->where('module', 'documents')
            ->where('lang_id', Lang::first()->id)
            ->where('is_enabled', true)
            ->first();
    }

    // Log warning if template not found
    if (!$template) {
        \Log::warning("Document email template not found: $templateKey (lang: $langId)");
    }

    return $template;
}
```

### Variable Substitution with Language Support

```php
// Variables available for all document templates

$variables = [
    'CUSTOMER_NAME' => $document->customer_name ?? 'Estimado cliente',
    'CUSTOMER_EMAIL' => $document->customer_email,
    'ORDER_REFERENCE' => $document->order_reference,
    'ORDER_ID' => $document->order_id,
    'DOCUMENT_TYPE' => $documentType->document_type_label ?? 'General',
    'DOCUMENT_TYPE_LABEL' => ucfirst(str_replace('_', ' ', $document->document_type)),
    'UPLOAD_LINK' => route('helpdesk.documents.upload', [
        'token' => $document->upload_token,
    ]),
    'EXPIRATION_DATE' => $document->expiration_date?->format('d/m/Y') ?? 'No especificado',
    'COMPANY_NAME' => config('app.name'),
    'SUPPORT_EMAIL' => config('mail.from.address'),
    'SUPPORT_PHONE' => config('documents.support_phone'),
    'REQUIRED_DOCUMENTS_LIST' => $this->buildRequiredDocumentsList($document),
];
```

### Language-Specific Date & Number Formatting

```php
// In TemplateRendererService - before variable substitution

private function formatVariablesForLanguage(
    array $variables,
    int $langId
): array
{
    $lang = Lang::find($langId);
    $locale = $lang->code ?? 'es'; // 'es', 'en', 'pt', etc.

    // Format dates based on language
    if (isset($variables['EXPIRATION_DATE'])) {
        $date = \Carbon\Carbon::createFromFormat('d/m/Y', $variables['EXPIRATION_DATE']);
        $variables['EXPIRATION_DATE'] = $date->locale($locale)->isoFormat('L'); // Language-specific format
    }

    // Format currency if needed
    if (isset($variables['ORDER_AMOUNT'])) {
        $amount = (float)$variables['ORDER_AMOUNT'];
        $variables['ORDER_AMOUNT'] = number_format($amount, 2, ',', '.'); // Spanish format: 1.234,56
    }

    return $variables;
}
```

---

## Email Trigger Points

### Complete Email Trigger Map

#### 1. Document Created (From Prestashop Order Payment)
```
Event: DocumentCreated (via API webhook or scheduled import)
Condition: enable_initial_request = yes
Email: document_initial_request
Queue: SendDocumentInitialRequestJob
Variables: CUSTOMER_NAME, UPLOAD_LINK, DOCUMENT_TYPE_LABEL, REQUIRED_DOCUMENTS_LIST
Delay: Immediate
```

#### 2. Automatic Reminder (After X Days)
```
Event: Daily scheduler job
Condition: enable_reminder = yes AND document.created_at < now - reminder_days
Email: document_reminder
Queue: SendDocumentReminderJob
Variables: CUSTOMER_NAME, UPLOAD_LINK, DAYS_REMAINING
Delay: Based on reminder_days setting
```

#### 3. Missing Documents Request (Admin Action)
```
Event: Admin clicks "Request Documents"
Condition: enable_missing_docs = yes
Email: document_missing_documents
Queue: SendMissingDocumentsEmailJob
Variables: CUSTOMER_NAME, MISSING_DOCUMENTS, REQUEST_REASON
Delay: Immediate
Status Change: Stays in AWAITING_DOCUMENTS with action logged
```

#### 4. Document Approved (Admin Action)
```
Event: Admin approves document (Status → APPROVED)
Email: document_approved
Queue: SendDocumentApprovedJob
Variables: CUSTOMER_NAME, ORDER_REFERENCE, NEXT_STEPS
Delay: Immediate
Status Change: AWAITING_DOCUMENTS → APPROVED
Creates: DocumentStatusHistory with action_type='approved'
```

#### 5. Document Rejected (Admin Action)
```
Event: Admin rejects document (Status → INCOMPLETE)
Email: document_rejected
Queue: SendDocumentRejectedJob
Variables: CUSTOMER_NAME, REJECTION_REASON, REQUIRED_DOCUMENTS_LIST
Delay: Immediate
Status Change: AWAITING_DOCUMENTS → INCOMPLETE
Creates: DocumentStatusHistory with action_type='rejected'
Resets: Upload portal to ask for new documents
```

#### 6. Document Completed (Admin Action)
```
Event: Admin marks complete (Status → COMPLETED)
Email: document_completed
Queue: SendDocumentCompletedJob
Variables: CUSTOMER_NAME, ORDER_REFERENCE, NEXT_STEPS
Delay: Immediate
Status Change: APPROVED → COMPLETED
Creates: DocumentStatusHistory with action_type='completed'
```

#### 7. SLA Escalation (Scheduler Job)
```
Event: Hourly SLA check job
Condition: enable_escalation = yes AND elapsed_time ≥ (sla × threshold%)
Email: Custom escalation email (to team)
Queue: SendDocumentEscalationEmailJob
Recipients: escalation_recipients from SLA policy
Variables: DOCUMENT_ID, OVERDUE_MINUTES, CUSTOMER_NAME, REQUIRED_ACTIONS
Delay: Immediate when threshold reached
Creates: DocumentSlaBreach record with escalated=true
```

---

## Template Variables

### Core Variables (All Templates)

| Variable | Type | Example | Used In |
|----------|------|---------|---------|
| `{CUSTOMER_NAME}` | string | Juan García | All |
| `{CUSTOMER_EMAIL}` | string | juan@example.com | All |
| `{ORDER_ID}` | integer | 12345 | All |
| `{ORDER_REFERENCE}` | string | ORD-2025-00123 | All |
| `{DOCUMENT_TYPE_LABEL}` | string | Armas Cortas | initial_request, missing_documents |
| `{UPLOAD_LINK}` | URL | https://portal.example.com/upload/abc123 | initial_request, missing_documents, rejected |
| `{EXPIRATION_DATE}` | date | 2025-12-24 | initial_request, missing_documents |
| `{COMPANY_NAME}` | string | Alsernet | All |
| `{SUPPORT_EMAIL}` | string | support@example.com | All |
| `{SUPPORT_PHONE}` | string | +34 900 123 456 | All |

### Template-Specific Variables

#### document_initial_request
```
{REQUIRED_DOCUMENTS_LIST}
  → HTML list of required documents for type

Example:
<ul>
  <li>DNI - Cara delantera</li>
  <li>DNI - Cara trasera</li>
  <li>Licencia de armas cortas</li>
</ul>

{INITIAL_REQUEST_MESSAGE}
  → Custom message from settings (if configured)

{DAYS_UNTIL_EXPIRATION}
  → Number of days until deadline
```

#### document_missing_documents
```
{MISSING_DOCUMENTS}
  → Bulleted list of missing document names

{REQUEST_REASON}
  → Admin's explanation for request

{UPLOAD_LINK}
  → Direct link to upload missing docs
```

#### document_reminder
```
{DAYS_SINCE_REQUEST}
  → How many days since initial request

{REMINDER_MESSAGE}
  → Custom message from settings (if configured)

{DAYS_UNTIL_DEADLINE}
  → Days remaining before considered overdue
```

#### document_approved
```
{ORDER_REFERENCE}
  → Prestashop order number

{NEXT_STEPS}
  → What happens next in the process
```

#### document_rejected
```
{REJECTION_REASON}
  → Why documents were rejected

{REQUIRED_DOCUMENTS_LIST}
  → Documents that need to be re-uploaded

{UPLOAD_LINK}
  → Link to upload corrected documents
```

#### document_completed
```
{ORDER_REFERENCE}
  → Prestashop order reference

{NEXT_STEPS}
  → Instructions for next phase
```

---

## Implementation Workflow

### Step 1: Create Email Templates in Mailers UI

**URL:** `/manager/settings/mailers/templates`

For each language, create 6 templates with these details:

#### Template 1: Initial Document Request

```
Key: document_initial_request
Module: documents
Language: Spanish (or your languages)

Subject:
Solicitud de Documentación - Orden {ORDER_REFERENCE}

Content:
Estimado/a {CUSTOMER_NAME},

Gracias por realizar su compra. Para poder procesar su pedido (Orden {ORDER_REFERENCE}),
necesitamos que cargue los siguientes documentos:

{REQUIRED_DOCUMENTS_LIST}

Por favor, cargue los documentos antes del {EXPIRATION_DATE} a través del siguiente enlace:
{UPLOAD_LINK}

Si tiene alguna pregunta, contáctenos en {SUPPORT_EMAIL} o {SUPPORT_PHONE}.

Saludos cordiales,
{COMPANY_NAME}
```

#### Template 2: Missing Documents

```
Key: document_missing_documents
Module: documents
Language: Spanish

Subject:
Documentación Faltante - Orden {ORDER_REFERENCE}

Content:
Estimado/a {CUSTOMER_NAME},

Revisamos su solicitud de documentos y detectamos que aún faltan los siguientes documentos:

{MISSING_DOCUMENTS}

{REQUEST_REASON}

Por favor, cargue los documentos faltantes en el siguiente enlace:
{UPLOAD_LINK}

Gracias,
{COMPANY_NAME}
```

#### Template 3: Reminder

```
Key: document_reminder
Module: documents
Language: Spanish

Subject:
Recordatorio: Documentación Pendiente - Orden {ORDER_REFERENCE}

Content:
Estimado/a {CUSTOMER_NAME},

Le recordamos que aún no ha cargado los documentos requeridos para su orden {ORDER_REFERENCE}.
Hace {DAYS_SINCE_REQUEST} días que se le solicitó la documentación.

{REMINDER_MESSAGE}

Cargue los documentos antes del {EXPIRATION_DATE}:
{UPLOAD_LINK}

Saludos,
{COMPANY_NAME}
```

#### Template 4: Approved

```
Key: document_approved
Module: documents
Language: Spanish

Subject:
Documentos Aprobados - Orden {ORDER_REFERENCE}

Content:
Estimado/a {CUSTOMER_NAME},

¡Excelente! Sus documentos han sido revisados y aprobados.

Su orden {ORDER_REFERENCE} está siendo procesada.

{NEXT_STEPS}

Gracias,
{COMPANY_NAME}
```

#### Template 5: Rejected

```
Key: document_rejected
Module: documents
Language: Spanish

Subject:
Documentos Rechazados - Acción Requerida - Orden {ORDER_REFERENCE}

Content:
Estimado/a {CUSTOMER_NAME},

Hemos revisado los documentos enviados para su orden {ORDER_REFERENCE} y necesitamos que
realice algunas correcciones.

Razón del rechazo:
{REJECTION_REASON}

Por favor, cargue nuevamente los siguientes documentos:
{REQUIRED_DOCUMENTS_LIST}

Enlace de carga:
{UPLOAD_LINK}

Gracias,
{COMPANY_NAME}
```

#### Template 6: Completed

```
Key: document_completed
Module: documents
Language: Spanish

Subject:
Documentación Completa - Orden {ORDER_REFERENCE}

Content:
Estimado/a {CUSTOMER_NAME},

Nos complace informarle que su solicitud de documentación ha sido completada exitosamente.

Su orden {ORDER_REFERENCE} está lista para ser entregada.

{NEXT_STEPS}

Saludos cordiales,
{COMPANY_NAME}
```

### Step 2: Create Email Layout Components

**URL:** `/manager/settings/mailers/components`

Create reusable layout components (if not already created):

#### Component: email_template_header
```
Alias: email_template_header
Type: partial

Content:
<div style="background-color: #f5f5f5; padding: 20px; text-align: center;">
    <img src="{COMPANY_LOGO}" alt="{COMPANY_NAME}" style="max-height: 50px;">
    <h1>{COMPANY_NAME}</h1>
</div>
```

#### Component: email_template_footer
```
Alias: email_template_footer
Type: partial

Content:
<div style="background-color: #f5f5f5; padding: 20px; margin-top: 40px; border-top: 1px solid #ddd;">
    <p>Copyright © {YEAR} {COMPANY_NAME}</p>
    <p>
        <a href="{TERMS_URL}">Términos</a> |
        <a href="{PRIVACY_URL}">Privacidad</a> |
        <a href="{CONTACT_URL}">Contacto</a>
    </p>
</div>
```

#### Component: email_template_wrapper
```
Alias: email_template_wrapper
Type: layout

Content:
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{SUBJECT}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        a { color: #007bff; }
    </style>
</head>
<body style="margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 0 auto; background: white;">
        @include('header')
        <div style="padding: 20px; background: white;">
            {CONTENT}
        </div>
        @include('footer')
    </div>
</body>
</html>
```

### Step 3: Verify Customer Language Field

Check if `customers` table has `lang_id` field:

```bash
php artisan tinker
>>> $customer = \App\Models\Customer::first();
>>> $customer->lang_id; // Should return language ID
```

If not present, create migration:

```bash
php artisan make:migration add_lang_id_to_customers_table
```

```php
public function up(): void
{
    Schema::table('customers', function (Blueprint $table) {
        $table->unsignedBigInteger('lang_id')->nullable()->after('email');
        $table->foreign('lang_id')->references('id')->on('langs')->onDelete('set null');
    });
}
```

### Step 4: Create Document Email Service

Create `app/Services/Documents/DocumentEmailService.php`:

```php
<?php

namespace App\Services\Documents;

use App\Factories\DocumentEmailFactory;use App\Jobs\Documents\SendDocumentEmailJob;use App\Models\Document\Document;use App\Models\Document\DocumentConfiguration;use Illuminate\Support\Facades\Setting;

class DocumentEmailService
{
    public function __construct(
        private DocumentEmailFactory $emailFactory,
    ) {}

    /**
     * Send initial document request email
     */
    public function sendInitialRequest(Document $document): void
    {
        if (Setting::get('documents.enable_initial_request') !== 'yes') {
            return;
        }

        $variables = $this->buildVariables($document);
        $variables['INITIAL_REQUEST_MESSAGE'] = Setting::get('documents.initial_request_message', '');

        SendDocumentEmailJob::dispatch(
            $document,
            'document_initial_request',
            $variables
        );
    }

    /**
     * Send reminder email for pending documents
     */
    public function sendReminder(Document $document): void
    {
        if (Setting::get('documents.enable_reminder') !== 'yes') {
            return;
        }

        $variables = $this->buildVariables($document);
        $variables['DAYS_SINCE_REQUEST'] = $document->created_at->diffInDays();
        $variables['REMINDER_MESSAGE'] = Setting::get('documents.reminder_message', '');

        SendDocumentEmailJob::dispatch(
            $document,
            'document_reminder',
            $variables
        );
    }

    /**
     * Send request for missing documents
     */
    public function sendMissingDocumentsRequest(Document $document, array $missingDocs, string $reason = ''): void
    {
        if (Setting::get('documents.enable_missing_docs') !== 'yes') {
            return;
        }

        $variables = $this->buildVariables($document);
        $variables['MISSING_DOCUMENTS'] = $this->buildMissingDocumentsList($document, $missingDocs);
        $variables['REQUEST_REASON'] = $reason ?: Setting::get('documents.missing_docs_message', '');

        SendDocumentEmailJob::dispatch(
            $document,
            'document_missing_documents',
            $variables
        );
    }

    /**
     * Send approval email
     */
    public function sendApprovalEmail(Document $document): void
    {
        $variables = $this->buildVariables($document);
        $variables['NEXT_STEPS'] = 'Su documentación está completa y su orden será procesada en breve.';

        SendDocumentEmailJob::dispatch(
            $document,
            'document_approved',
            $variables
        );
    }

    /**
     * Send rejection email
     */
    public function sendRejectionEmail(Document $document, string $reason): void
    {
        $variables = $this->buildVariables($document);
        $variables['REJECTION_REASON'] = $reason;
        $variables['REQUIRED_DOCUMENTS_LIST'] = $this->buildRequiredDocumentsList($document);

        SendDocumentEmailJob::dispatch(
            $document,
            'document_rejected',
            $variables
        );
    }

    /**
     * Send completion email
     */
    public function sendCompletionEmail(Document $document): void
    {
        $variables = $this->buildVariables($document);
        $variables['NEXT_STEPS'] = 'Su pedido está listo para ser entregado.';

        SendDocumentEmailJob::dispatch(
            $document,
            'document_completed',
            $variables
        );
    }

    /**
     * Build base variables for all emails
     */
    private function buildVariables(Document $document): array
    {
        return [
            'CUSTOMER_NAME' => $document->customer_name ?? 'Estimado cliente',
            'CUSTOMER_EMAIL' => $document->customer_email,
            'ORDER_ID' => $document->order_id,
            'ORDER_REFERENCE' => $document->order_reference,
            'DOCUMENT_TYPE_LABEL' => ucfirst(str_replace('_', ' ', $document->document_type)),
            'UPLOAD_LINK' => route('helpdesk.documents.upload', ['token' => $document->upload_token]),
            'EXPIRATION_DATE' => $document->expiration_date?->format('d/m/Y'),
            'COMPANY_NAME' => config('app.name'),
            'SUPPORT_EMAIL' => config('mail.from.address'),
            'SUPPORT_PHONE' => config('documents.support_phone', ''),
            'REQUIRED_DOCUMENTS_LIST' => $this->buildRequiredDocumentsList($document),
        ];
    }

    /**
     * Build HTML list of required documents
     */
    private function buildRequiredDocumentsList(Document $document): string
    {
        $config = DocumentConfiguration::getByType($document->document_type);
        if (!$config) {
            return '';
        }

        $items = array_map(
            fn($doc) => "<li>$doc</li>",
            $config->required_documents ?? []
        );

        return '<ul>' . implode('', $items) . '</ul>';
    }

    /**
     * Build HTML list of missing documents
     */
    private function buildMissingDocumentsList(Document $document, array $missingKeys): string
    {
        $config = DocumentConfiguration::getByType($document->document_type);
        if (!$config) {
            return '';
        }

        $items = array_map(
            fn($key) => "<li>{$config->required_documents[$key]}</li>",
            array_filter($missingKeys, fn($key) => isset($config->required_documents[$key]))
        );

        return '<ul>' . implode('', $items) . '</ul>';
    }
}
```

### Step 5: Create Email Sending Job

Create `app/Jobs/Document/SendDocumentEmailJob.php`:

```php
<?php

namespace App\Jobs\Document;

use App\Models\Document\Document;use App\Services\Mails\MailTemplateRendererService;use Illuminate\Bus\Queueable;use Illuminate\Contracts\Queue\ShouldQueue;use Illuminate\Foundation\Bus\Dispatchable;use Illuminate\Queue\InteractsWithQueue;use Illuminate\Queue\SerializesModels;use Illuminate\Support\Facades\Mail;

class SendDocumentEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Document $document,
        private string $templateKey,
        private array $variables,
    ) {
        $this->onQueue('emails');
    }

    public function handle(MailTemplateRendererService $renderer): void
    {
        // Get template with language support
        $template = $this->getTemplate();

        if (!$template) {
            \Log::warning(
                "Email template not found: {$this->templateKey}",
                ['document_id' => $this->document->id]
            );
            return;
        }

        // Render template with variables and layout
        $html = $renderer->renderEmailTemplate(
            $template,
            $this->variables
        );

        // Send email
        Mail::html($html, function ($message) use ($template) {
            $message
                ->to($this->document->customer_email)
                ->subject($template->subject);
        });

        // Log action
        \App\Models\Document\DocumentAction::create([
            'document_id' => $this->document->id,
            'action_type' => "email_sent_{$this->templateKey}",
            'description' => "Email enviado: {$template->subject}",
            'metadata' => [
                'template_key' => $this->templateKey,
                'recipient' => $this->document->customer_email,
            ],
        ]);
    }

    private function getTemplate()
    {
        $langId = $this->document->customer?->lang_id
            ?? \App\Models\Language\Lang::first()->id;

        // Try exact language match
        $template = \App\Models\Mail\MailTemplate::where('key', $this->templateKey)
            ->where('module', 'documents')
            ->where('lang_id', $langId)
            ->where('is_enabled', true)
            ->first();

        // Fallback to default language
        if (!$template) {
            $template = \App\Models\Mail\MailTemplate::where('key', $this->templateKey)
                ->where('module', 'documents')
                ->where('lang_id', \App\Models\Language\Lang::first()->id)
                ->where('is_enabled', true)
                ->first();
        }

        return $template;
    }
}
```

---

## Testing & Troubleshooting

### Test Email Templates in Mailers UI

1. **Preview Email:** Most mailer systems have a "Preview" button showing rendered HTML
2. **Test Send:** Send test email to admin account
3. **Check Variables:** Verify {VARIABLES} are replaced correctly

### Debug Template Rendering

```bash
# In tinker, test variable substitution
php artisan tinker

$document = Document::find(1);
$service = app(TemplateRendererService::class);

$variables = [
    'CUSTOMER_NAME' => 'Juan García',
    'UPLOAD_LINK' => 'https://example.com/upload/abc123',
    'EXPIRATION_DATE' => '2025-12-24',
];

$template = \App\Models\Email\EmailTemplate::where('key', 'document_initial_request')->first();
$html = $service->renderEmailTemplate($template, $variables);

echo $html; // See rendered output
```

### Check Job Queue

```bash
# Monitor queued jobs
php artisan queue:monitor

# Process jobs manually (for testing)
php artisan queue:work --queue=emails --once
```

### Verify Language Support

```bash
# Check if customer has lang_id
php artisan tinker

>>> Customer::first()->lang_id;

# Check available languages
>>> Lang::all();

# Check email templates per language
>>> EmailTemplate::where('key', 'document_initial_request')->get();
```

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| Email not sent | Job not processed | Check `php artisan queue:work` is running |
| Variables not replaced | Template key wrong | Verify key spelling matches exactly |
| No template found | Language mismatch | Check customer.lang_id and fallback logic |
| Wrong email layout | layout_id missing | Add layout_id to email_templates record |
| Email not delivered | Mail config | Check `.env` MAIL_* settings |

---

## Integration Checklist

- [ ] Email templates created for all 6 template keys
- [ ] Email layout components created (header, footer, wrapper)
- [ ] Customer table has `lang_id` field
- [ ] DocumentEmailService created and registered in container
- [ ] SendDocumentEmailJob created
- [ ] Global settings configured in `/manager/settings/documents/configurations`
- [ ] Document types and required documents configured
- [ ] SLA policies created with escalation recipients
- [ ] Queue worker running (`php artisan queue:work`)
- [ ] Test email sent and received successfully
- [ ] Language fallback tested for missing language template
- [ ] Variable substitution verified in test emails
- [ ] Status transition triggers integrated with email sending

---

## Architecture Summary

```
User/Admin Action
    ↓
Event Triggered (DocumentCreated, StatusChanged, etc.)
    ↓
DocumentEmailService method called
    ↓
Check global settings (enable_initial_request, enable_reminder, etc.)
    ↓
SendDocumentEmailJob dispatched to queue
    ↓
Job processed by queue worker
    ↓
Determine customer language (lang_id)
    ↓
Fetch EmailTemplate (with language fallback)
    ↓
Build variables (CUSTOMER_NAME, UPLOAD_LINK, etc.)
    ↓
TemplateRendererService renders template
    ↓
Apply layout (header/footer/wrapper)
    ↓
Mail::html() sends email
    ↓
DocumentAction logged for audit trail
```

This architecture ensures:
✅ **Language Support** - Emails respect customer language
✅ **Configurability** - Settings control email behavior
✅ **Flexibility** - Document types determine required docs shown
✅ **Scalability** - Queue system handles high volume
✅ **Auditability** - Every email logged in DocumentAction
✅ **Maintainability** - Centralized template management via mailers UI
