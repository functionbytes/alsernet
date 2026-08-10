<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Services\TicketMailDispatcher;

/**
 * Envía los emails compuestos manualmente (bandeja "Emails enviados") cuya
 * hora de programación ya venció. Distinto de ticket:send-scheduled-replies
 * (TicketScheduledReply: solo cuerpo de respuesta, sin asunto/CC/BCC/adjuntos
 * propios) — aquí cada fila es un email completo ya redactado y guardado en
 * TicketMail con status=scheduled a la espera de scheduled_at.
 */
class SendScheduledTicketMailsCommand extends Command
{
    protected $signature = 'helpdesk:send-scheduled-emails';

    protected $description = 'Deliver composed ticket emails whose scheduled send time has passed';

    public function handle(TicketMailDispatcher $dispatcher): int
    {
        $sent = 0;
        $skipped = 0;

        TicketMail::query()
            ->dueForDelivery()
            ->with('ticket')
            ->chunkById(200, function ($mails) use ($dispatcher, &$sent, &$skipped) {
                foreach ($mails as $mail) {
                    if ($this->deliver($mail, $dispatcher)) {
                        $sent++;
                    } else {
                        $skipped++;
                    }
                }
            });

        if ($sent > 0 || $skipped > 0) {
            Log::info('SendScheduledTicketMails: emails programados procesados', ['sent' => $sent, 'skipped' => $skipped]);
        }

        $this->info("Enviados {$sent} email(s) programado(s)".($skipped > 0 ? ", {$skipped} omitido(s) sin ticket." : '.'));

        return self::SUCCESS;
    }

    private function deliver(TicketMail $mail, TicketMailDispatcher $dispatcher): bool
    {
        // El ticket pudo eliminarse tras programar el email: se marca como
        // fallido en vez de reintentarlo indefinidamente cada minuto.
        if (! $mail->ticket) {
            $mail->markAsFailed('El ticket asociado ya no existe.');

            return false;
        }

        $cc = $mail->cc ? array_map('trim', explode(',', $mail->cc)) : [];
        $bcc = $mail->bcc ? array_map('trim', explode(',', $mail->bcc)) : [];

        $dispatcher->send($mail, $mail->ticket, $cc, $bcc, $dispatcher->resendableAttachments($mail));

        return true;
    }
}
