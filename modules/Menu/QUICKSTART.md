# Menu Module - Quick Start Guide

Get up and running with the Menu module in 5 minutes.

## 1. Run Migrations

```bash
php artisan migrate
```

This creates the `menus` and `menu_items` tables.

## 2. Seed Sample Data (Optional)

```bash
php artisan module:seed Menu
```

This creates:
- Header Menu with navigation items
- Footer Menu with footer links

## 3. Add Alpine.js & SortableJS

Add to your layout head (`resources/views/layouts/app.blade.php`):

```html
<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
```

## 4. Render a Menu

In your layout:

```blade
<header>
    <nav>
        <x-menu::menu location="header" class="main-nav" />
    </nav>
</header>

<footer>
    <x-menu::menu location="footer" class="footer-nav" />
</footer>
```

## 5. Access Admin Interface

Visit: `http://your-app.test/menus`

(Make sure you're logged in as an admin)

## That's it!

You now have:
- ✅ Menu management interface at `/menus`
- ✅ Drag & drop menu builder
- ✅ Menus rendering on your site
- ✅ Sample menus to work with

---

## Next Steps

### Customize Menu Locations

Edit `modules/Menu/config/config.php`:

```php
'locations' => [
    'header' => 'Header Menu',
    'footer' => 'Footer Menu',
    'sidebar' => 'Sidebar Menu',
    'mobile' => 'Mobile Menu',
    // Add your own locations
],
```

### Create a Menu Programmatically

```php
use Modules\Menu\Services\MenuService;

$menuService = app(MenuService::class);

$menu = $menuService->createMenu([
    'name' => 'Main Navigation',
    'slug' => 'main-nav',
    'location' => 'header',
    'status' => true,
]);

$menuService->addMenuItem($menu, [
    'title' => 'Home',
    'url' => '/',
    'type' => 'custom',
    'icon' => 'fa fa-home',
]);
```

### Style Your Menus

#### Bootstrap Example

```blade
@php
    $menu = menu('header');
@endphp

@if($menu)
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <ul class="navbar-nav">
            @foreach($menu->items as $item)
                <li class="nav-item">
                    <a class="nav-link {{ $item->isActive() ? 'active' : '' }}"
                       href="{{ $item->full_url }}">
                        {{ $item->title }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</nav>
@endif
```

#### Tailwind Example

```blade
@php
    $menu = menu('header');
@endphp

@if($menu)
<nav class="flex space-x-4">
    @foreach($menu->items as $item)
        <a href="{{ $item->full_url }}"
           class="text-gray-700 hover:text-blue-500 px-3 py-2 rounded {{ $item->isActive() ? 'text-blue-500 font-semibold' : '' }}">
            {{ $item->title }}
        </a>
    @endforeach
</nav>
@endif
```

---

## Common Tasks

### Add Navigation Link to Admin

```blade
{{-- In your admin sidebar --}}
<li>
    <a href="{{ route('menu.index') }}">
        <i class="fa fa-bars"></i>
        <span>Menus</span>
    </a>
</li>
```

### Clear Menu Cache

```bash
# Clear all menus
php artisan menu:clear-cache

# Clear specific location
php artisan menu:clear-cache header
```

### Link to Pages/Posts

In the menu builder:
1. Select "Page" or "Post" as type
2. Choose from dropdown
3. Title auto-fills
4. Click "Add Item"

---

## Troubleshooting

### Menu not showing?

Check:
1. ✅ Menu status is "Active"
2. ✅ Menu has a location assigned
3. ✅ Menu has items added
4. ✅ You're using correct location in template

### Drag & drop not working?

Check browser console for errors:
1. ✅ Alpine.js is loaded
2. ✅ SortableJS is loaded
3. ✅ No JavaScript errors

### Permission errors?

Make sure user has permissions:
```php
$user->givePermissionTo('menu.view');
$user->givePermissionTo('menu.create');
$user->givePermissionTo('menu.edit');
$user->givePermissionTo('menu.delete');
```

---

## Documentation

- 📖 [README.md](README.md) - Complete overview
- 🚀 [INSTALLATION.md](INSTALLATION.md) - Detailed installation
- 💡 [USAGE_EXAMPLES.md](USAGE_EXAMPLES.md) - Code examples
- 📋 [MODULE_STRUCTURE.md](MODULE_STRUCTURE.md) - File structure

---

## Quick Reference

### Helper Functions

```php
// Get a menu
$menu = menu('header');

// Render a menu
echo render_menu('header', ['class' => 'nav-menu']);
```

### Blade Component

```blade
<x-menu::menu location="header" class="nav-menu" />
```

### Service Methods

```php
$menuService = app(MenuService::class);

$menuService->createMenu([...]);
$menuService->addMenuItem($menu, [...]);
$menuService->updateMenuStructure($menu, $items);
$menuService->clearMenuCache('header');
```

### Model Usage

```php
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;

// Get active menus
$menus = Menu::active()->get();

// Get menu by location
$menu = Menu::byLocation('header')->first();

// Check if item has children
$item->hasChildren();

// Check if item is active page
$item->isActive();
```

---

**Happy Menu Building! 🎉**

For questions or issues, check the documentation files in this module.
