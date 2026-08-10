<?php

namespace Modules\Helpdesk\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Helpdesk\Models\Inbox;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Acceso al módulo de WhatsApp/Helpdesk para el equipo real de soporte.
 * Sigue el mismo patrón que Document: permisos granulares por rol (no un
 * toggle aparte), pero acá con roles propios del módulo en vez de reusar los
 * departamentales, porque este grupo de gente no mapea 1:1 a un solo
 * departamento existente.
 */
class HelpdeskAccessSeeder extends Seeder
{
    private const SUPER_ADMINS = [
        ['firstname' => 'Ana', 'lastname' => 'Cupeiro', 'email' => 'anacup@a-alvarez.com'],
        ['firstname' => 'Monica', 'lastname' => 'Monica', 'email' => 'attcliente@a-alvarez.com'],
    ];

    private const USERS = [
        ['firstname' => 'Helena', 'lastname' => 'Helena', 'email' => 'web@a-alvarez.com'],
        ['firstname' => 'Rebeca', 'lastname' => 'Rebeca', 'email' => 'clientes@a-alvarez.com'],
        ['firstname' => 'Graci', 'lastname' => 'Graci', 'email' => 'graci@a-alvarez.com'],
        ['firstname' => 'Ángelica', 'lastname' => 'Ángelica', 'email' => 'pedidos.madrid@a-alvarez.com'],
        ['firstname' => 'Inma', 'lastname' => 'Inma', 'email' => 'pedidos@a-alvarez.com'],
        ['firstname' => 'Angeles', 'lastname' => 'Angeles', 'email' => 'devoluciones@a-alvarez.com'],
        ['firstname' => 'Begoña', 'lastname' => 'Begoña', 'email' => 'begona@a-alvarez.com'],
        ['firstname' => 'Mari', 'lastname' => 'Carmen', 'email' => 'pedidos1@a-alvarez.com'],
        ['firstname' => 'Eva', 'lastname' => 'Eva', 'email' => 'ebelen@a-alvarez.com'],
        // Resto de las cuentas reales @a-alvarez.com que no estaban en la
        // lista original (casillas distintas de la misma gente, y Miguel).
        ['firstname' => 'Helena', 'lastname' => 'Helena', 'email' => 'helena@a-alvarez.com'],
        ['firstname' => 'Ángeles', 'lastname' => 'Ángeles', 'email' => 'reparaciones@a-alvarez.com'],
        ['firstname' => 'Miguel', 'lastname' => 'Miguel', 'email' => 'contenidosweb@a-alvarez.com'],
    ];

    private const AGENT_PERMISSIONS = [
        'helpdesk.view',
        'helpdesk.conversations.view',
        'helpdesk.conversations.reply',
        'helpdesk.conversations.create',
        'helpdesk.conversations.update',
        'helpdesk.conversations.link-customer',
        'helpdesk.conversations.participants.manage',
        'helpdesk.customers.view',
        'helpdesk.customers.create',
        'helpdesk.customers.update',
        'helpdesk.customers.insights',
        'helpdesk.canned-replies.view',
        'helpdesk.groups.view',
        'helpdesk.tags.view',
        // Contactos/CRM es un módulo aparte (HelpdeskContacts) con su propio
        // ícono en el mini-nav — sin esto no pueden ni verlo ni entrar.
        'modules.view.contacts',
        'contacts.view',
        'contacts.insights',
        // Sin esto la búsqueda externa ERP/PrestaShop (modal "CLIENTE ·
        // BÚSQUEDA EXTERNA") se puede ver pero no crear/vincular el
        // resultado, y tampoco se puede enviar plantilla HSM a un contacto.
        'contacts.update',
        // Import/sync de plantillas WhatsApp desde Meta y envío de HSM.
        'helpdesk.whatsapp-templates.view',
        'helpdesk.whatsapp-templates.manage',
        // Confirmar vínculo en el modal "CLIENTE · IDENTIDAD" (LinkCustomerIntegrationRequest::authorize).
        // syncPermissions() pisa cualquier givePermissionTo externo (ej. el
        // backfill de HelpdeskIntegrationPermissionsSeeder) si no está acá.
        'helpdesk.integrations.manage',
    ];

    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'helpdesk-admin', 'guard_name' => 'web']);
        $agentRole = Role::firstOrCreate(['name' => 'helpdesk-agent', 'guard_name' => 'web']);

        // modules.view.helpdesk es lo que NavService::userCanAccessModule()
        // chequea para mostrar el ícono del mini-nav (no helpdesk.view).
        // Contactos/CRM (HelpdeskContacts) es un módulo aparte con su propio
        // ícono — el admin recibe control completo, no solo lectura.
        $allHelpdeskPermissions = Permission::where('guard_name', 'web')
            ->where(fn ($q) => $q->where('name', 'like', 'helpdesk%')->orWhere('name', 'like', 'contacts.%'))
            ->pluck('name')
            ->push('modules.view.helpdesk')
            ->push('modules.view.contacts')
            ->unique()
            ->all();
        $adminRole->syncPermissions($allHelpdeskPermissions);

        $agentPermissions = array_values(array_intersect(
            [...self::AGENT_PERMISSIONS, 'modules.view.helpdesk'],
            Permission::where('guard_name', 'web')->pluck('name')->all()
        ));
        $agentRole->syncPermissions($agentPermissions);

        foreach (self::SUPER_ADMINS as $data) {
            $this->userWithRole($data, $adminRole);
        }

        $whatsappInboxId = Inbox::query()->where('name', 'WhatsApp Soporte')->value('id');

        foreach (self::USERS as $data) {
            $user = $this->userWithRole($data, $agentRole);

            // Sin helpdesk.manage, ConversationsController::getUserInboxIds()
            // solo muestra las bandejas listadas acá — sin esto el sidebar de
            // "Inboxes" queda vacío pese a tener el rol/permisos correctos.
            if ($whatsappInboxId) {
                DB::connection('helpdesk')->table('helpdesk_agent_inbox_capacity')->updateOrInsert(
                    ['user_id' => $user->id, 'inbox_id' => $whatsappInboxId],
                    ['max_concurrent' => 15, 'accepts_new' => 1, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    /**
     * assignRole (no syncRoles): esta gente ya tiene roles departamentales
     * (callcenter, license, accounting, incluso super-admin) que no hay que
     * tocar — solo se suma el acceso a Helpdesk.
     */
    private function userWithRole(array $data, Role $role): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $data['email']],
            [
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'password' => Hash::make('Alv.2026'),
            ]
        );

        if (! $user->hasRole($role->name)) {
            $user->assignRole($role);
        }

        return $user;
    }
}
