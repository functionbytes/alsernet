# Language Management Views Reference

**Location:** `/modules/Mailing/resources/views/settings/languages/`

This document provides comprehensive reference for the language management views created for the Mailing module.

## Overview

Four Blade views have been created to manage email campaign languages and translations:

1. **index.blade.php** - List, search, filter, and manage all languages
2. **create.blade.php** - Form to create a new language with initial translations
3. **edit.blade.php** - Form to edit existing language and translations
4. **_form.blade.php** - Reusable form partial (used by create and edit)

## File Descriptions

### 1. index.blade.php (405 lines)

**Purpose:** Display all languages with management capabilities

**Features:**

#### Statistics Cards
- Total languages count
- Active languages count
- Default language indicator (with name badge)
- Last update timestamp

#### Search & Filter
- Text search by language code or name
- Dropdown filter by status (Active/Inactive)
- Auto-submit search form
- Clear button for resetting filters

#### Languages Table
Columns:
- **Language** - Name with language icon and code
- **Code** - ISO code displayed as code snippet
- **Status** - Active/Inactive badge
- **Default** - Star badge if default, dash otherwise
- **Translations** - Item count and completion percentage
- **Last Modified** - Date and relative time
- **Actions** - Dropdown menu with options

#### Action Dropdown Menu
- **Edit** - Modify language details
- **View Translations** - Open translation viewer
- **Set as Default** - Mark as default (conditional)
- **Download** - Export as JSON file
- **Delete** - Remove language (destructive)

#### Import/Export Section
- **Import Button** - Opens modal for JSON file upload
- **Export Button** - Downloads all languages as JSON (visible when languages exist)

#### Import Modal
- File input with JSON type restriction
- File size validation (5MB max)
- Instructions and format examples
- Success/error messaging

#### Empty State
- Large globe icon
- Helpful message
- Create button shortcut

#### Pagination
- Bootstrap 5 pagination
- Item range display
- Per-page records

**JavaScript Features:**
- Auto-submit on status filter change
- File validation for import
- Escape key clears search
- Confirm delete with custom handler

---

### 2. create.blade.php (336 lines)

**Purpose:** Create a new language with optional initial translations

**Form Sections:**

#### Language Information
- **Language Code** (required)
  - ISO format (e.g., es, en, fr)
  - Pattern validation
  - Max 10 characters

- **Language Name** (required)
  - User-friendly name
  - Max 100 characters

- **Region** (optional)
  - Geographic/variant specification
  - Max 50 characters

- **Description** (optional)
  - Purpose and notes
  - Max 255 characters

#### Initial Translations (Optional)
- **Ace JSON Editor**
  - Syntax highlighting
  - Autocomplete enabled
  - Real-time validation
  - Format button

- **Example Format Display**
  - Shows proper JSON structure
  - Key-value pair examples
  - Translation variable placeholders

#### Options Section
- **Checkbox: Active**
  - Make language available for use
  - Default: checked

- **Checkbox: Default**
  - Set as default language
  - Default: unchecked

- **Checkbox: RTL**
  - Right-to-left language support
  - For Arabic, Hebrew, etc.

**Validation:**
- Language code: Required, max 10, ISO pattern
- Language name: Required, max 100
- Region: Max 50
- Description: Max 255
- Translations: Valid JSON format

**JavaScript:**
- Ace editor initialization
- JSON validation before submit
- Form validation with jQuery Validate
- Custom error messages (Spanish)

---

### 3. edit.blade.php (382 lines)

**Purpose:** Modify existing language details and translations

**Features:**

#### Language Information
- **Language Code** - Disabled (immutable) with info text
- **Language Name** - Editable
- **Region** - Editable
- **Description** - Editable

#### Translations Editor
- **Ace JSON Editor**
  - Full JSON editing capability
  - Syntax highlighting
  - Live element counting
  - Format/beautify button

- **Statistics Card**
  - Total translations count
  - Last updated timestamp
  - Creation date
  - Dynamic element counter

#### Default Language Indicator
- Shows badge if language is default
- Visual distinction in header

#### Action Buttons
- **View Translations** - Opens translation viewer page
- **Download** - Exports language as JSON
- **Save Changes** - Primary action
- **Cancel** - Returns to list

**Special Features:**
- Shows immutability of language code
- Timestamps for audit trail
- Real-time translation counting
- All validations same as create

---

### 4. _form.blade.php (388 lines)

**Purpose:** Reusable form partial for DRY principle

**Usage:**
```blade
@include('settings.languages._form', ['language' => $language])
```

**Context Detection:**
- Automatically detects create vs edit mode
- Based on existence of `$language` variable

**Conditional Rendering:**

When creating (no `$language`):
- Language code field enabled
- No hidden fallback needed
- Different button text: "Crear idioma"
- No action buttons (View/Download)

When editing (`$language` exists):
- Language code field disabled
- Hidden field for code submission
- "Edit mode" badge if default
- Action buttons visible
- Button text: "Guardar cambios"

**Complete Form Implementation:**
- All three sections (Info, Translations, Options)
- Full validation rules
- Ace editor initialization
- JSON formatting utilities

---

## Design System Integration

### Bootstrap 5.3 Components Used

**Cards:**
- `.card` - Main container
- `.card-header` - Title section
- `.card-body` - Content area
- `.card-footer` - Action buttons

**Tables:**
- `.table` - Base styling
- `.table-responsive` - Mobile wrapper
- `.table-hover` - Row hover effect
- `.align-middle` - Vertical centering

**Badges:**
- `.badge` - Status indicator
- `.bg-success-subtle` - Light background
- `.text-success` - Colored text

**Forms:**
- `.form-control` - Input styling
- `.form-label` - Label styling
- `.form-check` - Checkbox/switch styling
- `.form-switch` - Toggle switch
- `.input-group` - Grouped inputs

**Modals:**
- `.modal` - Container
- `.modal-header` - Title section
- `.modal-body` - Content
- `.modal-footer` - Actions

**Buttons:**
- `.btn` - Base button
- `.btn-primary` - Primary action
- `.btn-secondary` - Secondary action
- `.btn-outline-*` - Outlined variants
- `.btn-light-*` - Light backgrounds

**Utilities:**
- `.d-flex` - Flexbox layout
- `.align-items-center` - Vertical alignment
- `.justify-content-between` - Space distribution
- `.gap-2` - Flex gap spacing
- `.mb-3`, `.p-4` - Margin/padding
- `.text-muted` - Lighter text
- `.border-bottom` - Divider

### Font Awesome 6 Icons

**Navigation/Status:**
- `fas fa-globe` - Language/world icon
- `fas fa-language` - Language symbol
- `fas fa-check-circle` - Checkmark (active)
- `fas fa-ban` - Prohibited (inactive)
- `fas fa-star` - Default language

**Actions:**
- `fas fa-plus` - Create/add
- `fas fa-edit` - Edit
- `fas fa-eye` - View/see
- `fas fa-download` - Download
- `fas fa-trash` - Delete
- `fas fa-file-import` - Import
- `fas fa-file-export` - Export

**UI Elements:**
- `fas fa-ellipsis-vertical` - More options
- `fas fa-magnifying-glass` - Search
- `fas fa-times-circle` - Clear/close
- `fas fa-search` - Search action
- `fas fa-info-circle` - Information
- `fas fa-lightbulb` - Tip/suggestion
- `fas fa-align-left` - Format/beautify
- `fas fa-times` - Close button
- `fas fa-save` - Save action
- `fas fa-circle-exclamation` - Error/warning

### Color Scheme

**Status Colors:**
- **Success** (.text-success, .bg-success-subtle) - Active, checkmarks
- **Danger** (.text-danger) - Delete, destructive actions
- **Warning** (.text-warning, .bg-warning-subtle) - Default language
- **Info** (.text-info) - Information, special features
- **Secondary** (.text-secondary) - Inactive, disabled

**Semantic Colors:**
- **Primary** - Main actions (Create, Save, Update)
- **Secondary** - Alternative actions (Cancel, Back)
- **Light** - Backgrounds, subtle elements
- **Dark** - Text, high contrast

---

## Required Dependencies

### Laravel/Blade
- `layouts.theme` - Main layout template
- `core::components.alerts` - Alert messages
- `core::components.card` - Card header component

### Frontend Libraries
- **Bootstrap 5.3** - CSS framework
- **jQuery** - JavaScript library
- **jQuery Validate** - Form validation
- **Ace Editor 1.30.0** - Code editor
  - `ace.js` - Main library
  - `mode-json.js` - JSON mode
  - `theme-chrome.js` - Chrome theme
- **Font Awesome 6** - Icon library

### CDN Links Used
```html
<!-- Ace Editor CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ace/1.30.0/ace.min.css">

<!-- Ace Editor JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.30.0/ace.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.30.0/mode-json.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.30.0/theme-chrome.min.js"></script>
```

---

## Controller Integration

### Required Routes

```php
Route::resource('languages', LanguageController::class);
Route::post('languages/import', [LanguageController::class, 'import'])->name('languages.import');
Route::post('languages/{language}/set-default', [LanguageController::class, 'setDefault'])->name('languages.setDefault');
Route::get('languages/{language}/export', [LanguageController::class, 'export'])->name('languages.export');
Route::get('languages/{language}/show', [LanguageController::class, 'show'])->name('languages.show');
```

### Required Controller Methods

```php
public function index()
{
    // Return:
    // - $languages (paginated collection)
    // - $stats (array with total, active, last_updated, total_translations)
    // - $defaultLanguage (single language object or null)
}

public function create()
{
    // Return: empty view
}

public function store(Request $request)
{
    // Validate and save language
}

public function edit(Language $language)
{
    // Return: $language object
}

public function update(Request $request, Language $language)
{
    // Validate and update language
}

public function destroy(Language $language)
{
    // Delete language
}

public function show(Language $language)
{
    // Return: translation viewer page
}

public function export(Language $language)
{
    // Return: JSON file download
}

public function setDefault(Language $language)
{
    // Set as default and return
}

public function import(Request $request)
{
    // Handle JSON file upload
}
```

---

## Data Model Requirements

### Language Model Attributes

```php
class Language extends Model
{
    protected $fillable = [
        'language_code',      // ISO code: es, en, fr
        'language_name',      // Readable name
        'region',             // Optional region
        'description',        // Optional description
        'translations',       // JSON or text field
        'is_active',          // Boolean
        'is_default',         // Boolean
        'is_rtl',             // Boolean
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_rtl' => 'boolean',
        'translations' => 'array', // or 'json'
    ];

    // Accessor for JSON translations
    public function getTranslationsJsonAttribute()
    {
        return json_encode($this->translations, JSON_PRETTY_PRINT);
    }
}
```

---

## Validation Rules

### Language Code
- Required
- Max 10 characters
- Pattern: `/^[a-z]{2,10}(-[a-z]{2})?$/i`
- Examples: `es`, `en`, `pt-BR`, `zh-Hans`

### Language Name
- Required
- Max 100 characters

### Region
- Optional
- Max 50 characters

### Description
- Optional
- Max 255 characters

### Translations
- Valid JSON format
- Each property is a key-value pair
- Values should be strings

### Boolean Fields
- is_active: true/false
- is_default: true/false
- is_rtl: true/false

---

## Responsive Behavior

### Mobile Breakpoints

**Small screens (< 768px):**
- Forms stack vertically
- Tables become scrollable
- Dropdown menus adjust positioning
- Buttons wrap to new lines

**Medium screens (768px - 1024px):**
- Two-column forms (col-md-6)
- Table columns optimized
- Action buttons on one line

**Large screens (> 1024px):**
- Full layout as designed
- All features visible
- Optimal spacing

---

## Error Handling

### Server-Side Errors
- Display via `@error` directive
- Show message in red
- Field highlighted with `is-invalid`

### Client-Side Validation
- jQuery Validate plugin
- Real-time validation
- Error messages before submit
- Field highlighting

### JSON Editor Errors
- Syntax validation before submit
- Alert dialog with error message
- Focus returns to editor
- Helpful error descriptions

### File Upload Errors
- File type validation
- File size validation
- User-friendly error messages

---

## Accessibility Features

- Semantic HTML structure
- ARIA labels on modals
- Proper heading hierarchy
- Form labels associated with inputs
- Color not only indicator (icons, text)
- Keyboard navigation support
- Clear focus states
- Descriptive button text

---

## Performance Considerations

### Optimization Tips

1. **Pagination:**
   - Load only visible languages
   - Limit per page (15-25 recommended)

2. **Search/Filter:**
   - Debounce search input
   - Server-side filtering

3. **JSON Editor:**
   - Lazy load Ace library
   - Consider max translation size
   - Compress large JSON objects

4. **Icons:**
   - Use Font Awesome web fonts
   - Consider SVG alternatives
   - Lazy load if many icons

5. **Database:**
   - Index language_code and is_default
   - Use JSON column type for translations
   - Cache default language

---

## Testing Checklist

### Create View
- [ ] Form submits correctly
- [ ] Language code validation
- [ ] JSON validation works
- [ ] Translations optional
- [ ] All checkboxes work
- [ ] Responsive on mobile

### Edit View
- [ ] Language code disabled
- [ ] Form shows current data
- [ ] Updates correctly
- [ ] Statistics display
- [ ] Export button works
- [ ] All buttons functional

### Index View
- [ ] Languages display in table
- [ ] Stats cards show correct numbers
- [ ] Search works
- [ ] Filter works
- [ ] Actions dropdown functions
- [ ] Import modal displays
- [ ] Export downloads file
- [ ] Pagination works
- [ ] Empty state displays

### Cross-Browser
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari
- [ ] Mobile browsers

---

## Future Enhancements

Potential improvements:
1. Bulk operations (select multiple)
2. Translation comparison tool
3. Language duplication
4. Translation suggestions/AI
5. Version history
6. Real-time collaboration
7. Translation memory
8. Export formats (CSV, XML)
9. Import validation preview
10. Audit logging

---

## Support & Troubleshooting

### Common Issues

**JSON Formatting Error**
- Ensure all keys are quoted
- All values are quoted (for strings)
- No trailing commas
- Proper nesting

**Missing Ace Editor**
- Check CDN availability
- Verify script loading
- Check console for errors

**Icons Not Showing**
- Verify Font Awesome 6 loaded
- Check icon class names
- No custom CSS overriding

**Validation Not Working**
- jQuery required
- jQuery Validate plugin required
- Check form ID matches script

---

## Document Version

- **Created:** 2026-01-28
- **Version:** 1.0
- **Status:** Complete
- **Compatibility:** Bootstrap 5.3, Laravel 11+, jQuery 3.x+
