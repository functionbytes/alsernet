# Email Variables System - Quick Start Guide

## 🚀 Access the Variables Manager

**URL:** `/manager/settings/mailers/variables`

**Navigation:** Settings → Mailers → Variables de correo

## 📊 System Status

```
✅ 30 Variables configured
✅ 5 Languages supported
✅ 150 Translations ready
✅ 4 Categories organized
✅ 3 Modules covered
```

## 📋 Variable Categories

### System (10 variables)
- COMPANY_NAME
- SITE_NAME, SITE_URL
- SUPPORT_EMAIL, SUPPORT_PHONE
- CONTACT_EMAIL
- CURRENT_YEAR, CURRENT_DATE, CURRENT_DATETIME
- LANG_CODE

### Customer (4 variables)
- CUSTOMER_NAME
- CUSTOMER_FIRSTNAME, CUSTOMER_LASTNAME
- CUSTOMER_EMAIL

### Order (2 variables)
- ORDER_ID
- ORDER_REFERENCE

### Document (14 variables)
- DOCUMENT_TYPE, DOCUMENT_TYPE_LABEL
- DOCUMENT_INSTRUCTIONS, DOCUMENT_UID
- UPLOAD_LINK, UPLOAD_URL
- EXPIRATION_DATE, DEADLINE
- MISSING_DOCUMENTS, MISSING_DOCUMENTS_LIST
- REQUIRED_DOCUMENTS_LIST
- NOTES, NOTES_SECTION
- REQUEST_REASON

## 🔧 Module Coverage

**Core Module** (14 vars)
- System + Customer variables

**Documents Module** (14 vars)
- All document-specific variables

**Orders Module** (2 vars)
- Order-specific variables

## 💡 Example Values

Every variable has an example value for testing:
- CUSTOMER_NAME → "Juan García López"
- ORDER_REFERENCE → "PED-2025-001234"
- SUPPORT_EMAIL → "soporte@mitienraonline.com"

## 🎯 Common Tasks

### View All Variables
1. Go to Settings → Mailers → Variables de correo
2. See all 30 variables in table format
3. Filter by module or category
4. Search by name or key

### Add New Variable
1. Click **Crear Variable** button
2. Fill in:
   - Clave: MY_NEW_VAR (uppercase + underscore)
   - Nombre: My New Variable
   - Categoría: Select category
   - Módulo: Select module
   - Ejemplo: Example value for preview
3. Add translations for each language (tab-based)
4. Click **Crear Variable**

### Edit Variable
1. Find variable in list
2. Click dropdown menu → Editar
3. Update fields (key/category locked for system vars)
4. Update translations in language tabs
5. Click **Guardar Cambios**

### Enable/Disable Variable
1. Click toggle switch on variable row
2. Changes apply immediately
3. Disabled variables won't appear in templates

### Delete Variable (Custom Only)
1. Find custom variable
2. Click dropdown menu → Eliminar
3. Confirm deletion
4. System variables cannot be deleted

## 🔗 Using Variables in Code

### Get Variables by Module

```php
use App\Services\Mails\MailVariableService;

$variables = MailVariableService::getVariablesByModule('documents');
$grouped = MailVariableService::getVariablesGroupedByCategory('documents');
```

### Get Variable Info
```php
$variable = MailVariableService::getVariable('CUSTOMER_NAME');
$translated = MailVariableService::getTranslatedVariable('CUSTOMER_NAME', $langId);
```

### Validate Variables
```php
$keys = MailVariableService::getAllVariableKeys();
if (in_array('MY_VAR', $keys)) {
    // Variable exists
}
```

## 📱 Mobile-Friendly Interface

- Responsive design works on all devices
- Touch-friendly buttons and controls
- Mobile-optimized table view
- Dropdown menus for actions

## 🔒 Security Features

- System variables protected (can't modify/delete)
- CSRF protection on all forms
- HTML escaping in UI
- Database validation
- Authorization checks

## 🌍 Multi-Language Support

- Each variable has translations for all languages
- Tab-based interface for language selection
- Translation indicators (✓ complete, ⚠ incomplete)
- Per-language name and description

## 📈 Performance

- Database indexed for fast queries
- Eager loading of translations
- Efficient filtering and searching
- Pagination on list views (20 per page)

## 🎓 Best Practices

1. **Use consistent naming**: ALL_CAPS with underscores
2. **Add descriptions**: Help admins understand purpose
3. **Provide examples**: For testing and previewing
4. **Organize by category**: Makes finding easier
5. **Translate completely**: Fill all language tabs
6. **Protect when needed**: Mark system variables
7. **Enable/disable first**: Before deleting custom vars

## 🔄 Integration with Email Services

Variables are automatically available in:
- Email template editor
- Custom email modal
- Template preview system
- Variable insertion helpers

## 📚 Documentation

- **Full Integration Guide**: `docs/EMAIL_VARIABLES_INTEGRATION.md`
- **Implementation Summary**: `IMPLEMENTATION_SUMMARY.md`
- **This Guide**: `QUICK_START_VARIABLES.md`

## 🆘 Troubleshooting

**Variables not showing?**
- Make sure `is_enabled = true`
- Check module matches template module
- Clear browser cache

**Can't edit system variable?**
- System variables have locked key/category
- You can still enable/disable them
- You can update translations

**Missing translation?**
- Go to variable and click Edit
- Go to language tab
- Fill in the translation
- Save changes

## 🚀 Next Steps

1. Create custom variables for your templates
2. Add translations for all languages
3. Use in email templates via {VARIABLE_NAME}
4. Test with example values
5. Monitor usage in email audit logs

## 📞 Need Help?

Refer to:
- Email Variables Integration Guide in docs/
- MailVariableService for API access
- MailVariable model for database queries
- MailVariableController for business logic

---

**Last Updated**: December 15, 2025
**Status**: ✅ Production Ready
**Variables**: 30 (+ unlimited custom)
