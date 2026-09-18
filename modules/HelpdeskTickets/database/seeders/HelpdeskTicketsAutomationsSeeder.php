<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\TicketGroup;

/**
 * Juego de automatismos de ejemplo, listos para probar la pantalla
 * Ajustes → Automatizaciones sin tener que escribir el JSON a mano.
 *
 * Son reglas de helpdesk corrientes —enrutar por palabra clave, marcar
 * urgencias, dejar rastro al cerrar—, no casos de laboratorio: la idea es que
 * al crear un ticket real se vea el efecto.
 *
 * Los grupos se buscan POR NOMBRE y no por id: los ids cambian entre entornos y
 * una regla con un id que no existe muere con violación de clave foránea. Si un
 * grupo no está, la regla que lo usa simplemente no se siembra.
 *
 *   php artisan db:seed --class="Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsAutomationsSeeder"
 */
class HelpdeskTicketsAutomationsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $definition) {
            // updateOrCreate por nombre: re-sembrar no duplica ni pisa el
            // run_count de lo que ya se haya ejecutado.
            Automation::updateOrCreate(
                ['name' => $definition['name']],
                $definition,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function definitions(): array
    {
        $facturacion = $this->groupId('Soporte Facturación', 'Facturación');
        $devoluciones = $this->groupId('Devoluciones y Logística', 'Devoluciones');
        $tecnico = $this->groupId('Soporte Técnico', 'Técnico');

        $definitions = [];

        // ─── 1. Urgencias por palabra clave ──────────────────────────────────
        $definitions[] = [
            'name' => 'Marcar urgente por palabra clave',
            'description' => 'Sube a prioridad urgente los tickets cuyo asunto menciona una reclamación o pide algo urgente, y los etiqueta para poder filtrarlos.',
            'trigger_event' => 'ticket.created',
            'conditions' => [
                ['field' => 'subject', 'op' => 'contains', 'value' => 'urgente'],
            ],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
                ['type' => 'add_tag', 'value' => 'urgente'],
            ],
            'order' => 1,
            'is_active' => true,
        ];

        $definitions[] = [
            'name' => 'Marcar urgente las reclamaciones',
            'description' => 'Una reclamación entra como urgente y con etiqueta propia: son los tickets que más caro salen si se quedan en la cola.',
            'trigger_event' => 'ticket.created',
            'conditions' => [
                ['field' => 'subject', 'op' => 'contains', 'value' => 'reclamacion'],
            ],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
                ['type' => 'add_tag', 'value' => 'reclamacion'],
            ],
            'order' => 2,
            'is_active' => true,
        ];

        // ─── 2. Enrutado por tema ────────────────────────────────────────────
        if ($facturacion) {
            $definitions[] = [
                'name' => 'Enrutar facturación',
                'description' => 'Manda al grupo de Facturación los tickets que hablan de facturas, cobros o reembolsos.',
                'trigger_event' => 'ticket.created',
                'conditions' => [
                    ['field' => 'subject', 'op' => 'contains', 'value' => 'factura'],
                ],
                'actions' => [
                    ['type' => 'assign_group', 'value' => $facturacion],
                    ['type' => 'add_tag', 'value' => 'facturacion'],
                ],
                'order' => 10,
                'is_active' => true,
            ];
        }

        if ($devoluciones) {
            $definitions[] = [
                'name' => 'Enrutar devoluciones',
                'description' => 'Manda al grupo de Devoluciones los tickets sobre devoluciones de pedido.',
                'trigger_event' => 'ticket.created',
                'conditions' => [
                    ['field' => 'subject', 'op' => 'contains', 'value' => 'devolucion'],
                ],
                'actions' => [
                    ['type' => 'assign_group', 'value' => $devoluciones],
                    ['type' => 'add_tag', 'value' => 'devolucion'],
                ],
                'order' => 11,
                'is_active' => true,
            ];
        }

        if ($tecnico) {
            $definitions[] = [
                'name' => 'Enrutar incidencias técnicas de la web',
                'description' => 'Los tickets nacidos del widget que mencionan un error van al grupo Técnico.',
                'trigger_event' => 'ticket.created',
                'conditions' => [
                    ['field' => 'source', 'op' => 'equals', 'value' => 'widget'],
                    ['field' => 'subject', 'op' => 'contains', 'value' => 'error'],
                ],
                'actions' => [
                    ['type' => 'assign_group', 'value' => $tecnico],
                    ['type' => 'add_tag', 'value' => 'incidencia-web'],
                ],
                'order' => 12,
                'is_active' => true,
            ];
        }

        // ─── 3. Higiene de la cola ───────────────────────────────────────────
        $definitions[] = [
            'name' => 'Etiquetar los que entran sin asignar',
            'description' => 'Deja marcados los tickets que nacen sin agente, para poder sacarlos en un filtro y repartirlos.',
            'trigger_event' => 'ticket.created',
            'conditions' => [
                ['field' => 'assignee_id', 'op' => 'is_null'],
            ],
            'actions' => [
                ['type' => 'add_tag', 'value' => 'sin-asignar'],
            ],
            'order' => 20,
            'is_active' => true,
        ];

        // ─── 4. Avisos y rastro ──────────────────────────────────────────────
        $definitions[] = [
            'name' => 'Avisar al agente cuando se le asigna un ticket',
            'description' => 'Notifica al agente en cuanto un ticket queda a su nombre, venga de donde venga la asignación.',
            'trigger_event' => 'ticket.assigned',
            'conditions' => [],
            'actions' => [
                ['type' => 'notify_agent'],
            ],
            'order' => 30,
            'is_active' => true,
        ];

        $definitions[] = [
            'name' => 'Dejar nota interna al cerrar',
            'description' => 'Añade una nota interna al cerrar el ticket, para que en el histórico quede claro que el cierre fue automático.',
            'trigger_event' => 'ticket.closed',
            'conditions' => [],
            'actions' => [
                ['type' => 'add_internal_note', 'value' => 'Ticket cerrado. Nota añadida automáticamente por una regla de Ajustes → Automatizaciones.'],
            ],
            'order' => 31,
            'is_active' => true,
        ];

        // ─── 5. Desactivada, para ver el interruptor ─────────────────────────
        $definitions[] = [
            'name' => '[Desactivada] Bajar prioridad a las consultas de horario',
            'description' => 'Ejemplo de regla apagada: sirve para comprobar que una automatización inactiva no se ejecuta. Actívala para verla funcionar.',
            'trigger_event' => 'ticket.created',
            'conditions' => [
                ['field' => 'subject', 'op' => 'contains', 'value' => 'horario'],
            ],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'low'],
                ['type' => 'add_tag', 'value' => 'consulta-simple'],
            ],
            'order' => 40,
            'is_active' => false,
        ];

        return $definitions;
    }

    /**
     * Id del primer grupo cuyo nombre coincida con alguno de los candidatos.
     * Se compara sin distinguir mayúsculas para no depender de cómo esté
     * escrito el nombre en cada entorno.
     */
    private function groupId(string ...$candidates): ?int
    {
        foreach ($candidates as $name) {
            $group = TicketGroup::whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($name).'%'])->first();

            if ($group) {
                return $group->id;
            }
        }

        return null;
    }
}
