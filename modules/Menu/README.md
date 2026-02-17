# Menu Module

A comprehensive menu management system for Laravel applications with drag & drop functionality, hierarchical structure support, and dynamic menu rendering.

## Features

- **Full CRUD Operations**: Create, read, update, and delete menus and menu items
- **Hierarchical Structure**: Support for nested menu items with unlimited depth (configurable)
- **Drag & Drop**: Interactive menu builder with drag & drop functionality
- **Multiple Menu Locations**: Support for multiple menu locations (header, footer, sidebar, etc.)
- **Dynamic References**: Link menu items to pages, posts, categories, or custom URLs
- **Caching**: Built-in caching for optimized performance
- **Blade Components**: Easy-to-use Blade components for menu rendering
- **Helper Functions**: Convenient helper functions for quick menu access

## Installation

The module is already installed. To run migrations and seed data:

```bash
php artisan migrate
php artisan module:seed Menu
```

## Configuration

Configuration file: `/modules/Menu/config/config.php`

```php
return [
    'locations' => [
        'header' => 'Header Menu',
        'footer' => 'Footer Menu',
        'sidebar' => 'Sidebar Menu',
        'mobile' => 'Mobile Menu',
    ],
    'max_depth' => 3,
    'cache_duration' => 3600,
];
```

## Usage

### Using Blade Components

The simplest way to render a menu:

```blade
<x-menu::menu location="header" class="nav-menu" />
```

### Using Helper Functions

```php
// Get a menu by location
$menu = menu('header');

// Render a menu
echo render_menu('header', ['class' => 'nav-menu']);
```

### Using the Service

```php
use Modules\Menu\Services\MenuService;

$menuService = app(MenuService::class);

// Render a menu
$html = $menuService->renderMenu('header', ['class' => 'nav-menu']);

// Create a menu
$menu = $menuService->createMenu([
    'name' => 'My Menu',
    'slug' => 'my-menu',
    'location' => 'header',
    'status' => true,
]);

// Add a menu item
$item = $menuService->addMenuItem($menu, [
    'title' => 'Home',
    'url' => '/',
    'type' => 'custom',
    'target' => '_self',
]);

// Update menu structure (from drag & drop)
$menuService->updateMenuStructure($menu, $itemsArray);
```

### Menu Builder Interface

Access the menu builder at: `/menus`

Features:
- List all menus
- Create new menus
- Edit menu settings
- Add/remove menu items
- Drag & drop to reorder items
- Create nested menu items

## API Endpoints

### Menus
- `GET /menus` - List all menus
- `GET /menus/create` - Show create form
- `POST /menus` - Create a new menu
- `GET /menus/{menu}/edit` - Show edit form
- `PUT /menus/{menu}` - Update a menu
- `DELETE /menus/{menu}` - Delete a menu

### Menu Items
- `POST /menus/{menu}/items` - Add a menu item
- `PUT /menus/{menu}/items/{item}` - Update a menu item
- `DELETE /menus/{menu}/items/{item}` - Delete a menu item
- `POST /menus/{menu}/structure` - Update menu structure (drag & drop)

## Models

### Menu Model

```php
use Modules\Menu\Models\Menu;

// Relationships
$menu->items(); // Get root items only
$menu->allItems(); // Get all items

// Scopes
Menu::active()->get();
Menu::byLocation('header')->first();
```

### MenuItem Model

```php
use Modules\Menu\Models\MenuItem;

// Relationships
$item->menu(); // Parent menu
$item->parent(); // Parent item
$item->children(); // Child items
$item->reference(); // Referenced model (polymorphic)

// Attributes
$item->full_url; // Get the full URL (considers reference)

// Methods
$item->hasChildren(); // Check if has children
$item->isActive(); // Check if current page
$item->hasActiveChild(); // Check if any child is active
```

## Menu Item Types

- **custom**: Custom link with manual URL
- **page**: Link to a Page model
- **post**: Link to a Post model
- **category**: Link to a Category model
- **route**: Link to a named route

## Styling

The menu builder uses Tailwind CSS classes. You can customize the appearance by:

1. Publishing the views:
```bash
php artisan vendor:publish --tag=menu-module-views
```

2. Modifying the views in `resources/views/modules/menu/`

## Frontend Integration

### Basic HTML Menu

```blade
<nav>
    <x-menu::menu location="header" class="main-nav" />
</nav>
```

### Customized Rendering

```blade
@php
    $menu = menu('header');
@endphp

@if($menu)
    <ul class="nav">
        @foreach($menu->items as $item)
            <li class="{{ $item->isActive() ? 'active' : '' }}">
                <a href="{{ $item->full_url }}" target="{{ $item->target }}">
                    @if($item->icon)
                        <i class="{{ $item->icon }}"></i>
                    @endif
                    {{ $item->title }}
                </a>

                @if($item->children->isNotEmpty())
                    <ul class="submenu">
                        @foreach($item->children as $child)
                            <li>
                                <a href="{{ $child->full_url }}">{{ $child->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
@endif
```

## Cache Management

The module automatically caches rendered menus. To clear the cache:

```php
use Modules\Menu\Services\MenuService;

$menuService = app(MenuService::class);
$menuService->clearMenuCache('header'); // Clear specific location
$menuService->clearMenuCache(); // Clear all menus
```

Cache is automatically cleared when:
- A menu is created, updated, or deleted
- Menu items are added, updated, or deleted
- Menu structure is changed

## Adding Custom Reference Types

To add support for linking to custom models:

1. Ensure your model has a `url` attribute or accessor
2. Update the `getAvailableReferences()` method in `MenuService.php`:

```php
// Products
if (class_exists(\Modules\Shop\Models\Product::class)) {
    $products = \Modules\Shop\Models\Product::where('status', 'published')
        ->select('id', 'name', 'slug')
        ->get()
        ->map(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->name,
                'type' => 'product',
                'reference_type' => \Modules\Shop\Models\Product::class,
            ];
        });

    $references['products'] = $products;
}
```

## Events

The module uses Laravel's soft deletes, so you can listen for these events:
- `Illuminate\Database\Events\ModelCreated`
- `Illuminate\Database\Events\ModelUpdated`
- `Illuminate\Database\Events\ModelDeleted`

## Database Structure

### menus table
- id
- name
- slug (unique)
- location (nullable)
- status (boolean)
- timestamps
- soft deletes

### menu_items table
- id
- menu_id (foreign key)
- parent_id (nullable, foreign key)
- title
- url (nullable)
- target (default: _self)
- icon (nullable)
- css_class (nullable)
- order (integer)
- type (enum: custom, page, post, category, route)
- reference_id (nullable)
- reference_type (nullable)
- timestamps
- soft deletes

## Requirements

- PHP 8.1+
- Laravel 10+
- Alpine.js (for interactive forms)
- SortableJS (for drag & drop)
- Tailwind CSS (for styling)

## License

This module is part of the InoQuaLab application.
