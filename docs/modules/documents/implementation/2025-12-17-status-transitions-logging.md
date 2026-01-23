# Status Transitions Logging System

## Complete Status Change Recording

When a document status changes, **3 things are now recorded**:

### 1. **Status Change in documents table**
- `documents.status_id` → updated to new status

### 2. **History in document_status_histories**
- Records WHAT changed (from_status → to_status)
- Records WHO made the change (changed_by)
- Records WHEN it happened (created_at)
- Records WHY (reason)

### 3. **Transition Log in document_status_transition_logs** ⭐ NEW
- Records WHICH TRANSITION RULE was used
- Links to `document_status_transitions` table (the permitted transitions)
- Tracks which transition definition was applied
- Audits transition rule usage

---

## Database Tables

### document_status_histories
```
id              - Primary key
document_id     - Which document
from_status_id  - Previous status
to_status_id    - New status
changed_by      - User ID (nullable for system)
reason          - Why it changed
metadata        - JSON with additional context
created_at      - When it happened
```

### document_status_transition_logs ⭐ NEW
```
id              - Primary key
document_id     - Which document
transition_id   - Reference to document_status_transitions (the rule used)
from_status_id  - Previous status
to_status_id    - New status
performed_by    - User who performed it
reason          - Reason for transition
metadata        - Additional data (JSON)
created_at      - When it occurred
```

### document_status_transitions (existing)
```
id              - Primary key
from_status_id  - Allowed FROM status
to_status_id    - Allowed TO status
permission      - Required permission (nullable)
is_active       - Is transition allowed?
... other fields
```

---

## Flow When Status Changes

```
Admin changes status in form
    ↓
Controller validates transition exists in document_status_transitions
    ↓
If NOT allowed → Return error "Transición de estado no permitida"
    ↓
If allowed → Save new status_id to documents
    ↓
Fire DocumentStatusChanged event
    ↓
LogDocumentStatusChange listener receives event
    ↓
Create TWO records:
    ├─ document_status_histories (main history)
    └─ document_status_transition_logs (which transition rule was used)
    ↓
Log to application.log
    ↓
Other listeners may send emails, etc.
```

---

## Controller Validation

**File**: `app/Http/Controllers/Administratives/Documents/DocumentsController.php`

```php
// Check if transition is allowed
$transition = DocumentStatusTransition::where('from_status_id', $oldStatusId)
    ->where('to_status_id', $newStatusId)
    ->active()
    ->first();

if (!$transition) {
    return response()->json([
        'success' => false,
        'message' => 'Transición de estado no permitida'
    ], 400);
}

// Check permissions
if (!$transition->canTransition(auth()->id())) {
    return response()->json([
        'success' => false,
        'message' => 'No tienes permisos para realizar esta transición'
    ], 403);
}
```

---

## Listener Implementation

**File**: `app/Listeners/Documents/LogDocumentStatusChange.php`

The listener now:
1. Creates entry in `document_status_histories` (main history)
2. Finds the transition rule that was used
3. Creates entry in `document_status_transition_logs` (which transition rule was applied)
4. Logs everything to application log with transition_id

---

## Models

### DocumentStatusTransitionLog (New)

```php
namespace App\Models\Document;

class DocumentStatusTransitionLog extends Model
{
    public function document() { /* BelongsTo */ }
    public function transition() { /* BelongsTo DocumentStatusTransition */ }
    public function fromStatus() { /* BelongsTo DocumentStatus */ }
    public function toStatus() { /* BelongsTo DocumentStatus */ }
    public function performedBy() { /* BelongsTo User */ }

    // Scopes
    public function scopeForDocument($query, int $documentId) { }
    public function scopeRecent($query) { }
}
```

---

## Example Data

### When Document Status Changes from "Solicitado" → "Documentos Recibidos"

#### In document_status_histories:
```php
[
    'document_id' => 123,
    'from_status_id' => 1,        // Solicitado
    'to_status_id' => 3,          // Documentos Recibidos
    'changed_by' => 45,           // Admin user ID
    'reason' => 'Manual status change via admin panel',
    'metadata' => [
        'document_uid' => '082dda70-435b-4776-adce-049ded1e3485',
        'from_status_key' => 'pending',
        'to_status_key' => 'received',
    ],
    'created_at' => '2025-12-17 10:30:45',
]
```

#### In document_status_transition_logs:
```php
[
    'document_id' => 123,
    'transition_id' => 7,          // The specific transition rule used
    'from_status_id' => 1,         // Solicitado
    'to_status_id' => 3,           // Documentos Recibidos
    'performed_by' => 45,          // Admin user
    'reason' => 'Manual status change via admin panel',
    'metadata' => [
        'document_uid' => '082dda70-435b-4776-adce-049ded1e3485',
        'transition_name' => 'pending → received',
    ],
    'created_at' => '2025-12-17 10:30:45',
]
```

---

## Query Examples

### Get all status changes for a document:
```php
$history = DocumentStatusHistory::where('document_id', $documentId)
    ->with(['fromStatus', 'toStatus', 'changedBy'])
    ->latest()
    ->get();
```

### Get all transitions used for a document:
```php
$transitions = DocumentStatusTransitionLog::where('document_id', $documentId)
    ->with(['transition', 'fromStatus', 'toStatus', 'performedBy'])
    ->latest()
    ->get();
```

### Get which transitions are most used:
```php
$usedTransitions = DocumentStatusTransitionLog::groupBy('transition_id')
    ->select('transition_id', DB::raw('count(*) as count'))
    ->with('transition')
    ->orderByDesc('count')
    ->get();
```

### Find documents using a specific transition:
```php
$documentsUsingTransition = DocumentStatusTransitionLog::where('transition_id', 7)
    ->with('document')
    ->get();
```

---

## Validation Errors

### If transition not allowed:
```json
{
    "success": false,
    "message": "Transición de estado no permitida",
    "code": 400
}
```

### If user lacks permissions:
```json
{
    "success": false,
    "message": "No tienes permisos para realizar esta transición",
    "code": 403
}
```

---

## Files Created/Modified

### Created:
- `database/migrations/2025_12_17_create_document_status_transition_logs.php`
- `app/Models/Document/DocumentStatusTransitionLog.php`

### Modified:
- `app/Http/Controllers/Administratives/Documents/DocumentsController.php` (validation)
- `app/Listeners/Documents/LogDocumentStatusChange.php` (dual logging)

---

## Benefits

✅ **Complete Audit Trail**: Know exactly which transition rule was used
✅ **Compliance**: Track all status changes with full context
✅ **Validation**: Only allowed transitions can be performed
✅ **Permissions**: Enforce role-based transition permissions
✅ **Analytics**: Analyze which transitions are most common
✅ **Debugging**: Trace the exact transition path documents took

---

## Summary

Now when a document status changes:
- ✅ `documents.status_id` is updated
- ✅ `document_status_histories` records the change
- ✅ `document_status_transition_logs` records which transition rule was used
- ✅ Validation ensures only allowed transitions occur
- ✅ Permissions are checked
- ✅ Full audit trail is maintained
