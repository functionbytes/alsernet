<?php

if (! function_exists('seo')) {
    /**
     * Get the SEO service instance.
     */
    function seo(): \Modules\Seo\Services\SeoService
    {
        return app('seo');
    }
}

if (! function_exists('seo_title')) {
    /**
     * Set or get the SEO title.
     *
     * @return \Modules\Seo\Services\SeoService|string|null
     */
    function seo_title(?string $title = null, bool $appendSuffix = true)
    {
        $seo = app('seo');

        if ($title === null) {
            return $seo->get('title');
        }

        return $seo->setTitle($title, $appendSuffix);
    }
}

if (! function_exists('seo_description')) {
    /**
     * Set or get the SEO description.
     *
     * @return \Modules\Seo\Services\SeoService|string|null
     */
    function seo_description(?string $description = null)
    {
        $seo = app('seo');

        if ($description === null) {
            return $seo->get('description');
        }

        return $seo->setDescription($description);
    }
}

if (! function_exists('seo_keywords')) {
    /**
     * Set or get the SEO keywords.
     *
     * @return \Modules\Seo\Services\SeoService|string|null
     */
    function seo_keywords(array|string|null $keywords = null)
    {
        $seo = app('seo');

        if ($keywords === null) {
            return $seo->get('keywords');
        }

        return $seo->setKeywords($keywords);
    }
}

if (! function_exists('seo_image')) {
    /**
     * Set the SEO/OG image.
     */
    function seo_image(string $url): \Modules\Seo\Services\SeoService
    {
        return app('seo')->setOgImage($url)->setTwitterImage($url);
    }
}

if (! function_exists('seo_canonical')) {
    /**
     * Set or get the canonical URL.
     *
     * @return \Modules\Seo\Services\SeoService|string|null
     */
    function seo_canonical(?string $url = null)
    {
        $seo = app('seo');

        if ($url === null) {
            return $seo->get('canonical_url');
        }

        return $seo->setCanonical($url);
    }
}

if (! function_exists('seo_robots')) {
    /**
     * Set or get the robots directive.
     *
     * @return \Modules\Seo\Services\SeoService|string|null
     */
    function seo_robots(?string $robots = null)
    {
        $seo = app('seo');

        if ($robots === null) {
            return $seo->get('robots');
        }

        return $seo->setRobots($robots);
    }
}

if (! function_exists('seo_noindex')) {
    /**
     * Set the page to noindex.
     */
    function seo_noindex(bool $nofollow = false): \Modules\Seo\Services\SeoService
    {
        $robots = $nofollow ? 'noindex,nofollow' : 'noindex,follow';

        return app('seo')->setRobots($robots);
    }
}

if (! function_exists('seo_render')) {
    /**
     * Render all SEO meta tags.
     */
    function seo_render(): string
    {
        return app('seo')->render();
    }
}

if (! function_exists('seo_from_model')) {
    /**
     * Load SEO data from a model.
     */
    function seo_from_model(\Illuminate\Database\Eloquent\Model $model): \Modules\Seo\Services\SeoService
    {
        return app('seo')->loadFromModel($model);
    }
}

if (! function_exists('seo_preview')) {
    /**
     * Generate a preview of how the SEO will appear.
     */
    function seo_preview(): array
    {
        return app('seo')->generatePreview();
    }
}

if (! function_exists('truncate_for_seo')) {
    /**
     * Truncate text to SEO-friendly length.
     *
     * @param  string  $type  'title' or 'description'
     * @param  string  $platform  'seo', 'og', or 'twitter'
     */
    function truncate_for_seo(string $text, string $type = 'description', string $platform = 'seo'): string
    {
        $limits = [
            'title' => [
                'seo' => 60,
                'og' => 95,
                'twitter' => 70,
            ],
            'description' => [
                'seo' => 160,
                'og' => 200,
                'twitter' => 200,
            ],
        ];

        $limit = $limits[$type][$platform] ?? 160;

        return \Illuminate\Support\Str::limit(strip_tags($text), $limit, '...');
    }
}
