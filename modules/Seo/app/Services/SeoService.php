<?php

namespace Modules\Seo\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Seo\Models\SeoMeta;

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
        }

        return $this;
    }

    /**
     * Save SEO meta data to a model.
     */
    public function saveMeta(Model $model, array $data): SeoMeta
    {
        if (! method_exists($model, 'seoMeta')) {
            throw new \Exception('Model must use the HasSeo trait');
        }

        return $model->seoMeta()->updateOrCreate(
            [
                'seoable_id' => $model->id,
                'seoable_type' => get_class($model),
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
     * Render all meta tags as HTML.
     */
    public function render(): string
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

        return implode("\n", $html);
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
