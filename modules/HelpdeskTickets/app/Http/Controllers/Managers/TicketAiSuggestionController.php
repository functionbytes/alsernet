<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketReview;
use Modules\HelpdeskTickets\Services\ReplyGuardService;
use Modules\HelpdeskTickets\Services\TicketDuplicateService;
use Modules\HelpdeskTickets\Services\TicketReplySuggestionService;

/**
 * Borrador de respuesta generado por IA para un ticket.
 *
 * Devuelve texto para que el agente lo revise en el composer: este endpoint no
 * envia nada al cliente ni modifica el ticket. Ver TicketReplySuggestionService.
 *
 * El servicio es fail-silent, asi que "no hay sugerencia" (sin agente IA
 * configurado, sin API key, proveedor caido) es una respuesta 200 con
 * suggestion=null, no un error: la UI simplemente avisa y el agente sigue
 * escribiendo a mano.
 */
class TicketAiSuggestionController extends Controller
{
    public function __construct()
    {
        // duplicates() solo lee, así que le basta con `view`; el resto
        // escribe en el composer y exige `update`.
        $this->middleware('can:helpdesk.tickets.update')->except(['duplicates', 'disputeReview']);
        // Discutir la propia revisión no debería exigir permiso de edición: el
        // agente evaluado tiene que poder replicar aunque el ticket ya esté
        // cerrado para él.
        $this->middleware('can:helpdesk.tickets.view')->only(['duplicates', 'disputeReview']);
    }

    public function suggestReply(Request $request, Ticket $ticket, TicketReplySuggestionService $service): JsonResponse
    {
        $this->authorize('update', $ticket);

        // El tono llega del modal "Auto-respuesta IA". Lista cerrada: cualquier
        // otro valor se ignora y se usa el tono por defecto, en vez de colarse
        // como texto libre dentro del prompt.
        $tone = $request->string('tone')->toString();
        $tone = in_array($tone, ['formal', 'cercano', 'breve'], true) ? $tone : null;

        $suggestion = $service->suggest(
            $ticket,
            (int) $request->user()?->id,
            $request->boolean('refresh'),
            $tone,
        );

        if ($suggestion === null) {
            return response()->json([
                'success' => true,
                'suggestion' => null,
                'message' => 'No se pudo generar una sugerencia. Revisa que haya un agente de IA configurado.',
            ]);
        }

        return response()->json([
            'success' => true,
            'suggestion' => $suggestion,
        ]);
    }

    /**
     * Revisión del borrador antes de enviarlo.
     *
     * Avisa, no bloquea: la respuesta siempre es 200, incluso cuando hay
     * avisos, y `checked=false` significa que no se pudo revisar (sin agente
     * IA, borrador demasiado corto, proveedor caído). Quien llama debe dejar
     * enviar en cualquiera de los dos casos — que un proveedor caído impida
     * contestar a un cliente es peor que el problema que esto resuelve.
     */
    public function checkReply(Request $request, Ticket $ticket, ReplyGuardService $guard): JsonResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
        ]);

        $result = $guard->check($ticket, $validated['body'], (int) $request->user()?->id);

        return response()->json([
            'success' => true,
            'checked' => $result['checked'],
            'warnings' => $result['warnings'],
        ]);
    }

    /**
     * Posibles duplicados del ticket.
     *
     * Sugiere, no fusiona: devuelve candidatos con su parecido para que el
     * agente decida. Fusionar mueve mensajes y cierra un ticket, y una
     * similitud alta no es prueba de nada.
     */
    /**
     * Marca como disputada la revisión de calidad de un ticket.
     *
     * Es la contrapartida de evaluar el trabajo de alguien automáticamente: sin
     * derecho a réplica, una nota puesta por un modelo sobre cómo atendió una
     * persona no es una métrica, es un juicio. Las revisiones disputadas se
     * excluyen de las medias (`TicketReview::scopeCounted`), así que discutir
     * una tiene efecto real y no es solo un desahogo.
     */
    public function disputeReview(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = TicketReview::query()->where('ticket_id', $ticket->id)->first();

        if ($review === null) {
            return response()->json(['success' => false, 'message' => 'Este ticket no tiene revisión.'], 404);
        }

        $review->update([
            'disputed' => true,
            'dispute_note' => $validated['note'] ?? null,
            'disputed_by' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Revisión marcada como disputada. Deja de contar para las medias.',
        ]);
    }

    /**
     * Candidatos a duplicado ANTES de crear el ticket (modal 35).
     *
     * duplicates() necesita un Ticket ya guardado; aquí solo hay lo que el
     * agente lleva escrito en el formulario, así que se compara por texto.
     */
    public function duplicatesPreview(Request $request, TicketDuplicateService $duplicates): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'integer'],
        ]);

        $candidates = $duplicates->candidatesByText($validated['subject'], (int) $validated['customer_id']);

        return response()->json([
            'success' => true,
            'window_days' => (int) config('helpdeskagents.ticket_similarity.duplicate_window_days', 14),
            'duplicates' => $candidates->map(fn (array $c): array => [
                'id' => $c['ticket']->id,
                'ticket_number' => $c['ticket']->ticket_number,
                'subject' => $c['ticket']->subject,
                'status' => $c['ticket']->status?->name,
                'similarity' => $c['similarity'],
                'same_customer' => $c['same_customer'],
                'url' => route('manager.helpdesk.tickets.show-full', $c['ticket']->id),
            ])->all(),
        ]);
    }

    public function duplicates(Ticket $ticket, TicketDuplicateService $duplicates): JsonResponse
    {
        $this->authorize('view', $ticket);

        // Los embeddings pueden estar desactivados (lo están por defecto): en
        // ese caso candidatesFor() devuelve vacío y el aviso no se dispararía
        // nunca. Se cae al parecido de texto, que no depende de ningún
        // proveedor externo.
        $candidates = $duplicates->candidatesFor($ticket);

        if ($candidates->isEmpty()) {
            $candidates = $duplicates->candidatesByText(
                (string) $ticket->subject,
                $ticket->customer_id,
                $ticket->id,
            );
        }

        return response()->json([
            'success' => true,
            // La ventana real, para que el modal la diga en vez de decir "la
            // configurada" y obligar a ir a buscarla.
            'window_days' => (int) config('helpdeskagents.ticket_similarity.duplicate_window_days', 14),
            'duplicates' => $candidates->map(fn (array $c): array => [
                'id' => $c['ticket']->id,
                'ticket_number' => $c['ticket']->ticket_number,
                'subject' => $c['ticket']->subject,
                'status' => $c['ticket']->status?->name,
                'similarity' => $c['similarity'],
                'same_customer' => $c['same_customer'],
                'url' => route('manager.helpdesk.tickets.show-full', $c['ticket']->id),
            ])->all(),
        ]);
    }
}
