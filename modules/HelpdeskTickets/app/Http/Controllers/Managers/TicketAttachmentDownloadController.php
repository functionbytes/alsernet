<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAttachment;
use Modules\HelpdeskTickets\Models\TicketItem;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga de adjuntos desde el disco privado.
 *
 * El módulo tiene DOS sistemas de adjuntos conviviendo y cada uno necesita su
 * propia ruta:
 *
 *  - TicketItem.attachment_urls (JSON de rutas de storage) — lo que escribe el
 *    panel de agente. Lo sirve download().
 *  - TicketAttachment (tabla propia colgada de TicketMessage) — lo que escriben
 *    el portal, el widget y el formulario público vía
 *    TicketService::storeAttachments(). Lo sirve downloadMessageAttachment().
 *
 * Hasta ahora solo existía la primera ruta, así que todo fichero subido por un
 * cliente se guardaba bien y no había forma de descargarlo: ni el agente en el
 * panel ni el propio cliente en el portal. Ese era el agujero.
 */
class TicketAttachmentDownloadController extends Controller
{
    /**
     * Adjunto del sistema TicketItem.attachment_urls.
     *
     * Route: GET /manager/helpdesk/tickets/{ticket}/attachments/{item}/{index}
     * Name:  manager.helpdesk.tickets.attachments.download
     *
     * Donde $index es la posición (base cero) dentro de $item->attachment_urls,
     * que guarda rutas de storage, no URLs públicas.
     */
    public function download(Ticket $ticket, TicketItem $item, int $index): StreamedResponse
    {
        // authorize('view', $ticket) y no el permiso suelto
        // 'helpdesk.tickets.view': TicketPolicy::view() concede acceso por
        // permiso O por ser el agente asignado, así que con el permiso a secas
        // un agente que veía el ticket en pantalla recibía un 403 al pinchar
        // sus adjuntos. Además, cualquier endurecimiento futuro de la policy
        // (por grupo, por asignación) alcanza ya a esta ruta.
        $this->authorize('view', $ticket);

        abort_if($item->ticket_id !== $ticket->id, 404);

        $paths = $item->attachment_urls ?? [];

        abort_if(! isset($paths[$index]), 404);

        $path = $paths[$index];
        $disk = config('helpdesk.attachments.disk', 'local');

        abort_unless(Storage::disk($disk)->exists($path), 404);

        $filename = basename($path);

        $response = Storage::disk($disk)->download($path, $filename);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    /**
     * Adjunto del sistema TicketAttachment (subidas de cliente).
     *
     * Route: GET /manager/helpdesk/tickets/{ticket}/message-attachments/{attachment}
     * Name:  manager.helpdesk.tickets.message-attachments.download
     *
     * Se sirve con el nombre original que subió el cliente, no con el hash que
     * genera Storage::store().
     */
    public function downloadMessageAttachment(Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $ticket);

        // El adjunto cuelga de un TicketMessage, que a su vez cuelga del
        // ticket: sin esta comprobación la ruta serviría el adjunto de
        // cualquier ticket con solo cambiar el id.
        abort_if($attachment->message?->ticket_id !== $ticket->id, 404);

        $disk = config('helpdesk.attachments.disk', 'local');

        abort_unless(Storage::disk($disk)->exists((string) $attachment->path), 404);

        $response = Storage::disk($disk)->download(
            $attachment->path,
            $attachment->original_filename ?: $attachment->filename ?: basename((string) $attachment->path),
        );
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
