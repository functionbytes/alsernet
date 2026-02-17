<?php

namespace Modules\Seo\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class SchemaOrgService
{
    /**
     * Schema.org context URL.
     */
    protected string $context = 'https://schema.org';

    /**
     * Generate Article schema for pages/posts.
     */
    public function generateArticleSchema(object $model, array $options = []): array
    {
        $schema = [
            '@context' => $this->context,
            '@type' => $options['type'] ?? 'Article',
            'headline' => $this->getModelAttribute($model, 'title'),
            'description' => $this->getModelAttribute($model, 'description', 'seo_description'),
        ];

        // URL
        if ($url = $this->getModelUrl($model)) {
            $schema['url'] = $url;
            $schema['mainEntityOfPage'] = [
                '@type' => 'WebPage',
                '@id' => $url,
            ];
        }

        // Image
        if ($image = $this->getModelImage($model)) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $image,
            ];
        }

        // Author
        if (isset($options['author'])) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $options['author'],
            ];
        } elseif ($author = $this->getModelAttribute($model, 'author', 'author_name')) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $author,
            ];
        }

        // Publisher (Organization)
        $schema['publisher'] = $this->generateOrganizationSchema();

        // Dates
        if ($model->created_at) {
            $schema['datePublished'] = $model->created_at->toIso8601String();
        }

        if ($model->updated_at) {
            $schema['dateModified'] = $model->updated_at->toIso8601String();
        }

        // Keywords
        if ($keywords = $this->getModelAttribute($model, 'keywords', 'seo_keywords')) {
            $schema['keywords'] = is_array($keywords) ? implode(', ', $keywords) : $keywords;
        }

        // Word count
        if ($content = $this->getModelAttribute($model, 'content', 'body')) {
            $schema['wordCount'] = str_word_count(strip_tags($content));
        }

        // Article section/category
        if ($category = $this->getModelAttribute($model, 'category', 'category_name')) {
            $schema['articleSection'] = $category;
        }

        return array_filter($schema, fn ($value) => ! is_null($value) && $value !== '');
    }

    /**
     * Generate Organization schema.
     */
    public function generateOrganizationSchema(array $options = []): array
    {
        $config = Config::get('Seo.schema.organization', []);

        $schema = [
            '@context' => $this->context,
            '@type' => $options['type'] ?? $config['type'] ?? 'Organization',
            'name' => $options['name'] ?? $config['name'] ?? Config::get('app.name'),
            'url' => $options['url'] ?? $config['url'] ?? Config::get('app.url'),
        ];

        // Logo
        if ($logo = $options['logo'] ?? $config['logo'] ?? null) {
            $schema['logo'] = [
                '@type' => 'ImageObject',
                'url' => $logo,
            ];
        }

        // Description
        if ($description = $options['description'] ?? $config['description'] ?? null) {
            $schema['description'] = $description;
        }

        // Contact information
        if ($email = $options['email'] ?? $config['email'] ?? null) {
            $schema['email'] = $email;
        }

        if ($phone = $options['phone'] ?? $config['phone'] ?? null) {
            $schema['telephone'] = $phone;
        }

        // Address
        if (isset($options['address']) || isset($config['address'])) {
            $address = $options['address'] ?? $config['address'];
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address['street'] ?? null,
                'addressLocality' => $address['city'] ?? null,
                'addressRegion' => $address['region'] ?? null,
                'postalCode' => $address['postal_code'] ?? null,
                'addressCountry' => $address['country'] ?? null,
            ];
            $schema['address'] = array_filter($schema['address']);
        }

        // Social media profiles
        if ($socials = $options['social_profiles'] ?? $config['social_profiles'] ?? null) {
            $schema['sameAs'] = array_values(array_filter($socials));
        }

        return array_filter($schema, fn ($value) => ! is_null($value) && $value !== '');
    }

    /**
     * Generate Breadcrumb schema.
     */
    public function generateBreadcrumbSchema(array $items): array
    {
        $schema = [
            '@context' => $this->context,
            '@type' => 'BreadcrumbList',
            'itemListElement' => [],
        ];

        foreach ($items as $position => $item) {
            $schema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $item['name'] ?? $item['title'] ?? '',
                'item' => $item['url'] ?? $item['link'] ?? '',
            ];
        }

        return $schema;
    }

    /**
     * Generate FAQ schema.
     */
    public function generateFAQSchema(array $questions): array
    {
        $schema = [
            '@context' => $this->context,
            '@type' => 'FAQPage',
            'mainEntity' => [],
        ];

        foreach ($questions as $qa) {
            $schema['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $qa['question'] ?? $qa['q'] ?? '',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $qa['answer'] ?? $qa['a'] ?? '',
                ],
            ];
        }

        return $schema;
    }

    /**
     * Generate WebPage schema.
     *
     * @param  Model|array  $page
     */
    public function generateWebPageSchema($page, array $options = []): array
    {
        $isModel = $page instanceof Model;

        $schema = [
            '@context' => $this->context,
            '@type' => $options['type'] ?? 'WebPage',
            'name' => $isModel
                ? $this->getModelAttribute($page, 'title')
                : ($page['title'] ?? $page['name'] ?? ''),
            'description' => $isModel
                ? $this->getModelAttribute($page, 'description', 'seo_description')
                : ($page['description'] ?? ''),
        ];

        // URL
        if ($isModel && $url = $this->getModelUrl($page)) {
            $schema['url'] = $url;
        } elseif (isset($page['url'])) {
            $schema['url'] = $page['url'];
        } else {
            $schema['url'] = url()->current();
        }

        // Image
        if ($isModel && $image = $this->getModelImage($page)) {
            $schema['image'] = $image;
        } elseif (isset($page['image'])) {
            $schema['image'] = $page['image'];
        }

        // Publisher
        $schema['publisher'] = $this->generateOrganizationSchema();

        // Dates
        if ($isModel) {
            if ($page->created_at) {
                $schema['datePublished'] = $page->created_at->toIso8601String();
            }
            if ($page->updated_at) {
                $schema['dateModified'] = $page->updated_at->toIso8601String();
            }
        } elseif (isset($page['published_at'])) {
            $schema['datePublished'] = $page['published_at'];
        }

        // Breadcrumb
        if (isset($options['breadcrumbs']) && is_array($options['breadcrumbs'])) {
            $schema['breadcrumb'] = $this->generateBreadcrumbSchema($options['breadcrumbs']);
        }

        return array_filter($schema, fn ($value) => ! is_null($value) && $value !== '');
    }

    /**
     * Generate Product schema.
     */
    public function generateProductSchema(object $model, array $options = []): array
    {
        $schema = [
            '@context' => $this->context,
            '@type' => 'Product',
            'name' => $this->getModelAttribute($model, 'name', 'title'),
            'description' => $this->getModelAttribute($model, 'description'),
        ];

        // Image
        if ($image = $this->getModelImage($model)) {
            $schema['image'] = $image;
        }

        // SKU
        if ($sku = $this->getModelAttribute($model, 'sku')) {
            $schema['sku'] = $sku;
        }

        // Brand
        if ($brand = $options['brand'] ?? $this->getModelAttribute($model, 'brand')) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $brand,
            ];
        }

        // Offers
        if ($price = $options['price'] ?? $this->getModelAttribute($model, 'price')) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => $options['currency'] ?? Config::get('Seo.schema.default_currency', 'USD'),
                'availability' => $options['availability'] ?? 'https://schema.org/InStock',
            ];

            if ($url = $this->getModelUrl($model)) {
                $schema['offers']['url'] = $url;
            }
        }

        // Aggregate Rating
        if (isset($options['rating'])) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $options['rating']['value'] ?? 0,
                'reviewCount' => $options['rating']['count'] ?? 0,
            ];
        }

        return array_filter($schema, fn ($value) => ! is_null($value) && $value !== '');
    }

    /**
     * Generate LocalBusiness schema.
     */
    public function generateLocalBusinessSchema(array $options = []): array
    {
        $config = Config::get('Seo.schema.local_business', []);

        $schema = [
            '@context' => $this->context,
            '@type' => $options['type'] ?? $config['type'] ?? 'LocalBusiness',
            'name' => $options['name'] ?? $config['name'] ?? Config::get('app.name'),
            'url' => $options['url'] ?? $config['url'] ?? Config::get('app.url'),
        ];

        // Image/Logo
        if ($image = $options['image'] ?? $config['image'] ?? null) {
            $schema['image'] = $image;
        }

        // Address
        if (isset($options['address']) || isset($config['address'])) {
            $address = $options['address'] ?? $config['address'];
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address['street'] ?? null,
                'addressLocality' => $address['city'] ?? null,
                'addressRegion' => $address['region'] ?? null,
                'postalCode' => $address['postal_code'] ?? null,
                'addressCountry' => $address['country'] ?? null,
            ];
            $schema['address'] = array_filter($schema['address']);
        }

        // Geo coordinates
        if (isset($options['geo']) || isset($config['geo'])) {
            $geo = $options['geo'] ?? $config['geo'];
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $geo['latitude'] ?? null,
                'longitude' => $geo['longitude'] ?? null,
            ];
        }

        // Contact
        if ($phone = $options['phone'] ?? $config['phone'] ?? null) {
            $schema['telephone'] = $phone;
        }

        if ($email = $options['email'] ?? $config['email'] ?? null) {
            $schema['email'] = $email;
        }

        // Opening hours
        if ($hours = $options['opening_hours'] ?? $config['opening_hours'] ?? null) {
            $schema['openingHoursSpecification'] = [];
            foreach ($hours as $spec) {
                $schema['openingHoursSpecification'][] = [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $spec['days'] ?? [],
                    'opens' => $spec['opens'] ?? null,
                    'closes' => $spec['closes'] ?? null,
                ];
            }
        }

        // Price range
        if ($priceRange = $options['price_range'] ?? $config['price_range'] ?? null) {
            $schema['priceRange'] = $priceRange;
        }

        return array_filter($schema, fn ($value) => ! is_null($value) && $value !== '');
    }

    /**
     * Generate multiple schemas wrapped in a graph.
     */
    public function generateGraphSchema(array $schemas): array
    {
        return [
            '@context' => $this->context,
            '@graph' => array_values($schemas),
        ];
    }

    /**
     * Render schema as JSON-LD string.
     */
    public function renderJsonLd(array $schema, bool $prettyPrint = false): string
    {
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        if ($prettyPrint) {
            $options |= JSON_PRETTY_PRINT;
        }

        return json_encode($schema, $options);
    }

    /**
     * Render schema as complete script tag.
     */
    public function renderScriptTag(array $schema, bool $prettyPrint = false): string
    {
        $json = $this->renderJsonLd($schema, $prettyPrint);

        return sprintf('<script type="application/ld+json">%s</script>', $json);
    }

    /**
     * Get attribute from model with fallbacks.
     */
    protected function getModelAttribute(object $model, string ...$attributes): mixed
    {
        foreach ($attributes as $attribute) {
            if (isset($model->$attribute) && ! empty($model->$attribute)) {
                return $model->$attribute;
            }
        }

        // Check SEO meta relation
        if (method_exists($model, 'seoMeta') && $model->seoMeta) {
            foreach ($attributes as $attribute) {
                if (isset($model->seoMeta->$attribute) && ! empty($model->seoMeta->$attribute)) {
                    return $model->seoMeta->$attribute;
                }
            }
        }

        return null;
    }

    /**
     * Get URL for model.
     */
    protected function getModelUrl(object $model): ?string
    {
        // Check if model has URL method
        if (method_exists($model, 'getUrl')) {
            return $model->getUrl();
        }

        // Check if model has url attribute
        if (isset($model->url)) {
            return $model->url;
        }

        // Check if model has slug and can generate route
        if (isset($model->slug) && method_exists($model, 'getTable')) {
            $table = $model->getTable();
            $routeName = str_replace('_', '.', $table).'.show';

            if (\Route::has($routeName)) {
                return route($routeName, $model->slug);
            }
        }

        return null;
    }

    /**
     * Get image URL for model.
     */
    protected function getModelImage(object $model): ?string
    {
        // Check various common image attributes
        $imageAttributes = ['image', 'featured_image', 'thumbnail', 'og_image', 'cover_image'];

        foreach ($imageAttributes as $attr) {
            if (isset($model->$attr) && ! empty($model->$attr)) {
                return $model->$attr;
            }
        }

        // Check SEO meta
        if (method_exists($model, 'seoMeta') && $model->seoMeta && $model->seoMeta->og_image) {
            return $model->seoMeta->og_image;
        }

        return null;
    }
}
