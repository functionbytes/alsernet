<?php

namespace Modules\Optimize\Http\Middleware;

class RemoveQuotes extends PageSpeed
{
    public function apply(string $buffer): string
    {
        return $this->replace([
            '/(<(?:img|input|link|script|source)[^>]+(?:src|width|height|name|charset|align|border|crossorigin|type))="([^">\s]+)"/' => '$1=$2',
        ], $buffer);
    }
}
