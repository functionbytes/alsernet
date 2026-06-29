# ERP Module Documentation

## Overview

The **ERP Module** provides comprehensive enterprise resource planning integration for Alsernet, enabling seamless synchronization between Oracle ERP systems and PrestaShop e-commerce platforms. The module architecture supports both legacy (V1) endpoints and a modern, performance-optimized V2 integration system.

### Key Features

- **Dual Integration Modes**: Legacy V1 endpoints and modern V2 architecture
- **Dual Query Approaches**: Eloquent ORM mode and Direct SQL mode for performance optimization
- **Price Validation System**: Comprehensive price tracking with approval workflows and historical auditing
- **Product Import Management**: Track and synchronize product data between Oracle and PrestaShop
- **Real-time Stock Sync**: Keep inventory synchronized across multiple platforms
- **Multi-Country Support**: Built-in country mapping for international operations
- **Comprehensive Audit Trail**: Full history tracking for price validations and product changes
- **Connection Management**: Monitor and manage ERP connections via UI dashboard

---

## Module Architecture

### V1 (Legacy) vs V2 (Modern)

The module maintains **backward compatibility** while introducing robust V2 architecture:

#### V1 Legacy Architecture
- Single endpoint controller for XML-based communication
- HTTP-based REST API with XML responses
- Basic client data retrieval and order management
- URL: `/api/erp/*`

#### V2 Modern Architecture
- Dual controller pairs: Eloquent ORM and Direct SQL modes
- RESTful JSON endpoints with comprehensive filtering
- Advanced querying with pagination and relationships
- URL: `/api/erp/v2/eloquent/*` and `/api/erp/v2/direct/*`

### Dual-Mode Query Strategy

**V2 provides two query modes** for flexibility:

```
API Request
    ├─ /erp/v2/eloquent/*    (ORM Mode - Rich relationships)
    └─ /erp/v2/direct/*      (Direct SQL Mode - High performance)
```

**Choose Eloquent for:**
- Small to medium datasets (< 1000 records)
- Complex relationships needed
- Data transformations required

**Choose Direct SQL for:**
- Large datasets (> 10,000 records)
- Maximum performance needed
- Simple, flat data retrieval

---

## File Structure

```
modules/Erp/
├── app/
│   ├── Console/Commands/
│   │   ├── ErpCheckCommand.php                  # Connection health check
│   │   ├── TestOracleConnectionCommand.php      # Oracle connection verification
│   │   └── V2/
│   │       ├── ClearProductImports.php          # Clear import cache
│   │       ├── ExtractOracleDDL.php             # Extract Oracle schema
│   │       ├── ImportProductsFromPrestashop.php # Import from PS database
│   │       ├── ShowImportStatistics.php         # Display import stats
│   │       ├── SyncProducts.php                 # Sync product catalog
│   │       ├── SyncSpecificPrices.php           # Sync price data
│   │       └── TestOracleConnection.php         # Test Oracle access
│   │
│   ├── Http/Controllers/
│   │   ├── Api/
│   │   │   ├── ErpController.php                # V1 Legacy endpoints
│   │   │   └── V2/
│   │   │       ├── Eloquent/                    # ORM-based controllers
│   │   │       │   ├── AlbaranController.php
│   │   │       │   ├── ArticuloController.php
│   │   │       │   ├── BonoController.php
│   │   │       │   ├── ClienteCatalogoController.php
│   │   │       │   ├── ClienteController.php
│   │   │       │   ├── PedidoClienteController.php
│   │   │       │   ├── StockController.php
│   │   │       │   └── ValeController.php
│   │   │       └── Direct/                      # Direct SQL controllers
│   │   │           ├── AlbaranController.php
│   │   │           ├── ArticuloController.php
│   │   │           ├── BonoController.php
│   │   │           ├── ClienteCatalogoController.php
│   │   │           ├── ClienteController.php
│   │   │           ├── PedidoClienteController.php
│   │   │           ├── StockController.php
│   │   │           └── ValeController.php
│   │   └── ErpSettingsController.php            # Settings management UI
│   │
│   ├── Models/V2/
│   │   ├── Core/
│   │   │   ├── PriceValidation.php              # Price tracking model
│   │   │   ├── PriceValidationHistory.php       # Audit trail model
│   │   │   ├── ProductImport.php                # Product sync model
│   │   │   ├── ProductImportTag.php             # Tagging system
│   │   │   └── ScheduledPriceValidation.php     # Scheduled validations
│   │   └── Oracle/                              # Oracle ERP models
│   │       ├── Articulo/                        # Article/Product
│   │       ├── Albaran/                         # Delivery notes
│   │       ├── Bono/                            # Discounts
│   │       ├── Catalogo/                        # Catalogs
│   │       ├── Cliente/                         # Customers
│   │       ├── Cobro/                           # Collections
│   │       ├── Factura/                         # Invoices
│   │       ├── Lote/                            # Batches
│   │       ├── Mlog/                            # Change logs
│   │       ├── Pago/                            # Payments
│   │       ├── Pedido/                          # Orders
│   │       ├── Precio/                          # Pricing
│   │       ├── Promocion/                       # Promotions
│   │       ├── Stock/                           # Inventory
│   │       └── (other Oracle tables)
│   │
│   ├── Providers/
│   │   └── ErpServiceProvider.php               # Service provider
│   │
│   └── Services/Integrations/
│       ├── ErpService.php                       # ERP HTTP client
│       └── GestionPriceService.php              # Price management
│
├── config/
│   ├── erp.php                                  # ERP module settings
│   └── database.php                             # Oracle connection config
│
├── database/
│   ├── migrations/V2/                           # V2 database schema
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2025_12_30_000001_create_price_validations_table.php
│   │   ├── 2025_12_30_000002_create_price_validation_history_table.php
│   │   ├── 2025_12_30_000003_create_scheduled_price_validations_table.php
│   │   ├── 2025_12_30_000004_create_product_imports_table.php
│   │   └── 2025_12_30_000005_create_product_import_tags_table.php
│   └── seeders/
│
├── routes/
│   ├── api.php                                  # API routes (v1 & v2)
│   └── web.php                                  # Web routes (settings)
│
├── .env.example                                 # Environment variables template
├── README.md                                    # This documentation
└── composer.json                                # Module dependencies
```

---

## Environment Configuration

### Required Environment Variables

The ERP Module is fully encapsulated with its own database configuration managed through the **ErpServiceProvider**. All Oracle database variables are automatically registered when the module is loaded.

```bash
# ERP Service Connection
ERP_URL=https://your-erp-system.com

# Oracle Database Connection (GESTCENT System)
ORACLE_HOST=223.1.1.8
ORACLE_PORT=1521
ORACLE_DATABASE=GESTCENT
ORACLE_SERVICE_NAME=GESTCENT
ORACLE_USERNAME=your_oracle_user
ORACLE_PASSWORD=your_oracle_password
ORACLE_CHARSET=AL32UTF8
ORACLE_SCHEMA=DEVELOPER
ORACLE_SERVER_VERSION=11g
ORACLE_LOAD_BALANCE=yes
ORACLE_ENABLED=false

# PrestaShop Integration
PRESTASHOP_ENABLED=false
DB_PRESTASHOP_DRIVER=mysql
DB_PRESTASHOP_HOST=prestashop-db.example.com
DB_PRESTASHOP_PORT=3306
DB_PRESTASHOP_DATABASE=prestashop
DB_PRESTASHOP_USERNAME=ps_user
DB_PRESTASHOP_PASSWORD=secure_password
```

> **Note:** Copy variables from `modules/Erp/.env.example` to your main `.env` file.

### Configuration Files

#### config/erp.php - ERP Module Settings

```php
return [
    'url_erp' => env('ERP_URL'),

    // Payment methods mapping
    'payment_cashondelivery' => 1,
    'payment_creditcard' => 7,
    'payment_bizum' => 8,
    'payment_paypal' => 10,
    // ... more payment types

    'oracle' => [
        'enabled' => env('ORACLE_ENABLED', false),
        'connection' => 'oracle',
    ],

    'prestashop' => [
        'enabled' => env('PRESTASHOP_ENABLED', false),
        'connection' => 'prestashop',
    ],

    'price_validation' => [
        'queue' => 'default',
        'timeout' => 300,
        'retries' => 3,
    ],

    'country_mapping' => [
        6 => 1,   // Spain
        15 => 2,  // Portugal
        8 => 3,   // France
        1 => 4,   // Germany
        10 => 5,  // Italy
        2 => 6,   // Austria
    ],
];
```

#### config/database.php - Oracle Connection Configuration

The ERP module encapsulates its Oracle database connection in a dedicated configuration file:

```php
return [
    'connections' => [
        'oracle' => [
            'driver' => 'oracle',
            'host' => env('ORACLE_HOST', '223.1.1.8'),
            'port' => env('ORACLE_PORT', '1521'),
            'database' => env('ORACLE_DATABASE', 'GESTCENT'),
            'service_name' => env('ORACLE_SERVICE_NAME', 'GESTCENT'),
            'username' => env('ORACLE_USERNAME', ''),
            'password' => env('ORACLE_PASSWORD', ''),
            'charset' => env('ORACLE_CHARSET', 'AL32UTF8'),
            'prefix' => '',
            'prefix_schema' => env('ORACLE_SCHEMA', 'DEVELOPER'),
            'server_version' => env('ORACLE_SERVER_VERSION', '11g'),
            'load_balance' => env('ORACLE_LOAD_BALANCE', 'yes'),
            'pooled' => true,
            'options' => [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            ],
        ],
    ],
];
```

**Automatic Registration:** This configuration is automatically merged into Laravel's `config('database')` by the **ErpServiceProvider** during module initialization, making the `oracle` connection available application-wide.

---

## Database Models

### Core Application Models

#### ProductImport - Product Synchronization Tracking

Tracks synchronization of products between PrestaShop and Oracle ERP.

```php
// Query examples
ProductImport::where('sync_pending', true)
    ->whereHas('importable')
    ->with('tags')
    ->get();

// Mark as synced
$import->markAsSynced();

// Tagging operations
$import->addTag('featured');
$import->removeTag('promotional');
$import->hasTag('featured');  // boolean

// Sync to PrestaShop
$import->syncToPrestashop();
```

**Key Methods:**
- `isSimpleProduct()`, `isCombination()`
- `syncToPrestashop()`
- `markAsSyncPending()`, `markAsSynced()`
- `addTag()`, `removeTag()`, `hasTag()`

#### PriceValidation - Price Change Tracking

Comprehensive price validation system with approval workflows.

```php
// Create validation
$validation = PriceValidation::create([
    'id_product' => 123,
    'old_price' => 49.99,
    'new_price' => 79.99,
    'difference_percentage' => 60,
    'priority' => 'high',
    'status' => 'pending',
]);

// Approval workflow
$validation->approve(auth()->user()->name, 'Market analysis OK');
$validation->reject(auth()->user()->name, 'Price too high');
$validation->adjustPrice(69.99, auth()->user()->name);
$validation->markAsApplied();

// View history
$validation->history()->get();

// Query examples
PriceValidation::pending()->highPriority()->get();
PriceValidation::approved()->byCountry(1)->get();
```

**Statuses:** `pending`, `approved`, `adjusted`, `rejected`, `applied`

**Priorities:** `low`, `medium`, `high`, `critical` (auto-calculated by percentage)

---

## API Endpoints

### V1 Legacy Endpoints (Backward Compatible)

Base URL: `/api/erp`

```
POST /recuperarclienteerp              # Get client by ID
POST /recuperarpedidoscliente          # Get client orders
POST /recuperarpedido                  # Get order details
POST /recuperarcatalogosclienteerp     # Get client catalogs
POST /guardardatosclienteerp           # Save client data
POST /suscribircatalogosporeamilerp    # Subscribe to catalogs
```

### V2 Modern Endpoints

#### Eloquent Mode (ORM-based, rich relationships)

Base URL: `/api/erp/v2/eloquent`

```
GET|POST /clientes/                    # Client list/create
PUT      /clientes/                    # Update client LOPD
GET      /articulos/                   # Article list
GET      /albaranes/                   # Delivery notes
GET      /bonos/                       # Bonuses/Discounts
GET      /stock/                       # Inventory
GET      /vales/                       # Vouchers
GET      /pedidos/                     # Orders
GET      /catalogo/                    # Catalogs
```

#### Direct SQL Mode (High performance)

Base URL: `/api/erp/v2/direct`

Same endpoints as Eloquent, but using optimized direct SQL queries.

### Query Parameters

```
?page=1              # Page number
?per_page=50         # Records per page
?sort=id             # Sort column
?order=asc           # Sort direction
?filter[field]=value # Filter parameters
```

### Response Format

```json
{
  "status": "success",
  "data": [
    {"id": 123, "name": "Product Name", "price": 99.99}
  ],
  "pagination": {
    "total": 500,
    "per_page": 20,
    "current_page": 1,
    "last_page": 25
  }
}
```

---

## Service Classes

### ErpService - HTTP Communication

```php
use Modules\Erp\Services\Integrations\ErpService;

$erp = app(ErpService::class);
// or use alias: $erp = app('erp');

// GET request
$data = $erp->get('endpoint', ['param' => 'value']);

// POST request
$response = $erp->post('endpoint', ['key' => 'value']);

// Check connection
$isConnected = $erp->checkConnection();
```

### GestionPriceService - Price Management

```php
use Modules\Erp\Services\Integrations\GestionPriceService;

$priceService = app(GestionPriceService::class);

// Validate price
$validation = $priceService->validatePrice(
    productId: 123,
    newPrice: 79.99,
    countryId: 1
);

// Get history
$history = $priceService->getPriceHistory(productId: 123);
```

---

## Console Commands

### erp:check
Test ERP connection and system health.

```bash
php artisan erp:check
```

### erp:test-oracle-connection
Verify Oracle database connectivity.

```bash
php artisan erp:test-oracle-connection
```

### products:sync
Synchronize products from external API.

```bash
php artisan products:sync \
    --per-page=200 \
    --start-page=1 \
    --max-pages=10 \
    --update-existing
```

Options:
- `--per-page` - Records per request (default: 100)
- `--start-page` - Starting page (default: 1)
- `--max-pages` - Maximum pages to process
- `--update-existing` - Update existing products

### products:sync-specific-prices
Synchronize price data and create validations.

```bash
php artisan products:sync-specific-prices \
    --batch-size=50 \
    --countries=ES,PT \
    --limit=1000
```

### products:import-from-prestashop
Import product data from PrestaShop database.

```bash
php artisan products:import-from-prestashop \
    --category=5 \
    --limit=500 \
    --force
```

### products:show-import-statistics
Display import statistics.

```bash
php artisan products:show-import-statistics
```

Output example:
```
Total Imports: 5,432
Simple Products: 3,200
Combinations: 2,232
Pending Sync: 156
Sync Errors: 12
Last Sync: 2025-01-12 10:30:00
```

### products:clear-imports
Clear import table.

```bash
php artisan products:clear-imports --force
```

Options:
- `--force` - Skip confirmation
- `--except-pending` - Keep pending imports

### erp:extract-oracle-ddl
Extract Oracle schema definition.

```bash
php artisan erp:extract-oracle-ddl \
    --table=ARTICULOS \
    --output=schema.sql
```

---

## Database Migrations

### V2 Migration Files

All migrations are located in `database/migrations/V2/`

#### create_price_validations_table
Price validation tracking table with fields for old/new prices, differences, status, and audit info.

#### create_price_validation_history_table
Audit trail for price changes with action tracking, user tracking, and metadata.

#### create_scheduled_price_validations_table
Scheduled processing table for batch validation operations.

#### create_product_imports_table
Product synchronization tracking with polymorphic relationships to PrestaShop products/combinations.

#### create_product_import_tags_table
Flexible tagging system for organizing imports with product count tracking.

---

## Usage Examples

### Example 1: Price Validation Workflow

```php
use Modules\Erp\Models\V2\Core\PriceValidation;

// Create validation
$validation = PriceValidation::create([
    'id_product' => 123,
    'old_price' => 49.99,
    'new_price' => 79.99,
    'difference_percentage' => 60,
    'priority' => PriceValidation::calculatePriority(60),
    'status' => 'pending',
]);

// Review and approve
$validation->approve(
    approvedBy: auth()->user()->name,
    notes: 'Market analysis confirms price increase'
);

// Apply to system
$validation->markAsApplied();

// View complete history
$validation->history()->orderBy('created_at', 'desc')->get();
```

### Example 2: Product Synchronization

```php
use Modules\Erp\Models\V2\Core\ProductImport;

// Get pending products
$pending = ProductImport::pendingSync()
    ->with('importable')
    ->limit(100)
    ->get();

foreach ($pending as $import) {
    try {
        $import->syncToPrestashop();
        $import->markAsSynced();
    } catch (\Exception $e) {
        $import->markAsSyncPending(['error' => $e->getMessage()]);
    }
}
```

### Example 3: Tagged Product Queries

```php
// Products with specific tag
$featured = ProductImport::withTag('featured')->get();

// Products with multiple tags
$promo = ProductImport::withAllTags(['seasonal', 'discount'])->get();

// Add tags to product
$import = ProductImport::find(1);
$import->addTag('new-arrival');
$import->addTag('limited-stock');
```

### Example 4: Eloquent Mode Query

```php
$response = \Http::get('/api/erp/v2/eloquent/articulos', [
    'page' => 1,
    'per_page' => 50,
    'sort' => 'nombre',
    'order' => 'asc',
]);

$articulos = $response->json('data');
```

### Example 5: Direct SQL Mode Query (Performance)

```php
$response = \Http::get('/api/erp/v2/direct/stock', [
    'page' => 1,
    'per_page' => 500,
]);

$stock = $response->json('data');
```

---

## Best Practices

1. **Choose Right Query Mode**
   - Use Eloquent for < 1000 records with relationships
   - Use Direct SQL for > 10,000 records for performance

2. **Handle Failures Gracefully**
   ```php
   try {
       $result = $erp->get('endpoint');
   } catch (\Exception $e) {
       \Log::error('ERP request failed', ['error' => $e->getMessage()]);
   }
   ```

3. **Cache Frequently Used Data**
   ```php
   $clients = \Cache::remember('erp:clients', 3600, fn() => $erp->get('clientes'));
   ```

4. **Use Query Scopes**
   ```php
   // Good
   PriceValidation::pending()->highPriority()->get();

   // Instead of
   PriceValidation::where('status', 'pending')->where('priority', 'high')->get();
   ```

5. **Batch Large Operations**
   ```php
   ProductImport::chunkById(500, function ($imports) {
       foreach ($imports as $import) {
           $import->syncToPrestashop();
       }
   });
   ```

6. **Use Transactions for Critical Operations**
   ```php
   \DB::transaction(function () {
       $validation->approve(auth()->user()->name);
       $erp->post('precios/update', [...]);
   });
   ```

---

## Troubleshooting

### Connection Timeout
**Symptom:** Commands timeout connecting to Oracle
```bash
php artisan erp:test-oracle-connection
# Check: DB_ORACLE_HOST, DB_ORACLE_PORT, firewall rules
```

### PrestaShop Database Not Found
**Symptom:** `General error: 1030 Got error from storage engine`
```bash
mysql -u prestashop_user -p prestashop -e "SELECT COUNT(*) FROM ps_product;"
# Check: Database exists, user permissions
```

### Price Validation Not Creating
**Symptom:** Call to undefined method `PriceValidation::create()`
```bash
php artisan migrate --path=modules/Erp/database/migrations/V2
# Verify: Migration files executed
```

### Sync Errors in Product Import
**Symptom:** Products not syncing, `sync_errors` field populated
```php
$import = ProductImport::find(1);
dd($import->sync_errors);  // Review specific error
$import->importable;        // Verify relation exists
```

### API Returns 500 Error
```bash
tail -f storage/logs/laravel.log
php artisan tinker
>>> DB::connection('oracle')->getPdo();  // Test Oracle
>>> DB::connection('prestashop')->getPdo();  // Test PrestaShop
```

### Commands Not Found
```bash
php artisan cache:clear
php artisan config:cache
# Verify: ErpServiceProvider in bootstrap/providers.php
```

---

## Payment Methods Supported

1. Cash on Delivery (COD) - ID: 1
2. Wire Transfer - ID: 3
3. Credit Card - ID: 7
4. Redsys - ID: 22
5. Bizum - ID: 8
6. Google Pay - ID: 26
7. Apple Pay - ID: 27
8. PayPal - ID: 10
9. Finance/Installments - ID: 11
10. Sequra - ID: 100000101
11. Alsernet Finance - ID: 5
12. Online Transfer - ID: 25
13. BanLendSmart - ID: 28

---

## Settings UI Routes

Manage ERP configuration through web interface:

```
GET  /settings/erp/              # View settings
GET  /settings/erp/edit          # Edit form
PUT  /settings/erp/update        # Save changes
POST /settings/erp/check-connection  # Test connection
POST /settings/erp/clear-cache   # Clear cache
GET  /settings/erp/get-stats     # View statistics
```

---

**Last Updated:** 2025-01-12
**Module Version:** 2.0
**Status:** Production Ready
