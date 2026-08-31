<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Mail\TicketReplyMail;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Services\TicketChannelMailerService;
use Modules\HelpdeskTickets\Services\TicketOutboundTranslator;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

class SendCustomerReplyNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly TicketChannelMailerService $channelMailer,
        private readonly TicketOutboundTranslator $outboundTranslator,
    ) {
        $this->queue = 'notifications';
    }

    public function handle(MessageAdded $event): void
    {
        $item = $event->item;

        // Only notify customer when an agent (user_id set) sends a non-internal message
        if ($item->is_internal || $item->user_id === null) {
            return;
        }

        $template = MailerTemplate::where('key', 'helpdesk.ticket_reply')->first();

        if (! $template || ! $template->is_enabled) {
            Log::warning('helpdesk.ticket_reply template not found or disabled — skipping reply notification', [
                'item_id' => $item->id,
            ]);

            return;
        }

        $item->loadMissing(['ticket.customer', 'user']);

        $ticket = $item->ticket;

        if (! $ticket?->customer?->email) {
            return;
        }

        $langId = MailerLang::resolveDefaultId();

        // Traducido al idioma del cliente si HelpdeskTranslate está activo y
        // se conoce un idioma distinto al del agente — ver
        // TicketOutboundTranslator. Sin el módulo, devuelve el texto tal cual.
        $messageBody = $this->outboundTranslator->translateForCustomer($ticket, (string) $item->body);

        $variables = [
            'CUSTOMER_NAME' => $ticket->customer->name ?? 'Cliente',
            'TICKET_NUMBER' => $ticket->ticket_number,
            'SUBJECT' => $ticket->subject,
            'AGENT_NAME' => $item->user?->name ?? 'Soporte',
            // El contenido real de la respuesta — antes la plantilla no lo
            // incluía en absoluto: el cliente recibía "fulano te respondió"
            // sin ver qué decía la respuesta.
            'MESSAGE_BODY' => nl2br(e($messageBody)),
            'COMPANY_NAME' => config('app.name', 'Soporte'),
        ];

        $html = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

        $translation = $template->translate($langId);
        $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);

        // Responder desde el mismo buzón al que escribió el cliente (si el
        // ticket vino por un canal con SMTP configurado) y encadenar
        // Message-ID/In-Reply-To para que el hilo se vea correcto en su
        // cliente de correo — antes esto salía siempre desde
        // config('mail.from.address') sin ningún encabezado de hilo.
        $channel = $this->channelMailer->resolveChannelForTicket($ticket);
        $mailerName = $channel ? $this->channelMailer->mailerNameFor($channel) : null;
        $fromAddress = $channel['username'] ?? null;
        $inReplyTo = $this->channelMailer->lastInboundMessageId($ticket);
        // Sin '<' '>' al guardar — mismo criterio que TicketMail::createOutbound():
        // los correos entrantes se normalizan sin corchetes, y una respuesta del
        // cliente a este correo nunca engancharía si comparáramos "sin corchetes"
        // contra "con corchetes". headers() ya los agrega para el envío real.
        $ownMessageId = Str::uuid().'@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost');

        $mailable = new TicketReplyMail($ticket, $subject, $html, $fromAddress, $ownMessageId, $inReplyTo);

        ($mailerName ? Mail::mailer($mailerName) : Mail::mailer())
            ->to($ticket->customer->email)
            ->send($mailable);

        // Record outbound email for traceability
        TicketMail::create([
            'ticket_id' => $ticket->id,
            'ticket_item_id' => $item->id,
            'user_id' => $item->user_id,
            'direction' => 'outbound',
            'message_id' => $ownMessageId,
            'in_reply_to' => $inReplyTo,
            'from' => $fromAddress ?: config('mail.from.address'),
            'to' => $ticket->customer->email,
            'subject' => $subject,
            'body_html' => $html,
            'body_text' => strip_tags($html),
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function failed(MessageAdded $event, \Throwable $exception): void
    {
        Log::error('SendCustomerReplyNotification listener failed', [
            'ticket_item_id' => $event->item->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
