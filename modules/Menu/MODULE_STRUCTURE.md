# Menu Module - Structure Overview

Complete structure of the Menu module with all files created.

## Directory Structure

```
modules/Menu/
├── app/
│   ├── Console/
│   │   └── ClearMenuCacheCommand.php          # Artisan command to clear menu cache
│   ├── Helpers/
│   │   └── MenuHelper.php                     # Helper functions (menu(), render_menu())
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── MenuController.php             # Main controller for CRUD operations
│   │   └── Requests/
│   │       ├── StoreMenuRequest.php           # Validation for creating menus
│   │       ├── UpdateMenuRequest.php          # Validation for updating menus
│   │       ├── StoreMenuItemRequest.php       # Validation for creating items
│   │       └── UpdateMenuItemRequest.php      # Validation for updating items
│   ├── Models/
│   │   ├── Menu.php                           # Menu model with relationships
│   │   └── MenuItem.php                       # MenuItem model with polymorphic relations
│   ├── Policies/
│   │   ├── MenuPolicy.php                     # Authorization for menus
│   │   └── MenuItemPolicy.php                 # Authorization for menu items
│   ├── Providers/
│   │   ├── MenuServiceProvider.php            # Main service provider
│   │   ├── EventServiceProvider.php           # Event service provider
│   │   └── RouteServiceProvider.php           # Route service provider
│   ├── Services/
│   │   └── MenuService.php                    # Business logic service
│   └── View/
│       └── Components/
│           └── Menu.php                       # Blade component class
├── config/
│   └── config.php                             # Module configuration (locations, max_depth, cache)
├── database/
│   ├── factories/
│   │   ├── MenuFactory.php                    # Factory for testing
│   │   └── MenuItemFactory.php                # Factory for testing
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_menus_table.php
│   │   └── 2024_01_01_000002_create_menu_items_table.php
│   └── seeders/
│       └── MenuDatabaseSeeder.php             # Sample data seeder
├── resources/
│   ├── assets/
│   │   ├── js/
│   │   │   ├── app.js
│   │   │   └── menu-builder.js                # Alpine.js components for menu builder
│   │   └── sass/
│   │       ├── app.scss
│   │       └── menu-builder.scss              # Styles for menu builder interface
│   └── views/
│       ├── components/
│       │   ├── layouts/
│       │   │   └── master.blade.php           # Layout for module views
│       │   ├── menu.blade.php                 # Menu rendering component
│       │   └── menu-item.blade.php            # Menu item rendering component
│       ├── index.blade.php                    # List all menus
│       ├── create.blade.php                   # Create new menu
│       ├── edit.blade.php                     # Edit menu with drag & drop builder
│       └── v5.blade.php                       # Default view
├── routes/
│   ├── web.php                                # Web routes (CRUD + structure update)
│   └── api.php                                # API routes (if needed)
├── tests/
│   └── Unit/
│       └── MenuServiceTest.php                # Unit tests for MenuService
├── composer.json                              # Module dependencies
├── module.json                                # Module metadata
├── package.json                               # NPM dependencies
├── vite.config.js                             # Vite configuration
├── README.md                                  # Module documentation
├── INSTALLATION.md                            # Installation guide
├── USAGE_EXAMPLES.md                          # Usage examples
└── MODULE_STRUCTURE.md                        # This file
```

## Key Files Breakdown

### Models

#### Menu.php
- **Purpose**: Represents a menu container
- **Relationships**:
  - `items()` - Root level menu items only
  - `allItems()` - All menu items including nested
- **Scopes**:
  - `active()` - Get active menus
  - `byLocation()` - Filter by location
- **Fillable**: name, slug, location, status

#### MenuItem.php
- **Purpose**: Represents individual menu items
- **Relationships**:
  - `menu()` - Parent menu
  - `parent()` - Parent menu item
  - `children()` - Child menu items
  - `reference()` - Polymorphic relation to referenced models
- **Attributes**:
  - `full_url` - Computed URL (from reference or custom)
- **Methods**:
  - `hasChildren()` - Check if has children
  - `isActive()` - Check if current page
  - `hasActiveChild()` - Check if any child is active
- **Fillable**: menu_id, parent_id, title, url, target, icon, css_class, order, type, reference_id, reference_type

### Service Layer

#### MenuService.php
Core business logic for menu management:

**Methods**:
- `renderMenu(string $location, array $attributes)` - Render HTML menu
- `createMenu(array $data)` - Create new menu
- `updateMenu(Menu $menu, array $data)` - Update menu
- `deleteMenu(Menu $menu)` - Delete menu
- `addMenuItem(Menu $menu, array $data)` - Add menu item
- `updateMenuItem(MenuItem $item, array $data)` - Update menu item
- `deleteMenuItem(MenuItem $item)` - Delete menu item
- `updateMenuStructure(Menu $menu, array $items)` - Update from drag & drop
- `getAvailableReferences()` - Get linkable models (pages, posts, etc.)
- `clearMenuCache(?string $location)` - Clear menu cache

### Controllers

#### MenuController.php
Handles HTTP requests for menu management:

**Routes**:
- `GET /menus` - index() - List all menus
- `GET /menus/create` - create() - Show create form
- `POST /menus` - store() - Create menu
- `GET /menus/{menu}/edit` - edit() - Show edit form with builder
- `PUT /menus/{menu}` - update() - Update menu
- `DELETE /menus/{menu}` - destroy() - Delete menu
- `POST /menus/{menu}/structure` - updateStructure() - Update from drag & drop
- `POST /menus/{menu}/items` - storeItem() - Add menu item
- `PUT /menus/{menu}/items/{item}` - updateItem() - Update menu item
- `DELETE /menus/{menu}/items/{item}` - destroyItem() - Delete menu item

### Views

#### index.blade.php
- Lists all menus in a table
- Shows menu name, location, item count, status
- Actions: Edit, Delete

#### create.blade.php
- Form to create new menu
- Fields: Name, Slug, Location, Status

#### edit.blade.php
- Two-column layout:
  - Left: Menu settings + Add item form
  - Right: Menu builder with drag & drop
- Uses Alpine.js for interactivity
- Uses SortableJS for drag & drop
- Features:
  - Edit menu settings
  - Add menu items (custom, page, post, category, route)
  - Drag & drop to reorder
  - Nested items support
  - Delete items
  - Save structure

#### components/menu.blade.php
- Blade component for rendering menus
- Usage: `<x-menu::menu location="header" class="nav-menu" />`
- Automatically loads menu by location
- Includes nested items

#### components/menu-item.blade.php
- Recursive component for menu items
- Renders item with children
- Handles active states
- Supports icons

### JavaScript

#### menu-builder.js
Alpine.js components for the menu builder:

**menuItemForm()**:
- Handles adding new menu items
- Form validation
- AJAX submission
- Auto-fills title from reference selection

**menuBuilder(initialItems)**:
- Initializes SortableJS
- Renders menu items recursively
- Handles drag & drop
- Saves structure via AJAX
- Delete items confirmation

### Styles

#### menu-builder.scss
Styles for the menu builder interface:
- Drag handles
- Sortable states (ghost, chosen, drag)
- Menu item containers
- Action buttons
- Type badges
- Loading states
- Notifications
- Responsive adjustments

### Configuration

#### config/config.php
```php
[
    'locations' => [
        'header' => 'Header Menu',
        'footer' => 'Footer Menu',
        'sidebar' => 'Sidebar Menu',
        'mobile' => 'Mobile Menu',
    ],
    'max_depth' => 3,
    'cache_duration' => 3600,
]
```

### Database Schema

#### menus table
```sql
- id (bigint, primary key)
- name (string)
- slug (string, unique)
- location (string, nullable, indexed)
- status (boolean, default true)
- timestamps
- soft deletes
```

#### menu_items table
```sql
- id (bigint, primary key)
- menu_id (foreign key to menus)
- parent_id (foreign key to menu_items, nullable)
- title (string)
- url (string, nullable)
- target (string, default '_self')
- icon (string, nullable)
- css_class (string, nullable)
- order (integer, default 0)
- type (enum: custom, page, post, category, route)
- reference_id (bigint, nullable)
- reference_type (string, nullable)
- timestamps
- soft deletes
- index on (reference_id, reference_type)
```

### Helper Functions

#### MenuHelper.php
```php
render_menu(string $location, array $attributes): string
menu(string $location): ?Menu
```

### Commands

#### ClearMenuCacheCommand
```bash
php artisan menu:clear-cache [location]
```

### Policies

#### MenuPolicy.php
Permissions:
- viewAny, view, create, update, delete, restore, forceDelete

#### MenuItemPolicy.php
Permissions:
- create, update, delete, reorder

### Factories

#### MenuFactory.php
States:
- active(), inactive(), header(), footer()

#### MenuItemFactory.php
States:
- custom(), page(), withIcon(), newWindow(), child()

### Tests

#### MenuServiceTest.php
Tests:
- Menu CRUD operations
- Menu item CRUD operations
- Menu rendering
- Nested items
- Max depth
- Inactive menus
- Structure updates
- Available references

## File Count

- **PHP Files**: 26
- **Blade Views**: 7
- **JavaScript Files**: 2
- **SCSS Files**: 2
- **Migration Files**: 2
- **Configuration Files**: 1
- **Documentation Files**: 3
- **Test Files**: 1

**Total**: 44+ files

## Dependencies

### Backend
- Laravel 10+
- PHP 8.1+
- Laravel Modules package

### Frontend
- Alpine.js 3.x
- SortableJS 1.15+
- Tailwind CSS (for styling)

### Optional
- Font Awesome (for icons)
- Bootstrap (alternative styling)

## Features Implemented

✅ Full CRUD for menus
✅ Full CRUD for menu items
✅ Drag & drop menu builder
✅ Hierarchical/nested menu items
✅ Multiple menu locations
✅ Menu caching
✅ Polymorphic references (pages, posts, categories)
✅ Custom links
✅ Route-based links
✅ Icon support
✅ Target attribute (_self, _blank, etc.)
✅ CSS class support
✅ Active state detection
✅ Blade components
✅ Helper functions
✅ Service layer
✅ Form requests for validation
✅ Policies for authorization
✅ Factories for testing
✅ Unit tests
✅ Artisan command
✅ Soft deletes
✅ API endpoints for AJAX
✅ Comprehensive documentation

## Routes Summary

```
GET    /menus                          - List menus
GET    /menus/create                   - Create form
POST   /menus                          - Store menu
GET    /menus/{menu}/edit              - Edit form
PUT    /menus/{menu}                   - Update menu
DELETE /menus/{menu}                   - Delete menu
POST   /menus/{menu}/structure         - Update structure
POST   /menus/{menu}/items             - Add item
PUT    /menus/{menu}/items/{item}      - Update item
DELETE /menus/{menu}/items/{item}      - Delete item
```

All routes are protected with `auth` and `verified` middleware.

## Usage Summary

### Creating a Menu
```php
$menuService->createMenu([...]);
```

### Rendering a Menu
```blade
<x-menu::menu location="header" />
```

### Managing Cache
```bash
php artisan menu:clear-cache
```

---

**Module Path**: `/modules/Menu/`
**Namespace**: `Modules\Menu`
**Version**: 1.0.0
