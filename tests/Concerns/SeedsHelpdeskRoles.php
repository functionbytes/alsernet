<?php

namespace Tests\Concerns;

use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Siembra (idempotente) los roles que el código de la app consulta por nombre.
 *
 * La BD de test (system_test_pristine) arranca con la tabla `roles` VACÍA, y
 * varios caminos de la app lanzan `RoleDoesNotExist` si el rol no existe:
 *  - `User::role('helpdesk-agent')` en SendNewConversationNotification /
 *    SendMessageReceivedNotification (webhooks de inbound).
 *  - `assignRole(...)` en tests que antes creaban roles ad-hoc e inconsistentes.
 *
 * Se usa `Role::findOrCreate` para que sea seguro llamarlo en cada setUp():
 * dentro de DatabaseTransactions se revierte; fuera, es idempotente.
 */
trait SeedsHelpdeskRoles
{
    /**
     * @var array<int, string>
     */
    protected array $helpdeskRoles = [
        'super-admin',
        'super-settings',
        'manager',
        'helpdesk-agent',
        'helpdesk-admin',
        'helpdesk-manager',
    ];

    protected function seedHelpdeskRoles(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->helpdeskRoles as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
