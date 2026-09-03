<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Modules\Core\Models\Setting;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskTickets\Http\Controllers\FeedbackController;
use Modules\HelpdeskTickets\Mail\TicketSatisfactionSurveyMail;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\RecurringTicket;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketLink;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\AssignmentService;
use Modules\HelpdeskTickets\Services\MailReputationService;
use Modules\HelpdeskTickets\Services\OpsHealthService;
use Modules\HelpdeskTickets\Services\TicketEmailChannelsRepository;
use Modules\HelpdeskTickets\Services\TicketMailAiSummaryService;
use Modules\HelpdeskTickets\Support\TicketMailRenderer;

/**
 * Extraído de TicketsCrudController (30-ago-2026, controller de 1017 líneas
 * mezclaba CRUD + estos 5 endpoints de salud/carga/CSAT sin relación directa
 * con el CRUD de tickets). Sin cambios de comportamiento, solo movimiento.
 */
class TicketOpsController extends Controller
{
    /**
     * Buscador de tickets para los modales que piden un ticket destino.
     *
     * "Fusionar" y "Vincular ticket" exigían teclear el ID numérico a mano,
     * con la ayuda "visible en la URL al abrirlo": había que ir a buscarlo a
     * otra pantalla, copiarlo y volver. Devuelve lo justo para reconocer el
     * ticket en una lista.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $q = trim((string) $request->input('q'));

        // Menos de 2 caracteres devolvería medio listado y no ayuda a elegir.
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $tickets = Ticket::query()
            ->with(['status:id,name,slug', 'customer:id,name'])
            ->where(function ($query) use ($q) {
                $query->where('ticket_number', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%");

                // Un número suelto casi siempre es el id que el agente tenía
                // a mano; se acepta además del número de ticket.
                if (ctype_digit($q)) {
                    $query->orWhere('id', (int) $q);
                }
            })
            ->when($request->filled('exclude_id'), fn ($query) => $query->where('id', '!=', $request->integer('exclude_id')))
            ->latest('updated_at')
            ->limit(15)
            ->get();

        return response()->json([
            'data' => $tickets->map(fn (Ticket $t) => [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'subject' => $t->subject,
                'status_name' => $t->status?->name,
                'customer_name' => $t->customer?->name,
                'updated_at_human' => $t->updated_at?->diffForHumans(),
            ])->all(),
        ]);
    }

    /**
     * Resumen IA del ticket (mismo servicio y mismo criterio de "sin API
     * key configurada → summary null, nunca inventado" que ya usa
     * TicketMailsController::summary() por correo individual — aquí es a
     * nivel de ticket completo, para el banner del detalle nuevo).
     */
    public function summary(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        if (! class_exists(AgentLlmService::class)) {
            return response()->json(['success' => true, 'summary' => null]);
        }

        return response()->json([
            'success' => true,
            'summary' => app(TicketMailAiSummaryService::class)->summarize($ticket),
        ]);
    }

    /**
     * Snapshot de salud operativa (pill "Cola") — reusa OpsHealthService tal
     * cual (ya alimenta el dashboard de reports y el comando programado
     * helpdesk:ops-metrics); aquí solo se expone de forma ligera para el
     * modal del listado, sin duplicar ninguna de las sondas.
     */
    public function ops(): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        return response()->json(['success' => true, 'snapshot' => app(OpsHealthService::class)->cached()]);
    }

    /**
     * Reintentar jobs en dead-letter desde el modal "Cola y reintentos".
     *
     * Sin `uuid` reintenta todos: es lo que hace `queue:retry all`, y en un
     * incidente (el SMTP caído una hora) es justo lo que hace falta. Exige
     * permiso de ajustes, no solo de ver el listado — reencolar jobs vuelve
     * a mandar correos reales a clientes.
     */
    public function retryFailedJobs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);
        abort_unless($request->user()?->can('helpdesk.tickets.settings'), 403);

        $uuid = $request->string('uuid')->toString();

        try {
            Artisan::call('queue:retry', [
                'id' => $uuid !== '' ? [$uuid] : ['all'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'No se pudieron reencolar los jobs.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $uuid !== '' ? 'Job reencolado.' : 'Todos los jobs fallidos se han reencolado.',
        ]);
    }

    /**
     * Purgar la dead-letter. Es irreversible —los jobs se pierden— así que
     * exige el mismo permiso que reintentar y el modal pide confirmación.
     */
    public function flushFailedJobs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);
        abort_unless($request->user()?->can('helpdesk.tickets.settings'), 403);

        try {
            Artisan::call('queue:flush');
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'No se pudo purgar la cola de fallidos.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Dead-letter purgada.']);
    }

    /**
     * Carga real por agente (pill "Carga") — AssignmentService::
     * getAvailableAgents()/getAgentWorkload() ya existían pero sin ningún
     * punto de entrada HTTP. Sin "capacidad" ni "% ocupación" por agente:
     * no hay ninguna columna de capacidad máxima configurada, así que
     * mostrarla sería inventar un dato.
     */
    /**
     * Modal 22 "Reputación y autenticación": SPF/DKIM/DMARC reales del
     * dominio de envío más las tasas de rebote y supresión.
     */
    public function reputation(MailReputationService $service): JsonResponse
    {
        return response()->json($service->report());
    }

    /**
     * Modal 22: guarda los dos interruptores del footer ("avisar a
     * managers" / "suprimir automáticamente", ambos OFF por defecto). La
     * evaluación real de la tasa de rebote corre en ticket:check-reputation
     * (programado cada hora) — aquí solo se persiste la preferencia.
     */
    public function updateReputation(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('helpdesk.tickets.update'), 403);

        $notifyManagers = $request->boolean('notify_managers');
        $autoSuppress = $request->boolean('auto_suppress');

        Setting::set('tickets.reputation_notify_managers', $notifyManagers);
        Setting::set('tickets.reputation_auto_suppress', $autoSuppress);

        // Apagar la auto-supresión libera el envío de inmediato: no tiene
        // sentido dejarlo pausado hasta la próxima pasada del comando solo
        // porque el agente acaba de desactivar el interruptor.
        if (! $autoSuppress) {
            Setting::set('tickets.reputation_suppressed', false);
        }

        return response()->json([
            'success' => true,
            'message' => 'Preferencias de reputación guardadas.',
            'data' => [
                'notify_managers' => $notifyManagers,
                'auto_suppress' => $autoSuppress,
            ],
        ]);
    }

    public function workload(): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $service = app(AssignmentService::class);
        $agents = $service->getAvailableAgents();

        // "N en riesgo" del mockup: los tickets del agente con el SLA a
        // punto de vencer o ya vencido. Se cuentan de una vez para todos los
        // agentes en vez de una consulta por fila.
        $atRisk = Ticket::query()
            ->whereNotNull('assignee_id')
            ->where(fn ($q) => $q->slaWarning()->orWhere(fn ($q2) => $q2->slaBreach()))
            ->selectRaw('assignee_id, COUNT(*) as total')
            ->groupBy('assignee_id')
            ->pluck('total', 'assignee_id');

        return response()->json([
            'success' => true,
            'agents' => $agents->map(fn ($agent) => [
                'id' => $agent->id,
                'name' => trim($agent->firstname.' '.$agent->lastname),
                'open_tickets' => $service->getAgentWorkload($agent->id),
                'at_risk' => (int) ($atRisk[$agent->id] ?? 0),
            ])->sortByDesc('open_tickets')->values()->all(),
            'unassigned_count' => Ticket::query()->whereNull('assignee_id')->notSnoozed()->count(),
        ]);
    }

    /**
     * "Repartir sin asignar" — aplica AssignmentService::autoAssignByWorkload()
     * (ya usado por la asignación automática existente) a cada ticket sin
     * asignar visible en cola; un fallo puntual en un ticket no aborta el
     * resto. Requiere permiso de gestión (no solo lectura, como ops()/workload()).
     */
    public function distributeUnassigned(): JsonResponse
    {
        abort_unless(auth()->user()?->can('helpdesk.tickets.update'), 403);

        $service = app(AssignmentService::class);
        $assigned = 0;

        Ticket::query()->whereNull('assignee_id')->notSnoozed()->limit(50)->get()->each(function (Ticket $ticket) use ($service, &$assigned) {
            if ($service->autoAssignByWorkload($ticket)) {
                $assigned++;
            }
        });

        return response()->json(['success' => true, 'message' => "{$assigned} ticket(s) repartido(s).", 'assigned' => $assigned]);
    }

    /**
     * Reenvía la encuesta CSAT — misma plantilla/Mailable/enlace firmado que
     * UpdateTicketOnClose ya usa automáticamente al cerrar (helpdesk_tickets.
     * satisfaction_survey), extraído aquí como acción manual del agente para
     * el caso "el cliente no la vio" o "se cerró sin cliente con email en
     * ese momento". Mismas condiciones: ticket cerrado, cliente con email,
     * sin valorar todavía.
     */
    /**
     * Datos de configuración que consultan los modales 16 (buzones), 28
     * (horario y SLA), 29 (escalado) y 31 (notificaciones).
     *
     * Son cuatro lecturas pequeñas de sitios distintos que la pantalla pide
     * a la vez al abrir cualquiera de esos modales; agruparlas en un solo
     * endpoint evita cuatro round-trips para pintar cuatro cajas.
     */
    public function settingsSnapshot(TicketEmailChannelsRepository $channels): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $policy = TicketSlaPolicy::query()->where('active', true)->orderByDesc('is_default')->first();

        return response()->json([
            // Modal 16: buzones IMAP configurados. Sin credenciales: solo lo
            // que hace falta para saber si el canal está vivo y qué hace.
            'mailboxes' => collect($channels->all())->map(fn (array $c) => [
                'id' => $c['id'] ?? null,
                'name' => $c['name'] ?? null,
                'username' => $c['username'] ?? null,
                'host' => $c['host'] ?? null,
                'port' => $c['port'] ?? null,
                'encryption' => $c['encryption'] ?? null,
                'create_tickets' => (bool) ($c['create_tickets'] ?? false),
                'create_replies' => (bool) ($c['create_replies'] ?? false),
                'last_checked_at' => $c['last_checked_at'] ?? null,
                'last_error' => $c['last_error'] ?? null,
            ])->values()->all(),
            // Modal 28: horario laboral y objetivos de la política vigente.
            'sla_policy' => $policy ? [
                'name' => $policy->name,
                'timezone' => $policy->timezone,
                'business_hours_only' => (bool) $policy->business_hours_only,
                'business_hours' => $policy->business_hours,
                'first_response_time' => $policy->first_response_time,
                'next_response_time' => $policy->next_response_time,
                'resolution_time' => $policy->resolution_time,
                'enable_escalation' => (bool) $policy->enable_escalation,
                'escalation_threshold_percent' => $policy->escalation_threshold_percent,
            ] : null,
            // Modal 29: reglas de escalado ya definidas.
            'automations' => Automation::query()
                ->orderBy('order')
                ->limit(20)
                ->get(['id', 'name', 'trigger_event', 'is_active', 'run_count', 'last_run_at'])
                ->map(fn (Automation $a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'trigger_event' => $a->trigger_event,
                    'is_active' => (bool) $a->is_active,
                    'run_count' => $a->run_count,
                    'last_run_at_human' => $a->last_run_at?->diffForHumans(),
                ])->all(),
            // Modal 30: recurrencias activas.
            'recurring' => RecurringTicket::query()
                ->where('is_active', true)
                ->limit(20)
                ->get(['id', 'name', 'subject', 'frequency', 'next_run_at', 'tickets_created'])
                ->map(fn (RecurringTicket $r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'subject' => $r->subject,
                    'frequency' => $r->frequency,
                    'next_run_at_human' => $r->next_run_at?->diffForHumans(),
                    'tickets_created' => $r->tickets_created,
                ])->all(),
        ]);
    }

    /**
     * Modal 39 "Dividir ticket".
     *
     * Cuando el cliente mezcla dos asuntos en la misma conversación, mueve los
     * mensajes elegidos a un ticket nuevo. Era la única acción de la pantalla
     * que estaba deshabilitada con "Sin funcionalidad de backend equivalente".
     *
     * Se mueven los TicketItem seleccionados (no se copian: duplicarlos
     * dejaría el hilo original con mensajes que ya no le corresponden y el
     * agente vería la misma pregunta dos veces). El ticket original conserva
     * cliente, canal y SLA; el nuevo arranca limpio con su propia numeración.
     */
    public function split(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer'],
            'category_id' => ['nullable', 'integer', 'exists:helpdesk.helpdesk_ticket_categories,id'],
            'assignee_id' => ['nullable', 'integer'],
            'link_tickets' => ['nullable', 'boolean'],
        ]);

        // Los mensajes tienen que ser de ESTE ticket: si no, cualquiera con
        // permiso sobre un ticket podría arrastrar mensajes de otro.
        $items = $ticket->items()->whereIn('id', $validated['item_ids'])->get();
        abort_if($items->isEmpty(), 422, 'Ninguno de los mensajes seleccionados pertenece a este ticket.');
        abort_if(
            $items->count() >= $ticket->items()->count(),
            422,
            'No se pueden mover todos los mensajes: el ticket original se quedaría vacío.',
        );

        $created = DB::connection('helpdesk')->transaction(function () use ($ticket, $items, $validated, $request) {
            $new = Ticket::create([
                'ticket_number' => Ticket::generateTicketNumber(),
                'customer_id' => $ticket->customer_id,
                'category_id' => $validated['category_id'] ?? $ticket->category_id,
                'status_id' => $ticket->status_id,
                'group_id' => $ticket->group_id,
                'assignee_id' => $validated['assignee_id'] ?? null,
                'subject' => $validated['subject'],
                'priority' => $ticket->priority,
                'source' => $ticket->source,
                'assigned_at' => ($validated['assignee_id'] ?? null) ? now() : null,
            ]);

            $ticket->items()->whereIn('id', $items->pluck('id'))->update(['ticket_id' => $new->id]);

            TicketNote::create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'body' => sprintf(
                    'Se separaron %d mensaje(s) en el ticket %s ("%s").',
                    $items->count(),
                    $new->ticket_number,
                    $new->subject,
                ),
            ]);

            if ($request->boolean('link_tickets')) {
                TicketLink::create([
                    'ticket_id' => $ticket->id,
                    'linked_ticket_id' => $new->id,
                    'link_type' => 'related',
                    'created_by' => auth()->id(),
                ]);
            }

            return $new;
        });

        return response()->json([
            'success' => true,
            'message' => "Ticket dividido: se creó {$created->ticket_number}.",
            'ticket_id' => $created->id,
            'ticket_number' => $created->ticket_number,
        ]);
    }

    /**
     * Modal 40 "Encuesta CSAT": la valoración del cliente y el contexto con el
     * que se lee (cuánto tardó en resolverse, cuántas veces se reabrió, cómo
     * puntúa normalmente ese agente).
     *
     * Lo que el mockup muestra y aquí NO se devuelve: "compartir el
     * comentario con el agente" y "marcar como caso destacado". No hay
     * ninguna columna que los respalde y un interruptor que no guarda nada
     * es peor que no tenerlo.
     */
    public function csat(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $agentId = $ticket->assignee_id;

        // Reaperturas: transiciones registradas hacia un estado abierto
        // viniendo de uno resuelto o cerrado. Es el historial real
        // (helpdesk_ticket_history), no un contador que nadie mantiene.
        $reopenedStates = TicketStatus::query()->where('is_open', true)->pluck('name')->all();
        $finishedStates = TicketStatus::query()->where('is_open', false)->pluck('name')->all();

        $reopenings = DB::connection('helpdesk')->table('helpdesk_ticket_history')
            ->where('ticket_id', $ticket->id)
            ->where('action', 'status_changed')
            ->whereIn('old_value', $finishedStates)
            ->whereIn('new_value', $reopenedStates)
            ->count();

        // Media del agente sobre sus tickets valorados. Sin ninguno, null:
        // "0,0 / 5" leería como "puntúa fatal" en vez de "no hay datos".
        $agentAverage = $agentId
            ? Ticket::query()->where('assignee_id', $agentId)->whereNotNull('rating')->avg('rating')
            : null;

        $resolutionMinutes = $ticket->resolved_at
            ? (int) $ticket->created_at->diffInMinutes($ticket->resolved_at)
            : null;

        return response()->json([
            'success' => true,
            'csat' => [
                'rating' => $ticket->rating,
                'comment' => $ticket->rating_comment,
                'reason' => $ticket->rating_reason,
                'rated_at_human' => $ticket->rated_at?->translatedFormat('d M Y · H:i'),
                'agent' => $ticket->assignee ? trim($ticket->assignee->firstname.' '.$ticket->assignee->lastname) : null,
                'resolution_human' => $resolutionMinutes !== null ? self::humanizeMinutes($resolutionMinutes) : null,
                'reopenings' => $reopenings,
                'agent_average' => $agentAverage !== null ? round((float) $agentAverage, 1) : null,
                'can_resend' => (bool) ($ticket->closed_at && ! $ticket->rated_at && $ticket->customer?->email),
            ],
        ]);
    }

    /**
     * Minutos → "45 min" / "5 h 12 min" / "3 d 6 h", como el mockup.
     */
    private static function humanizeMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' min';
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return $hours.' h '.($minutes % 60).' min';
        }

        return intdiv($hours, 24).' d '.($hours % 24).' h';
    }

    public function sendCsatSurvey(Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        abort_unless($ticket->closed_at, 422, 'El ticket debe estar cerrado para enviar la encuesta.');
        abort_if($ticket->rated_at, 422, 'Este ticket ya tiene una valoración.');

        $customer = $ticket->customer;
        abort_unless($customer?->email, 422, 'El cliente no tiene email registrado.');

        $ratingButtons = '';
        for ($i = 1; $i <= 5; $i++) {
            $rateUrl = URL::signedRoute('portal.tickets.rate.email', [
                'ticketNumber' => $ticket->ticket_number,
                'rating' => $i,
            ]);
            $ratingButtons .= '<a href="'.e($rateUrl).'" style="display: inline-block; margin: 0 6px; padding: 12px 20px; background: #f9f9f9; border: 2px solid #ddd; border-radius: 50%; font-size: 22px; text-decoration: none; color: #333; font-weight: bold;">'.$i.'</a>';
        }

        [$subject, $content] = TicketMailRenderer::render(
            'helpdesk_tickets.satisfaction_survey',
            [
                'TICKET_NUMBER' => $ticket->ticket_number,
                'TICKET_SUBJECT' => e($ticket->subject),
                'CLOSED_AT' => $ticket->closed_at?->format('d/m/Y H:i') ?? '',
                'RATING_BUTTONS' => $ratingButtons,
                'FEEDBACK_URL' => FeedbackController::signedShowUrl($ticket),
            ],
            'Cuéntanos tu experiencia — Ticket #'.$ticket->ticket_number,
        );

        Mail::to($customer->email)->queue(new TicketSatisfactionSurveyMail($ticket, $subject, $content));

        return response()->json(['success' => true, 'message' => 'Encuesta de satisfacción enviada.']);
    }
}
