<?php

namespace Modules\Helpdesk\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Helpdesk\Events\ConversationAssigned;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationEscalated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\ConversationStatusChanged;
use Modules\Helpdesk\Events\ConversationTagAdded;
use Modules\Helpdesk\Events\ConversationUpdated;
use Modules\Helpdesk\Events\CsatRatingAnswered;
use Modules\Helpdesk\Events\MentionDetected;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Events\SlaBreached;
use Modules\Helpdesk\Listeners\AnalyzeSentimentOnIncoming;
use Modules\Helpdesk\Listeners\AutoTagFirstMessage;
use Modules\Helpdesk\Listeners\BroadcastConversationMessage;
use Modules\Helpdesk\Listeners\DispatchConversationWebhooks;
use Modules\Helpdesk\Listeners\EnrollCustomerDripOnCsat;
use Modules\Helpdesk\Listeners\EnrollCustomerDripOnTagAdded;
use Modules\Helpdesk\Listeners\EnrollCustomerToDripCampaigns;
use Modules\Helpdesk\Listeners\LogConversationCreated;
use Modules\Helpdesk\Listeners\LogConversationUpdated;
use Modules\Helpdesk\Listeners\NotifySlackOnSlaBreached;
use Modules\Helpdesk\Listeners\SendConversationAssignedNotification;
use Modules\Helpdesk\Listeners\SendEscalationNotification;
use Modules\Helpdesk\Listeners\SendMentionNotification;
use Modules\Helpdesk\Listeners\SendMessageReceivedNotification;
use Modules\Helpdesk\Listeners\SendNewConversationNotification;
use Modules\Helpdesk\Listeners\SendStatusChangedNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ConversationCreated::class => [
            LogConversationCreated::class,
            DispatchConversationWebhooks::class,
            SendNewConversationNotification::class,
        ],
        ConversationAssigned::class => [
            SendConversationAssignedNotification::class,
        ],
        ConversationMessageCreated::class => [
            BroadcastConversationMessage::class,
            AnalyzeSentimentOnIncoming::class,
            AutoTagFirstMessage::class,
        ],
        MessageReceived::class => [
            SendMessageReceivedNotification::class,
        ],
        MentionDetected::class => [
            SendMentionNotification::class,
        ],
        ConversationEscalated::class => [
            SendEscalationNotification::class,
        ],
        ConversationStatusChanged::class => [
            SendStatusChangedNotification::class,
        ],
        ConversationUpdated::class => [
            LogConversationUpdated::class,
            DispatchConversationWebhooks::class,
        ],
        SlaBreached::class => [
            NotifySlackOnSlaBreached::class,
        ],
        ConversationClosed::class => [
            EnrollCustomerToDripCampaigns::class,
        ],
        CsatRatingAnswered::class => [
            EnrollCustomerDripOnCsat::class,
        ],
        ConversationTagAdded::class => [
            EnrollCustomerDripOnTagAdded::class,
        ],
    ];
}
