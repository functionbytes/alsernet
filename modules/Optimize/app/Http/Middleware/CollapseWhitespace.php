<?php

namespace Modules\Optimize\Http\Middleware;

class CollapseWhitespace extends PageSpeed
{
    public function apply(string $buffer): string
    {
        $replace = [
            "/\n([\S])/" => '$1',
            "/\r/"       => '',
            "/\n/"       => '',
            "/\t/"       => '',
            '/ +/'       => ' ',
            '/> +</'     => '><',
        ];

        $blocks = preg_split('/(<\/?pre[^>]*>)/', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);
        $output = '';

        foreach ($blocks as $i => $block) {
            $output .= ($i % 4 === 2) ? $block : $this->replace($replace, $block);
        }

        return $output;
    }
}
