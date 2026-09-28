<?php

namespace Modules\HelpdeskAiPrompts\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Tests\TestCase as BaseTestCase;

/**
 * Boots the app with HelpdeskAiPrompts ENABLED even if it is off in
 * modules_statuses.json (same trick as HelpdeskChatFlow/tests/TestCase.php):
 * points nwidart's file activator at a temp copy of the statuses file with
 * this module forced to true, only for this test process. The real file is
 * never touched.
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
        $statuses['HelpdeskAiPrompts'] = true;

        $path = sys_get_temp_dir().'/helpdeskaiprompts-test-modules-statuses-'.md5($app->basePath()).'.json';
        file_put_contents($path, json_encode($statuses, JSON_PRETTY_PRINT));

        return $path;
    }
}
