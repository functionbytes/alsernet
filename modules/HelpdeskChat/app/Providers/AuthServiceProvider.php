<?php

namespace Modules\HelpdeskChat\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Automations\AutomationRule;
use Modules\HelpdeskChat\Models\Canneds\Canned;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Contacts\ContactAttribute;
use Modules\HelpdeskChat\Models\Contacts\ContactSegment;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Customers\Customer;
use Modules\HelpdeskChat\Models\Integrations\Integration;
use Modules\HelpdeskChat\Models\Label;
use Modules\HelpdeskChat\Models\Macro;
use Modules\HelpdeskChat\Models\Sla\SlaPolicy;
use Modules\HelpdeskChat\Models\Teams\Team;
use Modules\HelpdeskChat\Models\Teams\TeamRole;
use Modules\HelpdeskChat\Models\Templates\EmailTemplate;
use Modules\HelpdeskChat\Models\Templates\MessageTemplate;
use Modules\HelpdeskChat\Models\Webhook;
use Modules\HelpdeskChat\Policies\AutomationRulePolicy;
use Modules\HelpdeskChat\Policies\CannedResponsePolicy;
use Modules\HelpdeskChat\Policies\ContactPolicy;
use Modules\HelpdeskChat\Policies\ContactSegmentPolicy;
use Modules\HelpdeskChat\Policies\ConversationPolicy;
use Modules\HelpdeskChat\Policies\CustomAttributeDefinitionPolicy;
use Modules\HelpdeskChat\Policies\CustomerPolicy;
use Modules\HelpdeskChat\Policies\EmailTemplatePolicy;
use Modules\HelpdeskChat\Policies\InboxPolicy;
use Modules\HelpdeskChat\Policies\IntegrationPolicy;
use Modules\HelpdeskChat\Policies\LabelPolicy;
use Modules\HelpdeskChat\Policies\MacroPolicy;
use Modules\HelpdeskChat\Policies\MessageTemplatePolicy;
use Modules\HelpdeskChat\Policies\SlaPolicyPolicy;
use Modules\HelpdeskChat\Policies\TeamPolicy;
use Modules\HelpdeskChat\Policies\TeamRolePolicy;
use Modules\HelpdeskChat\Policies\WebhookPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the module.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AutomationRule::class => AutomationRulePolicy::class,
        Canned::class => CannedResponsePolicy::class,
        Contact::class => ContactPolicy::class,
        ContactAttribute::class => CustomAttributeDefinitionPolicy::class,
        ContactSegment::class => ContactSegmentPolicy::class,
        Conversation::class => ConversationPolicy::class,
        Customer::class => CustomerPolicy::class,
        EmailTemplate::class => EmailTemplatePolicy::class,
        Inbox::class => InboxPolicy::class,
        Integration::class => IntegrationPolicy::class,
        Label::class => LabelPolicy::class,
        Macro::class => MacroPolicy::class,
        MessageTemplate::class => MessageTemplatePolicy::class,
        SlaPolicy::class => SlaPolicyPolicy::class,
        Team::class => TeamPolicy::class,
        TeamRole::class => TeamRolePolicy::class,
        Webhook::class => WebhookPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
