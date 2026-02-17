# Seo Module - File Index

## Quick Start
Start here: [INSTALLATION.md](INSTALLATION.md) → [QUICK_REFERENCE.md](QUICK_REFERENCE.md) → [README.md](README.md)

## Documentation Files

### Getting Started
- **[INSTALLATION.md](INSTALLATION.md)** - Step-by-step installation and setup guide
- **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** - Quick lookup for common tasks
- **[README.md](README.md)** - Complete module documentation

### Learning & Examples
- **[USAGE_EXAMPLES.md](USAGE_EXAMPLES.md)** - Comprehensive code examples for all use cases
- **[MODULE_SUMMARY.md](MODULE_SUMMARY.md)** - Complete module overview and file listing

### Maintenance
- **[CHANGELOG.md](CHANGELOG.md)** - Version history and changes

## Core Application Files

### Models
- `app/Models/SeoMeta.php` - Main model for SEO metadata storage
  - Polymorphic relationships
  - Accessor methods
  - Query scopes

### Traits
- `app/Traits/HasSeo.php` - Add to any model to enable SEO features
  - Relationship methods
  - Accessor methods
  - Helper methods

### Services
- `app/Services/SeoService.php` - Core business logic
  - Chainable setters
  - Data loading
  - Meta tag rendering
  - Preview generation

### Facades
- `app/Facades/Seo.php` - Convenient static access to SeoService
  - All service methods available statically

### Controllers
- `app/Http/Controllers/SeoMetaController.php` - RESTful API controller
  - CRUD operations
  - Preview generation
  - Bulk operations
  - Statistics

### Form Requests
- `app/Http/Requests/StoreSeoMetaRequest.php` - Validation for creating SEO meta
- `app/Http/Requests/UpdateSeoMetaRequest.php` - Validation for updating SEO meta

### Providers
- `app/Providers/SeoServiceProvider.php` - Laravel service provider
  - Service registration
  - View registration
  - Blade directive registration
  - Asset publishing

### Helpers
- `app/helpers.php` - Global helper functions
  - `seo()` - Get service instance
  - `seo_title()` - Set/get title
  - `seo_description()` - Set/get description
  - `seo_keywords()` - Set/get keywords
  - `seo_image()` - Set image
  - `seo_canonical()` - Set/get canonical
  - `seo_robots()` - Set/get robots
  - `seo_noindex()` - Set noindex
  - `seo_render()` - Render tags
  - `seo_from_model()` - Load from model
  - `seo_preview()` - Generate preview
  - `truncate_for_seo()` - Truncate text

## View Files

### Components
- `resources/views/components/seo-tags.blade.php` - Blade component for rendering all SEO meta tags
  - Basic SEO tags
  - Open Graph tags
  - Twitter Card tags
  - JSON-LD schema
  - Usage: `<x-seo-tags :model="$model" />`

### Partials
- `resources/views/partials/seo-form.blade.php` - Complete SEO form with live preview
  - Basic SEO fields
  - Open Graph fields
  - Twitter Card fields
  - Advanced settings
  - Character counters
  - Live preview (Google, Facebook, Twitter)
  - Usage: `@include('Seo::partials.seo-form', ['model' => $model])`

## Route Files

### API Routes
- `routes/api.php` - RESTful API endpoints
  - `GET /api/seo-helper/seo-metas` - List
  - `GET /api/seo-helper/seo-metas/{id}` - Show
  - `POST /api/seo-helper/seo-metas` - Create
  - `PUT /api/seo-helper/seo-metas/{id}` - Update
  - `DELETE /api/seo-helper/seo-metas/{id}` - Delete
  - `POST /api/seo-helper/seo-metas/preview` - Preview
  - `POST /api/seo-helper/seo-metas/bulk-update` - Bulk update
  - `GET /api/seo-helper/seo-metas/statistics/all` - Statistics

### Web Routes
- `routes/web.php` - Web routes (empty, ready for custom routes)

## Database Files

### Migrations
- `database/migrations/2026_02_08_000001_create_seo_metas_table.php`
  - Creates `seo_metas` table
  - Polymorphic columns
  - SEO fields
  - Open Graph fields
  - Twitter fields
  - Indexes for performance

### Seeders
- `database/seeders/SeoDatabaseSeeder.php`
  - Empty seeder ready for custom data

## Configuration Files

### Module Config
- `config/Seo.php` - Module configuration
  - Default title suffix
  - Default description
  - Default OG image
  - Twitter site handle
  - Character limits
  - Image size recommendations
  - Canonical URL settings
  - JSON-LD settings

### Composer
- `composer.json` - Package definition
  - Dependencies
  - Autoloading
  - Service providers

### Module
- `module.json` - Module metadata
  - Name and alias
  - Description
  - Keywords
  - Version
  - Providers

## Testing Files

### Configuration
- `phpunit.xml` - PHPUnit configuration
  - Test suites
  - Coverage settings
  - Environment variables

## Other Files

### Version Control
- `.gitignore` - Git ignore rules
  - Vendor directory
  - IDE files
  - Log files

## Directory Structure

```
Seo/
├── app/
│   ├── Facades/
│   │   └── Seo.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── SeoMetaController.php
│   │   └── Requests/
│   │       ├── StoreSeoMetaRequest.php
│   │       └── UpdateSeoMetaRequest.php
│   ├── Models/
│   │   └── SeoMeta.php
│   ├── Providers/
│   │   └── SeoServiceProvider.php
│   ├── Services/
│   │   └── SeoService.php
│   ├── Traits/
│   │   └── HasSeo.php
│   └── helpers.php
├── config/
│   └── Seo.php
├── database/
│   ├── migrations/
│   │   └── 2026_02_08_000001_create_seo_metas_table.php
│   └── seeders/
│       └── SeoDatabaseSeeder.php
├── resources/
│   └── views/
│       ├── components/
│       │   └── seo-tags.blade.php
│       └── partials/
│           └── seo-form.blade.php
├── routes/
│   ├── api.php
│   └── web.php
├── .gitignore
├── CHANGELOG.md
├── composer.json
├── INDEX.md (this file)
├── INSTALLATION.md
├── MODULE_SUMMARY.md
├── module.json
├── phpunit.xml
├── QUICK_REFERENCE.md
├── README.md
└── USAGE_EXAMPLES.md
```

## File Relationships

### Model Layer
```
Model (with HasSeo trait)
    ↓
SeoMeta Model (polymorphic)
    ↓
Database (seo_metas table)
```

### Service Layer
```
Controller → SeoService → SeoMeta Model
                ↓
            Seo Facade
```

### View Layer
```
Layout → <x-seo-tags> → SeoService → render()
        or
Layout → @seoTags → SeoService → render()
```

### Form Layer
```
Form → @include('Seo::partials.seo-form')
    ↓
Controller → $model->updateSeoMeta()
    ↓
SeoMeta Model
```

## Usage Flow

### Reading SEO Data
1. Load model with HasSeo trait
2. Access SEO data via accessors (`$model->seo_title`)
3. Render in layout with `<x-seo-tags :model="$model" />`

### Writing SEO Data
1. Include form partial in edit view
2. Submit form data
3. Controller validates and saves with `$model->updateSeoMeta()`

### API Access
1. Client calls API endpoint
2. Controller validates request
3. Service processes data
4. Response returned as JSON

## Common File Combinations

### Basic Implementation
- `HasSeo.php` (trait)
- `seo-tags.blade.php` (component)
- Configuration setup

### Full Admin Integration
- `HasSeo.php` (trait)
- `seo-tags.blade.php` (component)
- `seo-form.blade.php` (form partial)
- `SeoService.php` (for saving)

### API Integration
- `SeoMetaController.php` (API controller)
- `StoreSeoMetaRequest.php` (validation)
- `UpdateSeoMetaRequest.php` (validation)
- `api.php` (routes)

### Custom Implementation
- `SeoService.php` (manual control)
- `Seo.php` (facade for clean syntax)
- `helpers.php` (helper functions)

## Support & Resources

### Internal Documentation
- Start with [INSTALLATION.md](INSTALLATION.md)
- Reference [QUICK_REFERENCE.md](QUICK_REFERENCE.md) for quick lookups
- Read [USAGE_EXAMPLES.md](USAGE_EXAMPLES.md) for detailed examples
- Consult [README.md](README.md) for complete documentation

### External Resources
- [Open Graph Protocol](https://ogp.me/)
- [Twitter Cards](https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards)
- [Schema.org](https://schema.org/)
- [Google Search Central](https://developers.google.com/search/docs)

### Testing Tools
- [Facebook Debugger](https://developers.facebook.com/tools/debug/)
- [Twitter Card Validator](https://cards-dev.twitter.com/validator)
- [LinkedIn Post Inspector](https://www.linkedin.com/post-inspector/)

## Version Information

- **Created:** 2026-02-08
- **Version:** 1.0.0
- **Total Files:** 26
- **Total Lines:** ~5,300
- **Status:** Production Ready

---

Navigate to specific sections using the links above, or start with [INSTALLATION.md](INSTALLATION.md) if you're setting up the module for the first time.
