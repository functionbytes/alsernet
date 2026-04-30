<?php

namespace Modules\Chat\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Chat\Models\Automations\AutomationRule;
use Modules\Chat\Models\Campaigns\Campaign;
use Modules\Chat\Models\Canneds\Canned;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationLabel;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerActivity;
use Modules\Chat\Models\Customers\CustomerAttribute;
use Modules\Chat\Models\Customers\CustomerNote;
use Modules\Chat\Models\Customers\CustomerSegment;
use Modules\Chat\Models\Customers\CustomerView;
use Modules\Chat\Models\Helpcenters\HelpcenterArticle;
use Modules\Chat\Models\Helpcenters\HelpcenterCategory;
use Modules\Chat\Models\Inbox\Inbox;
use Modules\Chat\Models\Integrations\Integration;
use Modules\Chat\Models\Labels\Label;
use Modules\Chat\Models\Macro;
use Modules\Chat\Models\Sla\SlaPolicy;
use Modules\Chat\Models\Teams\Team;
use Modules\Chat\Models\Teams\TeamRole;
use Modules\Chat\Models\Templates\EmailTemplate;
use Modules\Chat\Models\Templates\MessageTemplate;
use Modules\Chat\Models\Webhook;
use Modules\Chat\Policies\AutomationRulePolicy;
use Modules\Chat\Policies\CampaignPolicy;
use Modules\Chat\Policies\CannedResponsePolicy;
use Modules\Chat\Policies\ConversationPolicy;
use Modules\Chat\Policies\CustomAttributeDefinitionPolicy;
use Modules\Chat\Policies\CustomerActivityPolicy;
use Modules\Chat\Policies\CustomerNotePolicy;
use Modules\Chat\Policies\CustomerPolicy;
use Modules\Chat\Policies\CustomerSegmentPolicy;
use Modules\Chat\Policies\CustomerViewPolicy;
use Modules\Chat\Policies\EmailTemplatePolicy;
use Modules\Chat\Policies\HelpcenterArticlePolicy;
use Modules\Chat\Policies\HelpcenterCategoryPolicy;
use Modules\Chat\Policies\InboxPolicy;
use Modules\Chat\Policies\IntegrationPolicy;
use Modules\Chat\Policies\LabelPolicy;
use Modules\Chat\Policies\MacroPolicy;
use Modules\Chat\Policies\MessagePolicy;
use Modules\Chat\Policies\MessageTemplatePolicy;
use Modules\Chat\Policies\SlaPolicyPolicy;
use Modules\Chat\Policies\TeamPolicy;
use Modules\Chat\Policies\TeamRolePolicy;
use Modules\Chat\Policies\WebhookPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the module.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AutomationRule::class => AutomationRulePolicy::class,
        Campaign::class => CampaignPolicy::class,
        Canned::class => CannedResponsePolicy::class,
        CustomerAttribute::class => CustomAttributeDefinitionPolicy::class,
        Conversation::class => ConversationPolicy::class,
        ConversationMessage::class => MessagePolicy::class,
        Customer::class => CustomerPolicy::class,
        CustomerSegment::class => CustomerSegmentPolicy::class,
        EmailTemplate::class => EmailTemplatePolicy::class,
        HelpcenterArticle::class => HelpcenterArticlePolicy::class,
        HelpcenterCategory::class => HelpcenterCategoryPolicy::class,
        Inbox::class => InboxPolicy::class,
        Integration::class => IntegrationPolicy::class,
        ConversationLabel::class => LabelPolicy::class,
        Label::class => LabelPolicy::class,
        Macro::class => MacroPolicy::class,
        MessageTemplate::class => MessageTemplatePolicy::class,
        SlaPolicy::class => SlaPolicyPolicy::class,
        Team::class => TeamPolicy::class,
        TeamRole::class => TeamRolePolicy::class,
        Webhook::class => WebhookPolicy::class,
        CustomerNote::class => CustomerNotePolicy::class,
        CustomerActivity::class => CustomerActivityPolicy::class,
        CustomerView::class => CustomerViewPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
