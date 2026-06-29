<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

class SendCustomerStatusNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    public function __construct()
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

        Mail::html($html, fn ($m) => $m->to($ticket->customer->email)->subject($subject));
    }
}
