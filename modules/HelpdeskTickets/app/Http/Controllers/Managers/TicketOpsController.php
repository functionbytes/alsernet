<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskTickets\Http\Controllers\FeedbackController;
use Modules\HelpdeskTickets\Mail\TicketSatisfactionSurveyMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\AssignmentService;
use Modules\HelpdeskTickets\Services\OpsHealthService;
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
     * Carga real por agente (pill "Carga") — AssignmentService::
     * getAvailableAgents()/getAgentWorkload() ya existían pero sin ningún
     * punto de entrada HTTP. Sin "capacidad" ni "% ocupación" por agente:
     * no hay ninguna columna de capacidad máxima configurada, así que
     * mostrarla sería inventar un dato.
     */
    public function workload(): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $service = app(AssignmentService::class);
        $agents = $service->getAvailableAgents();

        return response()->json([
            'success' => true,
            'agents' => $agents->map(fn ($agent) => [
                'id' => $agent->id,
                'name' => trim($agent->firstname.' '.$agent->lastname),
                'open_tickets' => $service->getAgentWorkload($agent->id),
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
