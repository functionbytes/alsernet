<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Mail\TicketCreatedMail;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Services\TicketChannelMailerService;
use Modules\HelpdeskTickets\Services\TicketOutboundTranslator;
use Modules\HelpdeskTickets\Support\TicketMailRenderer;

/**
 * Send confirmation email to customer when ticket is created
 */
class SendCustomerConfirmation implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly TicketChannelMailerService $channelMailer,
        private readonly TicketOutboundTranslator $outboundTranslator,
    ) {
        $this->queue = 'notifications';
    }

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    /**
     * Handle the event
     */
    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;
        $customerEmail = $ticket->customer?->email;

        if (! $customerEmail) {
            Log::info('Skipping customer confirmation email: ticket has no customer', [
                'ticket_id' => $ticket->id,
            ]);

            return;
        }

        Log::info('Sending customer confirmation email', [
            'ticket_id' => $ticket->id,
            'customer_email' => $customerEmail,
        ]);

        // Vista previa del mensaje original del cliente (lo que escribió al
        // abrir el ticket) — antes el correo de confirmación solo repetía
        // número/asunto/fecha, sin nada de lo que el cliente realmente envió.
        // Traducido al idioma del cliente si ya se conoce (cliente recurrente
        // con Customer::language ya detectado en un ticket anterior); en el
        // primer contacto todavía no hay idioma detectado y se envía tal cual
        // (mismo criterio que TicketOutboundTranslator::translateForCustomer).
        $messagePreview = Str::limit((string) $ticket->description, 280);
        $translatedPreview = $this->outboundTranslator->translateForCustomer($ticket, $messagePreview);

        [$subject, $content] = TicketMailRenderer::render(
            'helpdesk_tickets.ticket_created',
            [
                'TICKET_NUMBER' => $ticket->ticket_number,
                'TICKET_SUBJECT' => $ticket->subject,
                'SUBMITTED_AT' => $ticket->created_at->format('M d, Y H:i'),
                'MESSAGE_PREVIEW' => nl2br(e($translatedPreview)),
            ],
            'Your ticket has been received — #'.$ticket->ticket_number,
        );

        // Mismo canal/hilo que las notificaciones posteriores del ticket
        // (SendCustomerReplyNotification, SendCustomerStatusNotification) —
        // sin esto este primer correo salía siempre desde el mailer global,
        // no desde el buzón real (p. ej. info@functionbytes.com), y una
        // respuesta directa del cliente a ESTE correo no habría tenido nada
        // con qué engancharse por Message-ID.
        //
        // Nota: si la cola corre en modo 'sync', este listener se ejecuta en
        // el mismo request que crea el ticket, ANTES de que
        // FetchTicketEmailsJob registre el TicketMail entrante — en ese caso
        // resolveChannelForTicket() no encuentra canal todavía y cae al
        // mailer genérico (no rompe nada, solo pierde el remitente exacto).
        // Con cola real (el caso normal aquí) esto no ocurre.
        $channel = $this->channelMailer->resolveChannelForTicket($ticket);
        $mailerName = $channel ? $this->channelMailer->mailerNameFor($channel) : null;
        $fromAddress = $channel['username'] ?? null;
        // Sin '<' '>' al guardar — ver TicketMail::createOutbound().
        $ownMessageId = Str::uuid().'@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost');

        ($mailerName ? Mail::mailer($mailerName) : Mail::mailer())
            ->to($customerEmail, $ticket->customer?->name)
            ->queue(new TicketCreatedMail($ticket, $subject, $content, $fromAddress, $ownMessageId));

        TicketMail::create([
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'direction' => 'outbound',
            'message_id' => $ownMessageId,
            'from' => $fromAddress ?: config('mail.from.address'),
            'to' => $customerEmail,
            'subject' => $subject,
            'body_html' => $content,
            'body_text' => strip_tags($content),
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function failed(TicketCreated $event, \Throwable $exception): void
    {
        Log::error('SendCustomerConfirmation listener failed', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
