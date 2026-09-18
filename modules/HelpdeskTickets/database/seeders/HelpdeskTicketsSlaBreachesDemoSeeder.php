<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\CatalogCacheService;

/**
 * Datos de ejemplo para el informe "Incumplimientos de SLA"
 * (/panel/helpdesk/reports/sla-breaches).
 *
 * El informe se apoya en SlaService::getBreachedTickets() y
 * getUpcomingBreaches(24), que solo miran tickets con sla_resolution_due_at,
 * sin closed_at y sin sla_paused_at. En la base no había ni uno con fecha de
 * SLA (0 de 12 tickets), así que la pantalla salía vacía.
 *
 * Se siembran tres cubos de incumplidos con recuentos distintos (4, 3 y 2) más
 * dos sin asignar, para que el agrupado por agente y su orden por volumen se
 * vean; y cinco por vencer repartidos dentro de las próximas 24 horas.
 *
 * Los tickets se crean con la fecha de SLA fijada A MANO después del insert:
 * TicketObserver::creating() calcula los vencimientos a partir de la política,
 * y aquí hace falta que unos estén vencidos y otros no.
 *
 * Idempotente: cada ticket lleva su clave en custom_fields.demo_key.
 *
 *   php artisan db:seed --class="Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsSlaBreachesDemoSeeder"
 */
class HelpdeskTicketsSlaBreachesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $agents = $this->agents();
        $customers = $this->customers();

        if ($customers === []) {
            $this->command?->warn('No hay clientes de helpdesk; no se siembra nada.');

            return;
        }

        $statusId = $this->openStatusId();
        $categoryIds = TicketCategory::orderBy('id')->pluck('id')->all();

        $created = 0;

        foreach ($this->definitions($agents) as $i => $definition) {
            $this->ticket(
                $definition,
                $customers[$i % count($customers)],
                $statusId,
                $categoryIds === [] ? null : $categoryIds[$i % count($categoryIds)],
            );
            $created++;
        }

        $this->command?->info("Sembrados {$created} tickets para el informe de SLA.");
    }

    /**
     * Once incumplidos repartidos en tres agentes y un cubo sin asignar, más
     * cinco por vencer dentro de las próximas 24 horas.
     *
     * horasVencido > 0  → ya incumplido (vencimiento en el pasado)
     * horasVencido < 0  → por vencer dentro de esas horas
     *
     * @param  array<string, int|null>  $agents
     * @return array<int, array<string, mixed>>
     */
    private function definitions(array $agents): array
    {
        $breached = [
            // Agente 1: el cubo más gordo, para que encabece el agrupado.
            ['key' => 'br-a1', 'agent' => $agents['first'], 'horasVencido' => 62, 'priority' => 'urgent', 'subject' => 'El pedido 88214 sigue sin salir de almacén'],
            ['key' => 'br-a2', 'agent' => $agents['first'], 'horasVencido' => 41, 'priority' => 'high', 'subject' => 'Cargo duplicado en la factura de agosto'],
            ['key' => 'br-a3', 'agent' => $agents['first'], 'horasVencido' => 27, 'priority' => 'high', 'subject' => 'La devolución lleva dos semanas sin abonarse'],
            ['key' => 'br-a4', 'agent' => $agents['first'], 'horasVencido' => 9, 'priority' => 'normal', 'subject' => 'Etiqueta de envío incorrecta en la recogida'],

            ['key' => 'br-b1', 'agent' => $agents['second'], 'horasVencido' => 48, 'priority' => 'urgent', 'subject' => 'Máquina parada, necesitan recambio urgente'],
            ['key' => 'br-b2', 'agent' => $agents['second'], 'horasVencido' => 22, 'priority' => 'high', 'subject' => 'Presupuesto de reparación sin confirmar'],
            ['key' => 'br-b3', 'agent' => $agents['second'], 'horasVencido' => 4, 'priority' => 'normal', 'subject' => 'Piden factura rectificativa del pedido 87990'],

            ['key' => 'br-c1', 'agent' => $agents['third'], 'horasVencido' => 31, 'priority' => 'high', 'subject' => 'Garantía rechazada sin explicación'],
            ['key' => 'br-c2', 'agent' => $agents['third'], 'horasVencido' => 6, 'priority' => 'normal', 'subject' => 'No consigue acceder al área de cliente'],

            // Sin asignar: el informe los agrupa aparte, bajo "Sin asignar".
            ['key' => 'br-x1', 'agent' => null, 'horasVencido' => 70, 'priority' => 'urgent', 'subject' => 'Reclamación de un pedido perdido en tránsito'],
            ['key' => 'br-x2', 'agent' => null, 'horasVencido' => 15, 'priority' => 'high', 'subject' => 'Solicita cambio de titular de la cuenta'],
        ];

        $upcoming = [
            ['key' => 'up-1', 'agent' => $agents['first'], 'horasVencido' => -2, 'priority' => 'urgent', 'subject' => 'Incidencia de facturación de un cliente grande'],
            ['key' => 'up-2', 'agent' => $agents['second'], 'horasVencido' => -5, 'priority' => 'high', 'subject' => 'Pendiente de confirmar fecha de instalación'],
            ['key' => 'up-3', 'agent' => $agents['third'], 'horasVencido' => -9, 'priority' => 'normal', 'subject' => 'Consulta sobre compatibilidad de accesorios'],
            ['key' => 'up-4', 'agent' => null, 'horasVencido' => -16, 'priority' => 'high', 'subject' => 'Pide presupuesto para un pedido grande'],
            ['key' => 'up-5', 'agent' => $agents['first'], 'horasVencido' => -22, 'priority' => 'normal', 'subject' => 'Duda sobre el plazo de entrega en Canarias'],
        ];

        return array_merge($breached, $upcoming);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function ticket(array $definition, Customer $customer, ?int $statusId, ?int $categoryId): void
    {
        $dueAt = now()->subHours($definition['horasVencido']);
        $isBreached = $definition['horasVencido'] > 0;

        // El ticket entra cuando entró: un rato antes de su propio vencimiento.
        $createdAt = $dueAt->copy()->subHours(8);

        $ticket = Ticket::where('custom_fields->demo_key', $definition['key'])->first()
            ?? new Ticket;

        $ticket->forceFill([
            'customer_id' => $customer->id,
            'category_id' => $categoryId,
            'status_id' => $statusId,
            'assignee_id' => $definition['agent'],
            'assigned_at' => $definition['agent'] ? $createdAt : null,
            'subject' => $definition['subject'],
            'description' => 'Ticket de ejemplo del informe de SLA.',
            'priority' => $definition['priority'],
            'source' => 'email',
            'custom_fields' => ['demo_key' => $definition['key']],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'last_activity_at' => $createdAt,
        ]);

        $ticket->save();

        // Después del insert: el observer recalcula los vencimientos a partir de
        // la política en creating(), y aquí interesa la fecha exacta.
        // saveQuietly para no llenar el historial de cambios de campo.
        $ticket->forceFill($this->slaAttributes($dueAt, $createdAt, $isBreached))->saveQuietly();
    }

    /**
     * @return array<string, mixed>
     */
    private function slaAttributes(Carbon $dueAt, Carbon $createdAt, bool $isBreached): array
    {
        return [
            'sla_first_response_due_at' => $createdAt->copy()->addHours(2),
            'sla_resolution_due_at' => $dueAt,
            'sla_resolution_breached' => $isBreached,
            'sla_first_response_breached' => $isBreached,
            'sla_paused_at' => null,
            'sla_paused_duration_minutes' => 0,
            'closed_at' => null,
            'resolved_at' => null,
        ];
    }

    /**
     * Tres agentes distintos para que el agrupado del informe tenga más de una
     * fila. Si el entorno tiene menos, se repiten: el informe sigue siendo
     * válido, solo con menos cubos.
     *
     * Vienen de CatalogCacheService::agents() (rol helpdesk-agent + available)
     * y no de los primeros usuarios por id: por ahí salen las cuentas de
     * sistema (super-admin, documentation, callcenter) y el informe quedaba
     * agrupado por gente que no atiende tickets.
     *
     * @return array<string, int|null>
     */
    private function agents(): array
    {
        $ids = CatalogCacheService::agents()->pluck('id')->take(3)->values()->all();

        if ($ids === []) {
            $ids = User::orderBy('id')->limit(3)->pluck('id')->all();
        }

        return [
            'first' => $ids[0] ?? null,
            'second' => $ids[1] ?? $ids[0] ?? null,
            'third' => $ids[2] ?? $ids[0] ?? null,
        ];
    }

    /**
     * Se reutilizan los clientes del informe de riesgo si están, para que las
     * dos pantallas hablen de la misma gente.
     *
     * @return array<int, Customer>
     */
    private function customers(): array
    {
        $demo = Customer::where('email', 'like', '%@ejemplo.test')->orderBy('id')->get();

        return ($demo->isNotEmpty() ? $demo : Customer::orderBy('id')->limit(5)->get())->all();
    }

    /**
     * Un estado abierto: el informe excluye lo cerrado por closed_at, pero un
     * ticket vivo tampoco debería figurar como "Resuelto".
     */
    private function openStatusId(): ?int
    {
        return TicketStatus::whereIn('slug', ['open', 'new', 'reopened'])->orderBy('id')->value('id')
            ?? TicketStatus::orderBy('id')->value('id');
    }
}
