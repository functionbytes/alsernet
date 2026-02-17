# Shortcode Module

A powerful and flexible shortcode system for Laravel applications, similar to WordPress shortcodes.

## Features

- Easy shortcode registration and compilation
- Support for nested shortcodes
- Self-closing and enclosing shortcode syntax
- Blade directive integration
- Built-in caching for performance
- Multiple default shortcodes included
- Helper functions for easy usage
- Facade support

## Installation

The module is automatically registered when enabled in your Laravel application.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Modules\Shortcode\Providers\ShortcodeServiceProvider" --tag="config"
```

Edit `config/shortcode.php` to customize:

```php
return [
    'enabled' => true,
    'cache' => true,
    'cache_duration' => 3600,
    'auto_register' => true,
    'default_shortcodes' => [
        'button' => true,
        'alert' => true,
        // ... more shortcodes
    ],
];
```

## Usage

### Basic Usage

#### Using Helper Functions

```php
$content = '[button url="/contact"]Contact Us[/button]';
$compiled = shortcode($content);
// Output: <a href="/contact" class="btn btn-primary">Contact Us</a>
```

#### Using Facade

```php
use Modules\Shortcode\Facades\Shortcode;

$content = '[alert type="success"]Operation completed![/alert]';
$compiled = Shortcode::compile($content);
```

#### Using Blade Directive

```blade
@shortcode('[button url="/register"]Sign Up[/button]')

{{-- Or with variable --}}
@shortcode($post->content)
```

### Default Shortcodes

#### 1. Button

Creates styled buttons with links.

```html
[button url="/contact" class="primary" target="_blank"]Contact Us[/button]
[button url="#" class="secondary" id="my-button"]Click Me[/button]
```

**Attributes:**
- `url` (default: `#`) - Link URL
- `class` (default: `btn-primary`) - Button class
- `target` (optional) - Link target (_blank, _self, etc.)
- `id` (optional) - Button ID

**Output:**
```html
<a href="/contact" class="btn btn-primary" target="_blank">Contact Us</a>
```

#### 2. Alert

Display Bootstrap alert messages.

```html
[alert type="success"]Your changes have been saved![/alert]
[alert type="danger" dismissible="true"]Error occurred![/alert]
```

**Attributes:**
- `type` (default: `info`) - Alert type: success, danger, warning, info
- `dismissible` (default: `false`) - Make alert dismissible

**Output:**
```html
<div class="alert alert-success" role="alert">Your changes have been saved!</div>
```

#### 3. Columns

Create responsive column layouts.

```html
[columns count="3" gap="4"]
    [column]Column 1[/column]
    [column]Column 2[/column]
    [column]Column 3[/column]
[/columns]
```

**Attributes:**
- `count` (default: `2`) - Number of columns
- `gap` (default: `3`) - Gap size (1-5)

**Output:**
```html
<div class="row row-cols-1 row-cols-md-3 g-4">
    <div class="col">Column 1</div>
    <div class="col">Column 2</div>
    <div class="col">Column 3</div>
</div>
```

#### 4. YouTube

Embed YouTube videos responsively.

```html
[youtube id="dQw4w9WgXcQ" /]
[youtube id="abc123" width="640" height="360" title="My Video" /]
```

**Attributes:**
- `id` (required) - YouTube video ID
- `width` (default: `560`) - Video width
- `height` (default: `315`) - Video height
- `title` (default: `YouTube video player`) - Video title

**Output:**
```html
<div class="ratio ratio-16x9">
    <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" ...></iframe>
</div>
```

#### 5. Image

Display images from Media module.

```html
[image id="123" size="medium" alt="Description" /]
[image id="456" size="large" class="rounded" /]
```

**Attributes:**
- `id` (required) - Media ID
- `size` (default: `medium`) - Image size
- `class` (default: `img-fluid`) - CSS class
- `alt` (optional) - Alt text

**Output:**
```html
<img src="/storage/media/image.jpg" class="img-fluid" alt="Description" loading="lazy">
```

#### 6. Icon

Display Bootstrap Icons.

```html
[icon name="heart" size="24" color="danger" /]
[icon name="star-fill" size="32" class="me-2" /]
```

**Attributes:**
- `name` (default: `circle`) - Icon name
- `size` (default: `24`) - Icon size in pixels
- `color` (optional) - Bootstrap color class
- `class` (optional) - Additional CSS classes

**Output:**
```html
<i class="bi bi-heart text-danger" style="font-size: 24px;"></i>
```

#### 7. Badge

Display Bootstrap badges.

```html
[badge type="primary"]New[/badge]
[badge type="success" pill="true"]Featured[/badge]
```

**Attributes:**
- `type` (default: `primary`) - Badge type
- `pill` (default: `false`) - Pill style

**Output:**
```html
<span class="badge bg-primary">New</span>
```

#### 8. Card

Create Bootstrap cards.

```html
[card title="Card Title" class="mb-3"]
    Card content goes here.
[/card]
```

**Attributes:**
- `title` (optional) - Card title
- `class` (optional) - Additional CSS classes
- `header_class` (optional) - Header CSS classes

**Output:**
```html
<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">Card Title</h5></div>
    <div class="card-body">Card content goes here.</div>
</div>
```

#### 9. Accordion

Create Bootstrap accordion.

```html
[accordion id="myAccordion"]
    [accordion-item title="Section 1" parent="myAccordion" show="true"]
        Content for section 1
    [/accordion-item]
    [accordion-item title="Section 2" parent="myAccordion"]
        Content for section 2
    [/accordion-item]
[/accordion]
```

**Attributes (accordion):**
- `id` (auto-generated) - Accordion ID
- `class` (optional) - Additional CSS classes

**Attributes (accordion-item):**
- `title` (default: `Accordion Item`) - Item title
- `parent` (default: `accordion`) - Parent accordion ID
- `show` (default: `false`) - Show by default
- `id` (auto-generated) - Item ID

#### 10. Quote

Display blockquotes.

```html
[quote author="John Doe" cite="Book Title"]
    This is a great quote.
[/quote]
```

**Attributes:**
- `author` (optional) - Quote author
- `cite` (optional) - Source citation

**Output:**
```html
<blockquote class="blockquote">
    <p>This is a great quote.</p>
    <footer class="blockquote-footer">John Doe <cite title="Book Title">Book Title</cite></footer>
</blockquote>
```

## Creating Custom Shortcodes

### Method 1: In Service Provider

```php
use Modules\Shortcode\Facades\Shortcode;

public function boot()
{
    Shortcode::register('price', function($attrs, $content) {
        $currency = $attrs['currency'] ?? 'USD';
        $amount = $attrs['amount'] ?? '0.00';

        return sprintf(
            '<span class="price">%s %s</span>',
            $currency,
            number_format($amount, 2)
        );
    });
}
```

### Method 2: Using Helper Function

```php
register_shortcode('highlight', function($attrs, $content) {
    $color = $attrs['color'] ?? 'yellow';

    return sprintf(
        '<mark style="background-color: %s;">%s</mark>',
        htmlspecialchars($color),
        $content
    );
});
```

### Usage:

```html
[price currency="EUR" amount="99.99" /]
[highlight color="lightblue"]Important text[/highlight]
```

## Advanced Usage

### Nested Shortcodes

Shortcodes can be nested within each other:

```html
[card title="Pricing"]
    [columns count="3"]
        [column]
            [badge type="primary"]Basic[/badge]
            [price amount="9.99" /]
        [/column]
        [column]
            [badge type="success"]Pro[/badge]
            [price amount="29.99" /]
        [/column]
        [column]
            [badge type="danger"]Enterprise[/badge]
            [price amount="99.99" /]
        [/column]
    [/columns]
[/card]
```

### Stripping Shortcodes

Remove shortcodes from content:

```php
$plain = strip_shortcodes($content);
```

### Checking Shortcode Existence

```php
if (has_shortcode('button')) {
    // Shortcode exists
}

// Get all registered shortcodes
$shortcodes = all_shortcodes();
```

### Clearing Cache

```php
use Modules\Shortcode\Facades\Shortcode;

Shortcode::clearCache();
```

### Unregistering Shortcodes

```php
use Modules\Shortcode\Facades\Shortcode;

Shortcode::unregister('button');
```

## Helper Functions

| Function | Description |
|----------|-------------|
| `shortcode($content)` | Compile shortcodes in content |
| `strip_shortcodes($content)` | Remove all shortcodes |
| `register_shortcode($name, $callback)` | Register a new shortcode |
| `has_shortcode($name)` | Check if shortcode exists |
| `all_shortcodes()` | Get all registered shortcodes |

## Blade Directives

| Directive | Description |
|-----------|-------------|
| `@shortcode($content)` | Compile shortcodes |
| `@stripshortcodes($content)` | Strip shortcodes |

## Facade Methods

| Method | Description |
|--------|-------------|
| `Shortcode::register($name, $callback)` | Register shortcode |
| `Shortcode::compile($content)` | Compile content |
| `Shortcode::strip($content)` | Strip shortcodes |
| `Shortcode::has($name)` | Check existence |
| `Shortcode::all()` | Get all shortcodes |
| `Shortcode::clearCache()` | Clear cache |
| `Shortcode::unregister($name)` | Remove shortcode |

## Examples

### Blog Post with Shortcodes

```php
// In your model or controller
$post->content = '[alert type="info"]Updated on ' . now()->format('Y-m-d') . '[/alert]

[quote author="Albert Einstein"]
    Imagination is more important than knowledge.
[/quote]

[columns count="2"]
    [column]
        [card title="Features"]
            [icon name="check-circle" color="success" /] Feature 1
            [icon name="check-circle" color="success" /] Feature 2
        [/card]
    [/column]
    [column]
        [card title="Benefits"]
            [badge type="primary"]Fast[/badge]
            [badge type="success"]Reliable[/badge]
        [/card]
    [/column]
[/columns]

[button url="/learn-more" class="primary"]Learn More[/button]';

// In your view
{!! shortcode($post->content) !!}
```

### Custom Newsletter Shortcode

```php
// Register in ServiceProvider
Shortcode::register('newsletter', function($attrs, $content) {
    $placeholder = $attrs['placeholder'] ?? 'Enter your email';
    $buttonText = $attrs['button'] ?? 'Subscribe';

    return view('shortcode::newsletter', [
        'placeholder' => $placeholder,
        'buttonText' => $buttonText,
        'content' => $content
    ])->render();
});
```

## Security Considerations

- All attributes are automatically escaped with `htmlspecialchars()`
- Content is not automatically escaped (allows HTML)
- Always validate and sanitize user input
- Be careful with user-generated shortcodes

## Performance

- Compiled shortcodes are cached by default
- Cache duration is configurable
- Use `clearCache()` when updating shortcode definitions
- Disable caching in development if needed

## Troubleshooting

### Shortcodes Not Working

1. Check if module is enabled: `config('shortcode.enabled')`
2. Clear cache: `Shortcode::clearCache()`
3. Verify shortcode is registered: `has_shortcode('name')`

### Nested Shortcodes Issues

- Ensure proper closing tags
- Check max nesting level in config
- Use unique IDs for nested elements

### Performance Issues

- Enable caching in production
- Increase cache duration
- Optimize callback functions
- Avoid database queries in shortcodes

## License

This module is open-sourced software licensed under the MIT license.

## Credits

Developed for Laravel modular applications using nWidart/laravel-modules.
