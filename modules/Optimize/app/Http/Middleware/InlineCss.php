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
     */
    private function injectClass(string $buffer): string
    {
        return preg_replace_callback(
            '/\sstyle="([^"]+)"/i',
            function (array $matches): string {
                $css = trim($matches[1]);

                if (! isset($this->classMap[$css])) {
                    $className = 'pso-'.$this->classCounter++;
                    $this->classMap[$css] = $className;
                }

                return ' class="'.$this->classMap[$css].'"';
            },
            $buffer
        );
    }

    /**
     * Inject collected CSS classes into the <head> section.
     */
    private function injectStyle(string $buffer): string
    {
        $css = '<style>';

        foreach ($this->classMap as $declaration => $className) {
            $css .= '.'.$className.'{'.$declaration.'}';
        }

        $css .= '</style>';

        return str_replace('</head>', $css.'</head>', $buffer);
    }
}
