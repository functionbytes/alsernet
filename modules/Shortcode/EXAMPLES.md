# Shortcode Examples

This document provides practical examples of using the Shortcode module.

## Table of Contents

1. [Basic Examples](#basic-examples)
2. [Advanced Examples](#advanced-examples)
3. [Real-World Use Cases](#real-world-use-cases)
4. [Custom Shortcodes](#custom-shortcodes)

## Basic Examples

### Simple Button

```html
[button url="/contact"]Contact Us[/button]
```

Output:
```html
<a href="/contact" class="btn btn-primary">Contact Us</a>
```

### Alert Message

```html
[alert type="success"]Your profile has been updated successfully![/alert]
```

Output:
```html
<div class="alert alert-success" role="alert">Your profile has been updated successfully!</div>
```

### YouTube Video

```html
[youtube id="dQw4w9WgXcQ" /]
```

### Two Column Layout

```html
[columns count="2"]
    [column]Left content[/column]
    [column]Right content[/column]
[/columns]
```

## Advanced Examples

### Pricing Table

```html
[columns count="3" gap="4"]
    [column]
        [card title="Basic" class="text-center h-100"]
            <h2 class="mb-3">$9.99</h2>
            [badge type="primary"]Popular[/badge]
            <ul class="list-unstyled my-4">
                <li>[icon name="check" color="success" /] Feature 1</li>
                <li>[icon name="check" color="success" /] Feature 2</li>
                <li>[icon name="x" color="muted" /] Feature 3</li>
            </ul>
            [button url="/pricing/basic" class="primary w-100"]Choose Plan[/button]
        [/card]
    [/column]
    [column]
        [card title="Pro" class="text-center h-100 border-primary"]
            <h2 class="mb-3">$29.99</h2>
            [badge type="success"]Best Value[/badge]
            <ul class="list-unstyled my-4">
                <li>[icon name="check" color="success" /] Feature 1</li>
                <li>[icon name="check" color="success" /] Feature 2</li>
                <li>[icon name="check" color="success" /] Feature 3</li>
            </ul>
            [button url="/pricing/pro" class="success w-100"]Choose Plan[/button]
        [/card]
    [/column]
    [column]
        [card title="Enterprise" class="text-center h-100"]
            <h2 class="mb-3">$99.99</h2>
            [badge type="dark"]Premium[/badge]
            <ul class="list-unstyled my-4">
                <li>[icon name="check" color="success" /] All Features</li>
                <li>[icon name="check" color="success" /] Priority Support</li>
                <li>[icon name="check" color="success" /] Custom Solutions</li>
            </ul>
            [button url="/pricing/enterprise" class="dark w-100"]Contact Sales[/button]
        [/card]
    [/column]
[/columns]
```

### FAQ Section

```html
[accordion id="faq"]
    [accordion-item title="What is your refund policy?" parent="faq" show="true"]
        We offer a 30-day money-back guarantee. If you're not satisfied with our product,
        you can request a full refund within 30 days of purchase.
    [/accordion-item]
    [accordion-item title="How do I cancel my subscription?" parent="faq"]
        You can cancel your subscription anytime from your account settings.
        Go to Settings > Billing > Cancel Subscription.
    [/accordion-item]
    [accordion-item title="Do you offer technical support?" parent="faq"]
        Yes! We offer 24/7 technical support via email and live chat.
        Premium users also get phone support.
    [/accordion-item]
[/accordion]
```

### Feature Showcase

```html
[columns count="4" gap="3"]
    [column class="text-center"]
        [icon name="lightning-fill" size="48" color="primary" /]
        <h4 class="mt-3">Fast</h4>
        <p>Lightning-fast performance</p>
    [/column]
    [column class="text-center"]
        [icon name="shield-fill-check" size="48" color="success" /]
        <h4 class="mt-3">Secure</h4>
        <p>Enterprise-grade security</p>
    [/column]
    [column class="text-center"]
        [icon name="people-fill" size="48" color="info" /]
        <h4 class="mt-3">Collaborative</h4>
        <p>Work together seamlessly</p>
    [/column]
    [column class="text-center"]
        [icon name="gear-fill" size="48" color="warning" /]
        <h4 class="mt-3">Customizable</h4>
        <p>Tailor to your needs</p>
    [/column]
[/columns]
```

### Testimonial Section

```html
[columns count="2" gap="4"]
    [column]
        [card class="h-100"]
            [quote author="Jane Smith" cite="CEO, TechCorp"]
                This product has transformed the way we work. Highly recommended!
            [/quote]
            <div class="mt-3">
                [icon name="star-fill" color="warning" /]
                [icon name="star-fill" color="warning" /]
                [icon name="star-fill" color="warning" /]
                [icon name="star-fill" color="warning" /]
                [icon name="star-fill" color="warning" /]
            </div>
        [/card]
    [/column]
    [column]
        [card class="h-100"]
            [quote author="John Doe" cite="Developer, StartupXYZ"]
                Amazing features and excellent support. Worth every penny.
            [/quote]
            <div class="mt-3">
                [icon name="star-fill" color="warning" /]
                [icon name="star-fill" color="warning" /]
                [icon name="star-fill" color="warning" /]
                [icon name="star-fill" color="warning" /]
                [icon name="star-half" color="warning" /]
            </div>
        [/card]
    [/column]
[/columns]
```

## Real-World Use Cases

### Blog Post with Call-to-Action

```html
<p>Welcome to our latest blog post about productivity tips...</p>

[alert type="info"]
    [icon name="info-circle" /] This article is part of our productivity series.
[/alert]

<p>Here are the main points we'll cover:</p>

[columns count="2"]
    [column]
        <h3>Morning Routine</h3>
        <p>Start your day right with these tips...</p>
    [/column]
    [column]
        <h3>Time Management</h3>
        <p>Learn to prioritize effectively...</p>
    [/column]
[/columns]

[card title="Free Download" class="bg-light my-4"]
    Get our complete productivity guide as a free PDF!
    [button url="/downloads/productivity-guide" class="primary mt-3"]
        [icon name="download" /] Download Now
    [/button]
[/card]

[quote author="Peter Drucker"]
    Time is the scarcest resource and unless it is managed nothing else can be managed.
[/quote]
```

### Product Landing Page

```html
<!-- Hero Section -->
[alert type="success" dismissible="true"]
    [badge type="success"]New[/badge] Version 2.0 is now available!
[/alert]

<!-- Features -->
<h2 class="text-center mb-5">Why Choose Us?</h2>

[columns count="3" gap="4"]
    [column]
        [card class="text-center h-100"]
            [icon name="rocket-takeoff" size="64" color="primary" /]
            <h3 class="mt-3">Fast Setup</h3>
            <p>Get started in minutes with our quick setup wizard.</p>
        [/card]
    [/column]
    [column]
        [card class="text-center h-100"]
            [icon name="graph-up-arrow" size="64" color="success" /]
            <h3 class="mt-3">Scalable</h3>
            <p>Grows with your business from startup to enterprise.</p>
        [/card]
    [/column]
    [column]
        [card class="text-center h-100"]
            [icon name="headset" size="64" color="info" /]
            <h3 class="mt-3">24/7 Support</h3>
            <p>Our team is always here to help you succeed.</p>
        [/card]
    [/column]
[/columns]

<!-- Video Demo -->
<h2 class="text-center my-5">See It In Action</h2>
[youtube id="demo-video-id" /]

<!-- CTA -->
<div class="text-center my-5">
    [button url="/signup" class="primary btn-lg"]
        Start Free Trial [icon name="arrow-right" /]
    [/button]
</div>
```

### Documentation Page

```html
[alert type="warning"]
    [icon name="exclamation-triangle" /] This documentation is for version 2.0.
    Looking for <a href="/docs/v1">version 1.0</a>?
[/alert]

<h1>Getting Started</h1>

[accordion id="docs"]
    [accordion-item title="Installation" parent="docs" show="true"]
        <p>To install the package, run:</p>
        <pre><code>composer require package/name</code></pre>

        [alert type="info"]
            Make sure you have Composer installed on your system.
        [/alert]
    [/accordion-item]

    [accordion-item title="Configuration" parent="docs"]
        <p>Publish the configuration file:</p>
        <pre><code>php artisan vendor:publish</code></pre>

        [card title="config/package.php"]
            <pre><code>return [
    'enabled' => true,
    'cache' => true,
];</code></pre>
        [/card]
    [/accordion-item]

    [accordion-item title="Basic Usage" parent="docs"]
        <p>Here's a simple example:</p>
        <pre><code>use Package\Facade;

Facade::doSomething();</code></pre>
    [/accordion-item]
[/accordion]
```

## Custom Shortcodes

### Newsletter Signup

```php
// In your ServiceProvider
Shortcode::register('newsletter', function($attrs, $content) {
    $title = $attrs['title'] ?? 'Subscribe to our newsletter';
    $buttonText = $attrs['button'] ?? 'Subscribe';

    return '
        <div class="newsletter-box bg-light p-4 rounded">
            <h3>' . htmlspecialchars($title) . '</h3>
            <p>' . $content . '</p>
            <form action="/newsletter/subscribe" method="POST" class="d-flex gap-2">
                <input type="email" name="email" class="form-control" placeholder="Your email" required>
                <button type="submit" class="btn btn-primary">' . htmlspecialchars($buttonText) . '</button>
            </form>
        </div>
    ';
});
```

Usage:
```html
[newsletter title="Stay Updated" button="Join Now"]
    Get the latest updates, articles, and resources delivered weekly to your inbox.
[/newsletter]
```

### Progress Bar

```php
Shortcode::register('progress', function($attrs, $content) {
    $value = $attrs['value'] ?? 0;
    $type = $attrs['type'] ?? 'primary';
    $striped = isset($attrs['striped']) ? 'progress-bar-striped' : '';
    $animated = isset($attrs['animated']) ? 'progress-bar-animated' : '';

    return sprintf(
        '<div class="progress">
            <div class="progress-bar bg-%s %s %s" role="progressbar" style="width: %s%%" aria-valuenow="%s" aria-valuemin="0" aria-valuemax="100">%s</div>
        </div>',
        htmlspecialchars($type),
        $striped,
        $animated,
        (int) $value,
        (int) $value,
        $content ?: $value . '%'
    );
});
```

Usage:
```html
[progress value="75" type="success" striped animated]75%[/progress]
```

### Tabs

```php
Shortcode::register('tabs', function($attrs, $content) {
    $id = $attrs['id'] ?? 'tabs-' . uniqid();
    return '<div class="tabs" id="' . htmlspecialchars($id) . '">' . $content . '</div>';
});

Shortcode::register('tab', function($attrs, $content) {
    $title = $attrs['title'] ?? 'Tab';
    $active = isset($attrs['active']) ? 'active' : '';

    return sprintf(
        '<div class="tab-pane %s" role="tabpanel">
            <h4>%s</h4>
            %s
        </div>',
        $active,
        htmlspecialchars($title),
        $content
    );
});
```

Usage:
```html
[tabs id="features"]
    [tab title="Overview" active]
        Overview content here...
    [/tab]
    [tab title="Features"]
        Features content here...
    [/tab]
    [tab title="Pricing"]
        Pricing content here...
    [/tab]
[/tabs]
```

### Social Share Buttons

```php
Shortcode::register('social-share', function($attrs, $content) {
    $url = $attrs['url'] ?? url()->current();
    $title = $attrs['title'] ?? '';

    $twitter = sprintf('<a href="https://twitter.com/intent/tweet?url=%s&text=%s" class="btn btn-info" target="_blank"><i class="bi bi-twitter"></i> Tweet</a>', urlencode($url), urlencode($title));

    $facebook = sprintf('<a href="https://www.facebook.com/sharer/sharer.php?u=%s" class="btn btn-primary" target="_blank"><i class="bi bi-facebook"></i> Share</a>', urlencode($url));

    $linkedin = sprintf('<a href="https://www.linkedin.com/sharing/share-offsite/?url=%s" class="btn btn-primary" target="_blank"><i class="bi bi-linkedin"></i> Share</a>', urlencode($url));

    return '<div class="social-share d-flex gap-2">' . $facebook . $twitter . $linkedin . '</div>';
});
```

Usage:
```html
[social-share url="https://example.com/article" title="Check out this article!" /]
```

## Tips and Best Practices

1. **Keep shortcodes simple**: Complex logic should be in models or services
2. **Use views for complex HTML**: Render Blade views from shortcode callbacks
3. **Cache expensive operations**: Use Laravel's cache for database queries
4. **Sanitize attributes**: Always use `htmlspecialchars()` on user input
5. **Provide defaults**: Always have default values for attributes
6. **Document your shortcodes**: Create clear documentation for custom shortcodes
7. **Test thoroughly**: Test with various attribute combinations
8. **Consider performance**: Avoid database queries in shortcode callbacks when possible
