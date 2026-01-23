# Document Status & Email Notification Flow Analysis

## Current Problem

The document status system has not been properly aligned with the email notification lifecycle. The statuses need to represent distinct stages in the document request process, with clear email communication at each stage.

## Current Email Notification Types

1. **Initial Request Email** - "Documentación Solicitada"
   - Triggered: `DocumentCreated` event (when document request is created)
   - Recipient: Client/Customer
   - Purpose: Request documents from customer
   - Next expected: Reminder after `reminder_days`

2. **Reminder Email** - "Recordatorio de Documentación"
   - Triggered: After `reminder_days` if `uploaded_documents` is empty
   - Recipient: Client/Customer
   - Purpose: Remind customer to upload documents
   - Recurrence: Can be sent multiple times (every 7 days)
   - Condition: Only if documents not yet uploaded

3. **Upload Confirmation Email** - "Confirmación de Recepción"
   - Triggered: `DocumentUploaded` event (when customer uploads files)
   - Recipient: Client/Customer
   - Purpose: Confirm documents were received
   - Note: `uploaded_confirmation_sent_at` tracks this

4. **Approval Email** - "Aprobación de Documentos"
   - Triggered: When status changes to "Approved"
   - Recipient: Client/Customer
   - Purpose: Notify that documents have been approved
   - Note: Should include approval details

5. **Rejection Email** - "Rechazo de Documentos"
   - Triggered: When status changes to "Rejected"
   - Recipient: Client/Customer
   - Purpose: Notify of rejection with reason to resubmit
   - Note: Should include rejection reason

---

## Current Document Statuses (From Seeder)

| Status | Label | Description | Order |
|--------|-------|-------------|-------|
| pending | Pendiente | Documento recién creado, en espera de procesamiento | 1 |
| incomplete | Incompleto | Faltan documentos requeridos | 2 |
| awaiting_documents | Esperando Documentos | En espera de que el cliente envíe documentos | 3 |
| approved | Aprobado | Documentos verificados y aprobados | 4 |
| completed | Completado | Documento procesado completamente | 5 |
| rejected | Rechazado | Documento rechazado por validación fallida | 6 |
| cancelled | Cancelado | Solicitud de documento cancelada | 7 |

---

## The Core Problem

The current status system doesn't properly represent the document lifecycle according to email communication:

### Issue 1: Confusing Initial Statuses
- **"Pending"** vs **"Awaiting Documents"** - What's the difference?
  - Currently: Both seem to mean "waiting for documents"
  - Should: One is the initial state (just created), one is after reminder

### Issue 2: Missing "Under Review" State
- When documents are uploaded, the status should change to indicate "admin is reviewing"
- Currently: No clear state for "documents received, awaiting review"
- Implications: Admin can't distinguish between "never uploaded" and "uploaded, reviewing"

### Issue 3: Incomplete Status Misuse
- **"Incomplete"** suggests "missing some documents"
- But it's also used when documents are "under review"
- Distinction needed: Is it incomplete due to missing docs OR pending admin review?

### Issue 4: No Status for Resubmission
- After rejection, customer needs to resubmit
- Currently: Document goes to "Rejected" - but what's next?
- Should: Have ability to transition from Rejected → Awaiting Documents again

### Issue 5: Completion Status Removed
- User removed "Completion" email notification
- But "Completed" status still exists
- What does "Completed" mean now without a completion email?

---

## Proposed Document Status Lifecycle

### Recommended Status Structure (Aligned with Emails)

```
┌─────────────────────────────────────────────────────────────┐
│                  DOCUMENT REQUEST LIFECYCLE                  │
└─────────────────────────────────────────────────────────────┘

1. PENDING (Pendiente)
   └─ Initial state when document request is created
   └─ Email sent: Initial Request ("Documentación Solicitada")
   └─ Next action: Wait for client upload OR send reminder
   └─ Can transition to: → Awaiting Documents

2. AWAITING_DOCUMENTS (Esperando Documentos)
   └─ Client has NOT uploaded documents yet
   └─ Email sent: Reminders periodically (every 7 days)
   └─ Trigger: After reminder_days or when reminded
   └─ Next action: Client uploads or request expires
   └─ Can transition to: → Received (docs uploaded)

3. RECEIVED (Documentos Recibidos)
   └─ Client has uploaded documents
   └─ Email sent: Upload Confirmation ("Confirmación de Recepción")
   └─ Status: Waiting for admin review
   └─ Next action: Admin reviews documents
   └─ Can transition to: → Approved, Rejected, Incomplete

4. INCOMPLETE (Incompleto)
   └─ Documents uploaded BUT some required docs are missing
   └─ Email sent: Could notify client of missing docs (optional)
   └─ Status: Admin reviewed, found missing documents
   └─ Next action: Admin sends message to client to upload missing docs
   └─ Can transition to: → Received (client uploads missing) → Approved

5. APPROVED (Aprobado)
   └─ All documents received and verified
   └─ Email sent: Approval ("Aprobación de Documentos")
   └─ Status: Documents approved, process complete
   └─ Next action: None required
   └─ Can transition to: → (end state)

6. REJECTED (Rechazado)
   └─ Documents rejected by admin (failed validation/requirements)
   └─ Email sent: Rejection with reason ("Rechazo de Documentos")
   └─ Status: Awaiting resubmission from client
   └─ Next action: Client resubmits (or request cancelled)
   └─ Can transition to: → Awaiting Documents (for resubmission)

7. CANCELLED (Cancelado)
   └─ Request cancelled by admin or system
   └─ Email sent: None (or optional cancellation notification)
   └─ Status: Final (no further action)
   └─ Can transition to: → (end state)
```

---

## Proposed Status Redesign

### Step 1: Keep These Statuses (Updated Definitions)

| Status | Old Label | New Label | Purpose | Email Trigger |
|--------|-----------|-----------|---------|---|
| `pending` | Pendiente | Solicitado | Document just created | Initial Request |
| `awaiting_documents` | Esperando Documentos | Esperando Documentos | Waiting for client upload + reminders | Reminders (periodic) |
| `received` | *(NEW)* | Documentos Recibidos | Docs uploaded, pending admin review | Upload Confirmation |
| `incomplete` | Incompleto | Incompleto | Missing some required documents | *(Optional: Missing docs notification)* |
| `approved` | Aprobado | Aprobado | Approved, process complete | Approval |
| `rejected` | Rechazado | Rechazado | Rejected, awaiting resubmission | Rejection |
| `cancelled` | Cancelado | Cancelado | Request cancelled | *(None)* |

### Step 2: Automatic Status Transitions

Based on events and document state:

```php
// When document is created
DocumentCreated event
  → Set status to "Pending"
  → Send Initial Request email
  → Schedule Reminder job with reminder_days delay

// When reminder executes (after reminder_days)
SendDocumentReminderJob (individual mode)
  → If uploaded_documents.empty() → Keep "Awaiting Document"
  → Send Reminder email
  → Schedule next reminder (7 days later)

// When client uploads documents
DocumentUploaded event
  → Set status to "Received"
  → Send Upload Confirmation email
  → Alert admin for review

// When admin reviews documents
Manual status update to:
  → "Incomplete" (if comparing required_documents vs uploaded_documents shows gap)
  → "Approved" (if all requirements met)
  → "Rejected" (if doesn't meet requirements)

// When status changes to "Approved"
DocumentStatusChanged event (to Approved)
  → Send Approval email

// When status changes to "Rejected"
DocumentStatusChanged event (to Rejected)
  → Send Rejection email with reason

// When status changes to "Cancelled"
DocumentStatusChanged event (to Cancelled)
  → No email (or optional cancellation notification)
```

---

## Implementation Changes Required

### 1. Database Migration
- Add new status: `received` (Documentos Recibidos)
- Update status definitions with email trigger mapping

### 2. Listeners Update
- `SendDocumentUploadNotification`: Auto-set status to "Pending" on creation
- `SendDocumentUploadConfirmation`: Auto-set status to "Received" on upload
- Create listener for status transitions → email notifications

### 3. API/Controller Updates
- When creating document → Set status to "Pending" automatically
- When uploading documents → Set status to "Received" automatically
- When changing status manually → Validate allowed transitions

### 4. Job Updates
- `SendDocumentReminderJob`: Only runs if status is "Awaiting Documents"
- Ensure it doesn't send if documents already uploaded

---

## Status Comparison: Before vs After

### BEFORE (Current - Confusing)
```
Document Created
  ↓
[Pending] (what does this mean?)
  ↓
[Awaiting Documents] (but isn't "pending" the same?)
  ↓
[Client uploads documents]
  ↓
[??? Status unclear - is it "incomplete" or "approved" or "received"?]
  ↓
[Admin reviews and sets to "Approved" or "Rejected"]
```

### AFTER (Proposed - Clear)
```
Document Created
  ↓
[Solicitado] ← Initial Request email sent
  ↓
[Esperando Documentos] ← Reminder emails sent periodically
  ↓
[Client uploads documents]
  ↓
[Documentos Recibidos] ← Upload Confirmation email sent
  ↓
[Admin reviews - 3 paths:]
  ├─ [Aprobado] ← Approval email sent
  ├─ [Rechazado] ← Rejection email sent
  └─ [Incompleto] ← Awaiting missing documents
```

---

## Questions to Answer

1. **Should "Received" status transition automatically?**
   - Yes: When documents are uploaded, auto-set to "Received"
   - This removes ambiguity

2. **Should "Incomplete" trigger a notification?**
   - Option A: Just update status, admin manually contacts client
   - Option B: Send "Documentos Incompletos" email listing missing docs

3. **After rejection, can client resubmit?**
   - Yes: Status transitions from "Rejected" → "Awaiting Documents"
   - This allows document request to be restarted

4. **Should we keep "Completed" status?**
   - Current: Yes, but represents "all approvals done"
   - Alternative: Remove entirely, "Approved" is final state
   - Depends on business process

5. **What about SLA policies?**
   - Each status should have SLA time limit
   - Alerts if document stuck in status too long
