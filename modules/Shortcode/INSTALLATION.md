# Installation Guide

## Requirements

- Laravel 10.x or higher
- PHP 8.1 or higher
- nWidart/laravel-modules package

## Installation Steps

### 1. Module is already created

The Shortcode module has been generated in `modules/Shortcode`.

### 2. Enable the Module

Enable the module using the artisan command:

```bash
php artisan module:enable Shortcode
```

### 3. Publish Configuration (Optional)

Publish the configuration file to customize settings:

```bash
php artisan vendor:publish --provider="Modules\Shortcode\Providers\ShortcodeServiceProvider" --tag="config"
```

This will create `config/shortcode.php`.

### 4. Register Module (if needed)

Make sure your `config/modules.php` is properly configured. The module should auto-register.

### 5. Clear Cache

Clear application cache:

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Configuration

Edit `config/shortcode.php` to customize:

```php
return [
    // Enable/disable shortcode processing
    'enabled' => true,

    // Enable caching for performance
    'cache' => true,

    // Cache duration in seconds
    'cache_duration' => 3600,

    // Auto-register default shortcodes
    'auto_register' => true,

    // Enable/disable specific shortcodes
    'default_shortcodes' => [
        'button' => true,
        'alert' => true,
        'columns' => true,
        // ... more shortcodes
    ],
];
```

## Verification

Test the installation by running:

```bash
php artisan tinker
```

Then try:

```php
shortcode('[button url="/test"]Test Button[/button]');
```

You should see compiled HTML output.

## Usage in Your Application

### In Blade Templates

```blade
{!! shortcode($content) !!}

@shortcode($post->content)
```

### In Controllers

```php
use Modules\Shortcode\Facades\Shortcode;

$compiled = Shortcode::compile($content);
```

### Register Custom Shortcodes

In your `AppServiceProvider` or any service provider:

```php
use Modules\Shortcode\Facades\Shortcode;

public function boot()
{
    Shortcode::register('custom', function($attrs, $content) {
        return '<div class="custom">' . $content . '</div>';
    });
}
```

## Testing

Visit the demo page to see examples:

```
http://your-app.test/shortcodes
```

## API Endpoints

The module provides several API endpoints:

- `POST /api/shortcodes/compile` - Compile shortcodes
- `POST /api/shortcodes/strip` - Strip shortcodes
- `GET /api/shortcodes/list` - List registered shortcodes
- `POST /api/shortcodes/check` - Check if shortcode exists
- `POST /api/shortcodes/clear-cache` - Clear cache

Example API usage:

```bash
curl -X POST http://your-app.test/api/shortcodes/compile \
  -H "Content-Type: application/json" \
  -d '{"content": "[button url=\"/test\"]Click Me[/button]"}'
```

## Troubleshooting

### Shortcodes not compiling

1. Check if module is enabled: `php artisan module:list`
2. Clear cache: `php artisan cache:clear`
3. Verify config: `config('shortcode.enabled')`

### Helpers not working

Make sure the helper file is loaded. Check `modules/Shortcode/helpers/shortcode.php` is included.

### Blade directive not found

Clear view cache:

```bash
php artisan view:clear
```

### Performance issues

Enable caching in configuration:

```php
'cache' => true,
'cache_duration' => 3600,
```

## Uninstallation

To remove the module:

1. Disable the module:
```bash
php artisan module:disable Shortcode
```

2. Remove the module directory:
```bash
rm -rf modules/Shortcode
```

3. Clear cache:
```bash
php artisan cache:clear
php artisan config:clear
```

## Support

For issues, questions, or contributions, please refer to the README.md and EXAMPLES.md files.
