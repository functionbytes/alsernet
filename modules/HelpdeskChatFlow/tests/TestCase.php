<?php

namespace Modules\HelpdeskChatFlow\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Tests\TestCase as BaseTestCase;

/**
 * Base de los tests del módulo: arranca la aplicación con HelpdeskChatFlow
 * ACTIVADO aunque esté apagado en modules_statuses.json (lo está en este
 * entorno desde 8c95c5bd8). Apagado, su ServiceProvider no registra rutas,
 * comandos ni listeners, y ~50 tests caían con "Route [chatflow.*] not
 * defined" sin señalar ningún defecto real.
 *
 * El activador de nwidart lee el fichero de estados al registrarse; aquí se
 * apunta (solo en este proceso de test) a una copia temporal con el módulo en
 * true, antes de que corran los providers. El fichero real no se toca.
 */
abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));

        $app->afterBootstrapping(LoadConfiguration::class, function (Application $app): void {
            $app['config']->set('modules.activators.file.statuses-file', self::enabledStatusesFile($app));
        });

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    private static function enabledStatusesFile(Application $app): string
    {
        $statuses = json_decode((string) @file_get_contents($app->basePath('modules_statuses.json')), true) ?: [];
        $statuses['HelpdeskChatFlow'] = true;

        $path = sys_get_temp_dir().'/chatflow-test-modules-statuses-'.md5($app->basePath()).'.json';
        file_put_contents($path, json_encode($statuses, JSON_PRETTY_PRINT));

        return $path;
    }
}
