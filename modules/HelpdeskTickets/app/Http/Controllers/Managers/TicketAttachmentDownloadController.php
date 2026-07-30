<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentDownloadController extends Controller
{
    /**
     * Download an attachment stored on the private disk.
     *
     * Route: GET /manager/helpdesk/tickets/{ticket}/attachments/{item}/{index}
     * Name:  manager.helpdesk.tickets.attachments.download
     *
     * Views should link to:
     *   route('manager.helpdesk.tickets.attachments.download', [$ticket, $item, $index])
     *
     * Where $index is the zero-based position in $item->attachment_urls (which now stores
     * storage paths, not public URLs).
     */
    public function download(Ticket $ticket, TicketItem $item, int $index): StreamedResponse
    {
        $this->authorize('helpdesk.tickets.view');

        abort_if($item->ticket_id !== $ticket->id, 404);

        $paths = $item->attachment_urls ?? [];

        abort_if(! isset($paths[$index]), 404);

        $path = $paths[$index];
        $disk = config('helpdesk.attachments.disk', 'local');

        abort_unless(Storage::disk($disk)->exists($path), 404);

        $filename = basename($path);

        return Storage::disk($disk)->download($path, $filename);
    }
}
