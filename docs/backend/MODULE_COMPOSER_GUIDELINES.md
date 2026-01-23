# Module Composer.json Guidelines

## 📦 Overview

Alsernet uses `wikimedia/composer-merge-plugin` to manage module-specific dependencies. Each module declares its own `composer.json` with the PHP packages it requires. The merge-plugin automatically combines all module dependencies with the root dependencies during installation.

## 🎯 Architecture

```
Root composer.json (Core Infrastructure)
    ↓
    ├── laravel/framework, sanctum, horizon, etc.
    ├── spatie/laravel-permission, laravel-activitylog
    └── Shared utilities (guzzle, doctrine, symfony)

wikimedia/composer-merge-plugin
    ↓
Module composer.json files (Specific Dependencies)
    ├── modules/Document/composer.json (PDF, Excel)
    ├── modules/Warehouse/composer.json (Barcodes, Images)
    ├── modules/Mailer/composer.json (Email, Templates)
    ├── modules/Supplier/composer.json (API, Translation)
    └── ... (other modules)
```

## 📋 Module Dependencies by Domain

### Content Management Modules

**Document Module** (`modules/Document/composer.json`)
- PDF Generation: `barryvdh/laravel-dompdf`, `setasign/fpdf`, `setasign/fpdi`
- Excel: `maatwebsite/excel`
- Text Processing: `soundasleep/html2text`
- HTML: `ezyang/htmlpurifier`

**Mailer Module** (`modules/Mailer/composer.json`)
- Email: `phpmailer/phpmailer`, `ashallendesign/email-utilities`
- Templates: `twig/twig`
- Media: `spatie/laravel-medialibrary`
- Translations: `barryvdh/laravel-translation-manager`

**MailsSettings Module** (`modules/MailsSettings/composer.json`)
- IMAP: `webklex/laravel-imap`
- Email: `phpmailer/phpmailer`

### Operations & Inventory

**Warehouse Module** (`modules/Warehouse/composer.json`)
- Image Processing: `intervention/image`
- Barcodes: `picqer/php-barcode-generator`, `milon/barcode`
- QR Codes: `bacon/bacon-qr-code`, `simplesoftwareio/simple-qrcode`

**Backup Module** (`modules/Backup/composer.json`)
- Backup: `spatie/laravel-backup`
- MySQL Dump: `ifsnop/mysqldump-php`

**Database Module** (`modules/Database/composer.json`)
- Schema: `doctrine/dbal`
- PDF: `setasign/fpdi-tcpdf`

### External Integrations

**Supplier Module** (`modules/Supplier/composer.json`)
- Translation: `deeplcom/deepl-php`
- HTTP Client: `guzzlehttp/guzzle`, `kriswallsmith/buzz`
- HTML Parsing: `kub-at/php-simple-html-dom-parser`

**Prestashop Module** (`modules/Prestashop/composer.json`)
- HTTP: `kriswallsmith/buzz`

**ERP Module** (`modules/Erp/composer.json`)
- HTML Parsing: `kub-at/php-simple-html-dom-parser`

### Core Features

**Auth Module** (`modules/Auth/composer.json`)
- JWT: `tymon/jwt-auth`

**Media Module** (`modules/Media/composer.json`)
- Media Library: `spatie/laravel-medialibrary`
- Images: `intervention/image`

**Notification Module** (`modules/Notification/composer.json`)
- Real-time: `pusher/pusher-php-server`

**Subscriber Module** (`modules/Subscriber/composer.json`)
- Email Validation: `egulias/email-validator`, `propaganistas/laravel-disposable-email`

**Campaign Module** (`modules/Campaign/composer.json`)
- Pipeline: `league/pipeline`
- Query Builder: `spatie/laravel-query-builder`

**Helpdesk Module** (`modules/Helpdesk/composer.json`)
- CSV: `league/csv`

## ✅ How to Add a Dependency to Your Module

### Step 1: Edit Your Module's composer.json

Open `modules/[YourModule]/composer.json`:

```json
{
    "name": "modules/yourmodule",
    "description": "Your Module Description",
    "keywords": ["keyword1", "keyword2"],
    "require": {
        "vendor/package": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Modules\\YourModule\\": "app/"
        }
    }
}
```

### Step 2: Update Dependencies

```bash
composer update
```

The merge-plugin will:
1. Read all `modules/*/composer.json` files
2. Merge them with the root `composer.json`
3. Install all dependencies in `vendor/`

### Step 3: Verify Installation

```bash
composer show | grep "your-package-name"
```

## 🚫 Restrictions & Guidelines

### ❌ What NOT to Do

1. **Don't duplicate root dependencies** - If a package is in root `composer.json`, don't add it to your module

   ```json
   // ❌ WRONG - guzzlehttp/guzzle is in root
   "require": {
       "guzzlehttp/guzzle": "^7.0"
   }
   ```

2. **Don't use incompatible versions** - Ensure version constraints align with other modules

   ```json
   // ❌ WRONG - Different versions might conflict
   "require": {
       "barryvdh/laravel-dompdf": "^2.0"  // Document uses ^3.1
   }
   ```

3. **Don't add development-only packages to `require`** - Use `require-dev` instead

   ```json
   {
       "require-dev": {
           "phpunit/phpunit": "^11.5.3"
       }
   }
   ```

4. **Don't modify root composer.json** - Coordinate through team lead for new root dependencies

### ✅ What To Do

1. **Check if package is already in root** - Ask your team or search `composer.json`

   ```bash
   grep -r "your-package-name" composer.json
   ```

2. **Use semantic versioning** - Follow composer conventions

   ```json
   "require": {
       "vendor/package": "^1.0"  // ✅ Allow minor/patch updates
   }
   ```

3. **Document your dependencies** - Add keywords and descriptions

   ```json
   {
       "keywords": ["pdf", "export", "documents"],
       "description": "Document management with PDF generation"
   }
   ```

4. **Test after adding dependencies**

   ```bash
   composer install
   php artisan test  # Run your module tests
   ```

## 🔄 Example: Adding Excel Support to Campaign Module

**Current state:** Campaign module doesn't export to Excel

**Step 1:** Edit `modules/Campaign/composer.json`

```json
{
    "name": "modules/campaign",
    "description": "Campaign Management",
    "require": {
        "league/pipeline": "^1.0",
        "spatie/laravel-query-builder": "^6.3",
        "maatwebsite/excel": "^3.1"  // ← NEW
    },
    "autoload": {
        "psr-4": {
            "Modules\\Campaign\\": "app/"
        }
    }
}
```

**Step 2:** Install

```bash
composer update
```

**Step 3:** Use in your code

```php
// modules/Campaign/app/Exports/CampaignsExport.php
use Maatwebsite\Excel\Facades\Excel;

class CampaignsExport
{
    public function export()
    {
        return Excel::download(new CampaignsCollectionExport(), 'campaigns.xlsx');
    }
}
```

## 🔍 Checking Module Dependencies

### View All Module Dependencies

```bash
# Show all installed packages from modules
composer show --all | grep modules/
```

### View Specific Module Dependencies

```bash
# Check what Document module needs
grep -A 10 '"require"' modules/Document/composer.json
```

### Find Where a Package is Required

```bash
# Find all modules using maatwebsite/excel
grep -r "maatwebsite/excel" modules/*/composer.json
```

## ⚠️ Version Conflicts

### Problem: Two Modules Need Different Versions

```json
// modules/Document/composer.json
"require": {
    "barryvdh/laravel-dompdf": "^3.1"
}

// modules/Mailer/composer.json
"require": {
    "barryvdh/laravel-dompdf": "^2.0"  // ❌ Conflict!
}
```

### Solution: Negotiate Compatible Version

```json
// Both modules should use:
"require": {
    "barryvdh/laravel-dompdf": "^3.1"  // ✅ Use latest compatible
}
```

## 📊 Module Dependency Matrix

| Module | Primary Dependencies | Domain |
|--------|---------------------|--------|
| Document | dompdf, excel, fpdf | Content Management |
| Warehouse | intervention/image, barcode-generators | Inventory |
| Mailer | phpmailer, twig, medialibrary | Email |
| MailsSettings | laravel-imap, phpmailer | Email Config |
| Supplier | deepl, guzzle, html-parser | Integration |
| Campaign | pipeline, query-builder | Marketing |
| Subscriber | email-validator, disposable-email | Marketing |
| Auth | jwt-auth | Security |
| Media | medialibrary, intervention/image | File Management |
| Notification | pusher | Real-time |
| Backup | laravel-backup, mysqldump | DevOps |
| Database | doctrine/dbal | Schema |
| Helpdesk | league/csv | Support |
| Event | (none) | Calendar |
| Erp | html-parser | Integration |
| Prestashop | buzz | Integration |

## 🛠️ Troubleshooting

### Issue: Composer can't find my new package

**Solution:**
```bash
# Clear composer cache
composer clearcache

# Update packages
composer update

# Validate your JSON
composer validate
```

### Issue: "Package not found in your version constraints"

**Solution:** Check version compatibility on [Packagist](https://packagist.org/)

```bash
# Find available versions
composer search vendor/package
```

### Issue: Autoload issues after adding dependency

**Solution:**
```bash
# Dump autoloader
composer dump-autoload

# Or with optimization
composer dump-autoload -o
```

## 📝 Best Practices

1. **Keep modules focused** - One module = related functionality
2. **Minimize external deps** - Use root packages when possible
3. **Document why** - Add keywords explaining the purpose
4. **Version wisely** - Use `^` for stability, avoid exact versions
5. **Test thoroughly** - Run tests after adding dependencies

## 🚀 Advanced: Module Dependencies

You can declare dependencies between modules in `module.json`:

```json
{
    "name": "Document",
    "requires": ["Mailer", "Media"]  // This module needs Mailer & Media
}
```

This ensures correct load order and initialization.

---

**Last Updated:** 2025-01-03
**Maintained By:** Alsernet Development Team
