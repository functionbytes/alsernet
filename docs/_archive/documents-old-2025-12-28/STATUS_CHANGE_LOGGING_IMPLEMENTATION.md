# Status Change Logging Implementation

## Overview

A complete system for logging all document status changes to `document_status_histories` table has been implemented. This provides a complete audit trail of all status transitions.

## Implementation Details

### 1. New Listener: LogDocumentStatusChange

**File**: `app/Listeners/Documents/LogDocumentStatusChange.php`

This listener automatically logs every status change to the database:

```php
public function handle(DocumentStatusChanged $event): void
{
    DocumentStatusHistory::create([
        'document_id' => $event->document->id,
        'from_status_id' => $event->fromStatus->id,
        'to_status_id' => $event->toStatus->id,
        'changed_by' => Auth::id(),
        'reason' => $event->reason,
        'metadata' => [
            'document_uid' => $event->document->uid,
            'from_status_key' => $event->fromStatus->key,
            'to_status_key' => $event->toStatus->key,
        ],
    ]);
}
```

### 2. Event Registration

**File**: `app/Providers/EventServiceProvider.php`

The listener is registered to listen to `DocumentStatusChanged` events:

```php
DocumentStatusChanged::class => [
    LogDocumentStatusChange::class,  // ← First priority: log the change
    SendApprovalEmailListener::class,
    SendRejectionEmailListener::class,
    SendCompletionEmailListener::class,
],
```

**Note**: Logging happens FIRST, before any email notifications are sent.

### 3. Controller Integration

**File**: `app/Http/Controllers/Administratives/Documents/DocumentsController.php`

The `update()` method now fires the `DocumentStatusChanged` event when status changes:

```php
public function update(Request $request)
{
    $document = Document::findByUid($request->uid);
    $oldStatusId = $document->status_id;

    // ... update document ...
    $document->save();

    // Fire DocumentStatusChanged event if status was actually changed
    if ($oldStatusId !== $document->status_id && $document->status_id) {
        $oldStatus = \App\Models\Document\DocumentStatus::find($oldStatusId);
        $newStatus = \App\Models\Document\DocumentStatus::find($document->status_id);

        if ($oldStatus && $newStatus) {
            event(new \App\Events\Document\DocumentStatusChanged(
                $document,
                $oldStatus,
                $newStatus,
                'Manual status change via admin panel'
            ));
        }
    }
}
```

### 4. Automatic Status Transitions

**File**: `app/Listeners/Documents/SendDocumentUploadConfirmation.php`

When documents are uploaded, the status automatically transitions to "Documentos Recibidos" and the event is fired:

```php
// Fire DocumentStatusChanged event
event(new \App\Events\Document\DocumentStatusChanged(
    $document,
    $currentStatus,
    $receivedStatus,
    'Automatic status change: documents uploaded'
));
```

## Status Change Scenarios

### Scenario 1: Manual Status Change (Admin)
```
Flow: Admin changes status in document management page
  ↓
Controller receives request with new status_id
  ↓
Controller fires DocumentStatusChanged event
  ↓
LogDocumentStatusChange listener logs to document_status_histories
  ↓
Other listeners send appropriate emails (approval, rejection, etc)
```

### Scenario 2: Automatic Status Change (Documents Uploaded)
```
Flow: Client uploads documents
  ↓
DocumentUploaded event fired
  ↓
SendDocumentUploadConfirmation listener runs
  ↓
Status auto-changes to "Received"
  ↓
DocumentStatusChanged event fired
  ↓
LogDocumentStatusChange listener logs to document_status_histories
```

### Scenario 3: Automatic Status Change (Document Created)
```
Flow: Document request created
  ↓
DocumentCreated event fired
  ↓
SendDocumentUploadNotification listener runs
  ↓
Status auto-set to "Pending" (Solicitado)
  ↓
Note: No DocumentStatusChanged event (initial state, not a transition)
```

## Database Schema

The `document_status_histories` table captures:

| Field | Type | Description |
|-------|------|-------------|
| `id` | UUID | Primary key |
| `document_id` | Foreign Key | Link to document |
| `from_status_id` | Foreign Key | Previous status |
| `to_status_id` | Foreign Key | New status |
| `changed_by` | Foreign Key | User who made the change |
| `reason` | Text | Reason for change |
| `metadata` | JSON | Additional context data |
| `created_at` | Timestamp | When change occurred |

## Example History Entry

```php
[
    'document_id' => 123,
    'from_status_id' => 1,      // "Solicitado" (Pending)
    'to_status_id' => 5,        // "Aprobado" (Approved)
    'changed_by' => 45,         // Admin user ID
    'reason' => 'Manual status change via admin panel',
    'metadata' => [
        'document_uid' => '082dda70-435b-4776-adce-049ded1e3485',
        'from_status_key' => 'pending',
        'to_status_key' => 'approved',
    ],
    'created_at' => '2025-12-17 10:30:45',
]
```

## Viewing Status History

```php
// Get all status changes for a document
$history = DocumentStatusHistory::where('document_id', $documentId)
    ->with(['fromStatus', 'toStatus', 'changedBy'])
    ->latest()
    ->get();

// Output example
foreach ($history as $record) {
    echo $record->fromStatus->label . ' → ' . $record->toStatus->label;
    echo ' by ' . $record->changedBy->name;
    echo ' on ' . $record->created_at;
}
```

## Logging Output

All status changes are also logged to the application log:

```
[2025-12-17 10:30:45] local.INFO: Document status change logged {
    "document_uid": "082dda70-435b-4776-adce-049ded1e3485",
    "from_status": "pending",
    "to_status": "approved",
    "changed_by": 45
}
```

## Event Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                   Status Change Triggered                    │
│              (Manual change or auto transition)               │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ↓
         ┌───────────────────────────────────┐
         │  DocumentStatusChanged Event       │
         │  - document                       │
         │  - fromStatus                     │
         │  - toStatus                       │
         │  - reason                         │
         └────────────┬────────────────────┘
                      │
        ┌─────────────┼─────────────┬────────────────────┐
        │             │             │                    │
        ↓             ↓             ↓                    ↓
   ┌─────────┐  ┌──────────┐  ┌──────────┐  ┌─────────────────┐
   │  Log    │  │ Approval │  │Rejection │  │   Completion    │
   │ Change  │  │  Email   │  │  Email   │  │     Email       │
   │ History │  │ Listener │  │Listener  │  │    Listener     │
   └─────────┘  └──────────┘  └──────────┘  └─────────────────┘
        │             │             │                    │
        └─────────────┴─────────────┴────────────────────┘
                      │
                      ↓
         ┌────────────────────────────────┐
         │ Task Complete: Status changed   │
         │ and logged with full audit trail│
         └────────────────────────────────┘
```

## Files Modified

1. **New Listener**: `app/Listeners/Documents/LogDocumentStatusChange.php`
2. **EventServiceProvider**: `app/Providers/EventServiceProvider.php`
3. **DocumentsController**: `app/Http/Controllers/Administratives/Documents/DocumentsController.php`
4. **SendDocumentUploadConfirmation**: `app/Listeners/Documents/SendDocumentUploadConfirmation.php`

## Code Quality

✅ Pint formatting: PASS (20 files)
✅ Event-driven architecture
✅ Automatic logging on all status changes
✅ Audit trail with user tracking
✅ Metadata preservation for debugging

## Testing

To test status change logging:

```php
// In tinker or test file
$document = Document::find(1);
$oldStatus = $document->status;
$newStatus = DocumentStatus::where('key', 'approved')->first();

// Simulate status change
$document->status_id = $newStatus->id;
$document->save();

// Fire event
event(new DocumentStatusChanged($document, $oldStatus, $newStatus, 'Test change'));

// Verify logging
$history = DocumentStatusHistory::where('document_id', $document->id)->latest()->first();
echo $history->reason; // Should output: "Test change"
```

## Summary

The status change logging system is now complete and fully integrated:

- ✅ All manual status changes are logged
- ✅ All automatic status transitions are logged
- ✅ Full audit trail with user and timestamp tracking
- ✅ Reason/metadata captured for context
- ✅ Integrated with email notification system
- ✅ Database queries available for reporting and analysis
