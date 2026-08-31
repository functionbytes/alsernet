<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Mail\TicketReplyMail;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Services\TicketChannelMailerService;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

class SendCustomerStatusNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    public function __construct(private readonly TicketChannelMailerService $channelMailer)
    {
        $this->queue = 'notifications';
    }

    public function handle(TicketStatusChanged $event): void
    {
        // Skip if the status did not actually change
        if ($event->newStatus->id === $event->previousStatus->id) {
            return;
        }

        $ticket = $event->ticket;

        if (! $ticket->customer?->email) {
            return;
        }

        $template = MailerTemplate::where('key', 'helpdesk.ticket_status_changed')->first();

        if (! $template || ! $template->is_enabled) {
            Log::warning('helpdesk.ticket_status_changed template not found or disabled — skipping status notification', [
                'ticket_id' => $ticket->id,
            ]);

            return;
        }

        $langId = MailerLang::resolveDefaultId();

        $variables = [
            'CUSTOMER_NAME' => $ticket->customer->name ?? 'Cliente',
            'TICKET_NUMBER' => $ticket->ticket_number,
            'SUBJECT' => $ticket->subject,
            'OLD_STATUS' => $event->previousStatus->name,
            'NEW_STATUS' => $event->newStatus->name,
            'COMPANY_NAME' => config('app.name', 'Soporte'),
        ];

        $html = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

        $translation = $template->translate($langId);
        $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);

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

    public function failed(TicketStatusChanged $event, \Throwable $exception): void
    {
        Log::error('SendCustomerStatusNotification listener failed', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
