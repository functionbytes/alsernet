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
use Modules\Helpdesk\Events\ConversationUnsnoozed;
use Modules\Helpdesk\Events\ConversationUpdated;
use Modules\Helpdesk\Events\CsatRatingAnswered;
use Modules\Helpdesk\Events\MentionDetected;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Events\SlaBreached;
use Modules\Helpdesk\Listeners\AnalyzeSentimentOnIncoming;
use Modules\Helpdesk\Listeners\AutoAssignNewConversation;
use Modules\Helpdesk\Listeners\AutoTagFirstMessage;
use Modules\Helpdesk\Listeners\BroadcastConversationMessage;
use Modules\Helpdesk\Listeners\DispatchConversationWebhooks;
use Modules\Helpdesk\Listeners\EnrollCustomerDripOnCsat;
use Modules\Helpdesk\Listeners\EnrollCustomerDripOnTagAdded;
use Modules\Helpdesk\Listeners\EnrollCustomerToDripCampaigns;
use Modules\Helpdesk\Listeners\HandleWithAiAgent;
use Modules\Helpdesk\Listeners\LogActivityOnConversationAssigned;
use Modules\Helpdesk\Listeners\LogActivityOnConversationStatusChanged;
use Modules\Helpdesk\Listeners\LogActivityOnConversationTagAdded;
use Modules\Helpdesk\Listeners\LogActivityOnConversationUnsnoozed;
use Modules\Helpdesk\Listeners\LogActivityOnConversationUpdated;
use Modules\Helpdesk\Listeners\LogConversationCreated;
use Modules\Helpdesk\Listeners\LogConversationUpdated;
use Modules\Helpdesk\Listeners\NotifySlackOnSlaBreached;
use Modules\Helpdesk\Listeners\RespondOffHoursOnConversationCreated;
use Modules\Helpdesk\Listeners\SendAwayAutoReply;
use Modules\Helpdesk\Listeners\SendConversationAssignedNotification;
use Modules\Helpdesk\Listeners\SendEscalationNotification;
use Modules\Helpdesk\Listeners\SendFarewellOnConversationClosed;
use Modules\Helpdesk\Listeners\SendGreetingOnConversationCreated;
use Modules\Helpdesk\Listeners\SendMentionNotification;
use Modules\Helpdesk\Listeners\SendMessageReceivedNotification;
use Modules\Helpdesk\Listeners\SendNewConversationNotification;
use Modules\Helpdesk\Listeners\SendStatusChangedNotification;
use Modules\Helpdesk\Listeners\TriggerWorkflowsOnConversationClosed;
use Modules\Helpdesk\Listeners\TriggerWorkflowsOnConversationCreated;
use Modules\Helpdesk\Listeners\TriggerWorkflowsOnMessageReceived;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ConversationCreated::class => [
            LogConversationCreated::class,
            DispatchConversationWebhooks::class,
            SendNewConversationNotification::class,
            // Auto-assignment — inert unless config helpdesk.auto_assignment.enabled
            // (default false); idempotent (skips already-assigned conversations).
            AutoAssignNewConversation::class,
            // Workflow engine — inert unless an active Workflow matches the trigger.
            TriggerWorkflowsOnConversationCreated::class,
            // Off-hours auto-reply — inert salvo que haya un OffHoursResponse activo
            // para el canal y estemos fuera de horario (ver BusinessHoursService).
            RespondOffHoursOnConversationCreated::class,
            // Bienvenida — mutuamente excluyente con el de arriba: solo actúa EN
            // horario, para no mandar dos mensajes automáticos a la vez.
            SendGreetingOnConversationCreated::class,
        ],
        ConversationAssigned::class => [
            SendConversationAssignedNotification::class,
            LogActivityOnConversationAssigned::class,
        ],
        ConversationMessageCreated::class => [
            BroadcastConversationMessage::class,
            AnalyzeSentimentOnIncoming::class,
            AutoTagFirstMessage::class,
            // Autonomous AI agent — inert unless config helpdesk.ai.agent_enabled
            // (default false); self-guards to incoming customer messages only.
            HandleWithAiAgent::class,
        ],
        MessageReceived::class => [
            SendMessageReceivedNotification::class,
            TriggerWorkflowsOnMessageReceived::class,
            SendAwayAutoReply::class,
        ],
        MentionDetected::class => [
            SendMentionNotification::class,
        ],
        ConversationEscalated::class => [
            SendEscalationNotification::class,
        ],
        ConversationStatusChanged::class => [
            SendStatusChangedNotification::class,
            LogActivityOnConversationStatusChanged::class,
        ],
        ConversationUpdated::class => [
            LogConversationUpdated::class,
            DispatchConversationWebhooks::class,
            LogActivityOnConversationUpdated::class,
        ],
        SlaBreached::class => [
            NotifySlackOnSlaBreached::class,
        ],
        ConversationClosed::class => [
            EnrollCustomerToDripCampaigns::class,
            TriggerWorkflowsOnConversationClosed::class,
            // Despedida — inert salvo que haya un ConversationFarewell activo para
            // el canal (no depende del horario: cerrar es acción del agente).
            SendFarewellOnConversationClosed::class,
        ],
        CsatRatingAnswered::class => [
            EnrollCustomerDripOnCsat::class,
        ],
        ConversationTagAdded::class => [
            EnrollCustomerDripOnTagAdded::class,
            LogActivityOnConversationTagAdded::class,
        ],
        ConversationUnsnoozed::class => [
            LogActivityOnConversationUnsnoozed::class,
        ],
    ];
}
