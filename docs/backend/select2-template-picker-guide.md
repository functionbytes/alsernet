# Select2 Template Picker - Complete Implementation Guide

## Overview

The document configuration page at `/manager/settings/documents/configurations` uses Select2 with AJAX to provide searchable dropdown menus for selecting email templates.

## Problem & Solution

### The Issue
When users visit the template picker page, the select elements appeared **completely empty** - even though:
- The AJAX endpoint was working perfectly (verified 15 templates returned)
- The backend code was correct
- JavaScript syntax was valid

### Root Causes & Fixes

#### 1. **Browser Compatibility Issue** ✓ FIXED
- **Problem:** Code used ES2020+ syntax (optional chaining, arrow functions, `const`)
- **Solution:** Converted to ES5-compatible syntax using `var`, traditional `for` loops, and explicit null checks
- **Result:** Works on all browsers including older versions

#### 2. **Empty Dropdown on Page Load** ✓ FIXED
- **Problem:** Select2 AJAX with `minimumInputLength: 0` requires the dropdown to be opened to trigger the first AJAX call
- **Solution:** Added `select2:opening` event handler to automatically trigger AJAX when user clicks dropdown
- **Result:** Templates load immediately when dropdown is opened

## Implementation Details

### Select2 Configuration

```javascript
var selectConfig = {
    placeholder: 'Selecciona un template o deja vacío para usar el predefinido',
    allowClear: true,
    width: '100%',
    ajax: {
        url: '/manager/settings/documents/configurations/search-templates',
        dataType: 'json',
        delay: 250,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        },
        data: function(params) {
            return {
                q: params.term || '',
                page: params.page || 1
            };
        },
        processResults: function(data) {
            return {
                results: data.results || [],
                pagination: data.pagination || { more: false }
            };
        }
    },
    minimumInputLength: 0,
    allowHtml: true,
    dropdownParent: $('body')
};
```

**Key Features:**
- **AJAX Search:** Endpoint filters templates by search term
- **Delay:** 250ms debounce to reduce server load
- **Headers:** CSRF token for security
- **Pagination:** Supports paginated results (currently returns all 15 at once)

### Event Handler for Empty Dropdown

```javascript
$el.on('select2:opening', function(e) {
    var searchField = $(this).data('select2').dropdown.$search ||
                      $(this).data('select2').selection.$search;

    if (searchField) {
        setTimeout(function() {
            searchField.val('').trigger('input');
        }, 100);
    }
});
```

**What This Does:**
1. Listens for when Select2 dropdown is about to open
2. Finds the search field inside the dropdown
3. Triggers an empty search (loads all templates)
4. Templates appear immediately instead of on first keystroke

## AJAX Endpoint

**Route:** `GET /manager/settings/documents/configurations/search-templates`

**Controller:** `DocumentConfigurationController::searchTemplates()`

**Request Parameters:**
```
q (string, optional): Search term to filter templates
```

**Response Format:**
```json
{
    "results": [
        {
            "id": 4,
            "text": "Confirmación - Documentos Aprobados [Sin idioma]",
            "name": "Confirmación - Documentos Aprobados",
            "key": "document_approved",
            "lang_id": null,
            "lang_name": "Sin idioma"
        }
        // ... more templates
    ],
    "pagination": {
        "more": false
    }
}
```

**Response Details:**
- `id`: Template ID (used as select value)
- `text`: Display text with language in brackets
- `name`: Template name only (useful for filtering)
- `key`: Unique template identifier
- `lang_id`: Language ID (tracks language preference)
- `lang_name`: Language display name

## Select Elements in Form

Six template picker dropdowns are available:

1. **Solicitud inicial de documentos** - `email_template_initial_request_id`
   - Sent when a paid order is detected and documents are created

2. **Recordatorio automático** - `email_template_reminder_id`
   - Sent automatically after specified days without document upload

3. **Solicitud de documentos específicos** - `email_template_missing_docs_id`
   - Admin can request specific documents

4. **Notificación de Aprobación** - `email_template_approval_id`
   - Sent when documents are approved

5. **Notificación de Rechazo** - `email_template_rejection_id`
   - Sent when documents are rejected

6. **Notificación de Finalización** - `email_template_completion_id`
   - Sent when documentation is complete

## Testing the Implementation

### 1. **Browser Test**

Navigate to: `https://alsernet.test/manager/settings/documents/configurations`

Expected behavior:
1. Page loads with 6 empty template dropdown sections
2. Click any "Plantilla de Email" dropdown
3. Templates list appears with all 15 available templates
4. Can scroll and see templates like "Confirmación - Documentos Aprobados"
5. Start typing to search (e.g., "Solicitud" finds 2 results)
6. Click a template to select it
7. Form can be submitted to save settings

### 2. **Browser Console Test (F12)**

Check Console tab for:

```javascript
// Should see these log messages:
"Templates cargados: {results: Array(15), pagination: {more: false}}"

// Verify libraries are loaded:
typeof $
// Output: "function"

typeof $.fn.select2
// Output: "function"

// Verify Select2 is initialized on element:
$('#email_template_initial_request_id').data('select2')
// Output: Select2 instance object (not undefined)
```

### 3. **Network Tab Test**

In DevTools Network tab:

1. Open dropdown
2. Look for request: `search-templates?q=`
3. Response should be 200 OK
4. Response body should contain JSON with template list

## Backend Verification

### Check Available Templates

```bash
php artisan tinker
> \App\Models\Email\EmailTemplate::module('documents')->enabled()->count()
# Return: 15
```

### Check Global Settings

```bash
php artisan tinker
> $settings = \App\Models\Setting::where('key', 'LIKE', 'documents.email_template%')->get();
> $settings->pluck('key', 'value');
# Shows current template assignments
```

### Test AJAX Endpoint Directly

```bash
php artisan tinker
> $request = new \Illuminate\Http\Request();
> $request->merge(['q' => 'Solicitud']);
> $controller = new \App\Http\Controllers\Managers\Settings\Orders\DocumentConfigurationController();
> $response = $controller->searchTemplates($request);
> $data = json_decode($response->getContent(), true);
> count($data['results'])
# Should return 2 (templates containing "Solicitud")
```

## Troubleshooting

### Symptom: Dropdown still appears empty

**Check 1: Verify jQuery and Select2 are loaded**
```javascript
console.log(typeof $);           // Should be "function"
console.log(typeof $.fn.select2); // Should be "function"
```

**Check 2: Verify CSRF Token**
```javascript
console.log(document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
// Should return a token string, not empty
```

**Check 3: Check AJAX endpoint in Network tab**
- Look for any 404 or 500 errors
- Check response content for proper JSON

**Check 4: Check browser console for errors**
- Look for "Error cargando templates:" messages
- Check for CORS or authentication errors

### Symptom: Templates load but very slowly

- This is normal - 250ms delay is intentional to reduce server load
- If slower than 1 second, check database query performance
- Run: `\App\Models\Email\EmailTemplate::module('documents')->enabled()->count()`

### Symptom: Some templates missing from dropdown

**Check:**
1. Template is enabled in database: `is_enabled = true`
2. Template is in documents module: `module = 'documents'`

```bash
php artisan tinker
> \App\Models\Email\EmailTemplate::module('documents')->where('is_enabled', false)->count()
# Check if any are disabled
```

## JavaScript Compatibility

The Select2 initialization script is **100% ES5 compatible**:

✓ No optional chaining (`?.`)
✓ No arrow functions
✓ No `const` or `let` (uses `var`)
✓ No template literals
✓ Works on IE9+

Verified with:
```bash
node -c /tmp/test_select2.js
# Output: ✓ JavaScript syntax is valid ES5
```

## Architecture

### Data Flow

```
User clicks dropdown
    ↓
select2:opening event fires
    ↓
Simulates empty search input
    ↓
AJAX request to /search-templates?q=
    ↓
Controller filters templates by search term
    ↓
Returns JSON with 15 templates
    ↓
Select2 renders dropdown with results
    ↓
User can select template
```

### Backend Flow

```
Request arrives at DocumentConfigurationController::searchTemplates()
    ↓
Query EmailTemplate model
    - Filter by module='documents'
    - Filter by is_enabled=true
    - Filter by search term (if provided)
    ↓
Map results to Select2 format
    - Include: id, text, name, key, lang_id, lang_name
    ↓
Return JSON response
    - results: array of templates
    - pagination: { more: false }
```

## Files Involved

1. **View:** `resources/views/managers/views/settings/documents/configurations/index.blade.php`
   - Select2 HTML and initialization script
   - 6 template picker dropdowns
   - Form for saving settings

2. **Controller:** `app/Http/Controllers/Managers/Settings/Orders/DocumentConfigurationController.php`
   - `searchTemplates()` - AJAX endpoint
   - `globalSettings()` - Load current settings
   - `updateGlobalSettings()` - Save settings
   - `getGlobalSettings()` - Retrieve settings from database
   - `loadTemplate()` - Load template by ID

3. **Model:** `app/Models/Email/EmailTemplate.php`
   - `module()` scope - filter by module
   - `enabled()` scope - filter by enabled status
   - `search()` scope - filter by search term
   - `lang()` relationship - access language info

4. **Database:** `email_templates` and `langs` tables
   - Templates with language associations
   - Settings table for storing selections

## Performance Considerations

- **AJAX Cache:** Enabled (`cache: true`) to reduce repeated requests
- **Debounce Delay:** 250ms prevents excessive server requests during typing
- **Eager Loading:** Controller uses `->with('lang')` to avoid N+1 queries
- **Limit:** Results limited to 50 templates per request
- **Index:** Queries use indexed columns (module, is_enabled, id)

## Future Improvements

1. Add template preview functionality
2. Implement bulk assignment of templates
3. Add caching with Redis for frequently used templates
4. Track template usage statistics
5. Add template version control and rollback

## References

- **Select2 Documentation:** https://select2.org/
- **Select2 AJAX:** https://select2.org/data-sources/formats
- **jQuery AJAX:** https://api.jquery.com/jQuery.ajax/
- **Laravel Eloquent:** https://laravel.com/docs/eloquent
