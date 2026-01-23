<?php

namespace Modules\HelpdeskChat\Services\Automation;

use App\Models\Automations\AutomationRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Notifications\AutomationTeamEmailNotification;

class AutomationRuleExecutor
{
    /**
     * Execute automation rules for a given event
     */
    public function execute(string $eventName, Conversation $conversation): void
    {
        $rules = AutomationRule::where('account_id', $conversation->account_id)
            ->where('event_name', $eventName)
            ->where('active', true)
            ->get();

        foreach ($rules as $rule) {
            if ($this->evaluateConditions($rule->conditions, $conversation)) {
                $this->executeActions($rule->actions, $conversation);
            }
        }
    }

    /**
     * Evaluate if conditions match
     */
    protected function evaluateConditions(array $conditions, Conversation $conversation): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! $this->evaluateCondition($condition, $conversation)) {
                return false; // All conditions must match (AND logic)
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateCondition(array $condition, Conversation $conversation): bool
    {
        $attribute = $condition['attribute'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        if (! $attribute || ! $operator) {
            return false;
        }

        $actualValue = $this->getAttributeValue($attribute, $conversation);

        return match ($operator) {
            'equals' => $actualValue == $value,
            'not_equals' => $actualValue != $value,
            'contains' => str_contains((string) $actualValue, (string) $value),
            'not_contains' => ! str_contains((string) $actualValue, (string) $value),
            'is_present' => ! empty($actualValue),
            'is_not_present' => empty($actualValue),
            'greater_than' => $actualValue > $value,
            'less_than' => $actualValue < $value,
            default => false,
        };
    }

    /**
     * Get attribute value from conversation
     */
    protected function getAttributeValue(string $attribute, Conversation $conversation): mixed
    {
        return match ($attribute) {
            'status' => $conversation->status,
            'priority' => $conversation->priority,
            'inbox_id' => $conversation->inbox_id,
            'team_id' => $conversation->team_id,
            'assignee_id' => $conversation->assignee_id,
            'contact_name' => $conversation->contact->name ?? null,
            'contact_email' => $conversation->contact->email ?? null,
            default => $conversation->custom_attributes[$attribute] ?? null,
        };
    }

    /**
     * Execute actions on conversation
     */
    protected function executeActions(array $actions, Conversation $conversation): void
    {
        foreach ($actions as $action) {
            $this->executeAction($action, $conversation);
        }
    }

    /**
     * Execute a single action
     */
    public function executeAction(array $action, Conversation $conversation): void
    {
        $actionType = $action['action'] ?? null;
        $value = $action['value'] ?? null;

        match ($actionType) {
            'assign_agent' => $conversation->update(['assignee_id' => $value]),
            'assign_team' => $conversation->update(['team_id' => $value]),
            'change_status' => $conversation->update(['status' => $value]),
            'change_priority' => $conversation->update(['priority' => $value]),
            'add_label' => $this->addLabel($conversation, $value),
            'send_email_to_team' => $this->sendEmailToTeam($conversation, $value),
            'send_message' => $this->sendMessage($conversation, $value),
            default => null,
        };
    }

    /**
     * Add label to conversation
     */
    protected function addLabel(Conversation $conversation, string $labelTitle): void
    {
        $currentLabels = $conversation->cached_label_list
            ? explode(',', $conversation->cached_label_list)
            : [];

        if (! in_array($labelTitle, $currentLabels)) {
            $currentLabels[] = $labelTitle;
            $conversation->update([
                'cached_label_list' => implode(',', array_filter($currentLabels)),
            ]);
        }
    }

    /**
     * Send email to team
     */
    protected function sendEmailToTeam(Conversation $conversation, array $emailData): void
    {
        $teamId = $emailData['team_id'] ?? null;
        $subject = $emailData['subject'] ?? 'Automation Notification';
        $message = $emailData['message'] ?? 'Automation rule triggered';

        if (! $teamId) {
            Log::warning('Team ID not provided for email automation', [
                'conversation_id' => $conversation->id,
            ]);

            return;
        }

        // Get team members
        $team = \App\Models\Team::with('members')->find($teamId);

        if (! $team || $team->members->isEmpty()) {
            Log::warning('Team not found or has no members', [
                'team_id' => $teamId,
                'conversation_id' => $conversation->id,
            ]);

            return;
        }

        // Send notification to each team member
        foreach ($team->members as $member) {
            try {
                $member->notify(new AutomationTeamEmailNotification(
                    $conversation,
                    $subject,
                    $message
                ));
            } catch (\Exception $e) {
                Log::error('Failed to send automation email', [
                    'user_id' => $member->id,
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Automation email sent to team', [
            'team_id' => $teamId,
            'conversation_id' => $conversation->id,
            'members_count' => $team->members->count(),
        ]);
    }

    /**
     * Send message in conversation
     */
    protected function sendMessage(Conversation $conversation, string $content): void
    {
        $conversation->messages()->create([
            'account_id' => $conversation->account_id,
            'inbox_id' => $conversation->inbox_id,
            'sender_id' => null, // System message
            'sender_type' => null,
            'message_type' => 'outgoing',
            'content' => $content,
            'content_type' => 'text',
            'private' => false,
        ]);
    }
}
