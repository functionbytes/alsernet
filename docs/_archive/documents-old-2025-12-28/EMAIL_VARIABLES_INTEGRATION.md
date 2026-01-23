# Email Variables Integration Guide

## Overview

The email variables system is now fully centralized in the database. All email services should use the `MailVariableService` to manage and access email variables.

## Components

### 1. MailVariableService
**Location:** `app/Services/Email/MailVariableService.php`

Central service for managing email variables with methods:

- `getVariablesByModule(string $module)` - Get all variables for a module
- `getAllVariables()` - Get all available variables
- `getVariablesByCategory(string $module, string $category)` - Filter by category
- `getVariable(string $key)` - Get single variable by key
- `getTranslatedVariable(string $key, int $langId)` - Get translated variable
- `variableExists(string $key)` - Check if variable exists
- `getAllVariableKeys()` - Get all variable keys for validation
- `getVariablesGroupedByCategory(string $module)` - Get grouped variables

### 2. MailVariable Model
**Location:** `app/Models/Mail/MailVariable.php`

Stores variable definitions with:
- `key` - Variable name (e.g., CUSTOMER_NAME)
- `name` - Display name
- `description` - What the variable represents
- `example_value` - Example value for preview/testing
- `category` - Variable category (system, customer, order, document, general)
- `module` - Module (core, documents, orders)
- `is_system` - Whether it's a system variable (protected)
- `is_enabled` - Whether it's active

### 3. MailVariableLang Model
**Location:** `app/Models/Mail/MailVariableLang.php`

Stores translations for variables:
- `name` - Translated variable name
- `description` - Translated description
- `lang_id` - Language ID

## Integration Examples

### Using MailVariableService in Email Services

```php
use App\Services\Mails\MailVariableService;
use App\Models\Document\Document;

class DocumentEmailTemplateService
{
    public static function sendInitialRequest(Document $document): bool
    {
        // Get available variables for documents module
        $variables = MailVariableService::getVariablesByModule('documents');

        // Get grouped variables by category
        $grouped = MailVariableService::getVariablesGroupedByCategory('documents');

        // Get translated variable info
        $customerNameVar = MailVariableService::getTranslatedVariable('CUSTOMER_NAME', $document->lang_id);

        // ... rest of email sending logic
    }
}
```

### Getting Example Values for Testing

```php
$variable = MailVariable::where('key', 'CUSTOMER_NAME')->first();
$exampleValue = $variable->example_value; // "Juan García López"
```

### Validating Variables in Templates

```php
$template = 'Hello {CUSTOMER_NAME}, your order {ORDER_ID} is...';

// Get all valid keys for validation
$validKeys = MailVariableService::getAllVariableKeys();

// Check if all variables in template are valid
$pattern = '/{([A-Z_]+)}/';
preg_match_all($pattern, $template, $matches);

foreach ($matches[1] as $key) {
    if (!in_array($key, $validKeys)) {
        throw new InvalidVariableException("Variable {$key} not found");
    }
}
```

## Default Variables

All modules have access to core variables:
- `COMPANY_NAME`
- `SITE_NAME`
- `SITE_URL`
- `SUPPORT_EMAIL`
- `SUPPORT_PHONE`
- `CURRENT_YEAR`
- `CURRENT_DATE`
- `CURRENT_DATETIME`
- `LANG_CODE`

Plus module-specific variables:
- **documents**: CUSTOMER_NAME, ORDER_ID, DOCUMENT_TYPE, UPLOAD_LINK, etc.
- **orders**: ORDER_ID, ORDER_REFERENCE, ORDER_STATUS, etc.

## Adding New Variables

1. Go to **Settings → Mailers → Variables de correo**
2. Click **Crear Variable** button
3. Fill in:
   - **Clave**: Variable key in uppercase with underscores (e.g., MY_CUSTOM_VAR)
   - **Nombre**: Display name
   - **Categoría**: Select category (system, customer, order, document, general)
   - **Módulo**: Select module (core, documents, orders)
   - **Ejemplo**: Example value for preview
   - **Traducciones**: Add translations for each language

4. Save - variable is now available system-wide

## API Endpoints

Variables can be fetched via API:

```
GET /settings/mailers/variables/by-module?module=documents&category=customer
GET /settings/mailers/variables/grouped-by-category?module=documents
GET /settings/mailers/variables/available-keys?module=documents
```

## Best Practices

1. **Always validate** template variables using `MailVariableService::getAllVariableKeys()`
2. **Use example values** for template previews and testing
3. **Respect module boundaries** - only use appropriate module variables
4. **Add descriptions** when creating variables so admins know what they're for
5. **Use translations** for non-English variables and descriptions
6. **Mark system variables** - these are protected and can't be deleted
7. **Enable/disable** variables instead of deleting them to maintain compatibility

## Database Tables

### mail_variables
Stores variable definitions
```sql
SELECT * FROM mail_variables
WHERE module = 'documents'
AND is_enabled = true
ORDER BY category, key;
```

### mail_variable_langs
Stores language-specific translations
```sql
SELECT * FROM mail_variable_langs
WHERE mail_variable_id = 1
AND lang_id = 1;
```

## Migration/Update Path

To update variables after initial seeding:

```bash
# Update all variables with example values
php artisan db:seed --class=MailVariableSeeder

# Or create custom update command
php artisan make:command UpdateMailVariables
```
