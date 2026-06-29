<?php

namespace Modules\Optimize\Http\Middleware;

class AddLoadingLazy extends PageSpeed
{
    public function apply(string $buffer): string
    {
        return $this->replace([
            // Add loading="lazy" to <img> tags without a loading attribute
            // Excludes images with fetchpriority="high" (LCP/hero images)
            '/<img(?=[^>]*\bsrc=)(?![^>]*\bloading=)(?![^>]*\bfetchpriority=["\']high["\'])([^>]+)>/i' => '<img loading="lazy"$1>',
            // Add loading="lazy" to <iframe> tags without a loading attribute
            '/<iframe(?=[^>]*\bsrc=)(?![^>]*\bloading=)([^>]+)>/i' => '<iframe loading="lazy"$1>',
        ], $buffer);
    }
}
