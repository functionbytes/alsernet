# Triple Logging System for Status Changes

## 🎯 Complete Audit Trail

When a document status changes, **THREE tables are updated**:

### 1️⃣ **documents**
```
status_id → [new status]
updated_at → [timestamp]
```

### 2️⃣ **document_status_histories**
```
- from_status_id: Previous status
- to_status_id: New status
- changed_by: User ID who made change
- reason: Why it changed
- metadata: JSON context
- created_at: When
```

### 3️⃣ **document_status_transition_logs**
```
- transition_id: Which rule was used
- from_status_id: Previous status
- to_status_id: New status
- performed_by: User ID
- reason: Why
- metadata: Context with transition details
```

### 4️⃣ **document_actions** ⭐ NEW
```
- action_type: 'status_changed'
- action_name: "Estado cambió: Pendiente → Aprobado"
- description: Full description of change
- performed_by: User ID
- performed_by_type: 'user' or 'system'
- metadata: Complete change details
```

---

## Complete Example

When admin changes document status from **"Solicitado"** → **"Aprobado"**:

### documents
```sql
UPDATE documents SET status_id = 5, updated_at = '2025-12-17 11:45:00'
WHERE id = 123;
```

### document_status_histories
```php
[
    'document_id' => 123,
    'from_status_id' => 1,
    'to_status_id' => 5,
    'changed_by' => 45,
    'reason' => 'Manual status change via admin panel',
    'metadata' => [
        'document_uid' => '082dda70-435b-4776-adce-049ded1e3485',
        'from_status_key' => 'pending',
        'to_status_key' => 'approved',
    ],
    'created_at' => '2025-12-17 11:45:00',
]
```

### document_status_transition_logs
```php
[
    'document_id' => 123,
    'transition_id' => 8,           // Which rule was used
    'from_status_id' => 1,
    'to_status_id' => 5,
    'performed_by' => 45,
    'reason' => 'Manual status change via admin panel',
    'metadata' => [
        'document_uid' => '082dda70-435b-4776-adce-049ded1e3485',
        'transition_name' => 'pending → approved',
    ],
    'created_at' => '2025-12-17 11:45:00',
]
```

### document_actions ⭐ NEW
```php
[
    'document_id' => 123,
    'action_type' => 'status_changed',
    'action_name' => 'Estado cambió: Solicitado → Aprobado',
    'description' => 'Estado cambiado de Solicitado a Aprobado. Razón: Manual status change via admin panel',
    'performed_by' => 45,
    'performed_by_type' => 'user',
    'metadata' => [
        'from_status_key' => 'pending',
        'to_status_key' => 'approved',
        'from_status_id' => 1,
        'to_status_id' => 5,
        'transition_id' => 8,
        'reason' => 'Manual status change via admin panel',
    ],
    'created_at' => '2025-12-17 11:45:00',
]
```

---

## Query Examples

### Get all actions for a document
```php
$actions = DocumentAction::where('document_id', 123)
    ->where('action_type', 'status_changed')
    ->latest()
    ->get();
```

### Get complete change history
```php
$history = DocumentStatusHistory::where('document_id', 123)
    ->with(['fromStatus', 'toStatus', 'changedBy'])
    ->latest()
    ->get();
```

### Get which transitions were used
```php
$transitions = DocumentStatusTransitionLog::where('document_id', 123)
    ->with(['transition', 'performedBy'])
    ->latest()
    ->get();
```

### Get all status changes performed by user
```php
$userChanges = DocumentAction::where('performed_by', $userId)
    ->where('action_type', 'status_changed')
    ->latest()
    ->get();
```

### Timeline of all document activities
```php
$timeline = DocumentAction::where('document_id', 123)
    ->orderBy('created_at', 'desc')
    ->get();
```

---

## Record Structure

### document_actions record for status change

```
{
    "id": 101,
    "document_id": 123,
    "action_type": "status_changed",
    "action_name": "Estado cambió: Solicitado → Aprobado",
    "description": "Estado cambiado de Solicitado a Aprobado. Razón: Manual status change via admin panel",
    "metadata": {
        "from_status_key": "pending",
        "to_status_key": "approved",
        "from_status_id": 1,
        "to_status_id": 5,
        "transition_id": 8,
        "reason": "Manual status change via admin panel"
    },
    "performed_by": 45,
    "performed_by_type": "user",
    "created_at": "2025-12-17T11:45:00.000000Z",
    "updated_at": "2025-12-17T11:45:00.000000Z"
}
```

---

## Flow When Status Changes

```
Admin Changes Status
    ↓
Validate transition is allowed
    ↓
Update documents.status_id
    ↓
Fire DocumentStatusChanged event
    ↓
LogDocumentStatusChange Listener:
    ├─ Create document_status_histories record
    ├─ Create document_status_transition_logs record
    └─ Create document_actions record ⭐
    ↓
Complete audit trail across 4 locations
```

---

## What Gets Recorded

| Location | Recorded | Purpose |
|----------|----------|---------|
| **documents** | Current status | Live state |
| **document_status_histories** | What changed + who + why | Change history |
| **document_status_transition_logs** | Which rule used | Transition audit |
| **document_actions** | Action summary + full metadata | Complete action log |

---

## Why Triple Logging?

1. **documents** - Live state (current status)
2. **document_status_histories** - Change history (what changed, who, why, when)
3. **document_status_transition_logs** - Transition audit (which transition rule used)
4. **document_actions** - Action summary (human-readable log for all document activities)

Together they provide:
- ✅ Current state
- ✅ Complete history
- ✅ Transition rule usage
- ✅ Human-readable audit trail
- ✅ Complete compliance record

---

## Metadata Preservation

All metadata is preserved in JSON format for debugging and analysis:

```php
'metadata' => [
    'from_status_key' => 'pending',
    'to_status_key' => 'approved',
    'from_status_id' => 1,
    'to_status_id' => 5,
    'transition_id' => 8,
    'reason' => 'Manual status change via admin panel',
]
```

This allows:
- Filtering by status key instead of ID
- Linking back to transition rule
- Understanding context
- Debugging issues

---

## Implementation

**File Modified**: `app/Listeners/Documents/LogDocumentStatusChange.php`

The listener now creates all 4 records when a status change occurs.

**Code Quality**: ✅ Pint formatted (25 files)

---

## Summary

✅ **3 tables are now updated** when status changes
✅ **Complete audit trail** maintained
✅ **Human-readable logs** in document_actions
✅ **Compliance ready** with full tracking
✅ **Queryable** from multiple angles
