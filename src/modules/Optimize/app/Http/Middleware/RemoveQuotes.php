<?php

namespace Modules\Optimize\Http\Middleware;

class RemoveQuotes extends PageSpeed
{
    public function apply(string $buffer): string
    {
        // Only remove quotes when the value is safe to leave unquoted per the HTML5 spec:
        // no whitespace, no " ' ` = < > characters, and non-empty.
        return $this->replace([
            '/(<(?:img|input|link|script|source)[^>]+(?:src|width|height|name|charset|align|border|crossorigin|type))="([a-zA-Z0-9\-_.#%\/]+)"/' => '$1=$2',
        ], $buffer);
    }
}
