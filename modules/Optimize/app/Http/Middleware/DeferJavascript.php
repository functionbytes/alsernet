<?php

namespace Modules\Optimize\Http\Middleware;

class DeferJavascript extends PageSpeed
{
    public function apply(string $buffer): string
    {
        return $this->replace([
            // Add defer to external scripts that don't already have defer/async/module/no-defer marker
            // type="module" is deferred by the browser natively, so we skip those too
            '/<script(?=[^>]*\bsrc=)(?![^>]*\b(?:defer|async|data-pagespeed-no-defer)\b)(?![^>]*type=["\']module["\'])([^>]+)>/i' => '<script defer$1>',
        ], $buffer);
    }
}
