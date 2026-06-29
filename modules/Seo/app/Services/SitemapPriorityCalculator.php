<?php

namespace Modules\Seo\Services;

use Carbon\Carbon;

class SitemapPriorityCalculator
{
    /**
     * Calculate sitemap priority (0.1–1.0) based on URL depth, freshness, and popularity.
     */
    public function calculate(string $url, ?Carbon $updatedAt = null, int $hitsOrViews = 0): float
    {
        $priority = 1.0;

        // Penalize by URL depth (each extra segment reduces priority)
        $path = parse_url($url, PHP_URL_PATH) ?? '/';
        $depth = substr_count(trim($path, '/'), '/');
        $priority -= $depth * 0.1;

        // Boost for recently updated content, penalize for stale content
        if ($updatedAt) {
            $daysSinceUpdate = $updatedAt->diffInDays(now());

            if ($daysSinceUpdate <= 7) {
                $priority += 0.1;
            } elseif ($daysSinceUpdate > 180) {
                $priority -= 0.1;
            }
        }

        // Boost for popular content
        if ($hitsOrViews > 1000) {
            $priority += 0.1;
        } elseif ($hitsOrViews > 100) {
            $priority += 0.05;
        }

        return round(max(0.1, min(1.0, $priority)), 1);
    }
}
