<?php

namespace Modules\Optimize\Http\Middleware;

class CollapseWhitespace extends PageSpeed
{
    public function apply(string $buffer): string
    {
        $replace = [
            "/\r\n/" => "\n",
            "/\r/" => "\n",
            "/\t/" => ' ',
            '/ {2,}/' => ' ',
            "/\n +/" => "\n",
            '/> +</' => '><',
            "/\n{2,}/" => "\n",
        ];

        // Extract protected blocks (pre, script, style, textarea) and replace with
        // unique placeholders so their content is never touched by whitespace collapse.
        $protected = [];
        $idx = 0;

        $buffer = preg_replace_callback(
            '/(<(pre|script|style|textarea)(?:\s[^>]*)?>)(.*?)(<\/\2>)/is',
            static function (array $m) use (&$protected, &$idx): string {
                $placeholder = "\x02OPT{$idx}\x03";
                $protected[$placeholder] = $m[0];
                $idx++;

                return $placeholder;
            },
            $buffer
        ) ?? $buffer;

        $buffer = $this->replace($replace, $buffer);

        return str_replace(array_keys($protected), array_values($protected), $buffer);
    }
}
