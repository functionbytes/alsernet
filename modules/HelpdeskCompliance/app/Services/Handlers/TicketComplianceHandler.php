<?php

namespace Modules\HelpdeskCompliance\Services\Handlers;

use Illuminate\Support\Facades\Storage;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAttachment;
use Modules\HelpdeskTickets\Models\TicketComment;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketMessage;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Models\TicketSideConversation;
use Modules\HelpdeskTickets\Models\TicketSideConversationMessage;

/**
 * Cascades a GDPR erasure to the customer's tickets AND every child record
 * that can hold PII: emails (helpdesk_ticket_mails), the agent thread
 * (helpdesk_ticket_items, via Ticket::items()/messages()), the portal/widget
 * thread (helpdesk_ticket_messages + helpdesk_ticket_attachments), notes,
 * comments and side conversations. Mirrors the pattern of
 * Modules\Helpdesk\Services\Compliance\GdprDeletionService::softDelete():
 * redact bodies + delete attachment files, keep the row for statistics —
 * except for the top-level Ticket, which is also soft-deleted (pre-existing
 * behaviour, covered by ComplianceCascadeTest).
 *
 * Before this handler only redacted Ticket.description/custom_fields in soft
 * mode: subject and every child table above (plus their attachments) stayed
 * fully legible after a "soft" GDPR erasure. In hard mode it only
 * force-deleted the Ticket row — MariaDB's ON DELETE CASCADE removes the
 * cascaded child ROWS but never fires Eloquent hooks, so the physical files
 * behind helpdesk_ticket_attachments.path and helpdesk_ticket_mails.attachments
 * were orphaned on disk forever. helpdesk_ticket_side_conversations has no DB
 * cascade at all, so hard delete now removes it explicitly too.
 *
 * Recibe el customer_id (no el modelo Customer): la cascada corre en cola y,
 * en modo hard, el Customer ya fue borrado por completo antes de encolarse —
 * intentar rehidratar el modelo fallaria.
 */
class TicketComplianceHandler
{
    /**
     * $conversationIds cubre tickets abiertos desde un canal omnicanal
     * (ej. ChatFlow) antes de resolver un Customer: customer_id es nullable
     * desde 2026_07_06_214936_make_customer_id_nullable_on_helpdesk_tickets_table
     * y esos tickets solo enlazan por conversation_id — sin este OR quedaban
     * fuera de la cascada por completo.
     *
     * @param  array<int, int>  $conversationIds
     * @return array{module: string, tickets: int, mode: string}
     */
    public function handle(int $customerId, bool $hard, array $conversationIds): array
    {
        $count = 0;

        Ticket::query()
            ->withTrashed()
            ->where(function ($query) use ($customerId, $conversationIds): void {
                $query->where('customer_id', $customerId);

                if ($conversationIds !== []) {
                    $query->orWhereIn('conversation_id', $conversationIds);
                }
            })
            ->chunkById(200, function ($tickets) use (&$count, $hard): void {
                foreach ($tickets as $ticket) {
                    if ($hard) {
                        $this->deleteTicketAttachmentFiles($ticket);
                        $this->deleteMailAttachmentFiles($ticket);
                        $this->deleteItemAttachmentFiles($ticket);

                        // Sin FK/cascade en BD para side conversations (ver
                        // create_ticket_side_conversations, sin ->foreign()):
                        // forceDelete() del ticket no las arrastra.
                        TicketSideConversationMessage::query()
                            ->whereIn('side_conversation_id', TicketSideConversation::query()->where('ticket_id', $ticket->id)->pluck('id'))
                            ->delete();
                        TicketSideConversation::query()->where('ticket_id', $ticket->id)->delete();

                        $ticket->forceDelete();
                    } else {
                        $this->redactTicketChildren($ticket);

                        $ticket->update([
                            'subject' => config('helpdeskcompliance.redacted_text'),
                            'description' => config('helpdeskcompliance.redacted_text'),
                            'custom_fields' => null,
                        ]);
                        $ticket->delete();
                    }

                    $count++;
                }
            });

        return [
            'module' => 'HelpdeskTickets',
            'tickets' => $count,
            'mode' => $hard ? 'deleted' : 'redacted',
        ];
    }

    private function redactTicketChildren(Ticket $ticket): void
    {
        $redacted = config('helpdeskcompliance.redacted_text');

        $ticket->mails()->withTrashed()->get()->each(function (TicketMail $mail) use ($redacted): void {
            $this->deleteStoragePaths($mail->attachments ?? []);
            $mail->update(['subject' => $redacted, 'body_html' => null, 'body_text' => null, 'attachments' => []]);
        });

        // Hilo real del panel de agente — Ticket::items()/messages(), modelo
        // TicketItem (helpdesk_ticket_items). No confundir con TicketMessage
        // (helpdesk_ticket_messages), el subsistema de adjuntos del portal/
        // widget que se redacta más abajo.
        $this->deleteItemAttachmentFiles($ticket);
        $ticket->items()->withTrashed()->get()->each(function ($item) use ($redacted): void {
            $item->update(['body' => $redacted, 'html_body' => null, 'attachment_urls' => []]);
        });

        $this->deleteTicketAttachmentFiles($ticket);
        TicketMessage::query()->withTrashed()->where('ticket_id', $ticket->id)
            ->update(['message' => $redacted, 'message_html' => null]);

        TicketNote::query()->withTrashed()->where('ticket_id', $ticket->id)
            ->update(['title' => null, 'body' => $redacted]);

        TicketComment::query()->withTrashed()->where('ticket_id', $ticket->id)->get()->each(function (TicketComment $comment) use ($redacted): void {
            $this->deleteRawStoragePaths($comment->attachment_urls ?? []);
            $comment->update(['body' => $redacted, 'html_body' => null, 'attachment_urls' => null]);
        });

        TicketSideConversation::query()->where('ticket_id', $ticket->id)->get()->each(function (TicketSideConversation $side) use ($redacted): void {
            TicketSideConversationMessage::query()->where('side_conversation_id', $side->id)
                ->update(['body' => $redacted]);
            $side->update(['subject' => $redacted, 'participant_email' => null]);
        });
    }

    /**
     * Adjuntos de TicketAttachment (helpdesk_ticket_attachments.path), colgados
     * de los TicketMessage del ticket. Reutilizable desde hard y soft: en
     * ambos casos el fichero físico es PII y debe desaparecer del disco, no
     * solo la fila (que en soft ni siquiera se borra).
     */
    private function deleteTicketAttachmentFiles(Ticket $ticket): void
    {
        $messageIds = TicketMessage::query()->withTrashed()->where('ticket_id', $ticket->id)->pluck('id');

        if ($messageIds->isEmpty()) {
            return;
        }

        $disk = config('helpdesk.attachments.disk', 'local');

        TicketAttachment::query()
            ->whereIn('ticket_message_id', $messageIds)
            ->get()
            ->each(function (TicketAttachment $attachment) use ($disk): void {
                if ($attachment->path && Storage::disk($disk)->exists($attachment->path)) {
                    Storage::disk($disk)->delete($attachment->path);
                }
            });
    }

    private function deleteMailAttachmentFiles(Ticket $ticket): void
    {
        $ticket->mails()->withTrashed()->get()->each(function (TicketMail $mail): void {
            $this->deleteStoragePaths($mail->attachments ?? []);
        });
    }

    private function deleteItemAttachmentFiles(Ticket $ticket): void
    {
        $ticket->items()->withTrashed()->get()->each(function ($item): void {
            $this->deleteRawStoragePaths($item->attachment_urls ?? []);
        });
    }

    /**
     * TicketMail.attachments guarda {name, path, disk, size} por adjunto (ver
     * TicketMailsController::storeAttachments() / FetchTicketEmailsJob::parseAttachments()).
     *
     * @param  array<int, array{path?: string, disk?: string}>  $attachments
     */
    private function deleteStoragePaths(array $attachments): void
    {
        $defaultDisk = config('helpdesk.attachments.disk', 'local');

        foreach ($attachments as $attachment) {
            $path = $attachment['path'] ?? null;

            if (! $path) {
                continue;
            }

            $disk = $attachment['disk'] ?? $defaultDisk;

            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    /**
     * TicketItem.attachment_urls / TicketComment.attachment_urls guardan rutas
     * de storage crudas (no URLs públicas), todas en el mismo disco de
     * adjuntos — ver TicketMessagingController::storeMessage().
     *
     * @param  array<int, string>  $paths
     */
    private function deleteRawStoragePaths(array $paths): void
    {
        $disk = config('helpdesk.attachments.disk', 'local');

        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }
}
