<?php

namespace Modules\Optimize\Http\Middleware;

class MinifyInlineStyles extends PageSpeed
{
    public function apply(string $buffer): string
    {
        return preg_replace_callback(
            '/<style(\s[^>]*)?>.*?<\/style>/is',
            static function (array $matches): string {
                $tag = $matches[0];
                $open = preg_match('/<style(\s[^>]*)?>/', $tag, $open_matches) ? $open_matches[0] : '<style>';
                $css = preg_replace('/<style(\s[^>]*)?>|<\/style>/i', '', $tag);

                // Remove CSS block comments
                $css = preg_replace('/\/\*.*?\*\//s', '', $css);

                // Collapse all whitespace to a single space
                $css = preg_replace('/\s+/', ' ', $css);

                // Remove spaces around structural characters
                $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);

                // Remove trailing semicolons before closing brace
                $css = str_replace(';}', '}', $css);

                return $open.trim($css).'</style>';
            },
            $buffer
        ) ?? $buffer;
    }
}
