<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

class NotifyAgentsOnNewTicket implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public array $backoff = [60, 120];

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

    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;

        // Apagado por defecto: hasta 3-sep-2026 esto avisaba a TODO usuario con
        // el rol helpdesk-agent, y esa lista estaba contaminada con 61 usuarios
        // de fixture de test en la BD compartida de dev (ver
        // reference_helpdesk_agent_role_leaked_to_test_fixtures) — cada ticket
        // nuevo mandaba ~20 correos, la mayoría a direcciones falsas. Se deja
        // el ajuste apagado hasta que un admin lo valide explícitamente desde
        // Ajustes → Tickets → General.
        if (! filter_var(Setting::get('tickets.notify_agents_new_ticket', false), FILTER_VALIDATE_BOOLEAN)) {
            Log::info('Notificación de nuevo ticket a agentes desactivada en ajustes — se omite', [
                'ticket_id' => $ticket->id,
            ]);

            return;
        }

        $template = MailerTemplate::where('key', 'helpdesk.new_ticket_agent')->first();

        if (! $template || ! $template->is_enabled) {
            Log::warning('helpdesk.new_ticket_agent template not found or disabled — skipping agent notification', [
                'ticket_id' => $ticket->id,
            ]);

            return;
        }

        $agents = $this->resolveAgents($ticket);

        if ($agents->isEmpty()) {
            Log::info('Sin grupo resuelto para el ticket (ni group_id propio ni grupo por defecto de su categoría) — se omite la notificación', [
                'ticket_id' => $ticket->id,
                'category_id' => $ticket->category_id,
                'group_id' => $ticket->group_id,
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

    /**
     * Miembros del grupo responsable del ticket: el suyo propio si ya está
     * asignado (group_id), si no el grupo por defecto de su categoría
     * (TicketCategory::getDefaultGroup(), antes sin usar en ningún sitio).
     * Ya NO se avisa a todo el rol helpdesk-agent ni se cae a un permiso
     * genérico — sin categoría/grupo resuelto, no se notifica a nadie (ver
     * el comentario en handle()).
     */
    private function resolveAgents(Ticket $ticket)
    {
        $ticket->loadMissing('category');

        $groupId = $ticket->group_id ?: $ticket->category?->getDefaultGroup()?->id;

        if (! $groupId) {
            return collect();
        }

        return TicketGroup::find($groupId)?->users()->get() ?? collect();
    }
}
