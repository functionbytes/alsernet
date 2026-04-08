<?php

namespace Modules\Helpdesk\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Helpdesk\Events\MessageAdded;
use Modules\Helpdesk\Events\SlaBreached;
use Modules\Helpdesk\Events\SlaWarning;
use Modules\Helpdesk\Events\TicketAssigned;
use Modules\Helpdesk\Events\TicketClosed;
use Modules\Helpdesk\Events\TicketCreated;
use Modules\Helpdesk\Events\TicketReopened;
use Modules\Helpdesk\Events\TicketStatusChanged;
use Modules\Helpdesk\Listeners\NotifyAgentOfAssignment;
use Modules\Helpdesk\Listeners\RecordTicketHistory;
use Modules\Helpdesk\Listeners\SendCustomerConfirmation;
use Modules\Helpdesk\Listeners\SendSlaBreachNotification;
use Modules\Helpdesk\Listeners\SendSlaWarningNotification;
use Modules\Helpdesk\Listeners\UpdateTicketLastActivity;
use Modules\Helpdesk\Listeners\UpdateTicketOnClose;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TicketCreated::class => [
            SendCustomerConfirmation::class,
            RecordTicketHistory::class,
        ],
        TicketAssigned::class => [
            NotifyAgentOfAssignment::class,
            RecordTicketHistory::class,
        ],
        TicketStatusChanged::class => [
            RecordTicketHistory::class,
            UpdateTicketLastActivity::class,
        ],
        MessageAdded::class => [
            UpdateTicketLastActivity::class,
        ],
        SlaBreached::class => [
            SendSlaBreachNotification::class,
            RecordTicketHistory::class,
        ],
        SlaWarning::class => [
            SendSlaWarningNotification::class,
            RecordTicketHistory::class,
        ],
        TicketClosed::class => [
            UpdateTicketOnClose::class,
            RecordTicketHistory::class,
        ],
        TicketReopened::class => [
            RecordTicketHistory::class,
        ],
    ];
}
