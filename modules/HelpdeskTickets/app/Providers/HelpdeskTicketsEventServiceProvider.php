<?php

namespace Modules\HelpdeskTickets\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Events\SlaWarning;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Events\TicketReopened;
use Modules\HelpdeskTickets\Events\TicketResolved;
use Modules\HelpdeskTickets\Events\TicketSlaBreached;
use Modules\HelpdeskTickets\Events\TicketSlaNearBreach;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Events\TicketUpdated;
use Modules\HelpdeskTickets\Listeners\AutoAssignNewTicket;
use Modules\HelpdeskTickets\Listeners\NotifyAgentOfAssignment;
use Modules\HelpdeskTickets\Listeners\NotifyAgentsOnNewTicket;
use Modules\HelpdeskTickets\Listeners\RecalculateSlaPolicy;
use Modules\HelpdeskTickets\Listeners\RecordTicketHistory;
use Modules\HelpdeskTickets\Listeners\RunAiAutoClassify;
use Modules\HelpdeskTickets\Listeners\RunAiSentimentAnalysis;
use Modules\HelpdeskTickets\Listeners\RunAutomationsOnTicketAssigned;
use Modules\HelpdeskTickets\Listeners\RunAutomationsOnTicketClosed;
use Modules\HelpdeskTickets\Listeners\RunAutomationsOnTicketCreated;
use Modules\HelpdeskTickets\Listeners\RunAutomationsOnTicketResolved;
use Modules\HelpdeskTickets\Listeners\RunAutomationsOnTicketStatusChanged;
use Modules\HelpdeskTickets\Listeners\RunAutomationsOnTicketUpdated;
use Modules\HelpdeskTickets\Listeners\SendCustomerConfirmation;
use Modules\HelpdeskTickets\Listeners\SendCustomerReopenNotification;
use Modules\HelpdeskTickets\Listeners\SendCustomerReplyNotification;
use Modules\HelpdeskTickets\Listeners\SendCustomerStatusNotification;
use Modules\HelpdeskTickets\Listeners\SendSlaBreachBroadcastNotification;
use Modules\HelpdeskTickets\Listeners\SendSlaBreachNotification;
use Modules\HelpdeskTickets\Listeners\SendSlaWarningBroadcastNotification;
use Modules\HelpdeskTickets\Listeners\SendSlaWarningNotification;
use Modules\HelpdeskTickets\Listeners\UpdateTicketLastActivity;
use Modules\HelpdeskTickets\Listeners\UpdateTicketOnClose;

class HelpdeskTicketsEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TicketCreated::class => [
            SendCustomerConfirmation::class,
            NotifyAgentsOnNewTicket::class,
            UpdateTicketLastActivity::class,
            RecordTicketHistory::class,
            RunAutomationsOnTicketCreated::class,
            RunAiAutoClassify::class,
            // Auto-asignación global (#78): inerte salvo toggle on.
            AutoAssignNewTicket::class,
        ],
        TicketUpdated::class => [
            RecordTicketHistory::class,
            RunAutomationsOnTicketUpdated::class,
        ],
        TicketClosed::class => [
            UpdateTicketOnClose::class,
            RecordTicketHistory::class,
            RunAutomationsOnTicketClosed::class,
        ],
        TicketReopened::class => [
            RecordTicketHistory::class,
            SendCustomerReopenNotification::class,
        ],
        TicketResolved::class => [
            RunAutomationsOnTicketResolved::class,
        ],
        TicketAssigned::class => [
            RecordTicketHistory::class,
            NotifyAgentOfAssignment::class,
            RunAutomationsOnTicketAssigned::class,
        ],
        TicketStatusChanged::class => [
            SendCustomerStatusNotification::class,
            RecordTicketHistory::class,
            RunAutomationsOnTicketStatusChanged::class,
            RecalculateSlaPolicy::class,
        ],
        MessageAdded::class => [
            SendCustomerReplyNotification::class,
            UpdateTicketLastActivity::class,
            RunAiSentimentAnalysis::class,
        ],
        SlaBreached::class => [
            SendSlaBreachNotification::class,
            RecordTicketHistory::class,
        ],
        SlaWarning::class => [
            SendSlaWarningNotification::class,
            RecordTicketHistory::class,
        ],
        TicketSlaBreached::class => [
            SendSlaBreachBroadcastNotification::class,
        ],
        TicketSlaNearBreach::class => [
            SendSlaWarningBroadcastNotification::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    /**
     * Gate listener registration on the HelpdeskTickets integration toggle.
     *
     * Note: Laravel's base EventServiceProvider registers listeners from
     * `listens()` inside `register()` (via a `booting` callback), not
     * inside `boot()` (which is a no-op). Overriding `boot()` alone would
     * NOT prevent registration, so the guard must live here.
     */
    public function listens(): array
    {
        return helpdesk_tickets_enabled() ? $this->listen : [];
    }
}
