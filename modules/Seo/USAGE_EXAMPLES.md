# Seo Usage Examples

## Table of Contents

1. [Basic Setup](#basic-setup)
2. [Controller Examples](#controller-examples)
3. [View Examples](#view-examples)
4. [Service Usage](#service-usage)
5. [API Usage](#api-usage)
6. [Advanced Examples](#advanced-examples)

## Basic Setup

### Step 1: Add Trait to Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Seo\Traits\HasSeo;

class Post extends Model
{
    use HasSeo;

    protected $fillable = ['title', 'content', 'slug', 'excerpt'];
}
```

### Step 2: Add to Layout

```blade
<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- SEO Meta Tags --}}
    <x-seo-tags :model="$model ?? null" />

    {{-- Other head elements --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @yield('content')
</body>
</html>
```

## Controller Examples

### Blog Post Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Modules\Seo\Services\SeoService;
use Modules\Seo\Facades\Seo;

class PostController extends Controller
{
    /**
     * Display a post (Option 1: Using Service Injection)
     */
    public function show(Post $post, SeoService $seo)
    {
        // Load SEO data from the model
        $seo->loadFromModel($post);

        return view('posts.show', compact('post', 'seo'));
    }

    /**
     * Display a post (Option 2: Passing model directly to view)
     */
    public function showAlt(Post $post)
    {
        return view('posts.show', ['model' => $post]);
    }

    /**
     * Display a post (Option 3: Using Facade)
     */
    public function showFacade(Post $post)
    {
        Seo::loadFromModel($post);

        return view('posts.show', compact('post'));
    }

    /**
     * Create/Edit form
     */
    public function edit(Post $post)
    {
        return view('posts.edit', ['model' => $post]);
    }

    /**
     * Store a new post
     */
    public function store(Request $request, SeoService $seoService)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'slug' => 'required|string|unique:posts',
        ]);

        // Create the post
        $post = Post::create($validated);

        // Save SEO data
        $seoService->saveMeta($post, [
            'title' => $request->input('seo_title'),
            'description' => $request->input('seo_description'),
            'keywords' => $request->input('seo_keywords'),
            'og_title' => $request->input('og_title'),
            'og_description' => $request->input('og_description'),
            'og_image' => $request->input('og_image'),
            'og_type' => 'article',
            'twitter_card' => $request->input('twitter_card', 'summary_large_image'),
            'twitter_title' => $request->input('twitter_title'),
            'twitter_description' => $request->input('twitter_description'),
            'twitter_image' => $request->input('twitter_image'),
            'canonical_url' => $request->input('canonical_url'),
            'robots' => $request->input('robots', 'index,follow'),
        ]);

        return redirect()->route('posts.show', $post)
            ->with('success', 'Post created successfully!');
    }

    /**
     * Update a post
     */
    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post->update($validated);

        // Update SEO data using the model method
        $post->updateSeoMeta([
            'title' => $request->input('seo_title'),
            'description' => $request->input('seo_description'),
            'keywords' => $request->input('seo_keywords'),
            'og_title' => $request->input('og_title'),
            'og_description' => $request->input('og_description'),
            'og_image' => $request->input('og_image'),
            'og_type' => 'article',
            'twitter_card' => $request->input('twitter_card', 'summary_large_image'),
            'twitter_title' => $request->input('twitter_title'),
            'twitter_description' => $request->input('twitter_description'),
            'twitter_image' => $request->input('twitter_image'),
            'canonical_url' => $request->input('canonical_url'),
            'robots' => $request->input('robots', 'index,follow'),
        ]);

        return redirect()->route('posts.show', $post)
            ->with('success', 'Post updated successfully!');
    }
}
```

### Product Controller (E-commerce)

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Modules\Seo\Facades\Seo;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        // Set SEO data programmatically
        Seo::setTitle($product->name . ' - Buy Online')
            ->setDescription($product->short_description)
            ->setKeywords([$product->category->name, $product->brand, 'buy online'])
            ->setOgType('product')
            ->setOgImage($product->main_image_url)
            ->setTwitterCard('summary')
            ->setCanonical(route('products.show', $product->slug));

        return view('products.show', compact('product'));
    }
}
```

### Page Controller (Static Pages)

```php
<?php

namespace App\Http\Controllers;

use Modules\Seo\Facades\Seo;

class PageController extends Controller
{
    public function about()
    {
        Seo::setTitle('About Us')
            ->setDescription('Learn more about our company, mission, and values')
            ->setOgType('website')
            ->setRobots('index,follow');

        return view('pages.about');
    }

    public function contact()
    {
        Seo::setTitle('Contact Us')
            ->setDescription('Get in touch with our team')
            ->setRobots('noindex,follow'); // Don't index contact page

        return view('pages.contact');
    }
}
```

## View Examples

### Post Show View

```blade
{{-- resources/views/posts/show.blade.php --}}
@extends('layouts.theme')

@section('content')
    <article class="post">
        <h1>{{ $post->title }}</h1>
        <div class="post-meta">
            <span>{{ $post->created_at->format('F j, Y') }}</span>
        </div>
        <div class="post-content">
            {!! $post->content !!}
        </div>
    </article>
@endsection
```

### Post Edit Form

```blade
{{-- resources/views/posts/edit.blade.php --}}
@extends('layouts.theme')

@section('content')
    <div class="container">
        <h1>Edit Post</h1>

        <form action="{{ route('posts.update', $model) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title"
                       value="{{ old('title', $model->title) }}" required>
            </div>

            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea class="form-control" id="content" name="content"
                          rows="10" required>{{ old('content', $model->content) }}</textarea>
            </div>

            {{-- Include SEO Form --}}
            @include('Seo::partials.seo-form', ['model' => $model])

            <button type="submit" class="btn btn-primary">Update Post</button>
        </form>
    </div>
@endsection
```

### Custom Layout with Manual SEO

```blade
{{-- resources/views/layouts/custom.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Manual SEO using blade directive --}}
    @seoTags

    {{-- Or using the component --}}
    {{-- <x-seo-tags :model="$model ?? null" /> --}}

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @yield('content')
</body>
</html>
```

## Service Usage

### Using the Service Class

```php
use Modules\Seo\Services\SeoService;

// Get the service instance
$seo = app(SeoService::class);
// or
$seo = app('seo');

// Set various SEO properties
$seo->setTitle('My Page Title')
    ->setDescription('This is a description of my page')
    ->setKeywords(['laravel', 'seo', 'php'])
    ->setOgImage('https://example.com/image.jpg')
    ->setTwitterCard('summary_large_image')
    ->setCanonical('https://example.com/my-page')
    ->setRobots('index,follow');

// Render all meta tags as HTML
$html = $seo->render();

// Get all data as array
$data = $seo->getData();

// Get specific value
$title = $seo->get('title');

// Generate preview
$preview = $seo->generatePreview();
/*
[
    'google' => [
        'title' => 'My Page Title',
        'url' => 'https://example.com/my-page',
        'description' => 'This is a description...'
    ],
    'facebook' => [...],
    'twitter' => [...]
]
*/
```

### Using the Facade

```php
use Modules\Seo\Facades\Seo;

// All methods are available through the facade
Seo::setTitle('My Page')
   ->setDescription('Page description')
   ->render();

// Get data
$title = Seo::get('title');
$allData = Seo::getData();
```

## API Usage

### Fetch SEO Metas

```bash
# List all SEO metas
curl -X GET http://localhost/api/seo-helper/seo-metas

# Filter by type
curl -X GET http://localhost/api/seo-helper/seo-metas?seoable_type=App\\Models\\Post

# Search
curl -X GET http://localhost/api/seo-helper/seo-metas?search=laravel

# Pagination
curl -X GET http://localhost/api/seo-helper/seo-metas?page=2&per_page=20
```

### Create SEO Meta

```bash
curl -X POST http://localhost/api/seo-helper/seo-metas \
  -H "Content-Type: application/json" \
  -d '{
    "seoable_id": 1,
    "seoable_type": "App\\Models\\Post",
    "title": "My SEO Title",
    "description": "My SEO description",
    "keywords": "laravel, seo",
    "og_image": "https://example.com/image.jpg",
    "twitter_card": "summary_large_image"
  }'
```

### Update SEO Meta

```bash
curl -X PUT http://localhost/api/seo-helper/seo-metas/1 \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated SEO Title",
    "description": "Updated description"
  }'
```

### Generate Preview

```bash
curl -X POST http://localhost/api/seo-helper/seo-metas/preview \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Title",
    "description": "Test description",
    "og_image": "https://example.com/image.jpg"
  }'
```

### Bulk Update

```bash
curl -X POST http://localhost/api/seo-helper/seo-metas/bulk-update \
  -H "Content-Type: application/json" \
  -d '{
    "ids": [1, 2, 3],
    "data": {
      "robots": "noindex,follow"
    }
  }'
```

### Get Statistics

```bash
curl -X GET http://localhost/api/seo-helper/seo-metas/statistics/all
```

## Advanced Examples

### Dynamic SEO Based on User Language

```php
public function show(Post $post)
{
    $locale = app()->getLocale();

    $seo = app(SeoService::class);
    $seo->setTitle($post->getTranslation('title', $locale))
        ->setDescription($post->getTranslation('excerpt', $locale))
        ->setOgImage($post->featured_image);

    return view('posts.show', compact('post'));
}
```

### SEO for Paginated Lists

```php
public function index(Request $request)
{
    $posts = Post::paginate(10);
    $page = $request->get('page', 1);

    $seo = app(SeoService::class);
    $title = 'Blog Posts';

    if ($page > 1) {
        $title .= ' - Page ' . $page;
        // Prevent duplicate content
        $seo->setRobots('noindex,follow');
    }

    $seo->setTitle($title)
        ->setDescription('Browse our latest blog posts');

    return view('posts.index', compact('posts'));
}
```

### Automatically Generate Description from Content

```php
public function store(Request $request)
{
    $post = Post::create($request->validated());

    // Auto-generate description if not provided
    $description = $request->input('seo_description');

    if (empty($description)) {
        // Strip HTML and limit to 160 characters
        $description = Str::limit(
            strip_tags($post->content),
            160,
            '...'
        );
    }

    $post->updateSeoMeta([
        'title' => $request->input('seo_title', $post->title),
        'description' => $description,
        'og_type' => 'article',
    ]);

    return redirect()->route('posts.show', $post);
}
```

### Conditional Canonical URLs

```php
public function show(Request $request, Post $post)
{
    $seo = app(SeoService::class);

    // Set canonical to the clean URL (without query parameters)
    $canonicalUrl = route('posts.show', $post->slug);

    // If accessed via different URL (like /posts/123 instead of /posts/slug)
    if ($request->url() !== $canonicalUrl) {
        $seo->setCanonical($canonicalUrl);
    }

    return view('posts.show', compact('post'));
}
```

### SEO for AMP Pages

```php
public function showAmp(Post $post)
{
    $seo = app(SeoService::class);
    $seo->loadFromModel($post);

    // Set canonical to the non-AMP version
    $seo->setCanonical(route('posts.show', $post->slug));

    return view('posts.show-amp', compact('post'));
}
```

### Image Optimization for Social Sharing

```php
public function update(Request $request, Post $post)
{
    $post->update($request->validated());

    $ogImage = $request->input('og_image');

    // If no specific OG image, generate one
    if (empty($ogImage) && $post->featured_image) {
        // Resize image to 1200x630 for optimal OG display
        $ogImage = $this->generateOgImage($post->featured_image);
    }

    $post->updateSeoMeta([
        'og_image' => $ogImage,
        'twitter_image' => $ogImage, // Use same image for Twitter
    ]);

    return redirect()->back();
}

private function generateOgImage($imageUrl)
{
    // Your image processing logic here
    // Example: resize to 1200x630, compress, etc.
    return $imageUrl; // Return processed image URL
}
```

### SEO Audit Command

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Seo\Models\SeoMeta;
use App\Models\Post;

class SeoAudit extends Command
{
    protected $signature = 'seo:audit';
    protected $description = 'Audit SEO data across the site';

    public function handle()
    {
        $posts = Post::all();
        $issues = [];

        foreach ($posts as $post) {
            if (!$post->hasSeoMeta()) {
                $issues[] = "Post #{$post->id} has no SEO data";
            } elseif (empty($post->seo_description)) {
                $issues[] = "Post #{$post->id} has no meta description";
            } elseif (strlen($post->seo_title) > 60) {
                $issues[] = "Post #{$post->id} title is too long (" . strlen($post->seo_title) . " chars)";
            }
        }

        if (count($issues) > 0) {
            $this->error('SEO Issues Found:');
            foreach ($issues as $issue) {
                $this->line('- ' . $issue);
            }
        } else {
            $this->info('No SEO issues found!');
        }
    }
}
```
