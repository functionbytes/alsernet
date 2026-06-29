<?php

namespace Modules\Seo\Traits;

use Modules\Seo\Services\SchemaOrgService;

trait HasStructuredData
{
    /**
     * Get the schema type for this model.
     * Override this method in your model to specify a different type.
     */
    public function getSchemaType(): string
    {
        return 'Article';
    }

    /**
     * Get custom schema options for this model.
     * Override this method to provide custom schema data.
     */
    public function getSchemaOptions(): array
    {
        return [];
    }

    /**
     * Generate structured data schema for this model.
     *
     * @param  string|null  $type  Override the schema type
     * @param  array  $options  Additional options
     */
    public function generateSchema(?string $type = null, array $options = []): array
    {
        $schemaService = app(SchemaOrgService::class);
        $schemaType = $type ?? $this->getSchemaType();
        $schemaOptions = array_merge($this->getSchemaOptions(), $options);

        return match (strtolower($schemaType)) {
            'article', 'blogposting', 'newsarticle' => $schemaService->generateArticleSchema(
                $this,
                array_merge(['type' => $schemaType], $schemaOptions)
            ),
            'webpage' => $schemaService->generateWebPageSchema($this, $schemaOptions),
            'product' => $schemaService->generateProductSchema($this, $schemaOptions),
            default => $schemaService->generateWebPageSchema($this, $schemaOptions),
        };
    }

    /**
     * Generate structured data schema as JSON-LD string.
     */
    public function generateSchemaJsonLd(?string $type = null, array $options = [], bool $prettyPrint = false): string
    {
        $schemaService = app(SchemaOrgService::class);
        $schema = $this->generateSchema($type, $options);

        return $schemaService->renderJsonLd($schema, $prettyPrint);
    }

    /**
     * Render structured data as script tag.
     */
    public function renderSchemaScript(?string $type = null, array $options = [], bool $prettyPrint = false): string
    {
        $schemaService = app(SchemaOrgService::class);
        $schema = $this->generateSchema($type, $options);

        return $schemaService->renderScriptTag($schema, $prettyPrint);
    }

    /**
     * Check if structured data is enabled for this model.
     */
    public function hasStructuredDataEnabled(): bool
    {
        return config('Seo.schema.enabled', true);
    }

    /**
     * Get breadcrumb items for this model.
     * Override this method to provide custom breadcrumbs.
     */
    public function getBreadcrumbItems(): ?array
    {
        return null;
    }

    /**
     * Get FAQ items for this model.
     * Override this method to provide FAQs.
     */
    public function getFaqItems(): ?array
    {
        return null;
    }

    /**
     * Generate complete structured data including all schemas.
     */
    public function generateCompleteSchema(): array
    {
        if (! $this->hasStructuredDataEnabled()) {
            return [];
        }

        $schemaService = app(SchemaOrgService::class);
        $schemas = [];

        // Main schema (Article, WebPage, etc.)
        $schemas[] = $this->generateSchema();

        // Breadcrumbs
        if ($breadcrumbs = $this->getBreadcrumbItems()) {
            $schemas[] = $schemaService->generateBreadcrumbSchema($breadcrumbs);
        }

        // FAQs
        if ($faqs = $this->getFaqItems()) {
            $schemas[] = $schemaService->generateFAQSchema($faqs);
        }

        // Return as graph if multiple schemas
        if (count($schemas) > 1) {
            return $schemaService->generateGraphSchema($schemas);
        }

        return $schemas[0] ?? [];
    }

    /**
     * Render complete structured data as script tag.
     */
    public function renderCompleteSchemaScript(bool $prettyPrint = false): string
    {
        $schema = $this->generateCompleteSchema();

        if (empty($schema)) {
            return '';
        }

        $schemaService = app(SchemaOrgService::class);

        return $schemaService->renderScriptTag($schema, $prettyPrint);
    }
}
