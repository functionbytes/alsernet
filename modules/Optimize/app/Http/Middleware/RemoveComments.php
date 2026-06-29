<?php

namespace Modules\Optimize\Http\Middleware;

class RemoveComments extends PageSpeed
{
    public function apply(string $buffer): string
    {
        // Remove HTML comments but preserve IE conditional comments (<!--[if ...]>)
        return $this->replace(['/<!--(?!\[if\s)(.*?)-->/si' => ''], $buffer);
    }
}
