# Documents Table - Consolidated Schema

**Status**: Single consolidated migration in `/database/migrations/2025_12_29_020000_create_documents_table.php`

This is the authoritative schema for the `documents` table, sourced directly from the `Document` entity (`Modules/Documents/app/Entities/Document.php`).

## Schema Overview

### Primary Key & Identity
- `id` - BigInteger, primary key
- `uid` - String, unique identifier (HasUid trait)

### Foreign Keys
- `type_id` - FK to `document_types` table (was `type` string before)
- `source_id` - FK to `document_sources` table
- `load_id` - FK to `document_loads` table
- `sync_id` - FK to `document_syncs` table
- `upload_id` - FK to `document_upload_types` table
- `status_id` - FK to `document_statuses` table
- `sla_policy_id` - FK to `document_sla_policies` table
- `assigned_user_id` - FK to `users` table (nullable for group assignments)

### Order & Customer Data (Denormalized)
```
// Order references
- order_id (int, nullable)
- order_reference (string, nullable)
- order_date (datetime, nullable)
- cart_id (int, nullable)

// Customer references
- customer_id (int, nullable)
- customer_firstname (string, nullable)
- customer_lastname (string, nullable)
- customer_email (string, nullable)
- customer_dni (string, nullable)
- customer_company (string, nullable)
- customer_cellphone (string, nullable)

// Localization
- lang_id (int, nullable)

// Process/source
- proccess (string, nullable)
```

### Document Content
```
- required_documents (json, nullable)
  Array of required document keys: ["dni_frontal", "dni_trasera", ...]

- uploaded_documents (json, nullable)
  Array of uploaded document keys

- additional_attachments (json, nullable)
  Array of attachments with metadata: [{"id", "name", "url", "size", ...}]
```

### Document Status & Tracking
```
- confirmed_at (datetime, nullable)
  When the document upload was confirmed

- uploaded_confirmation_sent_at (datetime, nullable)
  When the upload confirmation event was sent

- reminder_at (datetime, nullable)
  Scheduled reminder time

- reminder_sent_at (datetime, nullable)
  When reminder was sent
```

### Upload Configuration
```
- upload_type (string, nullable)
  Type of upload: 'automatic' or 'manual'

- requires_financing (boolean, default: false)
  Indicates if document requires financing validation
```

### Validation Workflow
```
- validation_status (string, nullable)
  Current validation status

- current_stage (integer, nullable)
  Current stage number in workflow

- total_stages (integer, nullable)
  Total stages in workflow

- current_validator_group (string, nullable)
  Current validator group identifier

- validation_started_at (datetime, nullable)
  When validation started

- validation_completed_at (datetime, nullable)
  When validation completed
```

### Timestamps
- `created_at` - Created timestamp
- `updated_at` - Last updated timestamp

## Indexes

### Primary Indexes
- `uid` (unique)
- `type_id`
- `status_id`
- `customer_id`
- `order_id`
- `created_at`

### Validation Workflow Indexes
- `validation_status`
- `assigned_user_id, validation_status` (composite - `idx_assigned_validation`)

### Search/Filter Indexes
- `requires_financing`
- `order_reference`
- `customer_email`
- `customer_dni`

## Casts (from Entity)

The Document entity defines these property casts:

```php
protected $casts = [
    'confirmed_at' => 'datetime',
    'uploaded_confirmation_sent_at' => 'datetime',
    'reminder_at' => 'datetime',
    'reminder_sent_at' => 'datetime',
    'order_date' => 'datetime',
    'required_documents' => 'array',
    'uploaded_documents' => 'array',
    'additional_attachments' => 'array',
    'upload_type' => 'string',
    'requires_financing' => 'boolean',
    'current_stage' => 'integer',
    'total_stages' => 'integer',
    'validation_started_at' => 'datetime',
    'validation_completed_at' => 'datetime',
];
```

## Fillable Properties

```php
protected $fillable = [
    'uid',
    'type_id',
    'proccess',
    'source_id',
    'load_id',
    'sync_id',
    'upload_id',
    'lang_id',
    'confirmed_at',
    'uploaded_confirmation_sent_at',
    'reminder_at',
    'reminder_sent_at',
    'order_id',
    'customer_id',
    'cart_id',
    'order_reference',
    'order_date',
    'customer_firstname',
    'customer_lastname',
    'customer_email',
    'customer_cellphone',
    'customer_dni',
    'customer_company',
    'required_documents',
    'uploaded_documents',
    'additional_attachments',
    'status_id',
    'sla_policy_id',
    'requires_financing',
    'validation_status',
    'current_stage',
    'total_stages',
    'current_validator_group',
    'assigned_user_id',
    'validation_started_at',
    'validation_completed_at',
    'created_at',
    'updated_at',
];
```

## Key Features

### Document Type Management
- Uses `type_id` foreign key to `document_types` table
- Migration 2025_12_20_141812 changed from `type` string to `type_id` foreign key
- Supports dynamic validation workflows per document type

### Validation Workflow
- Tracks current stage and total stages in workflow
- Stores current validator group name
- Has soft assignment per user via `assigned_user_id`
- Timestamps for workflow start/completion

### Financing Logic
- `requires_financing` boolean drives validation stage selection
- Entity methods evaluate conditions to determine required stages

### File Management
- Uses Spatie MediaLibrary for file storage (not in schema)
- Tracks uploaded document types via JSON array
- Supports additional attachments uploaded by administrators

### SLA Management
- Links to `document_sla_policies` table
- Supports SLA breach tracking via DocumentSlaBreach model

### Localization
- `lang_id` for language/locale support

## Related Tables

The documents table references these supporting tables:
- `document_types` - Document type configurations
- `document_sources` - Upload sources
- `document_loads` - Load types
- `document_syncs` - Sync types
- `document_upload_types` - Upload method types
- `document_statuses` - Status definitions
- `document_sla_policies` - SLA policy configurations
- `users` - For assigned validator users

## Media Storage

Document files are managed via Spatie MediaLibrary (not in DB schema):
- Collection: `documents` - Primary documents
- Collection: `additional_attachments` - Additional files

Custom properties stored with media:
- `document_type` - Type of document (e.g., "dni_frontal")
- `original_name` - Original file name
- `uploaded_by` - User ID who uploaded
- `notes` - Upload notes

## Previous Migrations (Now Consolidated)

These individual migrations have been consolidated into the single CREATE TABLE:

1. `2025_12_20_010000_create_documents_table.php` - Initial skeleton
2. `2025_12_20_012142_add_additional_attachments_to_documents_table.php` - Added additional_attachments
3. `2025_12_20_013840_add_assigned_user_id_to_documents_table.php` - Added assigned_user_id
4. `2025_12_20_141812_change_type_to_type_id_in_documents_table.php` - Changed type to type_id

All these changes are now part of a single, clean CREATE TABLE migration in `/database/migrations/2025_12_29_020000_create_documents_table.php`.

## Usage Example

```php
// Create a document
$document = Document::create([
    'uid' => Str::uuid(),
    'type_id' => 1,
    'status_id' => 1,
    'order_id' => 123,
    'customer_id' => 456,
    'customer_firstname' => 'John',
    'customer_lastname' => 'Doe',
    'customer_email' => 'john@example.com',
    'customer_dni' => '12345678X',
    'requires_financing' => false,
    'validation_status' => 'pending',
]);

// Assign to specific user
$document->assigned_user_id = auth()->id();
$document->save();

// Query by validation status
$pending = Document::where('validation_status', 'pending')
    ->where('assigned_user_id', auth()->id())
    ->get();

// Search by customer
$results = Document::filterListing(
    search: 'john@example.com',
    uploadStatus: 1,
    dateFrom: '2025-01-01',
    dateTo: '2025-12-31'
)->get();
```
