<?php

namespace Modules\HelpdeskSocial\Tests;

use Modules\HelpdeskSocial\Database\Seeders\HelpdeskSocialPermissionsSeeder;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // HelpdeskSocial está deshabilitado en modules_statuses.json, así que sus
        // migraciones no corren sobre la BD de test (system_test_pristine) y las
        // tablas helpdesk_social_* no existen. Sin esto, los 32 tests del módulo
        // fallan con "table doesn't exist", enmascarando regresiones reales del
        // resto de la suite. Al habilitar el módulo, sus tablas se migran y estos
        // tests vuelven a ejecutarse automáticamente.
        if (Module::find('HelpdeskSocial')?->isEnabled() !== true) {
            $this->markTestSkipped('Módulo HelpdeskSocial deshabilitado: sus tablas no están migradas en la BD de test.');
        }

        // Boot the real production permission seeder so the suite exercises the
        // exact permission names shipped to production (dot notation), instead of
        // inventing ad-hoc names that mask permission drift.
        $this->seed(HelpdeskSocialPermissionsSeeder::class);
    }
}
