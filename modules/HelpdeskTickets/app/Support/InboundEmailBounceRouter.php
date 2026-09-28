<?php

namespace Modules\HelpdeskTickets\Support;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskEmailActivity\Services\EmailBounceCorrelatorService;
use Modules\HelpdeskEmailActivity\Support\DsnMessageParser;
use Webklex\PHPIMAP\Message as ImapMessage;

/**
 * Detección de DSN (bounce)/quejas de spam entre los correos entrantes, para
 * desviarlos de la creación de ticket. Extraído de FetchTicketEmailsJob
 * (30-sep-2026, job de 1103 líneas) — FetchTicketEmailsJob::routeIfBounceOrComplaint()
 * es ahora un delegado fino a esta clase; ver su docblock para el porqué del
 * reparto.
 */
class InboundEmailBounceRouter
{
    public function __construct(
        private readonly InboundEmailMessageParser $parser,
    ) {}

    /**
     * Detecta si el mensaje entrante es un DSN (bounce)/queja de spam en vez
     * de correo real de un cliente, y de ser así lo desvía a
     * EmailBounceCorrelatorService (marca el EmailLog original como
     * bounced/complained si se puede correlacionar) — devuelve true en
     * cualquier caso para que el caller NUNCA cree un ticket con esto,
     * incluso si no hubo correlación (el remitente de un DSN casi siempre es
     * un MAILER-DAEMON interno, nunca un cliente real).
     *
     * Gateado por helpdesk_emaillog_enabled(): con el módulo/integración
     * apagados, no intenta nada y el DSN sigue el flujo normal de ticket
     * (comportamiento idéntico al de antes de este cambio).
     */
    public function routeIfBounceOrComplaint(ImapMessage $message): bool
    {
        if (! helpdesk_emaillog_enabled()) {
            return false;
        }

        $subject = $this->parser->stringAttribute($message->subject) ?: '';
        $rawBody = $this->parser->rawSource($message) ?: '';

        if (! DsnMessageParser::looksLikeBounceOrComplaint($subject, $rawBody)) {
            return false;
        }

        try {
            $ownMessageId = $this->parser->stringAttribute($message->message_id) ?: '';
            $isComplaint = DsnMessageParser::isComplaint($subject, $rawBody);
            $isHard = DsnMessageParser::isHardBounce($rawBody);

            $correlator = app(EmailBounceCorrelatorService::class);
            $originalMessageId = DsnMessageParser::findOriginalMessageId($rawBody, $ownMessageId);

            $matched = $originalMessageId
                && $correlator->correlateByMessageId($originalMessageId, $subject, $isHard, $isComplaint);

            if (! $matched && ($recipient = DsnMessageParser::findFailedRecipient($rawBody))) {
                $matched = $correlator->correlateByRecipient($recipient, $subject, ['HelpdeskTickets'], $isHard, $isComplaint);
            }

            Log::info('FetchTicketEmailsJob: mensaje con forma de DSN/queja desviado de la creación de ticket', [
                'subject' => $subject,
                'matched' => $matched,
            ]);
        } catch (\Throwable $e) {
            Log::warning('FetchTicketEmailsJob: fallo correlacionando un DSN/queja', ['error' => $e->getMessage()]);
        }

        return true;
    }
}
