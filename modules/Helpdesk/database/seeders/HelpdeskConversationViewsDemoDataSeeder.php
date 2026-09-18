<?php

namespace Modules\Helpdesk\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Group;

/**
 * Reparte las conversaciones de demostración entre estados, grupos, agentes,
 * pospuestas y archivadas.
 *
 * Sin esto las vistas de HelpdeskConversationViewsSeeder se guardan bien pero
 * salen todas vacías o todas con lo mismo: en la base de demo las
 * conversaciones están al 100 % sin asignar, sin grupo y en estado "Nuevo", así
 * que "Sin asignar" devuelve el total y "En espera", "Archivadas", "Pospuestas"
 * o "Cerradas" devuelven cero. Este seeder les da variedad para que cada vista
 * enseñe algo distinto.
 *
 * SOLO toca conversaciones con asunto: las que llegan de verdad por WhatsApp
 * entran sin asunto y no se tocan, para no manosear datos reales.
 *
 * Es idempotente: el perfil de cada conversación se decide por su posición en
 * el listado ordenado por id, así que volver a lanzarlo deja lo mismo.
 *
 *   php artisan db:seed --class="Modules\Helpdesk\Database\Seeders\HelpdeskConversationViewsDemoDataSeeder"
 */
class HelpdeskConversationViewsDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = $this->statuses();
        $groups = $this->groups();
        $agents = $this->agents();

        if (empty($statuses['nuevo']) || empty($agents)) {
            $this->command?->warn('Faltan estados o usuarios; no se reparte nada.');

            return;
        }

        $conversations = Conversation::whereNotNull('subject')
            ->where('subject', '!=', '')
            ->orderBy('id')
            ->get();

        $profiles = $this->profiles($statuses, $groups, $agents);
        $readByAdmin = [];

        foreach ($conversations->values() as $index => $conversation) {
            $profile = $profiles[$index % count($profiles)];

            // Lo urgente no se archiva, ni se pospone, ni se cierra: se atiende.
            // Además garantiza que la vista "Urgentes" (que filtra por abiertas)
            // nunca se quede sin nada que enseñar.
            if ($conversation->priority === 'urgent' && $this->takesItOutOfTheQueue($profile)) {
                $profile = $profiles[1];
            }

            $conversation->forceFill($this->attributesFor($profile, $conversation))->save();

            if (($profile['read_by_admin'] ?? false) && $profile['assignee_id'] === $agents['me']) {
                $readByAdmin[] = $conversation->id;
            }
        }

        $this->markAsRead($readByAdmin, $agents['me']);

        $this->command?->info("Repartidas {$conversations->count()} conversaciones de demostración.");
    }

    /**
     * Estado final de una conversación según su perfil.
     *
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function attributesFor(array $profile, Conversation $conversation): array
    {
        $closed = (bool) ($profile['closed'] ?? false);

        return [
            'status_id' => $profile['status_id'],
            'group_id' => $profile['group_id'],
            'assignee_id' => $profile['assignee_id'],
            'assigned_at' => $profile['assignee_id'] ? ($conversation->assigned_at ?? now()) : null,
            'closed_at' => $closed ? ($conversation->closed_at ?? now()->subHours(6)) : null,
            'is_archived' => (bool) ($profile['archived'] ?? false),
            'snoozed_until' => isset($profile['snooze_hours']) ? now()->addHours($profile['snooze_hours']) : null,
            'snoozed_by' => isset($profile['snooze_hours']) ? ($profile['assignee_id'] ?? null) : null,
        ];
    }

    /**
     * Los doce perfiles que se van repitiendo por orden de id.
     *
     * @param  array<string, int|null>  $statuses
     * @param  array<string, int|null>  $groups
     * @param  array<string, int|null>  $agents
     * @return array<int, array<string, mixed>>
     */
    private function profiles(array $statuses, array $groups, array $agents): array
    {
        return [
            // 0 — cola de entrada: nadie la ha cogido todavía.
            ['status_id' => $statuses['nuevo'], 'group_id' => null, 'assignee_id' => null],

            // 1 — la lleva el usuario con el que se entra al panel ("Mías").
            ['status_id' => $statuses['activo'], 'group_id' => $groups['general'], 'assignee_id' => $agents['me']],

            // 2 — esperando respuesta del cliente, en Facturación.
            ['status_id' => $statuses['esperando'], 'group_id' => $groups['facturacion'], 'assignee_id' => $agents['second']],

            // 3 — abierta y encolada en Técnico, sin agente.
            ['status_id' => $statuses['nuevo'], 'group_id' => $groups['tecnico'], 'assignee_id' => null],

            // 4 — en marcha con otro agente.
            ['status_id' => $statuses['activo'], 'group_id' => $groups['general'], 'assignee_id' => $agents['third']],

            // 5 — resuelta.
            ['status_id' => $statuses['resuelto'], 'group_id' => $groups['devoluciones'], 'assignee_id' => $agents['fourth'], 'closed' => true],

            // 6 — pospuesta un par de días.
            ['status_id' => $statuses['nuevo'], 'group_id' => null, 'assignee_id' => null, 'snooze_hours' => 48],

            // 7 — mía y ya leída, para que "Sin leer" sea un subconjunto real.
            ['status_id' => $statuses['activo'], 'group_id' => $groups['premium'], 'assignee_id' => $agents['me'], 'read_by_admin' => true],

            // 8 — archivada: el inbox la esconde salvo que la vista la pida.
            ['status_id' => $statuses['nuevo'], 'group_id' => null, 'assignee_id' => null, 'archived' => true],

            // 9 — esperando al cliente, en General.
            ['status_id' => $statuses['esperando'], 'group_id' => $groups['general'], 'assignee_id' => $agents['second']],

            // 10 — cerrada del todo.
            ['status_id' => $statuses['cerrado'], 'group_id' => $groups['tecnico'], 'assignee_id' => $agents['third'], 'closed' => true],

            // 11 — mía y en Devoluciones: es la que da carne a la vista personal
            // "Mis devoluciones", que cruza asignación y grupo.
            ['status_id' => $statuses['activo'], 'group_id' => $groups['devoluciones'], 'assignee_id' => $agents['me']],
        ];
    }

    /**
     * ¿El perfil saca la conversación de la cola de trabajo?
     *
     * @param  array<string, mixed>  $profile
     */
    private function takesItOutOfTheQueue(array $profile): bool
    {
        return ($profile['closed'] ?? false)
            || ($profile['archived'] ?? false)
            || isset($profile['snooze_hours']);
    }

    /**
     * Deja marcadas como leídas por el administrador las que le tocan.
     *
     * @param  array<int, int>  $conversationIds
     */
    private function markAsRead(array $conversationIds, ?int $userId): void
    {
        if (empty($conversationIds) || ! $userId) {
            return;
        }

        foreach ($conversationIds as $conversationId) {
            // updateOrInsert y no insert(): hay un índice único
            // (conversation_id, user_id), así que volver a lanzar el seeder
            // reventaría. Y una consulta nueva por vuelta, porque encadenar
            // where() sobre el mismo Builder va acumulando condiciones.
            DB::connection('helpdesk')->table('helpdesk_conversation_reads')->updateOrInsert(
                ['conversation_id' => $conversationId, 'user_id' => $userId],
                ['read_at' => now(), 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    /**
     * Estados buscados por nombre, con el id como último recurso.
     *
     * @return array<string, int|null>
     */
    private function statuses(): array
    {
        $byName = fn (string $name) => ConversationStatus::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->value('id');

        $nuevo = $byName('Nuevo') ?? ConversationStatus::orderBy('id')->value('id');

        return [
            'nuevo' => $nuevo,
            'activo' => $byName('Activo') ?? $nuevo,
            'esperando' => $byName('Esperando') ?? $nuevo,
            'resuelto' => $byName('Resuelto') ?? $byName('Cerrado') ?? $nuevo,
            'cerrado' => $byName('Cerrado') ?? $byName('Resuelto') ?? $nuevo,
        ];
    }

    /**
     * @return array<string, int|null>
     */
    private function groups(): array
    {
        $like = function (string $needle): ?int {
            return Group::whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($needle).'%'])->value('id');
        };

        return [
            'general' => $like('General'),
            'tecnico' => $like('Técnico') ?? $like('Tecnico'),
            'facturacion' => $like('Facturación') ?? $like('Facturacion'),
            'premium' => $like('Premium'),
            'devoluciones' => $like('Devoluciones'),
        ];
    }

    /**
     * Cuatro agentes: el primero es con el que se entra al panel, para que las
     * vistas "Mías" y "Sin leer" (que resuelven con auth()->id()) tengan carne.
     *
     * @return array<string, int|null>
     */
    private function agents(): array
    {
        $me = User::where('email', 'admin@alsernet.test')->value('id')
            ?? User::orderBy('id')->value('id');

        if (! $me) {
            return [];
        }

        $others = User::where('id', '!=', $me)->orderBy('id')->limit(3)->pluck('id')->all();

        return [
            'me' => $me,
            'second' => $others[0] ?? $me,
            'third' => $others[1] ?? $me,
            'fourth' => $others[2] ?? $me,
        ];
    }
}
