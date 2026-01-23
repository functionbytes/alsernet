<?php

namespace Modules\HelpdeskChat\Observers;

use App\Models\CsatSurveyResponse;
use Illuminate\Support\Str;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Services\Automation\AutomationRuleExecutor;
use Modules\HelpdeskChat\Services\SlaService;
use Modules\HelpdeskChat\Services\Webhooks\WebhookDispatcher;

class ConversationObserver
{
    public function __construct(
        protected AutomationRuleExecutor $executor,
        protected WebhookDispatcher $webhookDispatcher,
        protected SlaService $slaService
    ) {}

    /**
     * Handle the Conversation "created" event.
     */
    public function created(Conversation $conversation): void
    {
        $this->executor->execute('conversation_created', $conversation);
        $this->webhookDispatcher->dispatch('conversation_created', $conversation);
    }

    /**
     * Handle the Conversation "updated" event.
     */
    public function updated(Conversation $conversation): void
    {
        // Check what changed to trigger specific events
        if ($conversation->isDirty('status')) {
            $this->executor->execute('conversation_status_changed', $conversation);
            $this->webhookDispatcher->dispatch('conversation_status_changed', $conversation);

            // Check SLA when conversation is resolved
            if ($conversation->status === 'resolved') {
                $this->slaService->checkResolution($conversation);

                // Auto-create CSAT survey when conversation is resolved
                $this->createCsatSurvey($conversation);
            }
        }

        if ($conversation->isDirty('assignee_id')) {
            $this->executor->execute('assignee_changed', $conversation);
            $this->webhookDispatcher->dispatch('assignee_changed', $conversation);
        }

        if ($conversation->isDirty('priority')) {
            $this->executor->execute('priority_changed', $conversation);
            $this->webhookDispatcher->dispatch('priority_changed', $conversation);
        }

        if ($conversation->isDirty('team_id')) {
            $this->executor->execute('team_changed', $conversation);
            $this->webhookDispatcher->dispatch('team_changed', $conversation);
        }

        // General update event
        $this->executor->execute('conversation_updated', $conversation);
        $this->webhookDispatcher->dispatch('conversation_updated', $conversation);
    }

    /**
     * Handle the Conversation "deleted" event.
     */
    public function deleted(Conversation $conversation): void
    {
        // Optionally trigger automation on delete
        // $this->executor->execute('conversation_deleted', $conversation);
    }

    /**
     * Create CSAT survey for resolved conversation.
     */
    protected function createCsatSurvey(Conversation $conversation): void
    {
        // Only create if one doesn't already exist for this conversation
        if ($conversation->csatSurvey()->exists()) {
            return;
        }

        CsatSurveyResponse::create([
            'account_id' => $conversation->account_id,
            'conversation_id' => $conversation->id,
            'contact_id' => $conversation->contact_id,
            'assigned_agent_id' => $conversation->assignee_id,
            'survey_token' => Str::random(32),
        ]);
    }
}
