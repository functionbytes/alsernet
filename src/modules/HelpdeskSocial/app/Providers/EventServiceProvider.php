<?php

namespace Modules\HelpdeskSocial\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\HelpdeskSocial\Events\IntentClassified;
use Modules\HelpdeskSocial\Events\SocialCommentEscalated;
use Modules\HelpdeskSocial\Events\SocialCommentReceived;
use Modules\HelpdeskSocial\Events\SocialCommentReplied;
use Modules\HelpdeskSocial\Listeners\AnalyzeSentimentListener;
use Modules\HelpdeskSocial\Listeners\ApplySlaPolicyListener;
use Modules\HelpdeskSocial\Listeners\AutoAssignCommentListener;
use Modules\HelpdeskSocial\Listeners\AutoTagOnIntentClassified;
use Modules\HelpdeskSocial\Listeners\BroadcastSocialComment;
use Modules\HelpdeskSocial\Listeners\CreateTicketOnSocialEscalation;
use Modules\HelpdeskSocial\Listeners\LogSocialCommentReply;
use Modules\HelpdeskSocial\Listeners\SendNewSocialCommentNotification;
use Modules\HelpdeskSocial\Listeners\SendSocialEscalationNotification;
use Modules\HelpdeskSocial\Listeners\SendSocialWebPush;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SocialCommentReceived::class => [
            BroadcastSocialComment::class,
            SendNewSocialCommentNotification::class,
            SendSocialWebPush::class,
            AnalyzeSentimentListener::class,
            ApplySlaPolicyListener::class,
            AutoAssignCommentListener::class,
        ],
        IntentClassified::class => [
            AutoTagOnIntentClassified::class,
        ],
        SocialCommentReplied::class => [
            LogSocialCommentReply::class,
            SendSocialWebPush::class,
        ],
        SocialCommentEscalated::class => [
            SendSocialEscalationNotification::class,
            SendSocialWebPush::class,
            CreateTicketOnSocialEscalation::class,
        ],
    ];
}
