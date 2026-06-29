# Shortcode Module - Quick Start Guide

## 1. Enable the Module

```bash
cd /Users/functionbytes/Function/Coding/inoqualab
php artisan module:enable Shortcode
php artisan cache:clear
php artisan config:clear
```

## 2. Verify Installation

```bash
# List all registered shortcodes
php artisan shortcode:list
```

You should see 11 default shortcodes listed.

## 3. Basic Usage

### In Blade Templates

```blade
{{-- Using directive --}}
@shortcode('[button url="/contact"]Contact Us[/button]')

{{-- With variable --}}
@shortcode($post->content)

{{-- Using helper --}}
{!! shortcode($content) !!}
```

### In Controllers

```php
use Modules\Shortcode\Facades\Shortcode;

public function show($id)
{
    $post = Post::findOrFail($id);
    $post->compiled_content = Shortcode::compile($post->content);

    return view('posts.show', compact('post'));
}
```

### Using Helpers

```php
// Compile shortcodes
$html = shortcode('[alert type="success"]Saved![/alert]');

// Strip shortcodes
$plain = strip_shortcodes($content);

// Check if shortcode exists
if (has_shortcode('button')) {
    // Do something
}
```

## 4. Test It Out

### Using Artisan

```bash
# Compile a shortcode
php artisan shortcode:compile "[button url='/test']Click Me[/button]"

# Output:
# Original Content:
# [button url='/test']Click Me[/button]
#
# Compiled Content:
# <a href="/test" class="btn btn-primary">Click Me</a>
```

### Visit Demo Page

Open your browser and visit:
```
http://your-app.test/shortcodes
```

This will show you all available shortcodes with live examples.

## 5. Use Default Shortcodes

### Button

```html
[button url="/signup" class="primary"]Sign Up[/button]
[button url="/learn-more" class="secondary" target="_blank"]Learn More[/button]
```

### Alert

```html
[alert type="success"]Your changes have been saved![/alert]
[alert type="danger" dismissible="true"]Error occurred![/alert]
```

### Columns Layout

```html
[columns count="3" gap="4"]
    [column]Column 1 content[/column]
    [column]Column 2 content[/column]
    [column]Column 3 content[/column]
[/columns]
```

### YouTube Video

```html
[youtube id="dQw4w9WgXcQ" /]
```

### Icons

```html
[icon name="heart" size="24" color="danger" /]
[icon name="star-fill" size="32" color="warning" /]
```

### Badges

```html
[badge type="primary"]New[/badge]
[badge type="success" pill="true"]Featured[/badge]
```

### Cards

```html
[card title="Card Title" class="mb-3"]
    This is the card content.
    [button url="#"]Learn More[/button]
[/card]
```

### Accordion

```html
[accordion id="faq"]
    [accordion-item title="Question 1?" parent="faq" show="true"]
        Answer to question 1.
    [/accordion-item]
    [accordion-item title="Question 2?" parent="faq"]
        Answer to question 2.
    [/accordion-item]
[/accordion]
```

### Quotes

```html
[quote author="Albert Einstein"]
    Imagination is more important than knowledge.
[/quote]
```

## 6. Register Custom Shortcode

### In AppServiceProvider

```php
// app/Providers/AppServiceProvider.php

use Modules\Shortcode\Facades\Shortcode;

public function boot()
{
    Shortcode::register('highlight', function($attrs, $content) {
        $color = $attrs['color'] ?? 'yellow';
        return sprintf(
            '<mark style="background-color: %s;">%s</mark>',
            htmlspecialchars($color),
            $content
        );
    });
}
```

### Usage

```html
[highlight color="lightblue"]Important text here[/highlight]
```

## 7. Real-World Example

### Blog Post with Multiple Shortcodes

```html
[alert type="info"]
    This article was updated on February 8, 2026
[/alert]

<h1>Welcome to Our Blog</h1>

<p>Check out our latest features:</p>

[columns count="3"]
    [column]
        [card title="Fast Performance"]
            [icon name="lightning-fill" size="48" color="primary" /]
            <p class="mt-3">Lightning-fast page loads</p>
            [badge type="success"]New[/badge]
        [/card]
    [/column]
    [column]
        [card title="Secure"]
            [icon name="shield-check" size="48" color="success" /]
            <p class="mt-3">Enterprise-grade security</p>
            [badge type="primary"]Updated[/badge]
        [/card]
    [/column]
    [column]
        [card title="Support"]
            [icon name="headset" size="48" color="info" /]
            <p class="mt-3">24/7 customer support</p>
            [badge type="warning"]Popular[/badge]
        [/card]
    [/column]
[/columns]

[quote author="Customer" cite="Review"]
    This is the best platform I've ever used!
[/quote]

<div class="text-center my-5">
    [button url="/signup" class="primary btn-lg"]
        Get Started Now [icon name="arrow-right" /]
    [/button]
</div>

[accordion id="faq"]
    [accordion-item title="How do I get started?" parent="faq" show="true"]
        Simply click the Get Started button above and follow the setup wizard.
    [/accordion-item]
    [accordion-item title="Is there a free trial?" parent="faq"]
        Yes! We offer a 14-day free trial with no credit card required.
    [/accordion-item]
[/accordion]
```

## 8. API Usage

### Compile Shortcode via API

```bash
curl -X POST http://your-app.test/api/shortcodes/compile \
  -H "Content-Type: application/json" \
  -d '{"content": "[button]Click Me[/button]"}'
```

### Response

```json
{
  "success": true,
  "original": "[button]Click Me[/button]",
  "compiled": "<a href=\"#\" class=\"btn btn-primary\">Click Me</a>"
}
```

### List All Shortcodes

```bash
curl -X GET http://your-app.test/api/shortcodes/list
```

## 9. Common Use Cases

### CMS Content

```php
// In your CMS model
class Page extends Model
{
    protected $appends = ['compiled_content'];

    public function getCompiledContentAttribute()
    {
        return shortcode($this->content);
    }
}

// In your view
{!! $page->compiled_content !!}
```

### Email Templates

```php
$emailContent = '
[alert type="info"]You have a new message[/alert]

[card title="Message Details"]
    From: John Doe
    Subject: Important Update
[/card]

[button url="https://example.com/messages"]View Message[/button]
';

Mail::send('emails.notification', [
    'content' => shortcode($emailContent)
], function($message) {
    // ...
});
```

### Dynamic Content

```php
// Store content with shortcodes
$post->content = '[button url="/download"]Download PDF[/button]';
$post->save();

// Display compiled content
echo shortcode($post->content);
```

## 10. Configuration

Edit `config/shortcode.php`:

```php
return [
    'enabled' => true,              // Enable module
    'cache' => true,                // Enable caching (recommended)
    'cache_duration' => 3600,       // Cache 1 hour
    'auto_register' => true,        // Auto-register default shortcodes
    'default_shortcodes' => [
        'button' => true,
        'alert' => true,
        // ... enable/disable specific shortcodes
    ],
];
```

## 11. Artisan Commands

```bash
# List all registered shortcodes
php artisan shortcode:list

# Clear shortcode cache
php artisan shortcode:clear

# Compile shortcode from command line
php artisan shortcode:compile "[button]Test[/button]"

# Strip shortcodes
php artisan shortcode:compile "[button]Test[/button]" --strip
```

## 12. Tips & Best Practices

### 1. Always Escape Attributes

```php
Shortcode::register('link', function($attrs, $content) {
    $url = htmlspecialchars($attrs['url'] ?? '#');
    return "<a href=\"{$url}\">{$content}</a>";
});
```

### 2. Provide Default Values

```php
Shortcode::register('image', function($attrs, $content) {
    $src = $attrs['src'] ?? '/images/placeholder.jpg';
    $alt = $attrs['alt'] ?? 'Image';
    // ...
});
```

### 3. Use Views for Complex HTML

```php
Shortcode::register('newsletter', function($attrs, $content) {
    return view('shortcodes.newsletter', [
        'title' => $attrs['title'] ?? 'Subscribe',
        'content' => $content
    ])->render();
});
```

### 4. Cache Expensive Operations

```php
Shortcode::register('stats', function($attrs, $content) {
    return Cache::remember('stats-shortcode', 3600, function() {
        return DB::table('stats')->count();
    });
});
```

### 5. Validate Required Attributes

```php
Shortcode::register('video', function($attrs, $content) {
    if (!isset($attrs['id'])) {
        return '<!-- Video shortcode: ID required -->';
    }
    // ...
});
```

## 13. Troubleshooting

### Shortcodes Not Compiling

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# List shortcodes to verify
php artisan shortcode:list
```

### Helpers Not Found

Make sure the module is enabled:
```bash
php artisan module:list
```

### Performance Issues

Enable caching in `config/shortcode.php`:
```php
'cache' => true,
'cache_duration' => 3600,
```

## 14. Next Steps

- Read the full documentation: `README.md`
- Check examples: `EXAMPLES.md`
- Review API documentation: `API.md`
- Browse the demo page: `http://your-app.test/shortcodes`

## Need Help?

Refer to:
- `README.md` - Complete documentation
- `API.md` - Full API reference
- `EXAMPLES.md` - Real-world examples
- `/shortcodes` - Live demo page

---

**You're ready to use shortcodes!** Start adding them to your content now.
