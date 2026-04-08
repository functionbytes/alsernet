<?php

namespace Modules\Seo\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Models\SeoTemplate;

class SeoService
{
    /**
     * SEO data container.
     */
    protected array $data = [];

    /**
     * Schema.org structured data container.
     */
    protected array $schemas = [];

    /**
     * Whether caching is enabled for render().
     */
    protected bool $cachingEnabled = false;

    /**
     * Cache key for the rendered HTML.
     */
    protected ?string $cacheKey = null;

    /**
     * Pagination prev/next URLs for rel=prev/next link tags.
     */
    private string $prevUrl = '';

    private string $nextUrl = '';

    /**
     * Initialize with default values.
     */
    public function __construct()
    {
        $this->data = [
            'title' => null,
            'description' => null,
            'keywords' => null,
            'og_title' => null,
            'og_description' => null,
            'og_image' => config('Seo.default_og_image'),
            'og_type' => 'website',
            'twitter_card' => 'summary',
            'twitter_title' => null,
            'twitter_description' => null,
            'twitter_image' => null,
            'canonical_url' => null,
            'robots' => 'index,follow',
        ];
    }

    /**
     * Set the page title.
     */
    public function setTitle(string $title, bool $appendSuffix = true): self
    {
        $this->data['title'] = $appendSuffix
            ? $title.config('Seo.default_title_suffix', '')
            : $title;

        return $this;
    }

    /**
     * Set the meta description.
     */
    public function setDescription(string $description): self
    {
        $this->data['description'] = $description;

        return $this;
    }

    /**
     * Set the meta keywords.
     */
    public function setKeywords(array|string $keywords): self
    {
        $this->data['keywords'] = is_array($keywords)
            ? implode(', ', $keywords)
            : $keywords;

        return $this;
    }

    /**
     * Set Open Graph title.
     */
    public function setOgTitle(string $title): self
    {
        $this->data['og_title'] = $title;

        return $this;
    }

    /**
     * Set Open Graph description.
     */
    public function setOgDescription(string $description): self
    {
        $this->data['og_description'] = $description;

        return $this;
    }

    /**
     * Set Open Graph image.
     */
    public function setOgImage(string $url): self
    {
        $this->data['og_image'] = $url;

        return $this;
    }

    /**
     * Set Open Graph type.
     */
    public function setOgType(string $type): self
    {
        $this->data['og_type'] = $type;

        return $this;
    }

    /**
     * Set Twitter card type.
     */
    public function setTwitterCard(string $type = 'summary'): self
    {
        $this->data['twitter_card'] = $type;

        return $this;
    }

    /**
     * Set Twitter title.
     */
    public function setTwitterTitle(string $title): self
    {
        $this->data['twitter_title'] = $title;

        return $this;
    }

    /**
     * Set Twitter description.
     */
    public function setTwitterDescription(string $description): self
    {
        $this->data['twitter_description'] = $description;

        return $this;
    }

    /**
     * Set Twitter image.
     */
    public function setTwitterImage(string $url): self
    {
        $this->data['twitter_image'] = $url;

        return $this;
    }

    /**
     * Set canonical URL.
     */
    public function setCanonical(string $url): self
    {
        $this->data['canonical_url'] = $url;

        return $this;
    }

    /**
     * Set robots directive.
     */
    public function setRobots(string $robots = 'index,follow'): self
    {
        $this->data['robots'] = $robots;

        return $this;
    }

    /**
     * Set pagination rel=prev/next URLs for SEO link tags.
     */
    public function setPagination(string $prevUrl = '', string $nextUrl = ''): self
    {
        $this->prevUrl = $prevUrl;
        $this->nextUrl = $nextUrl;

        return $this;
    }

    /**
     * Load SEO data from a model.
     */
    public function loadFromModel(Model $model): self
    {
        if (method_exists($model, 'seoMeta') && $model->seoMeta) {
            $seoMeta = $model->seoMeta;

            $this->data['title'] = $seoMeta->title ?? $model->title ?? null;
            $this->data['description'] = $seoMeta->description ?? $model->description ?? null;
            $this->data['keywords'] = $seoMeta->keywords;
            $this->data['og_title'] = $seoMeta->og_title;
            $this->data['og_description'] = $seoMeta->og_description;
            $this->data['og_image'] = $seoMeta->og_image ?? config('Seo.default_og_image');
            $this->data['og_type'] = $seoMeta->og_type ?? 'website';
            $this->data['twitter_card'] = $seoMeta->twitter_card ?? 'summary';
            $this->data['twitter_title'] = $seoMeta->twitter_title;
            $this->data['twitter_description'] = $seoMeta->twitter_description;
            $this->data['twitter_image'] = $seoMeta->twitter_image;
            $this->data['canonical_url'] = $seoMeta->canonical_url;
            $this->data['robots'] = $seoMeta->robots ?? 'index,follow';

            // A/B testing: override title/description and track impressions
            if ($seoMeta->hasAbTest()) {
                $variant = $seoMeta->getActiveVariant();
                $this->data['title'] = $seoMeta->getActiveTitle() ?: ($model->title ?? null);
                $this->data['description'] = $seoMeta->getActiveDescription() ?: ($model->description ?? null);

                try {
                    $seoMeta->increment("ab_impressions_{$variant}");
                } catch (\Throwable) {
                    // Non-blocking: don't fail the request if increment fails
                }
            }

            if (empty($this->data['og_image']) && method_exists($model, 'getFirstMediaUrl')) {
                $mediaUrl = $model->getFirstMediaUrl('seo') ?: $model->getFirstMediaUrl();
                if ($mediaUrl) {
                    $this->data['og_image'] = $mediaUrl;
                }
            }
        }

        // Apply template fallbacks for empty fields
        if (method_exists($model, 'seoMeta')) {
            $this->applyTemplateDefaults($model);
        }

        return $this;
    }

    /**
     * Apply template defaults to fields that are still empty after loading from the meta record.
     */
    private function applyTemplateDefaults(Model $model): void
    {
        if (! empty($this->data['title']) && ! empty($this->data['description'])) {
            return;
        }

        $template = SeoTemplate::query()
            ->active()
            ->forModel(get_class($model))
            ->orderByDesc('priority')
            ->first();

        if (! $template) {
            return;
        }

        $applied = $template->applyToModel($model);

        foreach ($applied as $field => $value) {
            if (empty($this->data[$field])) {
                $this->data[$field] = $value;
            }
        }
    }

    /**
     * Save SEO meta data to a model, scoped by locale.
     */
    public function saveMeta(Model $model, array $data, ?string $locale = null): SeoMeta
    {
        if (! method_exists($model, 'seoMeta')) {
            throw new \Exception('Model must use the HasSeo trait');
        }

        return $model->seoMeta()->updateOrCreate(
            [
                'seoable_id' => $model->id,
                'seoable_type' => get_class($model),
                'locale' => $locale,
            ],
            array_filter($data, fn ($value) => ! is_null($value))
        );
    }

    /**
     * Get all SEO data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get a specific SEO data value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Enable caching for rendered HTML output.
     */
    public function enableCache(string $key, int $ttl = 3600): self
    {
        $this->cachingEnabled = true;
        $this->cacheKey = 'seo_render_'.md5($key);

        return $this;
    }

    /**
     * Invalidate the cached render for a specific key.
     */
    public function invalidateCache(string $key): void
    {
        Cache::forget('seo_render_'.md5($key));
    }

    /**
     * Render all meta tags as HTML, with optional caching.
     */
    public function render(): string
    {
        if ($this->cachingEnabled && $this->cacheKey) {
            return Cache::remember(
                $this->cacheKey,
                3600,
                fn () => $this->buildHtml()
            );
        }

        return $this->buildHtml();
    }

    /**
     * Build the full HTML string of meta tags.
     */
    private function buildHtml(): string
    {
        $html = [];

        // Basic SEO
        if ($this->data['title']) {
            $html[] = '<title>'.e($this->data['title']).'</title>';
        }

        if ($this->data['description']) {
            $html[] = '<meta name="description" content="'.e($this->data['description']).'">';
        }

        if ($this->data['keywords']) {
            $html[] = '<meta name="keywords" content="'.e($this->data['keywords']).'">';
        }

        // Robots
        $html[] = '<meta name="robots" content="'.e($this->data['robots']).'">';

        // Canonical URL
        if ($this->data['canonical_url']) {
            $html[] = '<link rel="canonical" href="'.e($this->data['canonical_url']).'">';
        }

        // Pagination rel=prev/next
        if ($this->prevUrl !== '') {
            $html[] = '<link rel="prev" href="'.e($this->prevUrl).'">';
        }

        if ($this->nextUrl !== '') {
            $html[] = '<link rel="next" href="'.e($this->nextUrl).'">';
        }

        // Open Graph
        $ogTitle = $this->data['og_title'] ?? $this->data['title'];
        if ($ogTitle) {
            $html[] = '<meta property="og:title" content="'.e($ogTitle).'">';
        }

        $ogDescription = $this->data['og_description'] ?? $this->data['description'];
        if ($ogDescription) {
            $html[] = '<meta property="og:description" content="'.e($ogDescription).'">';
        }

        if ($this->data['og_image']) {
            $html[] = '<meta property="og:image" content="'.e($this->data['og_image']).'">';
        }

        $html[] = '<meta property="og:url" content="'.e(url()->current()).'">';
        $html[] = '<meta property="og:type" content="'.e($this->data['og_type']).'">';
        $html[] = '<meta property="og:site_name" content="'.e(config('app.name')).'">';
        $html[] = '<meta property="og:locale" content="'.e(config('app.locale', 'es_ES')).'">';

        if ($this->data['og_image']) {
            $html[] = '<meta property="og:image:width" content="'.e(config('Seo.og_image_width', 1200)).'">';
            $html[] = '<meta property="og:image:height" content="'.e(config('Seo.og_image_height', 630)).'">';
        }

        // Twitter Card
        $html[] = '<meta name="twitter:card" content="'.e($this->data['twitter_card']).'">';

        if (config('Seo.twitter_site')) {
            $html[] = '<meta name="twitter:site" content="'.e(config('Seo.twitter_site')).'">';
        }

        $twitterTitle = $this->data['twitter_title'] ?? $this->data['title'];
        if ($twitterTitle) {
            $html[] = '<meta name="twitter:title" content="'.e($twitterTitle).'">';
        }

        $twitterDescription = $this->data['twitter_description'] ?? $this->data['description'];
        if ($twitterDescription) {
            $html[] = '<meta name="twitter:description" content="'.e($twitterDescription).'">';
        }

        $twitterImage = $this->data['twitter_image'] ?? $this->data['og_image'];
        if ($twitterImage) {
            $html[] = '<meta name="twitter:image" content="'.e($twitterImage).'">';
        }

        // Search engine verification tags (database-first, config/env fallback)
        $verification = [
            'google' => setting('seo_google_verification', config('Seo.verification.google', '')),
            'bing' => setting('seo_bing_verification', config('Seo.verification.bing', '')),
            'pinterest' => setting('seo_pinterest_verification', config('Seo.verification.pinterest', '')),
            'baidu' => setting('seo_baidu_verification', config('Seo.verification.baidu', '')),
            'yandex' => setting('seo_yandex_verification', config('Seo.verification.yandex', '')),
        ];

        if (! empty($verification['google'])) {
            $html[] = '<meta name="google-site-verification" content="'.e($verification['google']).'">';
        }

        if (! empty($verification['bing'])) {
            $html[] = '<meta name="msvalidate.01" content="'.e($verification['bing']).'">';
        }

        if (! empty($verification['pinterest'])) {
            $html[] = '<meta name="p:domain_verify" content="'.e($verification['pinterest']).'">';
        }

        if (! empty($verification['baidu'])) {
            $html[] = '<meta name="baidu-site-verification" content="'.e($verification['baidu']).'">';
        }

        if (! empty($verification['yandex'])) {
            $html[] = '<meta name="yandex-verification" content="'.e($verification['yandex']).'">';
        }

        // Hreflang
        if ($this->data['canonical_url']) {
            $html[] = $this->renderHreflang();
        }

        return implode("\n", $html);
    }

    /**
     * Render hreflang alternate link tags for the current canonical URL.
     */
    public function renderHreflang(): string
    {
        if (! $this->data['canonical_url']) {
            return '';
        }

        $canonical = $this->data['canonical_url'];
        $defaultLocale = config('app.locale', 'es');

        $lines = [
            sprintf('<link rel="alternate" hreflang="x-default" href="%s">', e($canonical)),
            sprintf('<link rel="alternate" hreflang="%s" href="%s">', e($defaultLocale), e($canonical)),
        ];

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /**
     * Generate a preview array for display.
     */
    public function generatePreview(): array
    {
        return [
            'google' => [
                'title' => $this->data['title'] ?? 'Untitled',
                'url' => $this->data['canonical_url'] ?? url()->current(),
                'description' => $this->data['description'] ?? config('Seo.default_description', ''),
            ],
            'facebook' => [
                'title' => $this->data['og_title'] ?? $this->data['title'] ?? 'Untitled',
                'description' => $this->data['og_description'] ?? $this->data['description'] ?? '',
                'image' => $this->data['og_image'] ?? config('Seo.default_og_image'),
            ],
            'twitter' => [
                'title' => $this->data['twitter_title'] ?? $this->data['title'] ?? 'Untitled',
                'description' => $this->data['twitter_description'] ?? $this->data['description'] ?? '',
                'image' => $this->data['twitter_image'] ?? $this->data['og_image'] ?? config('Seo.default_og_image'),
                'card' => $this->data['twitter_card'] ?? 'summary',
            ],
        ];
    }

    /**
     * Add a schema to the schemas collection.
     *
     * @param  string|null  $key  Optional key to identify the schema
     */
    public function addSchema(array $schema, ?string $key = null): self
    {
        if ($key) {
            $this->schemas[$key] = $schema;
        } else {
            $this->schemas[] = $schema;
        }

        return $this;
    }

    /**
     * Set all schemas at once.
     */
    public function setSchemas(array $schemas): self
    {
        $this->schemas = $schemas;

        return $this;
    }

    /**
     * Get all schemas.
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * Clear all schemas.
     */
    public function clearSchemas(): self
    {
        $this->schemas = [];

        return $this;
    }

    /**
     * Render structured data schemas as JSON-LD script tags.
     */
    public function renderSchema(bool $prettyPrint = false): string
    {
        if (! config('Seo.schema.enabled', true)) {
            return '';
        }

        if (empty($this->schemas)) {
            return '';
        }

        $schemaService = app(SchemaOrgService::class);
        $html = [];

        // If multiple schemas, wrap in graph
        if (count($this->schemas) > 1) {
            $graphSchema = $schemaService->generateGraphSchema($this->schemas);
            $html[] = $schemaService->renderScriptTag($graphSchema, $prettyPrint);
        } else {
            // Single schema
            foreach ($this->schemas as $schema) {
                $html[] = $schemaService->renderScriptTag($schema, $prettyPrint);
            }
        }

        return implode("\n", $html);
    }

    /**
     * Load schemas from model if it uses HasStructuredData trait.
     */
    public function loadSchemasFromModel(Model $model): self
    {
        if (method_exists($model, 'generateCompleteSchema')) {
            $schema = $model->generateCompleteSchema();

            if (! empty($schema)) {
                $this->schemas = [$schema];
            }
        }

        return $this;
    }
}
