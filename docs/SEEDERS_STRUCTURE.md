# Database Seeders Organization

This document describes the organized structure of database seeders by module/feature type.

## Directory Structure

```
database/seeders/
├── DatabaseSeeder.php          # Main orchestrator seeder
├── layout_spec.php             # Layout specifications
│
├── Core/                        # Core system data
│   ├── CategorySeeder.php
│   ├── CountrySeeder.php
│   ├── LangSeeder.php
│   ├── MailVariableSeeder.php
│   └── MediaFolderSeeder.php
│
├── Documents/                   # Document management system (16 files)
│   ├── DocumentConfigurationSeeder.php
│   ├── DocumentEmailLayoutSeeder.php
│   ├── DocumentEmailTemplateSeeder.php
│   ├── DocumentGroupSeeder.php
│   ├── DocumentLoadSeeder.php
│   ├── DocumentSettingsSeeder.php
│   ├── DocumentSourceSeeder.php
│   ├── DocumentStatusSeeder.php
│   ├── DocumentStatusTransitionSeeder.php
│   ├── DocumentSyncSeeder.php
│   ├── DocumentTypeSeeder.php
│   ├── DocumentUploadTypeSeeder.php
│   ├── DocumentValidationConditionSeeder.php
│   ├── DocumentValidatorGroupConfigurationSeeder.php
│   ├── DocumentValidatorGroupSeeder.php
│   └── StageEmailActionSeeder.php
│
├── Email/                       # Email templates and configurations (5 files)
│   ├── EmailTemplateLayoutSeeder.php
│   ├── EmailTemplateSeeder.php
│   ├── MailVariableSeeder.php
│   ├── MigrateDocumentEmailTemplatesSeeder.php
│   └── MigrateEmailTemplateTranslationsSeeder.php
│
├── Warehouse/                   # Warehouse and inventory management (11 files)
│   ├── FloorSeeder.php
│   ├── InventorySlotSeeder.php
│   ├── StandSeeder.php
│   ├── StandStyleSeeder.php
│   ├── WarehouseExampleSeeder.php
│   ├── WarehouseLayoutSeeder.php
│   ├── WarehouseLocationConditionSeeder.php
│   ├── WarehouseLocationStyleSeeder.php
│   ├── WarehouseSeeder.php
│   ├── WarehouseSeedersV2.php
│   └── WarehouseShopSeeder.php
│
├── Suppliers/                   # Supplier management (6 files)
│   ├── SupplierAutomationSettingSeeder.php
│   ├── SupplierPromptSeeder.php
│   ├── SupplierSeeder.php
│   ├── SupplierSourceOptionSeeder.php
│   ├── SupplierSourceSeeder.php
│   └── SupplierSourceTemplateSeeder.php
│
├── Helpdesk/                    # Support ticketing system (10 files)
│   ├── ConversationViewSeeder.php
│   ├── HelpCenterSeeder.php
│   ├── HelpdeskCannedReplySeeder.php
│   ├── HelpdeskConversationStatusSeeder.php
│   ├── HelpdeskGroupSeeder.php
│   ├── HelpdeskTicketCategorySeeder.php
│   ├── HelpdeskTicketSlaPolicySeeder.php
│   ├── HelpdeskTicketStatusSeeder.php
│   ├── TicketPermissionsSeeder.php
│   └── TicketViewSeeder.php
│
├── Returns/                     # Returns and warranty management (7 files)
│   ├── ProductReturnRulesSeeder.php
│   ├── ReturnPolicySeeder.php
│   ├── ReturnReasonSeeder.php
│   ├── ReturnStatesSeeder.php
│   ├── ReturnStatusSeeder.php
│   ├── ReturnTypeSeeder.php
│   └── WarrantySystemSeeder.php
│
├── Permissions/                 # Role-based access control (2 files)
│   ├── CompleteRolesAndPermissionsSeeder.php
│   └── RolesAndUsersSeeder.php
│
├── Campaigns/                   # Marketing campaigns (2 files)
│   ├── CampaignSeeder.php
│   └── CampaignTemplateSeeder.php
│
├── Locations/                   # Location-based data (3 files)
│   ├── Coruna1LocationsSeeder.php
│   ├── Coruna2LocationsSeeder.php
│   └── layout_spec.php
│
├── Components/                  # System components (2 files)
│   ├── ComponentSupplierManagementSeeder.php
│   └── ComponentSystemSeeder.php
│
└── Webhooks/                    # Webhook integrations (1 file)
    └── WebhookEventCatalogSeeder.php
```

## Namespace Structure

Each seeder folder has its own namespace for proper organization:

```php
// Document folder
namespace Database\Seeders\Documents;

// Suppliers folder
namespace Database\Seeders\Suppliers;

// Warehouse folder
namespace Database\Seeders\Warehouse;

// Email folder
namespace Database\Seeders\Email;

// And so on...
```

## Seeding Order (Execution Phases)

The `DatabaseSeeder.php` orchestrates seeding in 8 phases to handle dependencies correctly:

### PHASE 1: Foundational (No dependencies)
- Core system data that other seeders depend on
- Languages, countries, categories, webhook events, mail variables
- **Seeders:** `LangSeeder`, `CountrySeeder`, `CategorySeeder`, etc.

### PHASE 2: Warehouse Infrastructure
- Warehouse setup including locations, styles, and example data
- **Seeders:** `WarehouseLocationStyleSeeder`, `WarehouseExampleSeeder`, etc.

### PHASE 3: Document System
- Document configuration and catalog data
- Document statuses, sources, and transitions
- Depends on: PHASE 1

### PHASE 4: Return System
- Return policies and related configurations
- Depends on: PHASE 1

### PHASE 5: Helpdesk System
- Ticket statuses, categories, groups, and SLA policies
- Depends on: PHASE 1

### PHASE 6: Supplier & Automation
- Supplier data and automation settings
- Depends on: PHASE 1-5

### PHASE 7: Roles & Users
- System roles and user assignments
- Depends on: All previous phases

### PHASE 8: Optional Features
- Marketing campaigns, complete permissions
- Can be enabled by uncommenting in DatabaseSeeder

## Usage Examples

### Running all seeders
```bash
php artisan db:seed
```

### Running specific seeder from organized folder
```bash
php artisan db:seed --class="Database\Seeders\Documents\DocumentStatusSeeder"
php artisan db:seed --class="Database\Seeders\Suppliers\SupplierSeeder"
php artisan db:seed --class="Database\Seeders\Warehouse\WarehouseExampleSeeder"
```

### Migrating and seeding fresh
```bash
php artisan migrate:fresh --seed
```

## Benefits of This Organization

✅ **Clarity** - Easy to find seeders by module/feature type
✅ **Maintenance** - Grouped seeders are easier to maintain
✅ **Scalability** - New seeders fit naturally into existing structure
✅ **Documentation** - Clear namespace and folder structure serves as documentation
✅ **Testing** - Easier to test individual seeder groups
✅ **Dependency Management** - Explicit phase-based execution order

## Total Seeder Count

- **Documents**: 16 seeders
- **Helpdesk**: 10 seeders
- **Warehouse**: 11 seeders
- **Suppliers**: 6 seeders
- **Returns**: 7 seeders
- **Core**: 5 seeders
- **Email**: 5 seeders
- **Locations**: 3 seeders
- **Permissions**: 2 seeders
- **Campaigns**: 2 seeders
- **Components**: 2 seeders
- **Webhooks**: 1 seeder

**Total: 70+ seeders across 11 organized categories**
