# Seo Module

A comprehensive SEO management module for Laravel applications. This module provides a complete solution for managing meta tags, Open Graph tags, Twitter Card tags, and other SEO-related features.

## Features

### SEO Management
- **Basic SEO Meta Tags**: Title, description, keywords
- **Open Graph Tags**: Full support for Facebook, LinkedIn sharing
- **Twitter Card Tags**: Support for Twitter/X cards
- **Canonical URLs**: Prevent duplicate content issues
- **Robots Directives**: Control search engine indexing
- **Live Preview**: See how your content will appear on Google, Facebook, and Twitter
- **Polymorphic Relations**: Attach SEO data to any model
- **RESTful API**: Complete CRUD operations via API
- **Blade Components**: Easy integration in views

### Schema.org Structured Data (NEW)
- **Automatic JSON-LD Generation**: Generate valid Schema.org markup
- **Multiple Schema Types**: Article, Organization, Breadcrumb, FAQ, WebPage, Product, LocalBusiness
- **Graph Support**: Combine multiple schemas in a single output
- **HasStructuredData Trait**: Easy integration with models
- **SchemaOrgService**: Dedicated service for schema generation
- **Artisan Commands**: Test and generate schemas from CLI
- **Schema.org v13 Compatible**: Follows latest specification
- **Flexible Configuration**: Control schemas via config and environment variables

## Installation

1. The module should be automatically discovered by Laravel. If not, register the service provider in `config/app.php`:

```php
'providers' => [
    // ...
    Modules\Seo\Providers\SeoServiceProvider::class,
],
```

2. Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag=Seo-config
```

3. Publish the views (optional):

```bash
php artisan vendor:publish --tag=Seo-views
```

4. Run the migrations:

```bash
php artisan migrate
```

## Configuration

Edit `config/Seo.php` to customize default values:

```php
return [
    'default_title_suffix' => ' - ' . config('app.name'),
    'default_description' => 'Welcome to our website',
    'default_og_image' => asset('images/og-default.jpg'),
    'twitter_site' => '@yourhandle',
    // ... more options
];
```

### Environment Variables

Add these to your `.env` file:

```env
# Basic SEO
SEO_DEFAULT_OG_IMAGE=https://example.com/images/og-default.jpg
SEO_TWITTER_SITE=@yourhandle

# Schema.org Structured Data
SEO_SCHEMA_ENABLED=true
SEO_ORGANIZATION_NAME="Your Company Name"
SEO_ORGANIZATION_URL="https://example.com"
SEO_ORGANIZATION_LOGO="https://example.com/logo.png"
SEO_ORGANIZATION_EMAIL="info@example.com"
SEO_ORGANIZATION_PHONE="+1-555-123-4567"
```

For more configuration options, see [SCHEMA_ORG.md](SCHEMA_ORG.md)

## Usage

### 1. Add the Traits to Your Model

```php
use Modules\Seo\Traits\HasSeo;
use Modules\Seo\Traits\HasStructuredData;

class Post extends Model
{
    use HasSeo, HasStructuredData;

    // Define schema type for structured data
    public function getSchemaType(): string
    {
        return 'Article'; // or 'BlogPosting', 'NewsArticle', etc.
    }

    // Optional: Provide custom schema options
    public function getSchemaOptions(): array
    {
        return [
            'author' => $this->author->name ?? 'Admin',
        ];
    }

    // Optional: Define breadcrumbs
    public function getBreadcrumbItems(): ?array
    {
        return [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Blog', 'url' => route('blog.index')],
            ['name' => $this->title, 'url' => route('blog.show', $this->slug)],
        ];
    }
}
```

### 2. Display SEO Tags in Your Layout

#### Option A: Using the Blade Component (Recommended)

```blade
<head>
    {{-- This includes both SEO meta tags AND Schema.org structured data --}}
    <x-seo-tags :model="$post" />
</head>
```

#### Option B: Using the SEO Service

In your controller:

```php
use Modules\Seo\Services\SeoService;

public function show(Post $post, SeoService $seo)
{
    $seo->loadFromModel($post);

    return view('posts.show', compact('post', 'seo'));
}
```

In your view:

```blade
<head>
    @seoTags
    @schemaScript {{-- Include Schema.org structured data --}}
</head>
```

### 3. Add SEO Form to Your Admin Panel

```blade
<form action="{{ route('posts.update', $post) }}" method="POST">
    @csrf
    @method('PUT')

    {{-- Your existing form fields --}}
    <input type="text" name="title" value="{{ $post->title }}">
    <textarea name="content">{{ $post->content }}</textarea>

    {{-- SEO Form Partial --}}
    @include('Seo::partials.seo-form', ['model' => $post])

    <button type="submit">Save</button>
</form>
```

### 4. Save SEO Data in Your Controller

```php
use Modules\Seo\Services\SeoService;

public function update(Request $request, Post $post, SeoService $seoService)
{
    // Update your model
    $post->update($request->only(['title', 'content']));

    // Save SEO data
    $seoService->saveMeta($post, [
        'title' => $request->input('seo_title'),
        'description' => $request->input('seo_description'),
        'keywords' => $request->input('seo_keywords'),
        'og_title' => $request->input('og_title'),
        'og_description' => $request->input('og_description'),
        'og_image' => $request->input('og_image'),
        'og_type' => $request->input('og_type', 'article'),
        'twitter_card' => $request->input('twitter_card', 'summary_large_image'),
        'twitter_title' => $request->input('twitter_title'),
        'twitter_description' => $request->input('twitter_description'),
        'twitter_image' => $request->input('twitter_image'),
        'canonical_url' => $request->input('canonical_url'),
        'robots' => $request->input('robots', 'index,follow'),
    ]);

    return redirect()->back()->with('success', 'Post updated successfully');
}
```

### 5. Using the Service Manually

```php
use Modules\Seo\Services\SeoService;

$seo = app(SeoService::class);

$seo->setTitle('My Page Title')
    ->setDescription('This is my page description')
    ->setKeywords(['keyword1', 'keyword2'])
    ->setOgImage('https://example.com/image.jpg')
    ->setTwitterCard('summary_large_image')
    ->setCanonical('https://example.com/canonical-url')
    ->setRobots('index,follow');

// Render all meta tags
echo $seo->render();

// Get preview data
$preview = $seo->generatePreview();
```

### 6. Accessing SEO Data

```php
// Get SEO title (with fallback to model's title)
$post->seo_title;

// Get SEO description (with fallback to model's description)
$post->seo_description;

// Get Open Graph title
$post->og_title;

// Get Twitter card type
$post->twitter_card;

// Check if page is indexable
$post->isIndexable();

// Check if page is followable
$post->isFollowable();

// Get the SeoMeta relation
$post->seoMeta;
```

### 7. Updating SEO Data

```php
// Create or update SEO meta
$post->updateSeoMeta([
    'title' => 'New SEO Title',
    'description' => 'New description',
    'og_image' => 'https://example.com/new-image.jpg',
]);

// Delete SEO meta
$post->deleteSeoMeta();

// Check if has SEO meta
if ($post->hasSeoMeta()) {
    // Do something
}
```

## API Endpoints

### List SEO Metas
```
GET /api/seo-helper/seo-metas
```

Query parameters:
- `seoable_type`: Filter by model type
- `robots`: Filter by robots directive
- `search`: Search in title or description
- `per_page`: Items per page (default: 15)

### Get SEO Meta
```
GET /api/seo-helper/seo-metas/{id}
```

### Create SEO Meta
```
POST /api/seo-helper/seo-metas
```

### Update SEO Meta
```
PUT /api/seo-helper/seo-metas/{id}
```

### Delete SEO Meta
```
DELETE /api/seo-helper/seo-metas/{id}
```

### Preview SEO Meta
```
POST /api/seo-helper/seo-metas/preview
```

Request body:
```json
{
    "title": "Page Title",
    "description": "Page description",
    "og_title": "OG Title",
    "og_description": "OG Description",
    "og_image": "https://example.com/image.jpg"
}
```

### Bulk Update
```
POST /api/seo-helper/seo-metas/bulk-update
```

Request body:
```json
{
    "ids": [1, 2, 3],
    "data": {
        "robots": "noindex,follow"
    }
}
```

### Get Statistics
```
GET /api/seo-helper/seo-metas/statistics/all
```

## Best Practices

### Title Length
- **SEO Title**: 50-60 characters (Google displays ~50-60)
- **Open Graph Title**: Up to 95 characters
- **Twitter Title**: Up to 70 characters

### Description Length
- **Meta Description**: 150-160 characters
- **Open Graph Description**: Up to 200 characters
- **Twitter Description**: Up to 200 characters

### Image Sizes
- **Open Graph**: 1200x630px (1.91:1 ratio)
- **Twitter Summary**: 120x120px (1:1 ratio)
- **Twitter Large Image**: 1200x675px (16:9 ratio)

### Robots Directives
- `index,follow`: Allow indexing and following links (default)
- `noindex,nofollow`: Don't index and don't follow links
- `noindex,follow`: Don't index but follow links
- `index,nofollow`: Index but don't follow links

## Examples

### Blog Post

```php
$post->updateSeoMeta([
    'title' => 'How to Master Laravel',
    'description' => 'Learn advanced Laravel techniques in this comprehensive guide...',
    'keywords' => 'laravel, php, web development, tutorial',
    'og_type' => 'article',
    'og_image' => 'https://example.com/blog/laravel-guide.jpg',
    'twitter_card' => 'summary_large_image',
    'canonical_url' => 'https://example.com/blog/master-laravel',
    'robots' => 'index,follow',
]);
```

### Product Page

```php
$product->updateSeoMeta([
    'title' => $product->name . ' - Buy Online',
    'description' => 'Buy ' . $product->name . ' at the best price. ' . $product->short_description,
    'og_type' => 'product',
    'og_image' => $product->main_image_url,
    'twitter_card' => 'summary',
    'robots' => 'index,follow',
]);
```

### Draft/Private Content

```php
$draft->updateSeoMeta([
    'robots' => 'noindex,nofollow',
]);
```

## Artisan Commands

### Schema.org Testing and Generation

```bash
# Test all schema types
php artisan seo:generate-schemas --test

# Generate specific schema with examples
php artisan seo:generate-schemas article
php artisan seo:generate-schemas organization
php artisan seo:generate-schemas breadcrumb
php artisan seo:generate-schemas faq
php artisan seo:generate-schemas webpage
php artisan seo:generate-schemas product
php artisan seo:generate-schemas localbusiness

# Pretty print JSON
php artisan seo:generate-schemas article --pretty
```

Expected output:
```
Testing Schema.org structured data generation...

[PASS] Article Schema
[PASS] Organization Schema
[PASS] Breadcrumb Schema
[PASS] FAQ Schema
[PASS] WebPage Schema
[PASS] Product Schema
[PASS] LocalBusiness Schema
[PASS] Graph Schema

Tests completed: 8 passed, 0 failed
```

## Documentation

- **[SCHEMA_ORG.md](SCHEMA_ORG.md)** - Complete Schema.org documentation
- **[EXAMPLE_USAGE.md](EXAMPLE_USAGE.md)** - Detailed usage examples
- **[README_REDIRECTS.md](README_REDIRECTS.md)** - SEO redirects documentation

## Troubleshooting

### Meta tags not appearing
- Ensure the `HasSeo` trait is added to your model
- Check that the `<x-seo-tags>` component or `@seoTags` directive is in the `<head>` section
- Clear your view cache: `php artisan view:clear`

### Images not displaying in social media previews
- Ensure images are publicly accessible
- Use absolute URLs (including https://)
- Check image dimensions match recommendations
- Use social media debugging tools:
  - Facebook: https://developers.facebook.com/tools/debug/
  - Twitter: https://cards-dev.twitter.com/validator

### Changes not reflecting
- Clear cache: `php artisan cache:clear`
- Clear config: `php artisan config:clear`
- Clear view cache: `php artisan view:clear`

### Schemas not generating
- Check `SEO_SCHEMA_ENABLED=true` in `.env`
- Verify model uses `HasStructuredData` trait
- Run test command: `php artisan seo:generate-schemas --test`
- Validate schemas at: https://validator.schema.org/

### Schema validation errors
- Ensure all URLs are absolute (not relative)
- Check date formats are ISO 8601
- Verify required fields are present
- Test individual schemas: `php artisan seo:generate-schemas article --pretty`

## License

This module is open-sourced software licensed under the MIT license.

## Support

For issues, feature requests, or questions, please contact the development team.
