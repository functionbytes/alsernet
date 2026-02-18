<?php

namespace Modules\Optimize\Http\Middleware;

class DeferJavascript extends PageSpeed
{
    public function apply(string $buffer): string
    {
        return $this->replace([
            '/<script(?=[^>]+src[^>]+)((?![^>]+defer|data-pagespeed-no-defer[^>]+)[^>]+)/i' => '<script $1 defer',
        ], $buffer);
    }
}
