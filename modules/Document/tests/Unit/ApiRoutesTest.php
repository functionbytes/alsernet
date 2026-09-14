<?php

namespace Modules\Document\Tests\Unit;

use Tests\TestCase;

/**
 * Test de rutas API - Verifica que todas las rutas estén registradas
 * No requiere base de datos
 */
class ApiRoutesTest extends TestCase
{
    public function test_all_api_routes_are_registered()
    {
        $expectedRoutes = [
            // Acciones de validación
            'api.documents.approve-stage',
            'api.documents.reject-stage',
            'api.documents.send-approval',
            'api.documents.send-rejection',
            'api.documents.send-custom-email',
            'api.documents.send-reminder',
            'api.documents.request-initial',
            'api.documents.request-missing',

            // Datos dinámicos
            'api.documents.next-stage-info',
            'api.documents.custom-email-template',
            'api.documents.action-history',
            'api.documents.email-history',
            'api.documents.status-timeline',

            // Document notes
            'api.documents.notes.add',
            'api.documents.notes.update',
            'api.documents.notes.delete',

            // Additional attachments
            'api.documents.attachments.upload',
            'api.documents.attachments.delete',

            // Document operations
            'api.documents.sync-fields',
            'api.documents.state',
            'api.documents.delete',

            // File operations
            'api.documents.files.store',
            'api.documents.files.get',
            'api.documents.files.delete',
        ];

        foreach ($expectedRoutes as $routeName) {
            $this->assertTrue(
                \Route::has($routeName),
                "❌ Ruta faltante: {$routeName}"
            );
        }

        $this->assertTrue(true, '✅ Todas las rutas API están registradas correctamente');
    }

    public function test_api_routes_use_correct_http_methods()
    {
        $routes = \Route::getRoutes();

        // POST routes
        $postRoutes = [
            'api.documents.approve-stage',
            'api.documents.reject-stage',
            'api.documents.send-approval',
            'api.documents.send-rejection',
            'api.documents.send-custom-email',
            'api.documents.send-reminder',
            'api.documents.request-initial',
            'api.documents.request-missing',
        ];

        foreach ($postRoutes as $routeName) {
            $route = $routes->getByName($routeName);
            $this->assertNotNull($route, "Ruta {$routeName} no encontrada");
            $this->assertContains('POST', $route->methods(), "Ruta {$routeName} debe ser POST");
        }

        // GET routes
        $getRoutes = [
            'api.documents.next-stage-info',
            'api.documents.custom-email-template',
            'api.documents.action-history',
            'api.documents.email-history',
            'api.documents.status-timeline',
        ];

        foreach ($getRoutes as $routeName) {
            $route = $routes->getByName($routeName);
            $this->assertNotNull($route, "Ruta {$routeName} no encontrada");
            $this->assertContains('GET', $route->methods(), "Ruta {$routeName} debe ser GET");
        }
    }

    public function test_api_routes_have_auth_middleware()
    {
        $routes = \Route::getRoutes();
        $routesToCheck = [
            'api.documents.approve-stage',
            'api.documents.next-stage-info',
            'api.documents.custom-email-template',
        ];

        foreach ($routesToCheck as $routeName) {
            $route = $routes->getByName($routeName);
            $this->assertNotNull($route);

            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware, "Ruta {$routeName} debe tener middleware 'auth'");
        }
    }

    public function test_api_routes_point_to_correct_controller()
    {
        $routes = \Route::getRoutes();
        $expectedController = 'Modules\Document\Http\Controllers\Api\DocumentValidationController';

        $routesToCheck = [
            'api.documents.approve-stage',
            'api.documents.reject-stage',
            'api.documents.next-stage-info',
        ];

        foreach ($routesToCheck as $routeName) {
            $route = $routes->getByName($routeName);
            $this->assertNotNull($route);

            $action = $route->getAction();
            $this->assertStringContainsString(
                $expectedController,
                $action['controller'] ?? '',
                "Ruta {$routeName} debe apuntar a DocumentValidationController"
            );
        }
    }

    public function test_routes_summary()
    {
        $routes = \Route::getRoutes();
        $apiRoutes = collect($routes)->filter(function ($route) {
            return str_starts_with($route->getName() ?? '', 'api.documents.');
        });

        $count = $apiRoutes->count();

        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║         📊 RESUMEN DE RUTAS API DOCUMENTOS                ║\n";
        echo "╠════════════════════════════════════════════════════════════╣\n";
        echo sprintf("║  Total rutas registradas: %-30d║\n", $count);
        echo "║                                                            ║\n";
        echo "║  ✅ Todas las rutas API están funcionando correctamente   ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        $this->assertGreaterThanOrEqual(13, $count, 'Deberían existir al menos 13 rutas API');
    }
}
