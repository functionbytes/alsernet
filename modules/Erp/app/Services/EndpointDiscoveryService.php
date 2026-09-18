<?php

namespace Modules\Erp\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route as RouteFacade;
use Modules\Erp\Models\ErpEndpoint;
use ReflectionClass;

class EndpointDiscoveryService
{
    /**
     * URI prefix used to identify discoverable ERP endpoints.
     */
    private const ROUTE_PREFIX = 'api/erp/';

    /**
     * HTTP verbs ignored when listing endpoints (HEAD is auto-added by Laravel
     * for every GET; OPTIONS is rarely a meaningful endpoint).
     */
    private const IGNORED_METHODS = ['HEAD', 'OPTIONS'];

    /**
     * Discover all registered ERP endpoints from Laravel's route registrar.
     */
    public function discoverEndpoints(): Collection
    {
        return collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route) => str_starts_with($route->uri(), self::ROUTE_PREFIX))
            ->flatMap(fn (Route $route) => $this->routeToEndpoints($route))
            ->values();
    }

    /**
     * Sync discovered endpoints with database.
     */
    public function syncWithDatabase(): void
    {
        $discovered = $this->discoverEndpoints()->keyBy('slug');

        $existingSlugs = ErpEndpoint::autoDiscovered()->pluck('slug');

        // Mark as deprecated endpoints that no longer exist in routes
        $deprecated = $existingSlugs->diff($discovered->keys());
        if ($deprecated->isNotEmpty()) {
            ErpEndpoint::whereIn('slug', $deprecated)->update(['deprecated_at' => now()]);
        }

        foreach ($discovered as $slug => $data) {
            $endpoint = ErpEndpoint::firstOrNew(['slug' => $slug]);
            // forceFill: bypass $fillable for discovery-managed columns
            // (controller/action/is_auto_discovered/deprecated_at).
            $endpoint->forceFill([
                'name' => ucwords(str_replace(['-', '_'], ' ', $slug)),
                'url' => '/'.$data['uri'],
                'method' => $data['method'],
                'controller' => $data['controller'],
                'action' => $data['action'],
                'description' => $data['description'],
                'is_auto_discovered' => true,
                // Default to inactive so new endpoints don't go live unsupervised
                'is_active' => $endpoint->exists ? $endpoint->is_active : false,
                'deprecated_at' => null,
            ])->save();
        }
    }

    /**
     * Get endpoint metadata including example request/response.
     */
    public function getEndpointMetadata(string $slug): array
    {
        $endpoint = ErpEndpoint::where('slug', $slug)->firstOrFail();

        return [
            'name' => $endpoint->name,
            'uri' => $endpoint->url,
            'method' => $endpoint->method,
            'description' => $endpoint->description,
            'is_configured' => $endpoint->credential_id !== null,
            'credential' => $endpoint->credential,
            'headers' => $endpoint->headers,
            'timeout' => $endpoint->timeout,
            'retry_attempts' => $endpoint->retry_attempts,
            'created_at' => $endpoint->created_at,
            'last_used_at' => $endpoint->logs()->latest()->first()?->created_at,
        ];
    }

    /**
     * Get summary statistics.
     */
    public function getSyncStatistics(): array
    {
        $inDatabase = ErpEndpoint::autoDiscovered()->count();
        $deprecated = ErpEndpoint::deprecated()->count();
        $configured = ErpEndpoint::autoDiscovered()->configured()->count();

        return [
            'total_discovered' => $this->discoverEndpoints()->count(),
            'total_in_database' => $inDatabase,
            'deprecated' => $deprecated,
            'configured' => $configured,
            'unconfigured' => $inDatabase - $configured,
        ];
    }

    /**
     * Convert a Laravel route into one or more endpoint definitions
     * (one per usable HTTP verb).
     */
    private function routeToEndpoints(Route $route): array
    {
        $action = $route->getAction();
        $controllerClass = is_string($action['controller'] ?? null)
            ? explode('@', $action['controller'])[0]
            : null;
        $actionMethod = $route->getActionMethod();
        $controllerShort = $controllerClass ? class_basename($controllerClass) : 'Closure';

        $endpoints = [];
        foreach ($route->methods() as $method) {
            if (in_array($method, self::IGNORED_METHODS, true)) {
                continue;
            }

            $endpoints[] = [
                'uri' => $route->uri(),
                'method' => $method,
                'controller' => $controllerShort,
                'action' => $actionMethod,
                'controller_class' => $controllerClass,
                'slug' => $this->generateSlug($method, $route->uri()),
                'description' => $this->extractDocblockDescription($controllerClass, $actionMethod),
                'is_auto_discovered' => true,
            ];
        }

        return $endpoints;
    }

    /**
     * Generate a deterministic, unique slug per (method, uri) pair.
     */
    private function generateSlug(string $method, string $uri): string
    {
        $normalized = preg_replace('/\{[^}]+\}/', 'param', $uri);
        $normalized = preg_replace('#^api/erp/#', '', $normalized);
        $normalized = trim(preg_replace('/[^a-z0-9]+/i', '-', $normalized), '-');

        return strtolower($method.'-'.($normalized ?: 'root'));
    }

    /**
     * Extract the first line of the action's docblock as description.
     */
    private function extractDocblockDescription(?string $controllerClass, ?string $action): ?string
    {
        if (! $controllerClass || ! $action || ! class_exists($controllerClass)) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($controllerClass);
            if (! $reflection->hasMethod($action)) {
                return null;
            }

            $docblock = $reflection->getMethod($action)->getDocComment();
            if (! $docblock) {
                return null;
            }

            preg_match('/\/\*\*\s*\n\s*\*\s*(.+?)\n/', $docblock, $matches);

            return $matches[1] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}
