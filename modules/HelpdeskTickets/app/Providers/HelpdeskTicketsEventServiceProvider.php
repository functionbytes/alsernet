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
use Modules\HelpdeskTickets\Events\TicketSlaBreached;
use Modules\HelpdeskTickets\Events\TicketSlaNearBreach;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Events\TicketUpdated;
use Modules\HelpdeskTickets\Listeners\RecalculateSlaPolicy;
use Modules\HelpdeskTickets\Listeners\RecordTicketHistory;
use Modules\HelpdeskTickets\Listeners\RunAiAutoClassify;
use Modules\HelpdeskTickets\Listeners\RunAiSentimentAnalysis;
use Modules\HelpdeskTickets\Listeners\RunAutomationsOnTicketCreated;
use Modules\HelpdeskTickets\Listeners\RunAutomationsOnTicketStatusChanged;
use Modules\HelpdeskTickets\Listeners\SendCustomerConfirmation;
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
            UpdateTicketLastActivity::class,
            RecordTicketHistory::class,
            RunAutomationsOnTicketCreated::class,
            RunAiAutoClassify::class,
        ],
        TicketUpdated::class => [
            RecordTicketHistory::class,
        ],
        TicketClosed::class => [
            UpdateTicketOnClose::class,
            RecordTicketHistory::class,
        ],
        TicketReopened::class => [
            RecordTicketHistory::class,
        ],
        TicketAssigned::class => [
            RecordTicketHistory::class,
        ],
        TicketStatusChanged::class => [
            RecordTicketHistory::class,
            RunAutomationsOnTicketStatusChanged::class,
            RecalculateSlaPolicy::class,
        ],
        MessageAdded::class => [
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
}
