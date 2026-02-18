<?php

namespace Modules\Optimize\Http\Middleware;

class ElideAttributes extends PageSpeed
{
    public function apply(string $buffer): string
    {
        return $this->replace([
            '/ method=("get"|get)/' => '',
            '/ disabled=[^ >]*(.*?)/' => ' disabled',
            '/ selected=[^ >]*(.*?)/' => ' selected',
        ], $buffer);
    }
}
