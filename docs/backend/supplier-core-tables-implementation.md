# Supplier Core Tables - Implementation Summary

**Date:** 2025-12-20
**Status:** Completed

## Overview

This document summarizes the implementation of the core supplier tables for the AI Content Automation system, based on the requirements in `ai-content-automation-requirements.md`.

## Created Files

### Migrations

1. **`2025_12_20_000500_create_suppliers_table.php`**
   - Main supplier entity table
   - ULID-based unique identifiers
   - Integration with ERP and PrestaShop systems

2. **`2025_12_20_000500_create_supplier_sources_table.php`**
   - Data sources configuration per supplier
   - Support for: website, ftp, file, api
   - Trust levels and priority management

3. **`2025_12_20_000500_create_supplier_source_options_table.php`**
   - Dynamic key-value configuration storage
   - Type-aware option handling (string, url, json, path, integer, boolean)
   - Unique constraint on source_id + option_key

### Models

1. **`app/Models/Supplier/Supplier.php`**
   - Complete Eloquent model with HasUid trait
   - Relationships: hasMany sources
   - Scopes: active, byErpId, byCode, search
   - Helpers: isActive(), getDisplayNameAttribute()

2. **`app/Models/Supplier/SupplierSource.php`**
   - Complete Eloquent model with HasUid trait
   - Constants for source types and trust levels
   - Relationships: belongsTo supplier, hasMany options
   - Scopes: active, ofType, withTrustLevel, byPriority, forSupplier
   - Rich helpers for type checking and option management
   - Methods: getOption(), setOption(), getOptionsArray(), markAsAccessed()

3. **`app/Models/Supplier/SupplierSourceOption.php`**
   - Complete Eloquent model (no UID - simple pivot-like table)
   - Type constants for all supported data types
   - Relationship: belongsTo source
   - Scopes: forSource, byKey, required, ofType
   - Smart helpers: getParsedValue(), setValue(), validateValue(), isValidJson()

### Updated Files

1. **`app/Library/Traits/HasUid.php`**
   - Updated to use ULID instead of uniqid()
   - Now generates proper 26-character ULID identifiers
   - Uses `Str::ulid()->toBase32()` for Laravel 12 compatibility

## Database Schema

### Table: `suppliers`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uid | char(26) | ULID unique identifier |
| label | varchar(255) | Supplier name |
| code | varchar(50) | Internal supplier code (unique) |
| erp_id | int (nullable) | ERP/Management system ID |
| supplier_id | int (nullable) | PrestaShop supplier ID |
| website_url | varchar(500) | Main website URL |
| is_active | boolean | Active status (default: true) |
| created_at | timestamp | |
| updated_at | timestamp | |

**Indexes:**
- Unique: uid, code
- Index: erp_id, supplier_id, is_active

### Table: `supplier_sources`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uid | char(26) | ULID unique identifier |
| supplier_id | bigint | FK to suppliers (cascade) |
| source_type | enum | website, ftp, file, api |
| label | varchar(255) | Descriptive name |
| description | text | Notes about source |
| trust_level | enum | high, medium, low (default: medium) |
| usage_notes | text | Usage restrictions |
| priority | int | Priority order (default: 1) |
| is_active | boolean | Active status (default: true) |
| last_accessed_at | timestamp | Last access time |
| created_at | timestamp | |
| updated_at | timestamp | |

**Indexes:**
- Unique: uid
- Foreign key: supplier_id → suppliers.id (cascade)
- Composite: (supplier_id, source_type), (supplier_id, is_active)
- Index: priority

### Table: `supplier_source_options`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| source_id | bigint | FK to supplier_sources (cascade) |
| option_key | varchar(100) | Configuration key |
| option_value | text | Configuration value |
| option_type | varchar(50) | string, url, json, path, integer, boolean |
| is_required | boolean | Required flag (default: false) |
| created_at | timestamp | |
| updated_at | timestamp | |

**Indexes:**
- Foreign key: source_id → supplier_sources.id (cascade)
- Unique: (source_id, option_key)
- Index: source_id

## Usage Examples

### Creating a Supplier with Web Source

```php
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierSource;

// Create supplier
$supplier = Supplier::create([
    'label' => 'Nike España',
    'code' => 'NIKE_ES',
    'erp_id' => 12345,
    'website_url' => 'https://www.nike.com/es',
    'is_active' => true,
]);

// Create web scraping source
$source = $supplier->sources()->create([
    'source_type' => SupplierSource::SOURCE_TYPE_WEBSITE,
    'label' => 'Web oficial Nike',
    'trust_level' => SupplierSource::TRUST_LEVEL_HIGH,
    'priority' => 1,
    'is_active' => true,
]);

// Configure source options
$source->setOption('base_url', 'https://www.nike.com/es', 'url', true);
$source->setOption('rate_limit', '30', 'integer', true);
$source->setOption('selectors', [
    'name' => 'h1.product-title',
    'description' => '.description-text'
], 'json', true);
```

### Querying Sources

```php
// Get all active sources for a supplier
$activeSources = $supplier->activeSources;

// Get website sources ordered by priority
$webSources = SupplierSource::active()
    ->ofType(SupplierSource::SOURCE_TYPE_WEBSITE)
    ->byPriority()
    ->get();

// Get source configuration
$baseUrl = $source->getOption('base_url');
$allOptions = $source->getOptionsArray();

// Validate and parse options
$selectors = $source->options()
    ->byKey('selectors')
    ->first()
    ->getParsedValue(); // Return decoded JSON array
```

### Using Scopes

```php
// Find active suppliers
$activeSuppliers = Supplier::active()->get();

// Search suppliers
$results = Supplier::search('nike')->get();

// Get high-trust website sources
$sources = SupplierSource::active()
    ->ofType('website')
    ->withTrustLevel('high')
    ->byPriority()
    ->get();
```

## Testing

All models, relationships, and helper methods have been tested successfully using Laravel Tinker:

```bash
php artisan tinker
```

Test results confirmed:
- ✓ ULID generation (26 characters)
- ✓ Supplier creation and retrieval
- ✓ Source creation with relationships
- ✓ Option creation and type handling
- ✓ Helper methods (isActive, isWebsite, getOption, etc.)
- ✓ Display name attributes
- ✓ JSON option parsing
- ✓ All scopes functioning correctly

## Migration Status

```bash
php artisan migrate:status
```

All three migrations executed successfully:
- ✓ 2025_12_20_000500_create_suppliers_table
- ✓ 2025_12_20_000500_create_supplier_sources_table
- ✓ 2025_12_20_000500_create_supplier_source_options_table

## Code Quality

All files formatted with Laravel Pint:
```bash
vendor/bin/pint [files]
```

All code follows Laravel 12 conventions and project standards.

## Next Steps

According to `ai-content-automation-requirements.md`, the following related tables should be implemented next:

1. **supplier_categories** - Supplier-Category relationships
2. **supplier_products** - Supplier-Product relationships
3. **supplier_prompts** - AI prompts per supplier/category/source
4. **supplier_contents** - Generated AI content
5. **supplier_content_logs** - Content generation audit trail

## Related Documentation

- Source requirements: `docs/backend/ai-content-automation-requirements.md`
- Section 2: ESTRUCTURA DE DATOS (Tables 2.1 - 2.5)
