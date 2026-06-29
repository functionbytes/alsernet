# Shortcode API Documentation

This document describes the programmatic API for the Shortcode module.

## Table of Contents

1. [PHP API](#php-api)
2. [HTTP API](#http-api)
3. [Blade Directives](#blade-directives)
4. [Helper Functions](#helper-functions)

---

## PHP API

### Facade: `Modules\Shortcode\Facades\Shortcode`

#### `register(string $name, callable $callback): void`

Register a new shortcode.

```php
use Modules\Shortcode\Facades\Shortcode;

Shortcode::register('custom', function($attrs, $content) {
    $class = $attrs['class'] ?? 'default';
    return sprintf('<div class="%s">%s</div>', $class, $content);
});
```

**Parameters:**
- `$name` (string): Shortcode name
- `$callback` (callable): Function that receives `$attrs` (array) and `$content` (string)

**Returns:** void

---

#### `compile(string $content): string`

Compile shortcodes in content.

```php
$content = '[button url="/test"]Click Me[/button]';
$compiled = Shortcode::compile($content);

echo $compiled;
// Output: <a href="/test" class="btn btn-primary">Click Me</a>
```

**Parameters:**
- `$content` (string): Content containing shortcodes

**Returns:** string - Compiled content

---

#### `strip(string $content): string`

Remove all shortcodes from content.

```php
$content = 'Before [button]Click[/button] After';
$stripped = Shortcode::strip($content);

echo $stripped;
// Output: Before  After
```

**Parameters:**
- `$content` (string): Content containing shortcodes

**Returns:** string - Content without shortcodes

---

#### `has(string $name): bool`

Check if a shortcode is registered.

```php
if (Shortcode::has('button')) {
    // Shortcode exists
}
```

**Parameters:**
- `$name` (string): Shortcode name

**Returns:** bool

---

#### `all(): array`

Get all registered shortcode names.

```php
$shortcodes = Shortcode::all();

print_r($shortcodes);
// Output: ['button', 'alert', 'columns', ...]
```

**Returns:** array - Array of shortcode names

---

#### `clearCache(): void`

Clear the shortcode compilation cache.

```php
Shortcode::clearCache();
```

**Returns:** void

---

#### `unregister(string $name): void`

Unregister a shortcode.

```php
Shortcode::unregister('button');
```

**Parameters:**
- `$name` (string): Shortcode name

**Returns:** void

---

## HTTP API

All API endpoints are prefixed with `/api/shortcodes`.

### POST `/api/shortcodes/compile`

Compile shortcodes in content via API.

**Request Body:**
```json
{
  "content": "[button url=\"/test\"]Click Me[/button]"
}
```

**Response:**
```json
{
  "success": true,
  "original": "[button url=\"/test\"]Click Me[/button]",
  "compiled": "<a href=\"/test\" class=\"btn btn-primary\">Click Me</a>"
}
```

**cURL Example:**
```bash
curl -X POST http://your-app.test/api/shortcodes/compile \
  -H "Content-Type: application/json" \
  -d '{"content": "[button url=\"/test\"]Click Me[/button]"}'
```

---

### POST `/api/shortcodes/strip`

Strip all shortcodes from content.

**Request Body:**
```json
{
  "content": "Before [button]Click[/button] After"
}
```

**Response:**
```json
{
  "success": true,
  "original": "Before [button]Click[/button] After",
  "stripped": "Before  After"
}
```

**cURL Example:**
```bash
curl -X POST http://your-app.test/api/shortcodes/strip \
  -H "Content-Type: application/json" \
  -d '{"content": "Before [button]Click[/button] After"}'
```

---

### GET `/api/shortcodes/list`

Get list of all registered shortcodes.

**Response:**
```json
{
  "success": true,
  "shortcodes": [
    "button",
    "alert",
    "columns",
    "column",
    "youtube",
    "image",
    "icon",
    "badge",
    "card",
    "accordion",
    "accordion-item",
    "quote"
  ],
  "count": 12
}
```

**cURL Example:**
```bash
curl -X GET http://your-app.test/api/shortcodes/list
```

---

### POST `/api/shortcodes/check`

Check if a specific shortcode is registered.

**Request Body:**
```json
{
  "name": "button"
}
```

**Response:**
```json
{
  "success": true,
  "name": "button",
  "exists": true
}
```

**cURL Example:**
```bash
curl -X POST http://your-app.test/api/shortcodes/check \
  -H "Content-Type: application/json" \
  -d '{"name": "button"}'
```

---

### POST `/api/shortcodes/clear-cache`

Clear the shortcode compilation cache.

**Response:**
```json
{
  "success": true,
  "message": "Shortcode cache cleared successfully"
}
```

**cURL Example:**
```bash
curl -X POST http://your-app.test/api/shortcodes/clear-cache
```

---

## Blade Directives

### `@shortcode($content)`

Compile shortcodes in a Blade template.

```blade
@shortcode('[button url="/contact"]Contact Us[/button]')

{{-- With variable --}}
@shortcode($post->content)
```

**Output:**
```html
<a href="/contact" class="btn btn-primary">Contact Us</a>
```

---

### `@stripshortcodes($content)`

Strip shortcodes from content in a Blade template.

```blade
@stripshortcodes('[button]Click[/button] Text')

{{-- With variable --}}
@stripshortcodes($post->content)
```

**Output:**
```
 Text
```

---

## Helper Functions

### `shortcode(string $content): string`

Compile shortcodes in content.

```php
$compiled = shortcode('[button]Click[/button]');
```

**Parameters:**
- `$content` (string): Content with shortcodes

**Returns:** string

---

### `strip_shortcodes(string $content): string`

Strip all shortcodes from content.

```php
$stripped = strip_shortcodes('[button]Click[/button]');
```

**Parameters:**
- `$content` (string): Content with shortcodes

**Returns:** string

---

### `register_shortcode(string $name, callable $callback): void`

Register a new shortcode.

```php
register_shortcode('custom', function($attrs, $content) {
    return '<div>' . $content . '</div>';
});
```

**Parameters:**
- `$name` (string): Shortcode name
- `$callback` (callable): Callback function

**Returns:** void

---

### `has_shortcode(string $name): bool`

Check if a shortcode exists.

```php
if (has_shortcode('button')) {
    // Exists
}
```

**Parameters:**
- `$name` (string): Shortcode name

**Returns:** bool

---

### `all_shortcodes(): array`

Get all registered shortcodes.

```php
$shortcodes = all_shortcodes();
```

**Returns:** array

---

## Advanced Usage

### Custom Shortcode with View

```php
Shortcode::register('newsletter', function($attrs, $content) {
    return view('components.newsletter', [
        'title' => $attrs['title'] ?? 'Subscribe',
        'content' => $content
    ])->render();
});
```

### Shortcode with Validation

```php
Shortcode::register('video', function($attrs, $content) {
    if (!isset($attrs['id'])) {
        \Log::warning('Video shortcode missing ID attribute');
        return '';
    }

    return sprintf(
        '<video src="%s" controls>%s</video>',
        $attrs['id'],
        $content
    );
});
```

### Dynamic Shortcode Registration

```php
$shortcodes = [
    'highlight' => function($attrs, $content) {
        return '<mark>' . $content . '</mark>';
    },
    'underline' => function($attrs, $content) {
        return '<u>' . $content . '</u>';
    }
];

foreach ($shortcodes as $name => $callback) {
    Shortcode::register($name, $callback);
}
```

### Conditional Shortcode

```php
Shortcode::register('premium', function($attrs, $content) {
    if (auth()->check() && auth()->user()->isPremium()) {
        return $content;
    }

    return '<div class="alert alert-info">
        Upgrade to premium to view this content.
    </div>';
});
```

### Shortcode with Database Query

```php
Shortcode::register('recent-posts', function($attrs, $content) {
    $limit = $attrs['limit'] ?? 5;

    $posts = \App\Models\Post::latest()
        ->limit($limit)
        ->get();

    return view('shortcodes.recent-posts', [
        'posts' => $posts
    ])->render();
});
```

## Error Handling

All shortcode callbacks should handle errors gracefully:

```php
Shortcode::register('safe', function($attrs, $content) {
    try {
        // Your logic here
        return processContent($content);
    } catch (\Exception $e) {
        \Log::error('Shortcode error: ' . $e->getMessage());
        return $content; // Return original content
    }
});
```

## Performance Tips

1. **Use caching** - Enable in config: `'cache' => true`
2. **Avoid database queries** - Use eager loading or cache results
3. **Keep callbacks simple** - Complex logic should be in services
4. **Use views for complex HTML** - Don't build HTML strings in callbacks
5. **Limit nesting depth** - Set `max_nesting_level` in config

## Security Considerations

1. **Always escape attributes**: Use `htmlspecialchars()` on all user input
2. **Validate input**: Check for required attributes and valid values
3. **Sanitize content**: Be careful with user-generated content
4. **Limit execution time**: Avoid infinite loops in nested shortcodes
5. **Rate limiting**: Consider rate limiting for API endpoints

## Testing

Example PHPUnit test:

```php
public function test_custom_shortcode()
{
    Shortcode::register('test', function($attrs, $content) {
        return 'TEST: ' . $content;
    });

    $result = Shortcode::compile('[test]Hello[/test]');

    $this->assertEquals('TEST: Hello', $result);
}
```

## Migration from WordPress

If migrating from WordPress shortcodes:

```php
// WordPress style
add_shortcode('button', function($atts, $content) {
    // ...
});

// Laravel Shortcode module style
Shortcode::register('button', function($attrs, $content) {
    // Same logic
});
```

The API is very similar, but note the parameter names:
- WordPress uses `$atts` (attributes)
- This module uses `$attrs` (attributes)

Both work the same way.
