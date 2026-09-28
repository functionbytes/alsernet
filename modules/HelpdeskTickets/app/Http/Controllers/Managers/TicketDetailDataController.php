<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Services\TicketDetailDataService;

/**
 * Extraído de TicketsCrudController (30-ago-2026, controller de 1017 líneas)
 * — el endpoint data() y sus 6 helpers privados construyen el JSON del panel
 * de detalle del ticket (Hilo, actividad, correo, notas, relacionados, etc.),
 * un bloque autocontenido de ~340 líneas sin relación con el CRUD. Sin
 * cambios de comportamiento, solo movimiento. mapCustomerDetail()/
 * contactStats() migrados a CustomerSummaryService (antes duplicados casi
 * al carácter con TicketMailsController, consolidados el mismo día).
 *
 * El ensamblado del payload de data() (query building + serialización, sin
 * nada específico de HTTP salvo authorize()) vive en TicketDetailDataService
 * (30-sep-2026, este controller llegó a 1502 líneas): esta clase se limita a
 * autorizar y despachar. Ver el docblock de ese servicio.
 */
class TicketDetailDataController extends Controller
{
    public function __construct(
        private readonly TicketDetailDataService $detailData,
    ) {}

    /**
     * ¿Ha cambiado algo en este ticket?
     *
     * El panel lo pregunta cada pocos segundos con el ticket abierto, así que
     * es deliberadamente diminuto: dos agregados sobre índices y ni una
     * relación cargada. `data()`, en cambio, arma el hilo entero con
     * traducciones, adjuntos, correos, actividad y tickets relacionados — no se
     * puede pedir en bucle.
     *
     * Existe como RESPALDO del tiempo real, no como sustituto. Lo normal es que
     * el aviso llegue por websocket (MessageAdded en el canal ticket.{id}); esto
     * cubre el caso de que Reverb no esté disponible, el navegador haya perdido
     * la conexión o la cola de broadcasts vaya con retraso — que es exactamente
     * lo que pasaba: los eventos se encolaban en `default`, que ningún worker
     * sirve, y el correo de un cliente no aparecía hasta recargar a mano.
     */
    public function pulse(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $items = TicketItem::query()
            ->where('ticket_id', $ticket->id)
            ->selectRaw('COUNT(*) as total, COALESCE(MAX(id), 0) as last_id, COALESCE(MAX(updated_at), "") as last_at')
            ->first();

        return response()->json([
            // El frontend compara este objeto con el anterior: si algo cambia,
            // pide data() completo. Se manda el total además del último id
            // porque un borrado no mueve el máximo.
            'items' => (int) $items->total,
            'last_item_id' => (int) $items->last_id,
            'last_item_at' => (string) $items->last_at,
            'ticket_updated_at' => optional($ticket->updated_at)->toIso8601String(),
            'status_id' => $ticket->status_id,
        ]);
    }

    public function data(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        return response()->json($this->detailData->build($ticket));
    }
}
