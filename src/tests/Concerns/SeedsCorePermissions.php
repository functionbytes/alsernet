<?php

namespace Tests\Concerns;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Siembra los permisos "core" del helpdesk que `Customer::scopeForAgent()` y las
 * policies (ConversationPolicy, CustomerPolicy) consultan con `hasPermissionTo()`.
 *
 * `hasPermissionTo()` LANZA `PermissionDoesNotExist` si el permiso no existe, así
 * que un test que ejerza esos caminos revienta en el setup si no los siembra —
 * la causa recurrente de fallos "There is no permission named `helpdesk.manage`".
 * Ejecutar el `PermissionsSeeder` core completo deadlockea en la BD de test
 * compartida (ver system_test_pristine), por eso aquí se crean solo los
 * imprescindibles con `findOrCreate` (idempotente). Llamar en `setUp()`.
 */
trait SeedsCorePermissions
{
    /**
     * @var array<int, string>
     */
    protected array $corePermissions = [
        'helpdesk.manage',
        'helpdesk.customers.manage',
        'helpdesk.customers.view',
        'helpdesk.conversations.view',
        'helpdesk.conversations.manage',
    ];

    protected function seedCorePermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->corePermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}
