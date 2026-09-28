<?php

namespace Modules\Helpdesk\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Inbox;
use Spatie\Permission\PermissionRegistrar;

/**
 * Perfiles de agente del cliente alvarez (21-sep-2026): 'helpdesk-agent-restricted'
 * (solo ve lo que le asignan) y 'helpdesk-supervisor' (ve/reasigna todo, sin
 * Settings). Ver HelpdeskRolesSeeder para la definición de esos roles.
 *
 * Dos grupos distintos:
 * - PREEXISTING_*: usuarios que ya existían con el rol 'helpdesk-agent' —
 *   aquí solo se les reemplaza el rol de Helpdesk (sin tocar sus otros roles
 *   como 'accounting'/'callcenter'/'license', ni ningún otro dato).
 * - NEW_RESTRICTED_USERS: cuentas nuevas creadas para completar la lista del
 *   cliente (contraseña Alv.2026) — se crean solo si no existen.
 *
 * Idempotente: correrlo de nuevo no duplica nada ni pisa cambios que un
 * admin haya hecho a mano después (salvo el rol de Helpdesk, que es
 * justamente lo que este seeder existe para fijar).
 */
class HelpdeskAlvarezAgentProfilesSeeder extends Seeder
{
    /** Ya existían con 'helpdesk-agent' → pasan a 'helpdesk-agent-restricted'. */
    private const PREEXISTING_RESTRICTED_EMAILS = [
        'ebelen@a-alvarez.com',
        'begona@a-alvarez.com',
        'pedidos@a-alvarez.com',
        'pedidos.madrid@a-alvarez.com',
        'graci@a-alvarez.com',
        'devoluciones@a-alvarez.com',
    ];

    /** Ya existían con 'helpdesk-agent' → pasan a 'helpdesk-supervisor'. */
    private const PREEXISTING_SUPERVISOR_EMAILS = [
        'clientes@a-alvarez.com',
        'web@a-alvarez.com',
        'contenidosweb@a-alvarez.com',
    ];

    /**
     * No existían: se crean con 'helpdesk-agent-restricted' y contraseña
     * Alv.2026 (must_change_password queda en false, igual que el resto de
     * cuentas de este cliente — no se fuerza cambio en el primer login).
     */
    private const NEW_RESTRICTED_USERS = [
        ['name' => 'Chus', 'email' => 'jfernandez@a-alvarez.com'],
        ['name' => 'Marisol', 'email' => 'mcancela@a-alvarez.com'],
        ['name' => 'Talia', 'email' => 'tmartin@a-alvarez.com'],
        ['name' => 'Rosa', 'email' => 'rosa.sanchez@a-alvarez.com'],
        ['name' => 'Carmen', 'email' => 'cvarela@a-alvarez.com'],
    ];

    private const NEW_USER_PASSWORD = 'Alv.2026';

    public function run(): void
    {
        // Los roles/permisos de Helpdesk deben existir antes de asignarlos —
        // seguro de re-ejecutar aunque ya se hayan sembrado.
        $this->call([PermissionsSeeder::class, HelpdeskRolesSeeder::class]);

        $whatsappInboxId = Inbox::where('channel_type', 'whatsapp')->value('id');

        foreach (self::PREEXISTING_RESTRICTED_EMAILS as $email) {
            $this->reassignRole($email, 'helpdesk-agent-restricted');
        }

        foreach (self::PREEXISTING_SUPERVISOR_EMAILS as $email) {
            $this->reassignRole($email, 'helpdesk-supervisor');
        }

        foreach (self::NEW_RESTRICTED_USERS as $data) {
            // Solo hashea/asigna password si el registro es nuevo — no pisar
            // la clave de alguien que ya la cambió en un re-run.
            $existingPassword = User::where('email', $data['email'])->value('password');

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'firstname' => $data['name'],
                    'lastname' => $data['name'],
                    'available' => true,
                    'timezone' => 'Europe/Madrid',
                    'mail_verified_at' => now(),
                    'password' => $existingPassword ?? Hash::make(self::NEW_USER_PASSWORD),
                ]
            );

            $this->reassignRole($data['email'], 'helpdesk-agent-restricted');

            if ($whatsappInboxId) {
                AgentInboxCapacity::firstOrCreate([
                    'user_id' => $user->id,
                    'inbox_id' => $whatsappInboxId,
                ]);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Quita cualquier rol de Helpdesk previo (agent/restricted/supervisor/
     * admin) y asigna el nuevo — nunca toca roles de otros módulos
     * (accounting, callcenter, license, ...).
     */
    private function reassignRole(string $email, string $role): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return;
        }

        foreach (['helpdesk-agent', 'helpdesk-agent-restricted', 'helpdesk-supervisor', 'helpdesk-admin'] as $helpdeskRole) {
            $user->removeRole($helpdeskRole);
        }

        $user->assignRole($role);
    }
}
