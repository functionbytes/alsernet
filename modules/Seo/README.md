# SEO Module

Search engine optimization, meta tags, OpenGraph, and JSON-LD structured data.

## Overview

The SEO module provides comprehensive tools for optimizing web pages for search engines and social media platforms.

## Features

- **Meta Tags** - Title, description, keywords, canonical URLs, robots directives
- **OpenGraph Tags** - Facebook and social media optimization
- **Twitter Cards** - Twitter-specific meta tags for rich cards
- **JSON-LD** - Structured data for search engines
- **Webmaster Verification** - Google, Bing, Yandex, Pinterest verification

## Installation

The module is automatically included via Composer's merge-plugin system.

Dependencies:
- `artesaos/seotools` (^1.3)

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag=seo-config
```

The configuration file is located at `config/seo.php`.

## Usage

### Setting Meta Tags

In your controller or blade template:

```php
use Artesaos\SEOTools\Facades\SEOMeta;

SEOMeta::setTitle('My Page Title');
SEOMeta::setDescription('My page description');
SEOMeta::addKeyword(['keyword1', 'keyword2']);
SEOMeta::setCanonical('https://example.com/my-page');
```

### OpenGraph Tags

```php
use Artesaos\SEOTools\Facades\OpenGraph;

OpenGraph::setTitle('My Page Title');
OpenGraph::setDescription('My page description');
OpenGraph::setUrl('https://example.com/my-page');
OpenGraph::addImage('https://example.com/image.jpg');
OpenGraph::setType('website');
```

### Twitter Cards

```php
use Artesaos\SEOTools\Facades\TwitterCard;

TwitterCard::setType('summary_large_image');
TwitterCard::setTitle('My Page Title');
TwitterCard::setDescription('My page description');
TwitterCard::setImage('https://example.com/image.jpg');
```

### JSON-LD Structured Data

```php
use Artesaos\SEOTools\Facades\JsonLd;

JsonLd::setTitle('My Page Title');
JsonLd::setDescription('My page description');
JsonLd::setUrl('https://example.com/my-page');
JsonLd::setType('Article');
```

### In Blade Templates

```blade
<head>
    {!! SEOMeta::generate() !!}
    {!! OpenGraph::generate() !!}
    {!! TwitterCard::generate() !!}
    {!! JsonLd::generate() !!}
</head>
```

## SEO Best Practices

1. **Unique Titles** - Create compelling, unique titles for each page
2. **Descriptions** - Write clear descriptions (160-160 characters)
3. **Keywords** - Focus on 2-3 relevant keywords per page
4. **Canonical URLs** - Prevent duplicate content issues
5. **Structured Data** - Add JSON-LD for rich snippets
6. **OpenGraph** - Optimize for social media sharing
7. **Mobile** - Ensure responsive, mobile-friendly design

## Common Use Cases

### Blog Post

```php
JsonLd::setType('BlogPosting');
JsonLd::setTitle($post->title);
JsonLd::setDescription($post->excerpt);
JsonLd::setUrl(route('posts.show', $post));
OpenGraph::addImage($post->featured_image);
```

### Product Page

```php
JsonLd::setType('Product');
JsonLd::setTitle($product->name);
JsonLd::setDescription($product->description);
OpenGraph::setType('og:product');
OpenGraph::addImage($product->image);
```

### Organization

```php
JsonLd::setType('Organization');
JsonLd::setTitle(config('app.name'));
JsonLd::setUrl(config('app.url'));
```

## References

- [Artesaos SEOTools GitHub](https://github.com/artesaos/seotools)
- [Google SEO Starter Guide](https://developers.google.com/search/docs/beginner/seo-starter-guide)
- [JSON-LD Documentation](https://json-ld.org/)
- [OpenGraph Protocol](https://ogp.me/)
- [Twitter Card Documentation](https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards)

## Authors

Alsernet Development Team
