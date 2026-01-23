# Document Source and Upload Type Refactoring

## Overview

The document source field has been properly refactored to separate two distinct concepts:

1. **Source/Origin (document_source_id)**: Where/how the document came from
   - Manual (Admin uploaded)
   - Email (Client sent via email)
   - WhatsApp (Client sent via WhatsApp)
   - PrestaShop (Client uploaded via portal)
   - API (Integrated via API)

2. **Upload Type (upload_type)**: WHO uploaded the document
   - `automatic` - Client or system uploaded
   - `manual` - Administrator uploaded

## Database Schema Changes

### New Table: document_sources

```sql
CREATE TABLE document_sources (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(255) UNIQUE NOT NULL,          -- email, whatsapp, prestashop, api, manual
    label VARCHAR(255) NOT NULL,               -- Display name
    description VARCHAR(255) NULLABLE,         -- Description of the source
    icon VARCHAR(255) NULLABLE,                -- Icon class
    color VARCHAR(255) NULLABLE,               -- Color code
    is_active BOOLEAN DEFAULT TRUE,
    order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Modified Table: documents

Added two new columns:

```sql
ALTER TABLE documents ADD COLUMN
    document_source_id BIGINT UNSIGNED NULLABLE AFTER source,
    upload_type ENUM('manual', 'automatic') DEFAULT 'automatic' AFTER source;

ALTER TABLE documents ADD FOREIGN KEY (document_source_id)
    REFERENCES document_sources(id) ON DELETE SET NULL;
```

## Models

### DocumentSource Model

**File**: `app/Models/Document/DocumentSource.php`

- Relations: `documents()` - all documents using this source
- Scopes: `active()`, `ordered()`
- Methods: `getByKey(string $key)`

### Document Model Updates

**File**: `app/Models/Document/Document.php`

- Added to `$fillable`: `document_source_id`, `upload_type`
- Added to `$casts`: `'upload_type' => 'string'`
- Added relation: `documentSource()` - BelongsTo DocumentSource

## Seeder Data

**File**: `database/seeders/DocumentSourceSeeder.php`

Predefined sources:

| Key | Label | Description |
|-----|-------|-------------|
| `manual` | Manual | Documento cargado manualmente por el administrador |
| `email` | Email | Cliente envió el documento por email |
| `whatsapp` | WhatsApp | Cliente envió el documento por WhatsApp |
| `prestashop` | PrestaShop | Cliente cargó el documento desde el portal PrestaShop |
| `api` | API | Documento cargado a través de integración API |

## UI Changes

### Document Management Form

**File**: `resources/views/administratives/views/documents/manage.blade.php`

Two separate fields now:

#### 1. Origen (Canal) - Source/Channel
```html
<label>Origen (Canal)</label>
<select name="document_source_id" id="document_source_id">
    <option>Manual</option>
    <option>Email</option>
    <option>WhatsApp</option>
    <option>PrestaShop</option>
    <option>API</option>
</select>
```

#### 2. Tipo de Carga - Upload Type
```html
<label>Tipo de Carga</label>
<select name="upload_type" id="upload_type">
    <option value="automatic">🤖 Automático (Cliente/Sistema)</option>
    <option value="manual">✏️ Manual (Administrador)</option>
</select>
```

## Controller Updates

### DocumentsController::manage()

```php
// Get all document sources for dropdown
$documentSources = \App\Models\Document\DocumentSource::where('is_active', true)
    ->orderBy('order')
    ->get();

// Pass to view
return view('administratives.views.documents.manage')->with([
    'documentSources' => $documentSources,
    // ... other data ...
]);
```

### DocumentsController::update()

```php
// Update document_source_id
if ($request->has('document_source_id') && !empty($request->document_source_id)) {
    $document->document_source_id = $request->document_source_id;
}

// Update upload_type
if ($request->has('upload_type')) {
    $document->upload_type = $request->upload_type;
}
```

## AJAX Form Submission

**File**: `resources/views/administratives/views/documents/manage.blade.php` (JavaScript)

Updated to send both new fields:

```javascript
const data = {
    proccess: proccess,
    source: source,                        // Legacy field
    status_id: statusId,
    document_source_id: documentSourceId,  // New field
    upload_type: uploadType                // New field
};

$.ajax({
    url: updateUrl,
    type: 'POST',
    data: data,
    // ... rest of ajax config ...
});
```

## Example Usage

### Setting Source and Upload Type

```php
// When admin uploads document manually
$document->document_source_id = DocumentSource::where('key', 'manual')->first()->id;
$document->upload_type = 'manual';
$document->save();

// When client sends via email
$document->document_source_id = DocumentSource::where('key', 'email')->first()->id;
$document->upload_type = 'automatic';
$document->save();

// When document received via API
$document->document_source_id = DocumentSource::where('key', 'api')->first()->id;
$document->upload_type = 'automatic';
$document->save();
```

### Querying Documents

```php
// Get all documents uploaded via email
$emailDocs = Document::whereHas('documentSource', function($q) {
    $q->where('key', 'email');
})->get();

// Get all manually uploaded documents
$manualDocs = Document::where('upload_type', 'manual')->get();

// Get all automatic uploads via WhatsApp
$whatsappDocs = Document::where('upload_type', 'automatic')
    ->whereHas('documentSource', function($q) {
        $q->where('key', 'whatsapp');
    })
    ->get();
```

## Migration & Seeding

```bash
# Run migration to create tables
php artisan migrate --path="database/migrations/2025_12_17_refactor_document_source_and_upload_type.php"

# Seed document sources
php artisan db:seed --class=DocumentSourceSeeder
```

## Files Created/Modified

### Created
- `database/migrations/2025_12_17_refactor_document_source_and_upload_type.php`
- `database/seeders/DocumentSourceSeeder.php`
- `app/Models/Document/DocumentSource.php`

### Modified
- `app/Models/Document/Document.php`
- `app/Http/Controllers/Administratives/Documents/DocumentsController.php`
- `resources/views/administratives/views/documents/manage.blade.php`

## Backward Compatibility

The old `source` field is kept for backward compatibility but no longer used in the UI. New code uses `document_source_id` and `upload_type`.

## Data Types

| Field | Type | Nullable | Default | Values |
|-------|------|----------|---------|--------|
| `document_source_id` | Foreign Key (BIGINT) | Yes | NULL | References document_sources.id |
| `upload_type` | ENUM | No | 'automatic' | 'manual', 'automatic' |

## Validation

Form validation rules for the new fields:

```php
$request->validate([
    'document_source_id' => 'nullable|integer|exists:document_sources,id',
    'upload_type' => 'required|in:manual,automatic',
]);
```

## Benefits of Refactoring

✅ **Clear Separation of Concerns**: Origin and type are now distinct concepts
✅ **Maintainability**: New sources can be easily added via seeder
✅ **Queryability**: Better filtering and reporting on document sources
✅ **Flexibility**: Each source can have metadata (icon, color, description)
✅ **Extensibility**: Can add more source types without modifying table structure
✅ **Audit Trail**: Clear tracking of how documents were uploaded

## Migration Path

For existing data:
1. Old `source` field values (manual, email, api, whatsapp) map to DocumentSource.key
2. New `upload_type` defaults to 'automatic' for existing documents
3. Manual uploads can be identified by historical records
