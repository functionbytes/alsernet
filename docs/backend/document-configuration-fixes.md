# Document Configuration - Fixes & Improvements

## Overview

This document describes the fixes and improvements made to the document configuration system, specifically for the email template picker functionality in the document configurations page.

## Changes Made

### 1. Added `lang_id` Field to `request_documents` Table

**Migration:** `database/migrations/2025_12_11_112446_add_lang_id_to_request_documents_table.php`

**Details:**
- Added `lang_id` column (unsignedBigInteger, nullable) to track the language/locale of each document request
- Positioned after the `type` column
- Foreign key constraint to `langs` table with `ON DELETE SET NULL`
- Index created on `lang_id` for query optimization

**Benefits:**
- Track language preference for each document request
- Enable multi-language document templates
- Support automatic language-specific email notifications

### 2. Updated Document Model

**File:** `app/Models/Order/Document.php`

**Changes:**
- Added `lang()` BelongsTo relationship to Lang model
- Added `lang_id` to the `$fillable` array
- Updated PHPDoc with `lang_id` property and relationship documentation

**Usage Example:**
```php
// Load document with language info
$document = Document::with('lang')->find($id);

// Access the language
echo $document->lang->title; // "Español", "English", etc.

// Create document with language
Document::create([
    'type' => 'dni',
    'lang_id' => 1, // Spanish
    'order_id' => $orderId,
]);
```

### 3. Fixed Select2 Template Picker Initialization

**File:** `resources/views/managers/views/settings/documents/configurations/index.blade.php`

**Previous Issues:**
- Script attempted to initialize Select2 before jQuery was fully loaded
- No error handling for AJAX failures
- Elements were not verified to exist before initialization

**Improvements:**
- Used IIFE (Immediately Invoked Function Expression) for better scope isolation
- Consolidated configuration to reduce code duplication
- Added checks for jQuery and Select2 availability
- Implemented proper error handling with console logging
- Added `X-Requested-With: XMLHttpRequest` header for AJAX detection
- Destroy existing Select2 instances before reinitializing
- Use `$(document).ready()` when jQuery is available (more reliable)

**Key Features:**
```javascript
// Wait for jQuery if not available
if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
    setTimeout(initializeSelect2, 100);
    return;
}

// Use jQuery.ready() for better timing
if (typeof $ !== 'undefined') {
    $(document).ready(initializeSelect2);
}
```

### 4. AJAX Endpoint

**Route:** `manager.settings.documents.configurations.search-templates`

**Controller Method:** `DocumentConfigurationController::searchTemplates()`

**Response Format:**
```json
{
    "results": [
        {
            "id": 12,
            "text": "Solicitud de Documentación [Español]",
            "name": "Solicitud de Documentación",
            "key": "document_initial_request",
            "lang_id": 1,
            "lang_name": "Español"
        }
    ],
    "pagination": {
        "more": false
    }
}
```

**Features:**
- Returns only enabled templates from the `documents` module
- Filters by search term if provided
- Includes language information for each template
- Properly formatted for Select2 integration

## Testing

### Manual Tests Performed

All tests executed successfully:

1. **TEST 1: Search without term**
   - Returns all 15 available templates
   - Proper JSON structure with results and pagination
   - Each result has required fields (id, text, name, key, lang_id, lang_name)

2. **TEST 2: Search with term "Solicitud"**
   - Returns 2 filtered results
   - Correctly filters by search term
   - Results include the search term in the name

3. **TEST 3: Response structure**
   - All required fields present in each result
   - Proper data types (integer id, string text, etc.)
   - Language information included

4. **TEST 4: Select2 compatibility**
   - ID is integer
   - Text is string
   - Text includes language in brackets [Idioma]
   - Proper formatting for dropdown display

### Automated Tests

Created comprehensive test suite in `tests/Feature/Managers/Settings/Orders/DocumentConfigurationControllerTest.php`

**Test Coverage:**
- Global settings page loads correctly
- Search templates returns all templates with correct structure
- Search filters by search term
- Only enabled templates are returned
- Only documents module templates are returned
- Templates include language information
- Response follows Select2 format

## Database Schema

### request_documents table

```
Column          | Type              | Nullable | Default | Index
----------------|-------------------|----------|---------|-------
id              | bigint unsigned   | NO       | -       | PRIMARY
uid             | varchar(255)      | NO       | -       | UNIQUE
type            | varchar(255)      | YES      | NULL    | -
lang_id         | bigint unsigned   | YES      | NULL    | YES
proccess        | varchar(255)      | YES      | NULL    | -
source          | varchar(255)      | YES      | NULL    | -
... other columns ...
```

### Foreign Key

```sql
ALTER TABLE request_documents ADD CONSTRAINT fk_request_documents_lang_id
FOREIGN KEY (lang_id) REFERENCES langs(id) ON DELETE SET NULL;
```

## API Endpoints

### Search Templates

**Endpoint:** `GET /manager/settings/documents/configurations/search-templates`

**Parameters:**
- `q` (string, optional): Search term to filter templates

**Response:**
- Status: 200 OK
- Content-Type: application/json
- Body: JSON object with `results` array and `pagination` object

**Example Request:**
```bash
curl -H "X-Requested-With: XMLHttpRequest" \
     "https://alsernet.test/manager/settings/documents/configurations/search-templates?q=Solicitud"
```

## Configuration

### Settings stored in `settings` table

The following configuration keys are used to store selected templates:

```php
Setting::set('documents.email_template_initial_request_id', $templateId);
Setting::set('documents.email_template_reminder_id', $templateId);
Setting::set('documents.email_template_missing_docs_id', $templateId);
Setting::set('documents.email_template_approval_id', $templateId);
Setting::set('documents.email_template_rejection_id', $templateId);
Setting::set('documents.email_template_completion_id', $templateId);
```

## Troubleshooting

### Template Picker Not Loading

**Symptoms:** Dropdown shows no options when clicked

**Solutions:**
1. Check browser console (F12) for errors
2. Verify templates exist and are enabled
3. Ensure jQuery and Select2 libraries are loaded
4. Check that AJAX endpoint returns 200 status

**Debug Steps:**
```javascript
// In browser console:
console.log($('#email_template_initial_request_id').data('select2'));
// Should return Select2 instance
```

### AJAX Endpoint Errors

**Symptoms:** Console shows 404 or 500 errors

**Check:**
1. Route is registered in `routes/managers.php`
2. Controller method exists and is public
3. Database connection is working
4. Required tables exist (email_templates, langs)

## Future Improvements

1. **Caching:** Add Redis caching for frequently accessed templates
2. **Preview:** Add template preview functionality in the dropdown
3. **Bulk Operations:** Allow bulk assignment of templates to multiple document types
4. **Version Control:** Track template changes and enable rollback
5. **Analytics:** Log template usage and success rates

## References

- Email Template Model: `app/Models/Email/EmailTemplate.php`
- Document Model: `app/Models/Order/Document.php`
- Document Configuration Controller: `app/Http/Controllers/Managers/Settings/Orders/DocumentConfigurationController.php`
- Configuration View: `resources/views/managers/views/settings/documents/configurations/index.blade.php`
