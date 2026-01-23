<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nwidart\Modules\Facades\Module;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar que los módulos en las rutas están activos
 *
 * Previene el acceso a rutas de módulos desactivados verificando:
 * - El prefijo del módulo en la ruta (ej: /manager/events → module: Event)
 * - El estado del módulo en modules_statuses.json
 */
class EnsureModuleIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $moduleName = $this->extractModuleFromRoute($request);

        if ($moduleName && ! $this->isModuleActive($moduleName)) {
            abort(404, "The {$moduleName} module is currently disabled.");
        }

        return $next($request);
    }

    /**
     * Extraer el nombre del módulo de la ruta actual
     *
     * Estrategias:
     * 1. Buscar parámetro de ruta 'module'
     * 2. Extraer del prefijo de ruta (ej: /manager/events → events)
     * 3. Mapear rutas conocidas a módulos
     */
    protected function extractModuleFromRoute(Request $request): ?string
    {
        // 1. Verificar si la ruta tiene un parámetro 'module'
        if ($request->route() && $request->route('module')) {
            return $request->route('module');
        }

        // 2. Mapeo de rutas a módulos
        $routeModuleMap = [
            'manager/events' => 'Event',
            'manager/campaigns' => 'Campaign',
            'manager/returns' => 'Return',
            'manager/helpdesk' => 'Helpdesk',
            'manager/faq' => 'Faq',
            'settings/mail' => 'Mail',
        ];

        $path = trim($request->getPathInfo(), '/');

        foreach ($routeModuleMap as $routePattern => $moduleName) {
            if (str_starts_with($path, $routePattern)) {
                return $moduleName;
            }
        }

        // 3. Intentar extraer del nombre de la ruta (ej: manager.events.* → Event)
        if ($request->route()) {
            $routeName = $request->route()->getName();

            if ($routeName && preg_match('/^(?:manager|settings)\.([a-z]+)\./', $routeName, $matches)) {
                $resource = $matches[1];

                // Mapeo de recursos a módulos
                $resourceModuleMap = [
                    'events' => 'Event',
                    'campaigns' => 'Campaign',
                    'returns' => 'Return',
                    'helpdesk' => 'Helpdesk',
                    'faq' => 'Faq',
                ];

                if (isset($resourceModuleMap[$resource])) {
                    return $resourceModuleMap[$resource];
                }

                // Intentar capitalizar el recurso (events → Event)
                $moduleName = ucfirst(rtrim($resource, 's'));
                if (Module::find($moduleName)) {
                    return $moduleName;
                }
            }
        }

        return null;
    }

    /**
     * Verificar si un módulo está activo
     */
    protected function isModuleActive(string $moduleName): bool
    {
        // Módulos críticos siempre activos
        $criticalModules = ['Core', 'Auth', 'Role', 'Theme', 'Modules'];
        if (in_array($moduleName, $criticalModules)) {
            return true;
        }

        $statusFile = base_path('modules_statuses.json');
        if (! file_exists($statusFile)) {
            return true; // Si no existe el archivo, asumir que todos están activos
        }

        $statuses = json_decode(file_get_contents($statusFile), true) ?? [];

        return isset($statuses[$moduleName]) && $statuses[$moduleName] === true;
    }
}
