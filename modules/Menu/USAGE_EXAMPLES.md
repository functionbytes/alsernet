# Menu Module - Usage Examples

This document provides practical examples for using the Menu module in your Laravel application.

## Table of Contents

1. [Basic Setup](#basic-setup)
2. [Creating Menus](#creating-menus)
3. [Rendering Menus](#rendering-menus)
4. [Advanced Usage](#advanced-usage)
5. [Customization](#customization)

---

## Basic Setup

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Seed Sample Data (Optional)

```bash
php artisan module:seed Menu
```

This will create two sample menus:
- Header Menu (location: header)
- Footer Menu (location: footer)

---

## Creating Menus

### Via Admin Interface

1. Navigate to `/menus` in your browser
2. Click "Create Menu"
3. Fill in the form:
   - **Name**: Display name for admin
   - **Slug**: URL-friendly identifier
   - **Location**: Where the menu will appear
   - **Status**: Active/Inactive

### Programmatically

```php
use Modules\Menu\Services\MenuService;

$menuService = app(MenuService::class);

// Create a menu
$menu = $menuService->createMenu([
    'name' => 'Main Navigation',
    'slug' => 'main-nav',
    'location' => 'header',
    'status' => true,
]);

// Add menu items
$homeItem = $menuService->addMenuItem($menu, [
    'title' => 'Home',
    'url' => '/',
    'type' => 'custom',
    'target' => '_self',
    'icon' => 'fa fa-home',
]);

// Add a child item
$aboutItem = $menuService->addMenuItem($menu, [
    'title' => 'About Us',
    'url' => '/about',
    'type' => 'custom',
]);

$teamItem = $menuService->addMenuItem($menu, [
    'parent_id' => $aboutItem->id,
    'title' => 'Our Team',
    'url' => '/about/team',
    'type' => 'custom',
]);
```

---

## Rendering Menus

### Method 1: Blade Component (Recommended)

The simplest way to render a menu:

```blade
{{-- In your layout file (e.g., resources/views/layouts/app.blade.php) --}}
<nav class="main-navigation">
    <x-menu::menu location="header" class="nav-menu" />
</nav>

<footer>
    <x-menu::menu location="footer" class="footer-menu" />
</footer>
```

### Method 2: Helper Function

```blade
{{-- Using the helper function --}}
<nav>
    {!! render_menu('header', ['class' => 'main-nav']) !!}
</nav>
```

### Method 3: Direct Access

For more control over the rendering:

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
                            <li class="{{ $child->isActive() ? 'active' : '' }}">
                                <a href="{{ $child->full_url }}" target="{{ $child->target }}">
                                    {{ $child->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
@endif
```

---

## Advanced Usage

### Bootstrap Navigation

```blade
@php
    $menu = menu('header');
@endphp

@if($menu)
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    @foreach($menu->items as $item)
                        @if($item->children->isNotEmpty())
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ $item->hasActiveChild() ? 'active' : '' }}"
                                   href="#"
                                   role="button"
                                   data-bs-toggle="dropdown">
                                    {{ $item->title }}
                                </a>
                                <ul class="dropdown-menu">
                                    @foreach($item->children as $child)
                                        <li>
                                            <a class="dropdown-item {{ $child->isActive() ? 'active' : '' }}"
                                               href="{{ $child->full_url }}">
                                                {{ $child->title }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link {{ $item->isActive() ? 'active' : '' }}"
                                   href="{{ $item->full_url }}"
                                   target="{{ $item->target }}">
                                    @if($item->icon)
                                        <i class="{{ $item->icon }}"></i>
                                    @endif
                                    {{ $item->title }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
    </nav>
@endif
```

### Tailwind CSS Navigation

```blade
@php
    $menu = menu('header');
@endphp

@if($menu)
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex space-x-7">
                    @foreach($menu->items as $item)
                        @if($item->children->isNotEmpty())
                            <div class="relative group">
                                <button class="flex items-center space-x-1 font-semibold text-gray-700 hover:text-blue-500 transition">
                                    @if($item->icon)
                                        <i class="{{ $item->icon }}"></i>
                                    @endif
                                    <span>{{ $item->title }}</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-10">
                                    @foreach($item->children as $child)
                                        <a href="{{ $child->full_url }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white transition">
                                            {{ $child->title }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $item->full_url }}"
                               target="{{ $item->target }}"
                               class="flex items-center space-x-1 font-semibold text-gray-700 hover:text-blue-500 transition {{ $item->isActive() ? 'text-blue-500' : '' }}">
                                @if($item->icon)
                                    <i class="{{ $item->icon }}"></i>
                                @endif
                                <span>{{ $item->title }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </nav>
@endif
```

### Mobile Menu

```blade
@php
    $menu = menu('mobile');
@endphp

@if($menu)
    <div x-data="{ open: false }">
        <button @click="open = !open" class="p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <div x-show="open" @click.away="open = false" class="fixed inset-0 z-50 bg-gray-800 bg-opacity-75">
            <div class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg">
                <div class="p-4">
                    <button @click="open = false" class="mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <ul class="space-y-2">
                        @foreach($menu->items as $item)
                            <li>
                                <a href="{{ $item->full_url }}"
                                   class="block px-4 py-2 rounded hover:bg-gray-100 {{ $item->isActive() ? 'bg-blue-100 text-blue-600' : '' }}">
                                    @if($item->icon)
                                        <i class="{{ $item->icon }} mr-2"></i>
                                    @endif
                                    {{ $item->title }}
                                </a>

                                @if($item->children->isNotEmpty())
                                    <ul class="ml-4 mt-2 space-y-1">
                                        @foreach($item->children as $child)
                                            <li>
                                                <a href="{{ $child->full_url }}"
                                                   class="block px-4 py-2 text-sm rounded hover:bg-gray-100">
                                                    {{ $child->title }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif
```

---

## Customization

### Custom Menu Item Component

Create your own menu item component:

```blade
{{-- resources/views/components/nav-item.blade.php --}}
@props(['item'])

<li {{ $attributes->merge(['class' => 'nav-item']) }}>
    <a href="{{ $item->full_url }}"
       target="{{ $item->target }}"
       class="nav-link {{ $item->isActive() ? 'active' : '' }}"
       @if($item->css_class) class="{{ $item->css_class }}" @endif>

        @if($item->icon)
            <span class="nav-icon">
                <i class="{{ $item->icon }}"></i>
            </span>
        @endif

        <span class="nav-text">{{ $item->title }}</span>

        @if($item->children->isNotEmpty())
            <span class="nav-arrow">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </span>
        @endif
    </a>

    @if($item->children->isNotEmpty())
        <ul class="nav-submenu">
            @foreach($item->children as $child)
                <x-nav-item :item="$child" />
            @endforeach
        </ul>
    @endif
</li>
```

Usage:

```blade
@php
    $menu = menu('header');
@endphp

@if($menu)
    <ul class="nav">
        @foreach($menu->items as $item)
            <x-nav-item :item="$item" />
        @endforeach
    </ul>
@endif
```

### Adding Custom Reference Types

If you want to link menu items to custom models (e.g., Products):

1. Edit `MenuService.php`:

```php
public function getAvailableReferences(): array
{
    $references = [];

    // ... existing code ...

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

    return $references;
}
```

2. Update migration to add 'product' to type enum:

```php
$table->enum('type', ['custom', 'page', 'post', 'category', 'route', 'product'])->default('custom');
```

3. Ensure your Product model has a `url` attribute:

```php
public function getUrlAttribute()
{
    return route('products.show', $this->slug);
}
```

---

## Cache Management

```php
use Modules\Menu\Services\MenuService;

$menuService = app(MenuService::class);

// Clear cache for specific location
$menuService->clearMenuCache('header');

// Clear all menu caches
$menuService->clearMenuCache();
```

Or via Artisan:

```bash
# Clear specific location
php artisan menu:clear-cache header

# Clear all
php artisan menu:clear-cache
```

---

## Testing

```php
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;

// Using factories
$menu = Menu::factory()
    ->header()
    ->active()
    ->create();

$item = MenuItem::factory()
    ->custom()
    ->withIcon('fa fa-home')
    ->create([
        'menu_id' => $menu->id,
    ]);

// Create a nested structure
$parent = MenuItem::factory()->create(['menu_id' => $menu->id]);
$child = MenuItem::factory()->child($parent)->create();
```

---

For more information, see the [README.md](README.md) file.
