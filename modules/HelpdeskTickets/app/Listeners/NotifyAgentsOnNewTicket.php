<?php

namespace Modules\HelpdeskTickets\Listeners;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

class NotifyAgentsOnNewTicket implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public array $backoff = [60, 120];

    public function __construct()
    {
        $this->queue = 'notifications';
    }

    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;

        $template = MailerTemplate::where('key', 'helpdesk.new_ticket_agent')->first();

        if (! $template || ! $template->is_enabled) {
            Log::warning('helpdesk.new_ticket_agent template not found or disabled — skipping agent notification', [
                'ticket_id' => $ticket->id,
            ]);

            return;
        }

        $agents = $this->resolveAgents();

        if ($agents->isEmpty()) {
            Log::info('No active agents found — skipping new ticket agent notification', [
                'ticket_id' => $ticket->id,
            ]);

            return;
        }

        if ($agents->count() > 20) {
            Log::warning('More than 20 agents found for new ticket notification — limiting to 20 to prevent spam', [
                'ticket_id' => $ticket->id,
                'total_agents' => $agents->count(),
            ]);

            $agents = $agents->take(20);
        }

        $langId = MailerLang::resolveDefaultId();

        $ticket->loadMissing('customer');

        foreach ($agents as $agent) {
            if (! $agent->email) {
                continue;
            }

            $variables = [
                'AGENT_NAME' => $agent->name,
                'TICKET_NUMBER' => $ticket->ticket_number,
                'SUBJECT' => $ticket->subject,
                'CUSTOMER_NAME' => $ticket->customer?->name ?? 'Cliente',
                'PRIORITY' => $ticket->priority ?? 'Normal',
                'SOURCE' => $ticket->source ?? 'Portal',
                'COMPANY_NAME' => config('app.name', 'Soporte'),
            ];

            $html = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            $translation = $template->translate($langId);
            $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);

            Mail::html($html, fn ($m) => $m->to($agent->email)->subject($subject));
        }
    }

    public function failed(TicketCreated $event, \Throwable $exception): void
    {
        Log::error('NotifyAgentsOnNewTicket listener failed', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }

    private function resolveAgents()
    {
        $agents = User::whereHas('roles', fn ($q) => $q->where('name', 'helpdesk-agent'))
            ->get();

        if ($agents->isNotEmpty()) {
            return $agents;
        }

        return User::permission('helpdesk.tickets.view')
            ->limit(10)
            ->get();
    }
}
