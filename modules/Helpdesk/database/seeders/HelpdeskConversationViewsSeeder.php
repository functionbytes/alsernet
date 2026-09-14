<?php

namespace Modules\Helpdesk\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationView;
use Modules\Helpdesk\Models\Group;

/**
 * Vistas guardadas del inbox (Ajustes → Helpdesk → Vistas).
 *
 * Los filtros usan SOLO el vocabulario que entiende de verdad
 * ConversationFilter::applyViewFilters(): status_id, snoozed, is_open,
 * is_archived, assignee, group, priority, status, archived, channel, inbox,
 * tag, search, urgent, mine, unread y vip. Cualquier otra clave cae en el
 * `default => null` del match y se ignora en silencio: la vista se guarda, se
 * ve bien en el listado y no filtra nada. De ahí que aquí no se invente ninguna.
 *
 * Igual con sort_by: StoreConversationViewRequest solo admite created_at,
 * updated_at, priority, status y assignee_id.
 *
 * Estados y grupos se buscan POR NOMBRE, nunca por id fijo: los ids cambian
 * entre entornos y una vista apuntando a un id inexistente saldría vacía sin
 * dar ningún error. Si el estado o el grupo no está, esa vista no se siembra.
 *
 * Ninguna vista se marca is_system, para que todas se puedan editar y borrar
 * desde la pantalla (canEdit()/canDelete() bloquean las de sistema).
 *
 *   php artisan db:seed --class="Modules\Helpdesk\Database\Seeders\HelpdeskConversationViewsSeeder"
 */
class HelpdeskConversationViewsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $definition) {
            // Clave por nombre + dueño: re-sembrar actualiza en vez de duplicar.
            ConversationView::updateOrCreate(
                ['name' => $definition['name'], 'user_id' => $definition['user_id']],
                $definition,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function definitions(): array
    {
        $views = [];

        // ─── Cola diaria (públicas) ──────────────────────────────────────────
        // La primera tiene que ser inclusiva a propósito: sin ?viewId el inbox
        // aplica la vista predeterminada (y si ninguna lo es, la primera del
        // orden), así que una vista estrecha aquí haría que al entrar en
        // Conversaciones pareciera que han desaparecido la mitad.
        $views[] = $this->publicView(
            'Todas las abiertas',
            'Todo lo que sigue abierto, sin más recortes. Es la que se aplica al entrar al inbox.',
            ['is_open' => true],
            'updated_at', 'desc', 1, isDefault: true,
        );

        $views[] = $this->publicView(
            'Sin asignar',
            'Conversaciones abiertas que todavía no tienen agente. Es la cola de la que hay que tirar primero.',
            ['is_open' => true, 'assignee' => 'unassigned'],
            'created_at', 'asc', 2,
        );

        $views[] = $this->publicView(
            'Mías',
            'Las conversaciones abiertas asignadas a mí. El filtro se resuelve con el usuario en sesión, así que cada agente ve las suyas.',
            ['is_open' => true, 'mine' => true],
            'updated_at', 'desc', 3,
        );

        $views[] = $this->publicView(
            'Sin leer',
            'Abiertas con mensajes que todavía no he abierto. También se resuelve por usuario.',
            ['is_open' => true, 'unread' => true],
            'updated_at', 'desc', 4,
        );

        // ─── Urgencia ────────────────────────────────────────────────────────
        $views[] = $this->publicView(
            'Urgentes',
            'Prioridad urgente y sin cerrar. Lo que no puede esperar a mañana.',
            ['is_open' => true, 'priority' => 'urgent'],
            'created_at', 'asc', 5,
        );

        $views[] = $this->publicView(
            'Clientes VIP',
            'Abiertas de clientes con cinco o más conversaciones a sus espaldas, que es como el inbox define VIP.',
            ['is_open' => true, 'vip' => true],
            'updated_at', 'desc', 6,
        );

        // ─── Por canal ───────────────────────────────────────────────────────
        $channels = [
            ['WhatsApp', 'whatsapp', 'Todo lo que entra por WhatsApp y sigue abierto.'],
            ['Correo', 'email', 'Conversaciones abiertas nacidas de un correo.'],
            ['Facebook', 'facebook', 'Abiertas llegadas por Facebook. Duplica esta vista cambiando el canal para Instagram o Web.'],
        ];

        foreach ($channels as $i => [$label, $channel, $description]) {
            $views[] = $this->publicView(
                'Canal: '.$label,
                $description,
                ['is_open' => true, 'channel' => $channel],
                'updated_at', 'desc', 10 + $i,
            );
        }

        // ─── Por estado concreto ─────────────────────────────────────────────
        if ($esperando = $this->statusId('Esperando')) {
            $views[] = $this->publicView(
                'En espera del cliente',
                'Conversaciones paradas esperando respuesta del cliente. Útil para repasar y cerrar las que ya no van a contestar.',
                ['status_id' => $esperando],
                'updated_at', 'asc', 20,
            );
        }

        // ─── Por grupo ───────────────────────────────────────────────────────
        if ($facturacion = $this->groupId('Facturación', 'Facturacion')) {
            $views[] = $this->publicView(
                'Grupo: Facturación',
                'Abiertas repartidas al grupo de Facturación.',
                ['is_open' => true, 'group' => $facturacion],
                'created_at', 'asc', 21,
            );
        }

        // ─── Fuera de la cola ────────────────────────────────────────────────
        $views[] = $this->publicView(
            'Pospuestas',
            'Conversaciones con un snooze activo: salen de la cola hasta que vence.',
            ['snoozed' => true],
            'updated_at', 'asc', 30,
        );

        $views[] = $this->publicView(
            'Archivadas',
            'Las archivadas, que el inbox esconde por defecto. Esta vista las saca a propósito.',
            ['is_archived' => true],
            'updated_at', 'desc', 31,
        );

        $views[] = $this->publicView(
            'Cerradas y resueltas',
            'Todo lo que ya no está abierto, sin distinguir entre Resuelto y Cerrado.',
            ['is_open' => false],
            'updated_at', 'desc', 32,
        );

        // ─── Personales ──────────────────────────────────────────────────────
        // Sin dueño no tiene sentido sembrarlas: el listado solo muestra las
        // propias o las públicas (scopeForUser), así que una vista privada y
        // huérfana no la vería nadie.
        if ($ownerId = $this->ownerId()) {
            $views[] = [
                'name' => 'Mi cola urgente',
                'description' => 'Ejemplo de vista personal: solo la ve quien la tiene asignada. Sirve para probar el filtro Personales / Públicas del listado.',
                'filters' => ['is_open' => true, 'mine' => true, 'priority' => 'urgent'],
                'sort_by' => 'created_at',
                'sort_direction' => 'asc',
                'user_id' => $ownerId,
                'is_public' => false,
                'is_default' => false,
                'is_system' => false,
                'order' => 40,
            ];

            $views[] = [
                'name' => 'Mis devoluciones',
                'description' => 'Segunda vista personal, cruzando grupo y asignación, para ver cómo se combinan dos filtros.',
                'filters' => array_filter([
                    'is_open' => true,
                    'mine' => true,
                    'group' => $this->groupId('Devoluciones'),
                ]),
                'sort_by' => 'updated_at',
                'sort_direction' => 'desc',
                'user_id' => $ownerId,
                'is_public' => false,
                'is_default' => false,
                'is_system' => false,
                'order' => 41,
            ];
        }

        return $views;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function publicView(
        string $name,
        string $description,
        array $filters,
        string $sortBy,
        string $sortDirection,
        int $order,
        bool $isDefault = false,
    ): array {
        return [
            'name' => $name,
            'description' => $description,
            'filters' => $filters,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
            'user_id' => null,
            'is_public' => true,
            'is_default' => $isDefault,
            'is_system' => false,
            'order' => $order,
        ];
    }

    /**
     * Dueño de las vistas personales: el administrador con el que se entra al
     * panel, o el usuario más antiguo si no está.
     */
    private function ownerId(): ?int
    {
        return User::where('email', 'admin@alsernet.test')->value('id')
            ?? User::orderBy('id')->value('id');
    }

    /**
     * Id del estado por nombre, sin distinguir mayúsculas.
     */
    private function statusId(string $name): ?int
    {
        return ConversationStatus::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->value('id');
    }

    /**
     * Id del primer grupo cuyo nombre contenga alguno de los candidatos.
     */
    private function groupId(string ...$candidates): ?int
    {
        foreach ($candidates as $name) {
            $id = Group::whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($name).'%'])->value('id');

            if ($id) {
                return $id;
            }
        }

        return null;
    }
}
