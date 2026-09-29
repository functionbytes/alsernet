<?php

namespace Modules\HelpdeskTickets\Services\TicketDetail;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAttachment;
use Modules\HelpdeskTickets\Models\TicketItem;

/**
 * Pestaña Adjuntos del panel de detalle: adjuntos de agente (JSON en
 * TicketItem.attachment_urls) y de cliente (filas TicketAttachment)
 * fusionados en una sola lista. Extraído de TicketDetailDataService
 * (30-sep-2026) al trocear ese servicio por sección de panel.
 */
class FilesBuilder
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function build(Ticket $ticket): Collection
    {
        $attachmentsDisk = config('helpdesk.attachments.disk', 'local');

        // Adjuntos escritos por el panel de agente: rutas de storage dentro de
        // TicketItem.attachment_urls.
        $itemsWithAttachments = TicketItem::query()
            ->where('ticket_id', $ticket->id)
            ->whereNotNull('attachment_urls')
            ->get();

        $itemFiles = $itemsWithAttachments
            ->filter(fn ($item) => $item->hasAttachments())
            ->flatMap(fn ($item) => collect($item->attachment_urls)->values()->map(fn ($path, $index) => [
                // Storage::putFile() genera un hash como nombre real de
                // fichero (p.ej. 'NlZV0tAIXTf6d73vPYZyKGrrAIm9yeHxAFUbGvQ9.png')
                // — a diferencia de TicketAttachment (adjuntos de CLIENTE, ver
                // $customerFiles más abajo), aquí no se guardó nunca un
                // 'original_filename' en ningún sitio, así que no hay nombre
                // real que recuperar. Mostrar el hash crudo es ilegible; sin
                // inventar un nombre que no existe, se usa una etiqueta
                // genérica con quién lo adjuntó + su extensión real. Guardar
                // el nombre original a futuro para adjuntos de agente
                // requeriría tocar el esquema (columna nueva en TicketItem o
                // una tabla propia como TicketAttachment) — fuera de alcance
                // de este fix.
                'name' => data_get($item->metadata, 'attachment_names.'.$index)
                    ?: 'Adjunto de '.$item->sender_name.(($ext = pathinfo((string) $path, PATHINFO_EXTENSION)) !== '' ? '.'.$ext : ''),
                'item_id' => $item->id,
                'source' => 'agent',
                'created_at_human' => $item->created_at?->diffForHumans(),
                'created_at' => $item->created_at?->toIso8601String(),
                // Descarga real (TicketAttachmentDownloadController) — el
                // índice es la posición dentro de attachment_urls del item,
                // que es como la ruta lo resuelve.
                'url_download' => route('manager.helpdesk.tickets.attachments.download', [$ticket, $item->id, $index]),
                'size' => Storage::disk($attachmentsDisk)->exists((string) $path)
                    ? Storage::disk($attachmentsDisk)->size((string) $path)
                    : null,
            ]));

        // Adjuntos subidos por el CLIENTE (portal, widget, formulario público):
        // TicketService::storeAttachments() los guarda como TicketAttachment
        // colgando de un TicketMessage. El panel no los leía en ningún sitio,
        // así que el agente nunca veía lo que mandaba el cliente. A diferencia
        // del array JSON, aquí sí tenemos nombre original, tamaño y mime
        // guardados en la propia fila.
        $customerFiles = TicketAttachment::query()
            ->whereHas('message', fn ($q) => $q->where('ticket_id', $ticket->id))
            ->with('message:id,ticket_id,created_at')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (TicketAttachment $a) => [
                'name' => $a->original_filename ?: $a->filename,
                'item_id' => null,
                'source' => 'customer',
                'created_at_human' => $a->created_at?->diffForHumans(),
                'created_at' => $a->created_at?->toIso8601String(),
                'url_download' => route('manager.helpdesk.tickets.message-attachments.download', [$ticket, $a->id]),
                'size' => $a->size,
            ]);

        return $itemFiles->concat($customerFiles)->sortByDesc('created_at')->values();
    }
}
