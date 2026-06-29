<?php

namespace Modules\Template\Helpers;

/**
 * Stub de SeoHelper para compatibilidad con plantillas diseñadas para Botble.
 * Los metadatos SEO reales se gestionan desde el módulo Seo.
 */
class SeoHelper
{
    protected string $title = '';

    protected string $description = '';

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title ?: config('app.name', '');
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
