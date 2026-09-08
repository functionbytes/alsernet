<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Services\TicketDuplicateService;
use Modules\HelpdeskTickets\Services\TicketUnificationService;

/**
 * "Unificar duplicados" (v2 del aviso de duplicados).
 *
 * La v1 —banner "Revisar duplicado" → fusionar de uno en uno— se queda como
 * está. Esto responde al caso que la v1 no cubre: el cliente manda tres correos
 * el mismo día, se abren tres tickets, y hace falta ver de un vistazo qué trae
 * cada uno, quedarse con el último y cerrar el resto avisando al cliente.
 */
class TicketUnificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.view')->only('summary');
    }

    /**
     * Resumen de los tickets abiertos del mismo cliente que son candidatos a
     * unificarse con este, con lo que hace falta para decidir sin abrirlos:
     * cuántos mensajes traen, cuál fue el último y de cuándo es.
     */
    public function summary(Ticket $ticket, TicketDuplicateService $duplicates): JsonResponse
    {
        $this->authorize('view', $ticket);

        $candidatos = $duplicates->candidatesFor($ticket);

        if ($candidatos->isEmpty()) {
            $candidatos = $duplicates->candidatesByText(
                (string) $ticket->subject,
                $ticket->customer_id,
                $ticket->id,
            );
        }

        // El propio ticket entra en la lista: la v2 deja elegir cuál se
        // conserva, y por defecto es el más reciente, que puede no ser este.
        $tickets = $candidatos
            ->map(fn (array $c) => $c['ticket'])
            ->push($ticket)
            ->unique('id')
            ->sortByDesc('created_at')
            ->values();

        $similitudes = $candidatos->mapWithKeys(fn (array $c) => [$c['ticket']->id => $c['similarity']]);

        return response()->json([
            'success' => true,
            'window_days' => (int) config('helpdeskagents.ticket_similarity.duplicate_window_days', 14),
            // El primero de la lista ordenada por fecha: "el último que envió
            // el cliente es el que queda".
            'suggested_survivor_id' => $tickets->first()?->id,
            'tickets' => $tickets->map(fn (Ticket $t) => $this->describe($t, $similitudes->get($t->id)))->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * Cuántos mensajes se mandan por ticket. Es un resumen para decidir, no el
     * hilo entero: con treinta ya se ve de sobra de qué va, y el modal los
     * pinta con scroll propio.
     */
    private const MAX_MESSAGES = 30;

    /**
     * Tope por mensaje. Un correo con la firma corporativa y tres reenvíos
     * dentro puede pasar de 50 KB, y aquí van varios tickets en la misma
     * respuesta.
     */
    private const MAX_BODY_CHARS = 4000;

    private function describe(Ticket $ticket, ?float $similarity): array
    {
        // type='message' y no "todo lo que no sea interno": los items del hilo
        // incluyen eventos ('status_change', 'assigned', 'closed'…) y colarlos
        // aquí llenaba el resumen de "Estado cambiado de Nuevo a Abierto" en
        // vez de lo que escribió el cliente.
        $consulta = fn () => $ticket->items()->where('type', 'message')->where('is_internal', false);

        $publicos = $consulta()
            ->with('user')
            ->latest('id')
            ->limit(self::MAX_MESSAGES)
            ->get();

        $ultimo = $publicos->first();
        $totalMensajes = $consulta()->count();

        // El texto COMPLETO de cada mensaje, en orden de lectura: el resumen
        // recortado a 160 caracteres no daba para decidir si dos tickets hablan
        // del mismo asunto — que es justo lo que hay que decidir aquí.
        $mensajes = $publicos->sortBy('id')->values()->map(fn ($item) => [
            'author' => $item->user_id
                ? ($item->user?->fullName() ?? 'Agente')
                : ($ticket->customer?->name ?: 'Cliente'),
            'is_agent' => (bool) $item->user_id,
            'at' => $item->created_at?->format('d/m/Y H:i'),
            'body' => Str::limit(trim(strip_tags((string) ($item->html_body ?: $item->body))), self::MAX_BODY_CHARS),
        ])->all();

        return [
            'messages_list' => $mensajes,
            'messages_truncated' => $totalMensajes > self::MAX_MESSAGES,
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'status' => $ticket->status?->name,
            'priority' => $ticket->priority,
            'source' => $ticket->source,
            'created_at' => $ticket->created_at?->format('d/m/Y H:i'),
            'created_ago' => $ticket->created_at?->diffForHumans(),
            'messages' => $totalMensajes,
            'last_message' => $ultimo ? Str::limit(trim(strip_tags((string) $ultimo->body)), 160) : null,
            'last_message_at' => $ultimo?->created_at?->format('d/m/Y H:i'),
            'assignee' => $ticket->assignee?->fullName() ?: null,
            'similarity' => $similarity,
            'url' => route('manager.helpdesk.tickets.show-full', $ticket->id),
        ];
    }

    /**
     * Unifica los tickets elegidos en el que se conserva.
     */
    public function unify(Request $request, Ticket $ticket, TicketUnificationService $unifier): JsonResponse
    {
        // El contenido se mueve y los duplicados se cierran: es la misma
        // capacidad que fusionar, así que se exige el mismo permiso.
        $this->authorize('merge', $ticket);

        $validated = $request->validate([
            'survivor_id' => ['required', 'integer', Rule::exists((new Ticket)->getConnectionName().'.helpdesk_tickets', 'id')],
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'different:survivor_id'],
            'notify_customer' => ['nullable', 'boolean'],
        ]);

        $survivor = Ticket::with('customer')->findOrFail($validated['survivor_id']);

        $this->authorize('merge', $survivor);

        $duplicados = Ticket::query()
            ->whereIn('id', $validated['ticket_ids'])
            ->whereKeyNot($survivor->id)
            // Solo del MISMO cliente: unificar tickets de clientes distintos
            // mezclaría conversaciones ajenas y mandaría a uno el historial del
            // otro. El cliente del superviviente manda.
            ->where('customer_id', $survivor->customer_id)
            ->get();

        if ($duplicados->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay tickets del mismo cliente que unificar.',
            ], 422);
        }

        foreach ($duplicados as $duplicado) {
            $this->authorize('merge', $duplicado);
        }

        $resultado = $unifier->unify($survivor, $duplicados, $request->boolean('notify_customer', true));

        $cerrados = count($resultado['closed']);

        return response()->json([
            'success' => true,
            'message' => $cerrados === 1
                ? "1 ticket unificado en {$survivor->ticket_number}."
                : "{$cerrados} tickets unificados en {$survivor->ticket_number}.",
            'survivor' => [
                'id' => $survivor->id,
                'ticket_number' => $survivor->ticket_number,
                'url' => route('manager.helpdesk.tickets.show-full', $survivor->id),
            ],
            'closed' => $resultado['closed'],
            'notified' => $resultado['notified'],
        ]);
    }

    /**
     * Bloquear al remitente desde el propio ticket y quitarlo de en medio.
     *
     * Ajustes › Lista negra ya permitía dar de alta reglas a mano, y la ficha
     * completa tenía un "Bloquear remitente" que solo cubría el correo exacto.
     * Lo que faltaba es lo que se hace de verdad con el spam: decidir si se
     * bloquea ESE correo o TODO el dominio (o las dos cosas) y borrar el ticket
     * en el mismo gesto, sin salir del panel.
     */
    public function blacklist(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('delete', $ticket);

        $validated = $request->validate([
            'block_email' => ['nullable', 'boolean'],
            'block_domain' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
            'delete_ticket' => ['nullable', 'boolean'],
        ]);

        $email = trim((string) $ticket->customer?->email);

        if ($email === '' || ! str_contains($email, '@')) {
            return response()->json([
                'success' => false,
                'message' => 'Este ticket no tiene un correo de cliente que bloquear.',
            ], 422);
        }

        $bloquearEmail = $request->boolean('block_email');
        $bloquearDominio = $request->boolean('block_domain');

        if (! $bloquearEmail && ! $bloquearDominio) {
            return response()->json([
                'success' => false,
                'message' => 'Elige si bloquear el correo, el dominio o ambos.',
            ], 422);
        }

        $dominio = Str::lower(Str::after($email, '@'));
        $creadas = [];

        foreach ([
            ['email', Str::lower($email), $bloquearEmail],
            ['domain', $dominio, $bloquearDominio],
        ] as [$tipo, $valor, $activo]) {
            if (! $activo) {
                continue;
            }

            // firstOrCreate: volver a bloquear algo ya bloqueado no puede
            // reventar por el índice único ni duplicar la regla.
            $regla = TicketEmailBlacklist::firstOrCreate(
                ['type' => $tipo, 'value' => $valor],
                [
                    'reason' => $validated['reason'] ?? "Bloqueado desde el ticket {$ticket->ticket_number}",
                    'is_active' => true,
                    'added_by' => $request->user()->id,
                ],
            );

            // Si existía pero estaba desactivada, se reactiva: el agente acaba
            // de pedir explícitamente que se bloquee.
            if (! $regla->is_active) {
                $regla->update(['is_active' => true]);
            }

            $creadas[] = $tipo === 'email' ? $valor : '@'.$valor;
        }

        $borrado = false;

        if ($request->boolean('delete_ticket', true)) {
            $ticket->delete();
            $borrado = true;
        }

        return response()->json([
            'success' => true,
            'message' => 'Bloqueado: '.implode(' y ', $creadas).($borrado ? '. Ticket eliminado.' : '.'),
            'blocked' => $creadas,
            'ticket_deleted' => $borrado,
        ]);
    }
}
