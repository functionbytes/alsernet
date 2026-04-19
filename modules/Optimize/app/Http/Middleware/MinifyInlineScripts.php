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

                // Collapse runs of spaces/tabs (NOT newlines — // comments must stay terminated by \n)
                $js = preg_replace('/[^\S\n]{2,}/', ' ', $js) ?? $js;
                // Collapse multiple blank lines into one
                $js = preg_replace('/\n\s*\n+/', "\n", $js) ?? $js;

                return $m[1].trim($js).$m[3];
            },
            $buffer
        ) ?? $buffer;
    }
}
