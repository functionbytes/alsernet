<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

class SendCustomerReplyNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    public function __construct()
    {
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

        $variables = [
            'CUSTOMER_NAME' => $ticket->customer->name ?? 'Cliente',
            'TICKET_NUMBER' => $ticket->ticket_number,
            'SUBJECT' => $ticket->subject,
            'AGENT_NAME' => $item->user?->name ?? 'Soporte',
            'COMPANY_NAME' => config('app.name', 'Soporte'),
        ];

        $html = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

        $translation = $template->translate($langId);
        $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);

        Mail::html($html, fn ($m) => $m->to($ticket->customer->email)->subject($subject));

        // Record outbound email for traceability
        TicketMail::create([
            'ticket_id' => $ticket->id,
            'ticket_item_id' => $item->id,
            'direction' => 'outbound',
            'from' => config('mail.from.address'),
            'to' => $ticket->customer->email,
            'subject' => $subject,
            'body_html' => $html,
            'body_text' => strip_tags($html),
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
