<?php

namespace Modules\Supplier\Helpers;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * HTML sanitiser used to render AI-generated rich-text content.
 *
 * Wraps ezyang/htmlpurifier with a conservative allowlist of text formatting
 * tags. Dangerous URI schemes (javascript:, vbscript:, data:) on href/src are
 * neutralised to "#" before purification so callers keeping a (now harmless)
 * link stay consistent with the previous behaviour.
 */
class HtmlSanitizer
{
    /**
     * Default allowlist passed to HTMLPurifier's `HTML.Allowed` directive.
     */
    private const DEFAULT_ALLOWED = 'p,br,strong,b,em,i,u,s,ul,ol,li,'
        .'h2,h3,h4,h5,h6,blockquote,pre,code,'
        .'table,thead,tbody,tr,th,td,a[href],img[src|alt],span,div,hr';

    private static ?HTMLPurifier $purifier = null;

    /**
     * @var array<string, HTMLPurifier>
     */
    private static array $customPurifiers = [];

    public static function clean(?string $html, ?string $allowedTags = null): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $html = self::neutraliseDangerousUris($html);

        return self::purifier($allowedTags)->purify($html);
    }

    /**
     * Replace dangerous URI schemes on href/src with a harmless "#".
     */
    private static function neutraliseDangerousUris(string $html): string
    {
        $scheme = '(?:javascript|vbscript|data)';

        $patterns = [
            '/(href|src|xlink:href)\s*=\s*"\s*'.$scheme.':[^"]*"/i' => '$1="#"',
            "/(href|src|xlink:href)\s*=\s*'\s*".$scheme.":[^']*'/i" => "$1='#'",
            '/(href|src|xlink:href)\s*=\s*'.$scheme.':[^\s>]*/i' => '$1="#"',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $html = preg_replace($pattern, $replacement, $html) ?? $html;
        }

        return $html;
    }

    private static function purifier(?string $allowedTags): HTMLPurifier
    {
        if ($allowedTags === null || trim($allowedTags) === '') {
            return self::$purifier ??= new HTMLPurifier(self::config(self::DEFAULT_ALLOWED));
        }

        $allowed = self::normaliseAllowed($allowedTags);

        return self::$customPurifiers[$allowed] ??= new HTMLPurifier(self::config($allowed));
    }

    private static function config(string $allowed): HTMLPurifier_Config
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', $allowed);
        $config->set('Cache.DefinitionImpl', null);
        $config->set('Attr.AllowedFrameTargets', []);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('AutoFormat.RemoveEmpty', false);
        $config->set('HTML.Nofollow', false);

        return $config;
    }

    /**
     * Accept both the legacy strip_tags-style list ("<p><strong>") and the
     * native HTMLPurifier list ("p,strong[class]").
     */
    private static function normaliseAllowed(string $allowedTags): string
    {
        if (! str_contains($allowedTags, '<')) {
            return $allowedTags;
        }

        preg_match_all('/<([a-z0-9]+)>/i', $allowedTags, $matches);

        return implode(',', array_map('strtolower', $matches[1]));
    }
}
