<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Events\TicketReopened;
use Modules\HelpdeskTickets\Mail\TicketReplyMail;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Services\TicketChannelMailerService;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

class SendCustomerReopenNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    public function __construct(private readonly TicketChannelMailerService $channelMailer) {}

    /**
     * La cola va en viaQueue() y no en el constructor: el Dispatcher lee las
     * opciones del listener sobre una instancia creada SIN constructor, así
     * que un $this->queue de ahí nunca se aplicaba y el job caía en
     * 'default' — cola que ningún worker atiende. Ver SendCustomerConfirmation.
     */
    public function viaQueue(): string
    {
        return 'notifications';
    }

    public function handle(TicketReopened $event): void
    {
        $ticket = $event->ticket;

        $template = MailerTemplate::where('key', 'helpdesk.ticket_reopened')->first();

        if (! $template || ! $template->is_enabled) {
            Log::warning('helpdesk.ticket_reopened template not found or disabled — skipping reopen notification', [
                'ticket_id' => $ticket->id,
            ]);

            return;
        }

        $ticket->loadMissing('customer');

        if (! $ticket->customer?->email) {
            return;
        }

        $langId = MailerLang::resolveDefaultId();

        $variables = [
            'CUSTOMER_NAME' => $ticket->customer->name ?? 'Cliente',
            'TICKET_NUMBER' => $ticket->ticket_number,
            'SUBJECT' => $ticket->subject,
            'COMPANY_NAME' => config('app.name', 'Soporte'),
        ];

        $html = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

        // El asunto de la plantilla ("Tu ticket #... fue reabierto") no
        // menciona el asunto original ni "Re:" — Gmail abría un hilo nuevo en
        // vez de seguir la conversación. Ver
        // TicketChannelMailerService::threadSubject().
        $subject = $this->channelMailer->threadSubject($ticket);

        // Mismo canal/hilo que SendCustomerReplyNotification — ver
        // TicketChannelMailerService.
        $channel = $this->channelMailer->resolveChannelForTicket($ticket);
        $mailerName = $channel ? $this->channelMailer->mailerNameFor($channel) : null;
        $fromAddress = $channel['username'] ?? null;
        $inReplyTo = $this->channelMailer->lastInboundMessageId($ticket);
        $ownMessageId = Str::uuid().'@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost');

        $mailable = new TicketReplyMail($ticket, $subject, $html, $fromAddress, $ownMessageId, $inReplyTo);

        ($mailerName ? Mail::mailer($mailerName) : Mail::mailer())
            ->to($ticket->customer->email)
            ->send($mailable);

        TicketMail::create([
            'ticket_id' => $ticket->id,
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

    public function failed(TicketReopened $event, \Throwable $exception): void
    {
        Log::error('SendCustomerReopenNotification listener failed', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
