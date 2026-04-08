<?php

namespace Modules\Optimize\Http\Middleware;

class MinifyInlineScripts extends PageSpeed
{
    public function apply(string $buffer): string
    {
        // Target only inline <script> blocks (no src attribute)
        return preg_replace_callback(
            '/(<script(?![^>]*\bsrc=)[^>]*>)(.*?)(<\/script>)/is',
            static function (array $m): string {
                $js = $m[2];

                // Remove /* */ block comments (safe — cannot appear inside strings)
                $js = preg_replace('/\/\*.*?\*\//s', '', $js) ?? $js;

                // Collapse runs of whitespace to a single space
                $js = preg_replace('/\s{2,}/', ' ', $js) ?? $js;

                return $m[1].trim($js).$m[3];
            },
            $buffer
        ) ?? $buffer;
    }
}
