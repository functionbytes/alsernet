<?php

namespace Tests\Concerns;

use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;

/**
 * Siembra los ~80 permisos `helpdesk.*` (incluido `helpdesk.manage`) que
 * varias policies consultan por nombre vía `hasPermissionTo()` — por ejemplo
 * `CustomerPolicy::sharesInboxWith()` y `ConversationPolicy::canAccessInbox()`
 * — y que lanzan `PermissionDoesNotExist` si la fila no existe todavía.
 *
 * Los seeders de permisos de módulos satélite (ej. HelpdeskContactsPermissionsSeeder)
 * solo crean SUS propios permisos (`contacts.*`), no el set core de Helpdesk;
 * cualquier test fuera de `modules/Helpdesk` que ejercite código que consulte
 * permisos `helpdesk.*` necesita este seeder aparte. Mismo patrón que
 * `modules/Helpdesk/tests/HelpdeskTestCase.php` (`$this->seed(PermissionsSeeder::class)`).
 */
trait SeedsCorePermissions
{
    protected function seedCorePermissions(): void
    {
        $this->seed(PermissionsSeeder::class);
    }
}
