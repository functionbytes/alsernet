<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Mail\TicketComposedMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;

/**
 * Único punto que realmente encola un TicketMail compuesto manualmente
 * (redactar/responder/reenviar/envío programado que vence). Compartido por
 * TicketMailsController y el comando de envío programado para no duplicar el
 * mismo Mail::to()->queue(...) + markAsSent() en dos sitios.
 */
class TicketMailDispatcher
{
    /**
     * @param  array<int, string>  $cc
     * @param  array<int, string>  $bcc
     * @param  array<int, array{disk: string, path: string, name?: string}>  $attachmentFiles
     */
    public function send(TicketMail $mail, Ticket $ticket, array $cc = [], array $bcc = [], array $attachmentFiles = []): void
    {
        // store()/createResendCopy() no fijan message_id (a diferencia de
        // TicketMail::createOutbound()) — sin esto, cada envío real quedaba
        // sin Message-ID propio y el tab "Trazabilidad" (que cruza contra
        // EmailLog por este valor) no podía enlazar nada.
        if (! $mail->message_id) {
            $mail->message_id = '<'.Str::uuid().'@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost').'>';
            $mail->save();
        }

        Mail::to($mail->to)
            ->cc($cc)
            ->bcc($bcc)
            ->queue(new TicketComposedMail(
                $ticket,
                $mail->subject,
                $mail->body_html ?? $mail->body_text ?? '',
                $cc,
                $bcc,
                $attachmentFiles,
                $mail->message_id,
            ));

        $mail->markAsSent();
    }

    /**
     * @return array<int, array{disk: string, path: string, name?: string}>
     */
    public function resendableAttachments(TicketMail $mail): array
    {
        return collect($mail->attachments ?? [])
            ->filter(fn ($a) => is_array($a) && isset($a['disk'], $a['path']))
            ->values()
            ->all();
    }
}
