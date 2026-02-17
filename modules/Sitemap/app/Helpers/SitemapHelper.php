<?php

namespace Modules\Sitemap\Helpers;

class SitemapHelper
{
    /**
     * Valid change frequency values
     */
    const CHANGEFREQ_ALWAYS = 'always';

    const CHANGEFREQ_HOURLY = 'hourly';

    const CHANGEFREQ_DAILY = 'daily';

    const CHANGEFREQ_WEEKLY = 'weekly';

    const CHANGEFREQ_MONTHLY = 'monthly';

    const CHANGEFREQ_YEARLY = 'yearly';

    const CHANGEFREQ_NEVER = 'never';

    /**
     * Get all valid change frequencies
     */
    public static function getChangeFrequencies(): array
    {
        return [
            self::CHANGEFREQ_ALWAYS,
            self::CHANGEFREQ_HOURLY,
            self::CHANGEFREQ_DAILY,
            self::CHANGEFREQ_WEEKLY,
            self::CHANGEFREQ_MONTHLY,
            self::CHANGEFREQ_YEARLY,
            self::CHANGEFREQ_NEVER,
        ];
    }

    /**
     * Validate change frequency
     */
    public static function isValidChangeFrequency(string $changefreq): bool
    {
        return in_array($changefreq, self::getChangeFrequencies());
    }

    /**
     * Validate priority
     */
    public static function isValidPriority(string $priority): bool
    {
        $value = (float) $priority;

        return $value >= 0.0 && $value <= 1.0;
    }

    /**
     * Format priority to one decimal place
     */
    public static function formatPriority($priority): string
    {
        return number_format((float) $priority, 1);
    }

    /**
     * Escape XML special characters
     */
    public static function escapeXml(string $string): string
    {
        return htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format date for sitemap
     */
    public static function formatDate($date): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        if ($date instanceof \DateTime) {
            return $date->format('c'); // ISO 8601
        }

        if (method_exists($date, 'toAtomString')) {
            return $date->toAtomString();
        }

        return now()->toAtomString();
    }

    /**
     * Get sitemap item count limit
     */
    public static function getItemLimit(): int
    {
        return config('sitemap.max_items', 50000);
    }

    /**
     * Check if sitemap needs to be split
     */
    public static function needsSplit(int $itemCount): bool
    {
        return $itemCount > self::getItemLimit();
    }

    /**
     * Ping search engines about sitemap update
     */
    public static function pingSearchEngines(string $sitemapUrl): array
    {
        $results = [];

        // Ping Google
        try {
            $googleUrl = 'https://www.google.com/ping?sitemap='.urlencode($sitemapUrl);
            $results['google'] = @file_get_contents($googleUrl) !== false;
        } catch (\Exception $e) {
            $results['google'] = false;
        }

        // Ping Bing
        try {
            $bingUrl = 'https://www.bing.com/ping?sitemap='.urlencode($sitemapUrl);
            $results['bing'] = @file_get_contents($bingUrl) !== false;
        } catch (\Exception $e) {
            $results['bing'] = false;
        }

        return $results;
    }
}
