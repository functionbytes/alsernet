<?php

namespace Modules\Optimize\Http\Middleware;

class ElideAttributes extends PageSpeed
{
    public function apply(string $buffer): string
    {
        return $this->replace([
            '/ method=("get"|\'get\'|get)/i' => '',
            '/ disabled=(?:"disabled"|\'disabled\'|disabled)/i' => ' disabled',
            '/ selected=(?:"selected"|\'selected\'|selected)/i' => ' selected',
            '/ checked=(?:"checked"|\'checked\'|checked)/i' => ' checked',
            '/ readonly=(?:"readonly"|\'readonly\'|readonly)/i' => ' readonly',
            '/ multiple=(?:"multiple"|\'multiple\'|multiple)/i' => ' multiple',
            // HTML5 defaults — type attribute is unnecessary on script and style/link tags
            '/(<script[^>]*) type=(?:"text\/javascript"|\'text\/javascript\')/i' => '$1',
            '/(<(?:style|link)[^>]*) type=(?:"text\/css"|\'text\/css\')/i' => '$1',
        ], $buffer);
    }
}
