# Menu Module - Installation Guide

This guide will walk you through the complete installation and setup of the Menu module.

## Prerequisites

- Laravel 10+
- PHP 8.1+
- Composer
- NPM/Yarn (for frontend assets)

## Installation Steps

### Step 1: Module is Already Generated

The Menu module has been created using Laravel Modules package. The structure is already in place at:
```
/modules/Menu/
```

### Step 2: Run Migrations

Run the migrations to create the necessary database tables:

```bash
php artisan migrate
```

This will create two tables:
- `menus` - Stores menu definitions
- `menu_items` - Stores individual menu items

### Step 3: (Optional) Seed Sample Data

To create sample menus for testing:

```bash
php artisan module:seed Menu
```

This creates:
- **Header Menu** with navigation items
- **Footer Menu** with footer links

### Step 4: Add Frontend Dependencies

The menu builder requires Alpine.js and SortableJS. Add them to your layout:

#### Option A: Via CDN (Quick Setup)

Add to your layout file (e.g., `resources/views/layouts/app.blade.php`):

```blade
<head>
    <!-- ... other head content ... -->

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- SortableJS (for drag & drop) -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <!-- Menu Builder JS -->
    <script src="{{ asset('modules/menu/js/menu-builder.js') }}"></script>

    <!-- Menu Builder CSS -->
    <link rel="stylesheet" href="{{ asset('modules/menu/css/menu-builder.css') }}">
</head>
```

#### Option B: Via NPM (Production Setup)

```bash
npm install alpinejs sortablejs --save
```

Then import in your `resources/js/app.js`:

```javascript
import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

// Make Alpine available globally
window.Alpine = Alpine;
window.Sortable = Sortable;

Alpine.start();
```

### Step 5: Compile Assets (if using NPM)

If you installed via NPM, compile your assets:

```bash
npm run dev
# or for production
npm run build
```

### Step 6: Configure Menu Locations

Edit the config file to define your menu locations:

```bash
# Publish config (optional)
php artisan vendor:publish --tag=config

# Edit config/menu.php or modules/Menu/config/config.php
```

```php
return [
    'locations' => [
        'header' => 'Header Menu',
        'footer' => 'Footer Menu',
        'sidebar' => 'Sidebar Menu',
        'mobile' => 'Mobile Menu',
        // Add more locations as needed
    ],
    'max_depth' => 3,
    'cache_duration' => 3600,
];
```

### Step 7: Register the Module

Ensure the module is registered in `config/modules.php`. It should be auto-registered, but verify:

```php
// config/modules.php
return [
    // ...
    'scan' => [
        'enabled' => true,
        'paths' => [
            base_path('modules'),
        ],
    ],
];
```

### Step 8: Set Up Permissions (Optional)

If your application uses permissions/roles, add these permissions:

```php
// In your permission seeder or setup
$permissions = [
    'menu.view',
    'menu.create',
    'menu.edit',
    'menu.delete',
    'menu.restore',
    'menu.force-delete',
];

foreach ($permissions as $permission) {
    Permission::create(['name' => $permission]);
}

// Assign to settings role
$adminRole = Role::findByName('settings');
$adminRole->givePermissionTo($permissions);
```

### Step 9: Add Navigation Link to Admin

Add a link to the menu manager in your admin navigation:

```blade
{{-- In your admin layout navigation --}}
<li>
    <a href="{{ route('menu.index') }}">
        <i class="fa fa-bars"></i>
        <span>Menus</span>
    </a>
</li>
```

### Step 10: Test the Installation

1. Visit `/menus` in your browser (logged in as admin)
2. You should see the menu management interface
3. Try creating a new menu
4. Add some menu items
5. Test the drag & drop functionality

## Post-Installation Configuration

### Middleware Protection

The menu routes are protected with `auth` and `verified` middleware by default. You can customize this in:

```php
// modules/Menu/routes/web.php
Route::middleware(['auth', 'verified', 'role:settings'])->group(function () {
    // Menu routes
});
```

### Customize Views

To customize the views, publish them:

```bash
php artisan vendor:publish --tag=menu-module-views
```

Views will be copied to `resources/views/modules/menu/`

### Add Custom CSS

The menu builder includes basic Tailwind CSS classes. To customize:

1. Copy `modules/Menu/resources/assets/sass/menu-builder.scss` to your theme
2. Modify as needed
3. Include in your build process

### Cache Configuration

Menus are cached by default. Configure cache behavior:

```php
// config/menu.php or modules/Menu/config/config.php
'cache_duration' => 3600, // 1 hour in seconds
```

Clear menu cache:

```bash
php artisan menu:clear-cache [location]
```

## Rendering Menus in Your Layout

### Basic Usage

```blade
{{-- In resources/views/layouts/app.blade.php --}}
<header>
    <nav>
        <x-menu::menu location="header" class="main-nav" />
    </nav>
</header>

<footer>
    <x-menu::menu location="footer" class="footer-nav" />
</footer>
```

### With Custom Styling

```blade
<nav class="navbar">
    <div class="container">
        <x-menu::menu location="header" class="navbar-nav" />
    </div>
</nav>
```

## Troubleshooting

### Issue: "Class 'Modules\Menu\Models\Menu' not found"

**Solution:** Make sure you've run:
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Issue: Menu not showing on frontend

**Solution:** Check:
1. Menu status is set to "Active"
2. Menu has a location assigned
3. Menu has items added
4. You're using the correct location in your Blade template

### Issue: Drag & drop not working

**Solution:** Verify:
1. SortableJS is loaded
2. Alpine.js is loaded
3. No JavaScript errors in browser console
4. CSRF token is present in the page

### Issue: Cache not clearing

**Solution:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan menu:clear-cache
```

### Issue: Permission denied errors

**Solution:** Check:
1. User has proper permissions
2. Middleware is correctly configured
3. User is authenticated

## Updating the Module

If you need to update the module in the future:

```bash
# Run any new migrations
php artisan migrate

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan menu:clear-cache

# Recompile assets if needed
npm run build
```

## Database Backup

Before making any changes, backup your menus:

```bash
# Export menus
php artisan db:seed --class=Modules\\Menu\\Database\\Seeders\\MenuDatabaseSeeder --export

# Or use mysqldump
mysqldump -u username -p database_name menus menu_items > menu_backup.sql
```

## Next Steps

1. Review the [README.md](README.md) for feature overview
2. Check [USAGE_EXAMPLES.md](USAGE_EXAMPLES.md) for implementation examples
3. Customize the views and styling to match your theme
4. Create your menus in the admin interface
5. Add menu rendering to your layouts

## Support

For issues or questions:
1. Check the documentation files in this module
2. Review Laravel Modules documentation
3. Check Laravel documentation for Blade components

## Version History

- **v1.0.0** - Initial release
  - Full CRUD functionality
  - Drag & drop menu builder
  - Hierarchical menu structure
  - Multiple menu locations
  - Cache support
  - Blade components
  - Helper functions

---

**Module Location:** `/modules/Menu/`
**Routes:** `/menus`
**Namespace:** `Modules\Menu`
