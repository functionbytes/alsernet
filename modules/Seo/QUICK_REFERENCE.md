# Seo - Quick Reference Guide

## Installation

```bash
php artisan migrate
php artisan vendor:publish --tag=Seo-config  # Optional
```

## Basic Usage

### 1. Add Trait to Model

```php
use Modules\Seo\Traits\HasSeo;

class Post extends Model {
    use HasSeo;
}
```

### 2. Add to Layout

```blade
<head>
    <x-seo-tags :model="$post" />
</head>
```

### 3. Include Form

```blade
@include('Seo::partials.seo-form', ['model' => $post])
```

### 4. Save SEO Data

```php
$post->updateSeoMeta([
    'title' => $request->input('seo_title'),
    'description' => $request->input('seo_description'),
    'og_image' => $request->input('og_image'),
]);
```

## Helper Functions

```php
// Get service instance
seo();

// Set title
seo_title('My Page Title');

// Set description
seo_description('Page description');

// Set keywords
seo_keywords(['laravel', 'seo']);

// Set image (both OG and Twitter)
seo_image('https://example.com/image.jpg');

// Set canonical URL
seo_canonical('https://example.com/page');

// Set robots
seo_robots('index,follow');

// Set noindex
seo_noindex();  // noindex,follow
seo_noindex(true);  // noindex,nofollow

// Load from model
seo_from_model($post);

// Render tags
seo_render();

// Generate preview
seo_preview();

// Truncate for SEO
truncate_for_seo($text, 'description', 'seo');
```

## Service Methods

```php
use Modules\Seo\Services\SeoService;

$seo = app(SeoService::class);

// Chainable setters
$seo->setTitle('Title')
    ->setDescription('Description')
    ->setKeywords(['key1', 'key2'])
    ->setOgTitle('OG Title')
    ->setOgDescription('OG Description')
    ->setOgImage('https://...')
    ->setOgType('article')
    ->setTwitterCard('summary_large_image')
    ->setTwitterTitle('Twitter Title')
    ->setTwitterDescription('Twitter Description')
    ->setTwitterImage('https://...')
    ->setCanonical('https://...')
    ->setRobots('index,follow');

// Load from model
$seo->loadFromModel($post);

// Save to model
$seo->saveMeta($post, $data);

// Get data
$data = $seo->getData();
$title = $seo->get('title');

// Render HTML
$html = $seo->render();

// Generate preview
$preview = $seo->generatePreview();
```

## Facade Usage

```php
use Modules\Seo\Facades\Seo;

Seo::setTitle('Title')
   ->setDescription('Description')
   ->render();
```

## Model Methods

```php
// Get SEO data (with fallbacks)
$post->seo_title;
$post->seo_description;
$post->seo_keywords;
$post->og_title;
$post->og_description;
$post->og_image;
$post->twitter_card;
$post->canonical_url;
$post->robots;

// Update SEO meta
$post->updateSeoMeta([...]);

// Delete SEO meta
$post->deleteSeoMeta();

// Check if has SEO meta
$post->hasSeoMeta();

// Check indexability
$post->isIndexable();
$post->isFollowable();

// Access relation
$post->seoMeta;
```

## Blade Directives

```blade
{{-- Render SEO tags --}}
@seoTags

{{-- Include SEO form --}}
@seoForm($model)
```

## API Endpoints

```bash
# List
GET /api/seo-helper/seo-metas

# Get
GET /api/seo-helper/seo-metas/{id}

# Create
POST /api/seo-helper/seo-metas

# Update
PUT /api/seo-helper/seo-metas/{id}

# Delete
DELETE /api/seo-helper/seo-metas/{id}

# Preview
POST /api/seo-helper/seo-metas/preview

# Bulk Update
POST /api/seo-helper/seo-metas/bulk-update

# Statistics
GET /api/seo-helper/seo-metas/statistics/all
```

## Configuration Keys

```php
// config/Seo.php
'default_title_suffix'     // ' - Site Name'
'default_description'      // Default description
'default_og_image'         // Default image URL
'twitter_site'             // '@handle'
'default_robots'           // 'index,follow'
'title_limits'             // Character limits
'description_limits'       // Character limits
'image_sizes'              // Recommended sizes
'canonical'                // Canonical settings
'json_ld'                  // Schema.org settings
```

## Environment Variables

```env
SEO_DEFAULT_OG_IMAGE=https://...
SEO_TWITTER_SITE=@handle
```

## Common Patterns

### Controller

```php
public function show(Post $post) {
    seo_from_model($post);
    return view('posts.show', compact('post'));
}

public function update(Request $request, Post $post) {
    $post->update($request->validated());
    $post->updateSeoMeta($request->only([
        'seo_title', 'seo_description', 'seo_keywords',
        'og_title', 'og_description', 'og_image',
        'twitter_card', 'canonical_url', 'robots'
    ]));
    return redirect()->back();
}
```

### View

```blade
@extends('layouts.theme')

@section('content')
    <h1>{{ $post->title }}</h1>
    <div>{!! $post->content !!}</div>
@endsection
```

### Form

```blade
<form method="POST">
    @csrf
    <input name="title" value="{{ $model->title }}">
    @include('Seo::partials.seo-form', ['model' => $model])
    <button type="submit">Save</button>
</form>
```

## Best Practices

### Title
- Keep under 60 characters for Google
- Include primary keyword
- Make it compelling
- Don't duplicate H1

### Description
- Keep under 160 characters
- Include call-to-action
- Summarize content
- Use active voice

### Keywords
- 3-5 relevant keywords
- Comma separated
- Match content
- Avoid keyword stuffing

### Images
- Use 1200x630px for OG
- JPG or PNG format
- Under 5MB file size
- Descriptive filename

### Canonical
- Point to original content
- Use for duplicate content
- Include protocol (https)
- Avoid chain redirects

### Robots
- `index,follow` - Default
- `noindex,follow` - Duplicate/thin content
- `noindex,nofollow` - Private pages
- `index,nofollow` - Avoid passing link equity

## Troubleshooting

### Tags not showing
```bash
php artisan view:clear
php artisan config:clear
```

### Images not loading
- Use absolute URLs
- Check image is public
- Verify image dimensions
- Test with debugger

### Social media preview not updating
- Use Facebook Debugger
- Use Twitter Card Validator
- Clear social media cache
- Allow 24h for propagation

## Testing Tools

- **Facebook**: https://developers.facebook.com/tools/debug/
- **Twitter**: https://cards-dev.twitter.com/validator
- **LinkedIn**: https://www.linkedin.com/post-inspector/
- **Google**: Search Console
- **Schema**: https://search.google.com/test/rich-results

## Character Limits

| Platform | Title | Description |
|----------|-------|-------------|
| Google   | 50-60 | 150-160     |
| Facebook | 95    | 200         |
| Twitter  | 70    | 200         |

## Image Sizes

| Platform | Dimensions | Ratio |
|----------|------------|-------|
| OG       | 1200x630   | 1.91:1|
| Twitter  | 1200x675   | 16:9  |

## Support

- Read [README.md](README.md)
- Check [USAGE_EXAMPLES.md](USAGE_EXAMPLES.md)
- Review [INSTALLATION.md](INSTALLATION.md)
- Contact development team
