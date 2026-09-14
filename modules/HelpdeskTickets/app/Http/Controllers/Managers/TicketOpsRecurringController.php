<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Http\Requests\Managers\RecurringTicketOpsRequest;
use Modules\HelpdeskTickets\Models\Priority;
use Modules\HelpdeskTickets\Models\RecurringTicket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Services\CatalogCacheService;

/**
 * Modal 30 "Tickets recurrentes" del riel de operación de /tickets.
 *
 * El modal solo sabía listar las recurrencias activas (desde
 * TicketOpsController::settingsSnapshot) y rematar con un enlace que sacaba
 * al agente a la pantalla de Ajustes. Aquí viven los endpoints JSON que le
 * faltaban para crear, editar y pausar sin salir del listado.
 *
 * Controlador aparte a propósito: RecurringTicketsController (Managers)
 * devuelve vistas Blade y redirects — mezclar ahí respuestas JSON obligaría
 * a bifurcar cada método por Accept, y sus rutas ya están en uso.
 */
class TicketOpsRecurringController extends Controller
{
    /**
     * Etiquetas en castellano de la enum `frequency` de la tabla.
     *
     * 'custom' no se ofrece al crear (ver RecurringTicketOpsRequest), pero sí
     * hay que saber nombrarla: las recurrencias creadas desde la pantalla
     * completa de Ajustes sí pueden usarla y salen igualmente en esta lista.
     */
    private const FREQUENCY_LABELS = [
        'daily' => 'Diaria',
        'weekly' => 'Semanal',
        'monthly' => 'Mensual',
        'custom' => 'Personalizada (cron)',
    ];

    /**
     * Lista para el modal: TODAS las recurrencias, activas y pausadas.
     *
     * settingsSnapshot solo devolvía las activas, así que una recurrencia
     * pausada desaparecía del modal y no había forma de reanudarla desde
     * aquí. Se devuelven también los catálogos que rellenan los desplegables
     * del formulario, para que el modal se pinte con una única petición.
     */
    public function index(): JsonResponse
    {
        $this->authorize('helpdesk.tickets.view');

        $items = RecurringTicket::query()
            ->with('category:id,name')
            ->orderByDesc('is_active')
            ->orderByRaw('next_run_at IS NULL')
            ->orderBy('next_run_at')
            ->limit(100)
            ->get();

        $assignees = $this->assigneeNames($items->pluck('assignee_id')->filter()->unique()->all());

        return response()->json([
            'data' => $items->map(fn (RecurringTicket $r) => $this->present($r, $assignees))->all(),
            'catalogs' => [
                'categories' => TicketCategory::active()->ordered()->get(['id', 'name'])
                    ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()->all(),
                'priorities' => Priority::query()->where('is_active', true)->orderBy('level')->get(['id', 'name'])
                    ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()->all(),
                // Misma fuente que el formulario completo de Ajustes: los
                // agentes reales del helpdesk, no todos los usuarios (la
                // columna users.verified no sirve para esto, es verificación
                // de la cuenta).
                'agents' => CatalogCacheService::agents()
                    ->map(fn (User $u) => ['id' => $u->id, 'name' => trim($u->firstname.' '.$u->lastname)])
                    ->values()->all(),
                'frequencies' => collect(self::FREQUENCY_LABELS)
                    ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                    ->values()->all(),
            ],
        ]);
    }

    public function store(RecurringTicketOpsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $recurring = new RecurringTicket($data);
        $recurring->is_active = true;

        // Sin next_run_at la recurrencia no se ejecuta NUNCA: scopeDueToRun()
        // filtra por whereNotNull('next_run_at'), y el formulario de Ajustes
        // deja ese campo opcional. Si no se indica, se calcula desde la
        // frecuencia elegida para que nazca ya programada.
        if (empty($data['next_run_at'])) {
            $recurring->next_run_at = $this->nextRunFromNow($recurring->frequency, $recurring->cron_expression);
        }

        $recurring->save();

        return response()->json([
            'success' => true,
            'message' => 'Recurrencia programada.',
            'item' => $this->present($recurring->fresh(['category']), $this->assigneeNames([$recurring->assignee_id])),
        ], 201);
    }

    public function update(RecurringTicketOpsRequest $request, RecurringTicket $recurringTicket): JsonResponse
    {
        $data = $request->validated();
        $frecuenciaAnterior = $recurringTicket->frequency;

        $recurringTicket->fill($data);

        // is_active no viaja en el formulario: pausar y reanudar es el botón
        // aparte (toggle), no un efecto colateral de guardar.
        if (empty($data['next_run_at']) && $frecuenciaAnterior !== $recurringTicket->frequency) {
            $recurringTicket->next_run_at = $this->nextRunFromNow($recurringTicket->frequency, $recurringTicket->cron_expression);
        }

        $recurringTicket->save();

        return response()->json([
            'success' => true,
            'message' => 'Recurrencia guardada.',
            'item' => $this->present($recurringTicket->fresh(['category']), $this->assigneeNames([$recurringTicket->assignee_id])),
        ]);
    }

    /**
     * Pausar / reanudar (botón "Pausar" del mockup).
     */
    public function toggle(Request $request, RecurringTicket $recurringTicket): JsonResponse
    {
        $this->authorize('helpdesk.tickets.update');

        $activar = ! $recurringTicket->is_active;
        $recurringTicket->is_active = $activar;

        // Al reanudar, una próxima ejecución que se quedó en el pasado
        // durante la pausa haría que el job creara un ticket en cuanto
        // arrancase (y, con una pausa larga, con la fecha desfasada). Se
        // reprograma desde ahora.
        if ($activar && (! $recurringTicket->next_run_at || $recurringTicket->next_run_at->isPast())) {
            $recurringTicket->next_run_at = $this->nextRunFromNow($recurringTicket->frequency, $recurringTicket->cron_expression);
        }

        $recurringTicket->save();

        return response()->json([
            'success' => true,
            'message' => $activar ? 'Recurrencia reanudada.' : 'Recurrencia pausada.',
            'item' => $this->present($recurringTicket->fresh(['category']), $this->assigneeNames([$recurringTicket->assignee_id])),
        ]);
    }

    /**
     * Próxima ejecución contada desde AHORA.
     *
     * RecurringTicket::calculateNextRun() parte de last_run_at, que en una
     * recurrencia pausada hace meses deja la fecha en el pasado. Se calcula
     * sobre una instancia limpia con solo frecuencia y cron, que es lo que
     * ese método necesita.
     */
    private function nextRunFromNow(string $frequency, ?string $cron): Carbon
    {
        return (new RecurringTicket([
            'frequency' => $frequency,
            'cron_expression' => $cron,
        ]))->calculateNextRun();
    }

    /**
     * Nombres de los agentes asignados, en una sola consulta.
     *
     * Los usuarios viven en la conexión por defecto y las recurrencias en
     * 'helpdesk': la relación assignee() es cross-connection y cargarla por
     * fila dispararía una consulta por recurrencia.
     *
     * @param  array<int, int|null>  $ids
     * @return array<int, string>
     */
    private function assigneeNames(array $ids): array
    {
        $ids = array_values(array_filter($ids));

        if ($ids === []) {
            return [];
        }

        // $user->name no existe en esta app: el User guarda firstname/lastname.
        return User::query()->whereIn('id', $ids)->get(['id', 'firstname', 'lastname'])
            ->mapWithKeys(fn (User $u) => [$u->id => trim($u->firstname.' '.$u->lastname)])
            ->all();
    }

    /**
     * @param  array<int, string>  $assignees
     * @return array<string, mixed>
     */
    private function present(RecurringTicket $r, array $assignees): array
    {
        return [
            'id' => $r->id,
            'name' => $r->name,
            'subject' => $r->subject,
            'description' => $r->description,
            'category_id' => $r->category_id,
            'category_name' => $r->category?->name,
            'priority_id' => $r->priority_id,
            'assignee_id' => $r->assignee_id,
            'assignee_name' => $r->assignee_id ? ($assignees[$r->assignee_id] ?? null) : null,
            'frequency' => $r->frequency,
            'frequency_label' => self::FREQUENCY_LABELS[$r->frequency] ?? $r->frequency,
            'cron_expression' => $r->cron_expression,
            'is_active' => (bool) $r->is_active,
            'tickets_created' => (int) $r->tickets_created,
            // Formato del <input type="datetime-local"> del modal.
            'next_run_at' => $r->next_run_at?->format('Y-m-d\TH:i'),
            'next_run_at_label' => $r->next_run_at?->translatedFormat('d M Y H:i'),
            'next_run_at_human' => $r->next_run_at?->diffForHumans(),
            'last_run_at_human' => $r->last_run_at?->diffForHumans(),
        ];
    }
}
