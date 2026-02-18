<?php

namespace Modules\Optimize\Http\Middleware;

class RemoveComments extends PageSpeed
{
    public function apply(string $buffer): string
    {
        return $this->replace(['/<!--[^]><!\[](.*?)[^\]]-->/s' => ''], $buffer);
    }
}
