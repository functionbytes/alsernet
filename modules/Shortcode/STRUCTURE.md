# Shortcode Module Structure

Complete file structure of the Shortcode module.

```
modules/Shortcode/
├── API.md                              # API documentation
├── CHANGELOG.md                        # Version history
├── EXAMPLES.md                         # Usage examples
├── INSTALLATION.md                     # Installation guide
├── LICENSE                             # MIT License
├── README.md                           # Main documentation
├── STRUCTURE.md                        # This file
│
├── app/
│   ├── Compiler/
│   │   └── ShortcodeCompiler.php      # Main compiler class
│   │
│   ├── Facades/
│   │   └── Shortcode.php              # Facade for easy access
│   │
│   ├── Http/
│   │   └── Controllers/
│   │       └── ShortcodeController.php # API and demo controller
│   │
│   └── Providers/
│       ├── EventServiceProvider.php    # Event provider
│       ├── RouteServiceProvider.php    # Route provider
│       └── ShortcodeServiceProvider.php # Main service provider
│
├── config/
│   └── config.php                      # Module configuration
│
├── composer.json                       # Composer package definition
├── package.json                        # NPM package definition
├── module.json                         # Module metadata
├── vite.config.js                      # Vite configuration
│
├── database/
│   ├── migrations/                     # Database migrations (empty)
│   └── seeders/
│       └── ShortcodeDatabaseSeeder.php # Database seeder
│
├── helpers/
│   └── shortcode.php                   # Helper functions
│
├── resources/
│   ├── assets/
│   │   ├── js/
│   │   │   └── app.js                  # JavaScript assets
│   │   └── sass/
│   │       └── app.scss                # Sass styles
│   │
│   └── views/
│       ├── components/
│       │   └── layouts/
│       │       └── master.blade.php    # Master layout
│       │
│       ├── examples/
│       │   └── demo.blade.php          # Demo page with examples
│       │
│       ├── partials/
│       │   └── shortcode-reference.blade.php # Reference guide
│       │
│       └── v5.blade.php                # Default view
│
├── routes/
│   ├── api.php                         # API routes
│   └── web.php                         # Web routes
│
└── tests/
    ├── Feature/
    │   ├── DefaultShortcodesTest.php   # Tests for default shortcodes
    │   └── ShortcodeHelperTest.php     # Tests for helper functions
    │
    └── Unit/
        └── ShortcodeCompilerTest.php   # Tests for compiler
```

## Core Components

### 1. Compiler (`app/Compiler/ShortcodeCompiler.php`)
- Main parsing and compilation logic
- Regex-based shortcode detection
- Attribute parsing
- Cache management
- Nested shortcode support

### 2. Facade (`app/Facades/Shortcode.php`)
- Laravel Facade for easy access
- Methods: register, compile, strip, has, all, clearCache, unregister

### 3. Service Provider (`app/Providers/ShortcodeServiceProvider.php`)
- Registers ShortcodeCompiler as singleton
- Loads helper functions
- Registers Blade directives
- Registers 11 default shortcodes
- Publishes configuration

### 4. Controller (`app/Http/Controllers/ShortcodeController.php`)
- Demo page
- API endpoints for compilation
- List shortcodes
- Cache management

### 5. Helpers (`helpers/shortcode.php`)
- shortcode() - Compile content
- strip_shortcodes() - Strip shortcodes
- register_shortcode() - Register new shortcode
- has_shortcode() - Check existence
- all_shortcodes() - List all

## Default Shortcodes

1. **[button]** - Styled buttons
2. **[alert]** - Bootstrap alerts
3. **[columns]** - Grid layouts
4. **[column]** - Grid columns
5. **[youtube]** - YouTube embeds
6. **[image]** - Media images
7. **[icon]** - Bootstrap icons
8. **[badge]** - Bootstrap badges
9. **[card]** - Bootstrap cards
10. **[accordion]** - Accordion components
11. **[accordion-item]** - Accordion items
12. **[quote]** - Blockquotes

## Routes

### Web Routes (`routes/web.php`)
- `GET /shortcodes` - Demo page
- `GET /shortcodes/reference` - Reference guide

### API Routes (`routes/api.php`)
- `POST /api/shortcodes/compile` - Compile shortcodes
- `POST /api/shortcodes/strip` - Strip shortcodes
- `GET /api/shortcodes/list` - List registered shortcodes
- `POST /api/shortcodes/check` - Check shortcode existence
- `POST /api/shortcodes/clear-cache` - Clear cache

## Configuration

### Config File (`config/config.php`)
```php
[
    'enabled' => true,              // Enable/disable module
    'cache' => true,                // Enable caching
    'cache_duration' => 3600,       // Cache duration
    'auto_register' => true,        // Auto-register defaults
    'default_shortcodes' => [...],  // Enable/disable specific shortcodes
    'error_handling' => 'log',      // Error handling mode
    'max_nesting_level' => 10,      // Max nesting depth
]
```

## Tests

### Unit Tests
- `ShortcodeCompilerTest.php` - Tests for compiler functionality
  - Registration
  - Compilation
  - Attribute parsing
  - Self-closing shortcodes
  - Stripping
  - Caching

### Feature Tests
- `DefaultShortcodesTest.php` - Tests for all default shortcodes
- `ShortcodeHelperTest.php` - Tests for helper functions

## Documentation

### Main Documentation
- **README.md** - Complete usage guide and examples
- **API.md** - Full API reference
- **EXAMPLES.md** - Real-world examples
- **INSTALLATION.md** - Installation instructions
- **CHANGELOG.md** - Version history

### Views
- **demo.blade.php** - Interactive demo page
- **shortcode-reference.blade.php** - Shortcode reference

## Dependencies

### PHP Dependencies (composer.json)
- php: ^8.1
- illuminate/support: ^10.0|^11.0

### Dev Dependencies
- phpunit/phpunit: ^10.0

## Features

1. **WordPress-like syntax** - Familiar shortcode format
2. **Nested shortcodes** - Support for complex structures
3. **Self-closing shortcodes** - `[shortcode /]` syntax
4. **Attribute parsing** - Key-value pairs
5. **Caching** - Built-in compilation cache
6. **Bootstrap 5 compatible** - Modern UI components
7. **Blade integration** - Custom directives
8. **Facade support** - Clean API
9. **Helper functions** - Easy to use
10. **RESTful API** - HTTP endpoints
11. **Extensible** - Easy to add custom shortcodes
12. **Security** - XSS protection via htmlspecialchars

## Usage Patterns

### In Controllers
```php
use Modules\Shortcode\Facades\Shortcode;
$compiled = Shortcode::compile($content);
```

### In Blade
```blade
@shortcode($post->content)
{!! shortcode($content) !!}
```

### Custom Registration
```php
Shortcode::register('custom', function($attrs, $content) {
    return '<div>' . $content . '</div>';
});
```

### Via Helper
```php
$result = shortcode('[button]Click[/button]');
```

## Performance Considerations

1. **Caching enabled by default** - Compiled results are cached
2. **Efficient regex** - Optimized pattern matching
3. **Lazy loading** - Shortcodes registered on boot
4. **No database queries** - Pure parsing (except image shortcode)
5. **Minimal dependencies** - Only Laravel framework

## Security

1. **XSS Protection** - All attributes escaped
2. **Content filtering** - Strip shortcodes when needed
3. **Validation** - Attributes validated in callbacks
4. **Error handling** - Graceful degradation
5. **Rate limiting** - Can be added to API routes

## Extensibility

1. **Custom shortcodes** - Easy registration
2. **Custom views** - Use Blade templates
3. **Middleware** - Can be added to routes
4. **Events** - Laravel event system compatible
5. **Service injection** - Use DI in callbacks

## File Sizes

Approximate file sizes:
- ShortcodeCompiler.php: ~5 KB
- ShortcodeServiceProvider.php: ~12 KB
- README.md: ~15 KB
- EXAMPLES.md: ~10 KB
- API.md: ~12 KB
- Tests: ~8 KB total

Total module size: ~60 KB (excluding vendor dependencies)

## Browser Compatibility

The generated HTML is compatible with:
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Bootstrap 5.x
- Responsive design
- Mobile-friendly

## Future Enhancements

Potential additions:
- Visual shortcode builder
- Shortcode templates
- Import/export functionality
- More default shortcodes
- Real-time preview
- Shortcode validation
- Performance profiling
- Admin UI
