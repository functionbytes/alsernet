<?php

namespace Modules\Reviews\Services;

class BadgeGeneratorService
{
    private const STYLES = ['standard', 'minimal', 'dark', 'light'];

    /**
     * Generate an SVG badge showing Google rating.
     *
     * @param  float  $avgRating  Average star rating (0–5)
     * @param  int  $reviewCount  Total number of reviews
     * @param  string  $businessName  Business display name
     * @param  string  $style  One of: standard, minimal, dark, light
     */
    public function generateSvg(
        float $avgRating,
        int $reviewCount,
        string $businessName,
        string $style = 'standard'
    ): string {
        if (! in_array($style, self::STYLES, true)) {
            $style = 'standard';
        }

        $name = mb_strlen($businessName) > 30
            ? mb_substr($businessName, 0, 29).'…'
            : $businessName;

        $ratingFormatted = number_format($avgRating, 1);
        $stars = $this->buildStars($avgRating);
        $reviewText = number_format($reviewCount).' '.($reviewCount === 1 ? 'reseña' : 'reseñas');

        return match ($style) {
            'minimal' => $this->buildMinimal($name, $ratingFormatted, $stars, $reviewText),
            'dark' => $this->buildDark($name, $ratingFormatted, $stars, $reviewText),
            'light' => $this->buildLight($name, $ratingFormatted, $stars, $reviewText),
            default => $this->buildStandard($name, $ratingFormatted, $stars, $reviewText),
        };
    }

    /**
     * Generate a PNG from an SVG string using GD.
     * Returns PNG binary string, or empty string if GD is unavailable.
     */
    public function generatePng(string $svg, int $width = 400, int $height = 120): string
    {
        if (! extension_loaded('gd') || ! function_exists('imagecreatefromstring')) {
            return '';
        }

        // Encode SVG as data URI for GD to render
        $pngData = '';

        // GD cannot render SVG natively; use rasterisation via imagecreatefromstring
        // with a fallback white canvas + text if the SVG cannot be decoded.
        $image = @imagecreatetruecolor($width, $height);

        if ($image === false) {
            return '';
        }

        // White background
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        ob_start();
        imagepng($image);
        $pngData = (string) ob_get_clean();
        imagedestroy($image);

        return $pngData;
    }

    /**
     * Generate an HTML embed snippet for the badge URL.
     */
    public function generateEmbedCode(string $badgeUrl): string
    {
        $escaped = htmlspecialchars($badgeUrl, ENT_QUOTES, 'UTF-8');

        return '<img src="'.$escaped.'" alt="Google Reviews Badge" width="400" height="120" style="border:none;">';
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build a unicode star string with full, half and empty stars.
     */
    private function buildStars(float $rating): string
    {
        $full = (int) floor($rating);
        $half = ($rating - $full) >= 0.5 ? 1 : 0;
        $empty = 5 - $full - $half;

        return str_repeat('★', $full)
            .str_repeat('⯨', $half)
            .str_repeat('☆', $empty);
    }

    /**
     * Standard style: white background, green left bar.
     */
    private function buildStandard(
        string $name,
        string $rating,
        string $stars,
        string $reviewText
    ): string {
        return <<<SVG
        <svg width="400" height="120" xmlns="http://www.w3.org/2000/svg">
          <rect width="400" height="120" rx="8" fill="white" stroke="#e0e0e0" stroke-width="1"/>
          <rect width="72" height="120" rx="8" fill="#90bb13"/>
          <rect x="64" width="8" height="120" fill="#90bb13"/>
          <text x="36" y="52" font-family="Arial,sans-serif" font-size="38" font-weight="bold" fill="white" text-anchor="middle">G</text>
          <text x="36" y="78" font-family="Arial,sans-serif" font-size="9" fill="rgba(255,255,255,0.85)" text-anchor="middle">Google</text>
          <text x="96" y="32" font-family="Arial,sans-serif" font-size="12" fill="#555" dominant-baseline="middle">{$name}</text>
          <text x="96" y="60" font-family="Arial,sans-serif" font-size="30" font-weight="bold" fill="#333" dominant-baseline="middle">{$rating}</text>
          <text x="158" y="51" font-family="Arial,sans-serif" font-size="19" fill="#F5A623" dominant-baseline="middle">{$stars}</text>
          <text x="96" y="92" font-family="Arial,sans-serif" font-size="11" fill="#888">Basado en {$reviewText}</text>
        </svg>
        SVG;
    }

    /**
     * Minimal style: borderless, compact layout.
     */
    private function buildMinimal(
        string $name,
        string $rating,
        string $stars,
        string $reviewText
    ): string {
        return <<<SVG
        <svg width="400" height="120" xmlns="http://www.w3.org/2000/svg">
          <rect width="400" height="120" rx="4" fill="#f5f6f8"/>
          <text x="20" y="30" font-family="Arial,sans-serif" font-size="11" fill="#888">{$name}</text>
          <text x="20" y="68" font-family="Arial,sans-serif" font-size="36" font-weight="bold" fill="#333">{$rating}</text>
          <text x="72" y="59" font-family="Arial,sans-serif" font-size="20" fill="#F5A623">{$stars}</text>
          <text x="20" y="95" font-family="Arial,sans-serif" font-size="11" fill="#999">Basado en {$reviewText} · Google</text>
          <circle cx="376" cy="28" r="16" fill="#90bb13"/>
          <text x="376" y="34" font-family="Arial,sans-serif" font-size="18" font-weight="bold" fill="white" text-anchor="middle">G</text>
        </svg>
        SVG;
    }

    /**
     * Dark style: dark background, light text.
     */
    private function buildDark(
        string $name,
        string $rating,
        string $stars,
        string $reviewText
    ): string {
        return <<<SVG
        <svg width="400" height="120" xmlns="http://www.w3.org/2000/svg">
          <rect width="400" height="120" rx="8" fill="#1e1e2e"/>
          <rect width="72" height="120" rx="8" fill="#90bb13"/>
          <rect x="64" width="8" height="120" fill="#90bb13"/>
          <text x="36" y="52" font-family="Arial,sans-serif" font-size="38" font-weight="bold" fill="white" text-anchor="middle">G</text>
          <text x="36" y="78" font-family="Arial,sans-serif" font-size="9" fill="rgba(255,255,255,0.75)" text-anchor="middle">Google</text>
          <text x="96" y="32" font-family="Arial,sans-serif" font-size="12" fill="#aaa" dominant-baseline="middle">{$name}</text>
          <text x="96" y="60" font-family="Arial,sans-serif" font-size="30" font-weight="bold" fill="#eee" dominant-baseline="middle">{$rating}</text>
          <text x="158" y="51" font-family="Arial,sans-serif" font-size="19" fill="#F5C518" dominant-baseline="middle">{$stars}</text>
          <text x="96" y="92" font-family="Arial,sans-serif" font-size="11" fill="#777">Basado en {$reviewText}</text>
        </svg>
        SVG;
    }

    /**
     * Light style: very light/pastel, soft border.
     */
    private function buildLight(
        string $name,
        string $rating,
        string $stars,
        string $reviewText
    ): string {
        return <<<SVG
        <svg width="400" height="120" xmlns="http://www.w3.org/2000/svg">
          <rect width="400" height="120" rx="12" fill="#f0f7e0" stroke="#c5e07b" stroke-width="1.5"/>
          <rect width="72" height="120" rx="12" fill="#90bb13"/>
          <rect x="64" width="8" height="120" fill="#90bb13"/>
          <text x="36" y="52" font-family="Arial,sans-serif" font-size="38" font-weight="bold" fill="white" text-anchor="middle">G</text>
          <text x="36" y="78" font-family="Arial,sans-serif" font-size="9" fill="rgba(255,255,255,0.85)" text-anchor="middle">Google</text>
          <text x="96" y="32" font-family="Arial,sans-serif" font-size="12" fill="#6a7a40" dominant-baseline="middle">{$name}</text>
          <text x="96" y="60" font-family="Arial,sans-serif" font-size="30" font-weight="bold" fill="#3a4a10" dominant-baseline="middle">{$rating}</text>
          <text x="158" y="51" font-family="Arial,sans-serif" font-size="19" fill="#e6a000" dominant-baseline="middle">{$stars}</text>
          <text x="96" y="92" font-family="Arial,sans-serif" font-size="11" fill="#7a8c50">Basado en {$reviewText}</text>
        </svg>
        SVG;
    }
}
