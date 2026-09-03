<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Core\Models\Setting;
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
    public function __construct(private readonly TicketChannelMailerService $channelMailer) {}

    /**
     * @param  array<int, string>  $cc
     * @param  array<int, string>  $bcc
     * @param  array<int, array{disk: string, path: string, name?: string}>  $attachmentFiles
     * @return bool false si no se encoló (auto-supresión del modal 22 activa) — el propio $mail queda marcado 'failed' con el motivo, nunca se deja "pending" en silencio.
     */
    public function send(TicketMail $mail, Ticket $ticket, array $cc = [], array $bcc = [], array $attachmentFiles = []): bool
    {
        // Modal 22 "Reputación y autenticación": si el checkbox "suprimir
        // automáticamente" está activo y ticket:check-reputation ya detectó
        // la tasa de rebote por encima del umbral crítico, seguir mandando
        // solo empeora la reputación del dominio. Se corta aquí porque es el
        // único punto real de envío (redactar/reenviar/reenvío masivo/envío
        // programado pasan los tres por send()).
        if (filter_var(Setting::get('tickets.reputation_suppressed', false), FILTER_VALIDATE_BOOLEAN)) {
            $mail->markAsFailed('Envío pausado automáticamente: la tasa de rebote del ticket mailer superó el umbral crítico (ver Reputación y autenticación).');

            return false;
        }

        // store()/createResendCopy() no fijan message_id (a diferencia de
        // TicketMail::createOutbound()) — sin esto, cada envío real quedaba
        // sin Message-ID propio y el tab "Trazabilidad" (que cruza contra
        // EmailLog por este valor) no podía enlazar nada.
        if (! $mail->message_id) {
            // Sin '<' '>' al guardar — ver TicketMail::createOutbound().
            $mail->message_id = Str::uuid().'@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost');
            $mail->save();
        }

        // Responder desde el buzón del canal (si tiene SMTP configurado) e
        // hilar contra el último correo entrante — mismo criterio que
        // SendCustomerReplyNotification/TicketCommentsController. createResendCopy()
        // ya puede haber fijado in_reply_to (reenvío); si no, se resuelve aquí.
        $channel = $this->channelMailer->resolveChannelForTicket($ticket);
        $mailerName = $channel ? $this->channelMailer->mailerNameFor($channel) : null;
        $fromAddress = $channel['username'] ?? null;
        $inReplyTo = $mail->in_reply_to ?: $this->channelMailer->lastInboundMessageId($ticket);

        if (! $mail->in_reply_to && $inReplyTo) {
            $mail->in_reply_to = $inReplyTo;
        }

        ($mailerName ? Mail::mailer($mailerName) : Mail::mailer())
            ->to($mail->to)
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
                $fromAddress,
                $inReplyTo,
            ));

        if ($fromAddress) {
            $mail->from = $fromAddress;
        }

        $mail->markAsSent();

        return true;
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
