# Seo Module - Installation Guide

## Requirements

- PHP >= 8.2
- Laravel >= 11.0
- Composer

## Installation Steps

### 1. Module Registration

The module should be automatically discovered by Laravel's package discovery mechanism. If not, manually register it in `config/app.php`:

```php
'providers' => [
    // Other providers...
    Modules\Seo\Providers\SeoServiceProvider::class,
],
```

### 2. Run Migrations

Create the `seo_metas` table:

```bash
php artisan migrate
```

This will create a table with the following structure:
- `id`
- `seoable_id` and `seoable_type` (polymorphic)
- Basic SEO fields (title, description, keywords)
- Open Graph fields (og_title, og_description, og_image, og_type)
- Twitter Card fields (twitter_card, twitter_title, twitter_description, twitter_image)
- Advanced fields (canonical_url, robots)
- Timestamps

### 3. Publish Configuration (Optional)

If you want to customize the default configuration:

```bash
php artisan vendor:publish --tag=Seo-config
```

This creates `config/Seo.php` where you can set:
- Default title suffix
- Default meta description
- Default Open Graph image
- Twitter site handle
- Character limits and recommendations
- JSON-LD settings

### 4. Publish Views (Optional)

To customize the views:

```bash
php artisan vendor:publish --tag=Seo-views
```

This publishes the views to `resources/views/vendor/Seo/`.

### 5. Environment Configuration

Add these variables to your `.env` file:

```env
# SEO Configuration
SEO_DEFAULT_OG_IMAGE=https://yourdomain.com/images/og-default.jpg
SEO_TWITTER_SITE=@yourhandle
```

### 6. Add Trait to Models

For any model you want to add SEO capabilities to:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Seo\Traits\HasSeo;

class Post extends Model
{
    use HasSeo;

    // Your model code...
}
```

### 7. Add to Layout

In your main layout file (e.g., `resources/views/layouts/app.blade.php`):

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- SEO Meta Tags --}}
    @if(isset($model))
        <x-seo-tags :model="$model" />
    @else
        @seoTags
    @endif

    {{-- Other head elements --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @yield('content')
</body>
</html>
```

## Quick Start

### Example 1: Blog Post with SEO

**1. Model:**

```php
use Modules\Seo\Traits\HasSeo;

class Post extends Model
{
    use HasSeo;
}
```

**2. Controller:**

```php
use Modules\Seo\Services\SeoService;

public function show(Post $post)
{
    return view('posts.show', ['model' => $post]);
}

public function update(Request $request, Post $post)
{
    $post->update($request->validated());

    $post->updateSeoMeta([
        'title' => $request->input('seo_title'),
        'description' => $request->input('seo_description'),
        'og_image' => $request->input('og_image'),
    ]);

    return redirect()->route('posts.show', $post);
}
```

**3. View (posts/show.blade.php):**

```blade
@extends('layouts.theme')

@section('content')
    <article>
        <h1>{{ $model->title }}</h1>
        <div>{!! $model->content !!}</div>
    </article>
@endsection
```

**4. Form (posts/edit.blade.php):**

```blade
@extends('layouts.theme')

@section('content')
    <form action="{{ route('posts.update', $model) }}" method="POST">
        @csrf
        @method('PUT')

        <input type="text" name="title" value="{{ $model->title }}">
        <textarea name="content">{{ $model->content }}</textarea>

        {{-- Include SEO Form --}}
        @include('Seo::partials.seo-form', ['model' => $model])

        <button type="submit">Update</button>
    </form>
@endsection
```

## Verification

### Test SEO Tags are Working

1. Create or edit a model with SEO data
2. Visit the frontend page
3. View page source (right-click → View Page Source)
4. Look for meta tags in the `<head>`:

```html
<title>Your Page Title - Site Name</title>
<meta name="description" content="Your description">
<meta property="og:title" content="Your OG Title">
<meta property="og:image" content="https://...">
<meta name="twitter:card" content="summary_large_image">
```

### Test with Social Media Debuggers

**Facebook/LinkedIn:**
- Go to: https://developers.facebook.com/tools/debug/
- Enter your page URL
- Click "Debug"

**Twitter:**
- Go to: https://cards-dev.twitter.com/validator
- Enter your page URL
- Click "Preview card"

## Troubleshooting

### Issue: Meta tags not appearing

**Solution:**
1. Clear view cache: `php artisan view:clear`
2. Clear config cache: `php artisan config:clear`
3. Check that `<x-seo-tags>` or `@seoTags` is in the `<head>` section
4. Verify the model has the `HasSeo` trait

### Issue: Migration fails

**Solution:**
```bash
# Check if table already exists
php artisan db:show

# If it exists and you need to recreate
php artisan migrate:rollback
php artisan migrate
```

### Issue: Images not showing in social media preview

**Solution:**
1. Images must be publicly accessible (not behind authentication)
2. Use absolute URLs (https://domain.com/image.jpg)
3. Check image dimensions:
   - Open Graph: 1200x630px recommended
   - Twitter: 1200x675px for large image card
4. Image file size should be < 5MB

### Issue: Service provider not found

**Solution:**
1. Check `module.json` has correct provider
2. Run `composer dump-autoload`
3. Clear cache: `php artisan cache:clear`

### Issue: Views not found

**Solution:**
```bash
# Publish views
php artisan vendor:publish --tag=Seo-views

# Or check view path
php artisan view:cache
php artisan view:clear
```

## Upgrading

### From a previous version:

```bash
# Backup your database
php artisan backup:run

# Pull latest changes
git pull

# Run migrations
php artisan migrate

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Next Steps

1. Read the [README.md](README.md) for full documentation
2. Check [USAGE_EXAMPLES.md](USAGE_EXAMPLES.md) for code examples
3. Configure `config/Seo.php` to match your needs
4. Add SEO data to your existing models
5. Test with social media debuggers

## Support

For issues or questions:
1. Check the documentation files
2. Review the code examples
3. Contact the development team

## Additional Resources

- [Google Search Central](https://developers.google.com/search/docs)
- [Open Graph Protocol](https://ogp.me/)
- [Twitter Cards Documentation](https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards)
- [Schema.org](https://schema.org/)
