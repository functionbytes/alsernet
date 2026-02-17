<?php

namespace Modules\Template\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\View\Factory;

/**
 * Middleware para registrar la ruta del template activo en el view factory
 *
 * Asegura que @extends('template::layouts.default') resuelva a la ruta correcta
 * del template activo en platform/themes/
 */
class RegisterTemplateViewPath
{
    public function __construct(protected Factory $viewFactory)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        // Obtener el nombre del template activo
        $activeTemplateName = $this->getActiveTemplateName();
        $activeTemplatePath = base_path('platform/themes/' . $activeTemplateName);

        // Registrar la ruta del template activo en el view factory
        if (is_dir($activeTemplatePath)) {
            // Prepend the active template path so it's checked first
            $paths = $this->viewFactory->getFinder()->getPaths();

            // Remove active template path if it's already there (to move it to the front)
            $paths = array_filter($paths, fn ($p) => $p !== $activeTemplatePath);

            // Add it to the front
            array_unshift($paths, $activeTemplatePath);

            $this->viewFactory->getFinder()->setPaths($paths);
        }

        return $next($request);
    }

    /**
     * Get the active template name from settings
     */
    private function getActiveTemplateName(): string
    {
        try {
            return setting('template', 'default');
        } catch (\Exception $e) {
            return 'default';
        }
    }
}
