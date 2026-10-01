<?php

namespace Modules\HelpdeskMedia\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAttachment;

/**
 * Datos de medios (antivirus, conversión, transcripción) para decorar la UI.
 * Solo lectura: lo que escribe el pipeline vive en
 * ConversationItem.metadata.media y en helpdesk_ticket_attachments.
 */
class MediaUiController extends Controller
{
    /**
     * @return JsonResponse {items: {item_id: {url: meta}}}
     */
    public function conversation(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($request->user()->can('view', $conversation), 403);

        $items = $conversation->items()
            ->whereNotNull('metadata->media')
            ->get(['id', 'metadata'])
            ->mapWithKeys(fn (ConversationItem $item): array => [$item->id => $item->metadata['media'] ?? []])
            ->filter(fn ($media): bool => is_array($media) && $media !== []);

        return response()->json(['items' => (object) $items->all()]);
    }

    /**
     * @return JsonResponse {attachments: {attachment_id: {...}}}
     */
    public function ticket(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($request->user()->can('view', $ticket), 403);

        $attachments = TicketAttachment::query()
            ->whereHas('message', fn ($query) => $query->where('ticket_id', $ticket->id))
            ->get()
            ->mapWithKeys(fn (TicketAttachment $attachment): array => [$attachment->id => $this->ticketPayload($attachment)]);

        return response()->json(['attachments' => (object) $attachments->all()]);
    }

    private function ticketPayload(TicketAttachment $attachment): array
    {
        $meta = $attachment->getAttribute('media_meta');

        if (is_string($meta)) {
            $meta = json_decode($meta, true);
        }

        $meta = is_array($meta) ? $meta : [];

        return $meta + [
            'kind' => $this->kindFromMime((string) $attachment->mime_type),
            'scan' => $attachment->getAttribute('scan_status'),
            'scan_signature' => $attachment->getAttribute('scan_signature'),
            'transcript' => $attachment->getAttribute('transcript'),
            'processed_at' => $attachment->getAttribute('processed_at'),
        ];
    }

    private function kindFromMime(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'audio/') => 'audio',
            default => 'file',
        };
    }
}
