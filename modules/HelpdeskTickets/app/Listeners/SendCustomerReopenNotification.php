<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Events\TicketReopened;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

class SendCustomerReopenNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    public function __construct()
    {
        $this->queue = 'notifications';
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

        $translation = $template->translate($langId);
        $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);

        Mail::html($html, fn ($m) => $m->to($ticket->customer->email)->subject($subject));
    }

    public function failed(TicketReopened $event, \Throwable $exception): void
    {
        Log::error('SendCustomerReopenNotification listener failed', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
