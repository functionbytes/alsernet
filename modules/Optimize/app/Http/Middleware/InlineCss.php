<?php

namespace Modules\Optimize\Http\Middleware;

class InlineCss extends PageSpeed
{
    /** @var array<string, string> */
    private array $classMap = [];

    private int $classCounter = 0;

    public function apply(string $buffer): string
    {
        $this->classMap = [];
        $this->classCounter = 0;

        $buffer = $this->injectClass($buffer);

        if (empty($this->classMap)) {
            return $buffer;
        }

        return $this->injectStyle($buffer);
    }

    /**
     * Replace inline style="" attributes with generated CSS classes.
     * Preserves any existing class="" attribute on the same element.
     */
    private function injectClass(string $buffer): string
    {
        // Support both double-quoted and single-quoted style attributes
        $buffer = preg_replace_callback(
            '/\sstyle=(?:"([^"]+)"|\'([^\']+)\')/i',
            function (array $matches): string {
                $css = trim($matches[1] !== '' ? $matches[1] : ($matches[2] ?? ''));

                if ($css === '') {
                    return '';
                }

                if (! isset($this->classMap[$css])) {
                    $this->classMap[$css] = 'pso-'.$this->classCounter++;
                }

                return ' data-pso-class="'.$this->classMap[$css].'"';
            },
            $buffer
        ) ?? $buffer;

        // Merge data-pso-class into an existing class attribute (either order)
        $mergeCallback = fn (array $m): string => ' class="'.trim($m[1].' '.$m[2]).'"';

        $buffer = preg_replace_callback(
            '/\sclass="([^"]*?)"\s+data-pso-class="([^"]+)"/i',
            $mergeCallback,
            $buffer
        ) ?? $buffer;

        $buffer = preg_replace_callback(
            '/\sdata-pso-class="([^"]+)"\s+class="([^"]*?)"/i',
            fn (array $m): string => ' class="'.trim($m[2].' '.$m[1]).'"',
            $buffer
        ) ?? $buffer;

        return preg_replace('/\sdata-pso-class="([^"]+)"/i', ' class="$1"', $buffer) ?? $buffer;
    }

    /**
     * Inject collected CSS classes into the <head> section.
     */
    private function injectStyle(string $buffer): string
    {
        $css = '<style>';

        foreach ($this->classMap as $declaration => $className) {
            // Escape closing tag to prevent breaking out of the <style> block
            $safe = str_ireplace('</style', '<\/style', $declaration);
            $css .= '.'.$className.'{'.$safe.'}';
        }

        $css .= '</style>';

        return str_replace('</head>', $css.'</head>', $buffer);
    }
}
