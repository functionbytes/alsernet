<?php

namespace Modules\Chat\Services\Automation;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Chat\Models\Automations\Automation;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Teams\Team;
use Modules\Chat\Notifications\AutomationTeamEmailNotification;
use Modules\Chat\Services\Conversations\ConversationLabelService;

class AutomationRuleExecutor
{
    /**
     * Initialize the automation rule executor.
     *
     * @param  ConversationLabelService  $labelService  Service for managing conversation labels
     */
    public function __construct(
        protected ConversationLabelService $labelService
    ) {}

    /**
     * Execute all applicable automation rules for a conversation event.
     *
     * Finds all active rules matching the event name, evaluates their conditions
     * against the conversation, and executes actions for matching rules.
     * Eagerly loads required relationships to prevent N+1 queries.
     *
     * @param  string  $eventName  The automation event trigger name
     * @param  Conversation  $conversation  The conversation context for rule evaluation
     */
    public function execute(string $eventName, Conversation $conversation): void
    {
        // Eager load relationships to prevent N+1 queries during condition evaluation
        $conversation->load(['customer', 'status', 'priority', 'assignee', 'team', 'inbox']);

        $rules = Automation::where('account_id', $conversation->account_id)
            ->where('event_name', $eventName)
            ->where('active', true)
            ->get();

        foreach ($rules as $rule) {
            if ($this->evaluateConditions($rule->conditions, $conversation)) {
                $this->executeActions($rule->actions, $conversation);

                // Log automation rule trigger
                activity()
                    ->performedOn($conversation)
                    ->withProperties([
                        'automation_id' => $rule->id,
                        'automation_name' => $rule->name,
                        'event_name' => $eventName,
                        'conditions_matched' => $rule->conditions,
                        'actions_executed' => array_column($rule->actions, 'action'),
                    ])
                    ->log('automation_rule_triggered');
            }
        }
    }

    /**
     * Evaluate if all rule conditions match for a conversation.
     *
     * Uses AND logic - all conditions must match for the rule to apply.
     * Returns true if no conditions specified.
     *
     * @param  array  $conditions  Array of condition specifications
     * @param  Conversation  $conversation  The conversation to evaluate against
     * @return bool True if all conditions match, false otherwise
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
     * Evaluate a single automation condition.
     *
     * Supports operators: equals, not_equals, contains, not_contains, is_present,
     * is_not_present, greater_than, less_than.
     *
     * @param  array  $condition  Condition specification with 'attribute', 'operator', 'value' keys
     * @param  Conversation  $conversation  The conversation to evaluate
     * @return bool True if the condition matches, false otherwise
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
     * Get the value of an attribute from a conversation.
     *
     * Supports built-in attributes (status, priority, inbox_id, team_id, assignee_id,
     * contact_name, contact_email) and custom attributes.
     *
     * @param  string  $attribute  The attribute name to retrieve
     * @param  Conversation  $conversation  The conversation to get the attribute from
     * @return mixed The attribute value, or null if not found
     */
    protected function getAttributeValue(string $attribute, Conversation $conversation): mixed
    {
        return match ($attribute) {
            'status' => $conversation->status,
            'priority' => $conversation->priority,
            'inbox_id' => $conversation->inbox_id,
            'team_id' => $conversation->team_id,
            'assignee_id' => $conversation->assignee_id,
            'contact_name' => $conversation->customer->name ?? null,
            'contact_email' => $conversation->customer->email ?? null,
            default => $conversation->custom_attributes[$attribute] ?? null,
        };
    }

    /**
     * Execute all actions in an automation rule.
     *
     * @param  array  $actions  Array of action specifications
     * @param  Conversation  $conversation  The conversation to perform actions on
     */
    protected function executeActions(array $actions, Conversation $conversation): void
    {
        foreach ($actions as $action) {
            $this->executeAction($action, $conversation);
        }
    }

    /**
     * Execute a single automation action.
     *
     * Supports actions: assign_agent, assign_team, change_status, change_priority,
     * add_label, send_email_to_team, send_message.
     *
     * @param  array  $action  Action specification with 'action' and 'value' keys
     * @param  Conversation  $conversation  The conversation to perform the action on
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
     * Add a label to a conversation.
     *
     * Delegates to ConversationLabelService for label management.
     *
     * @param  Conversation  $conversation  The conversation to add the label to
     * @param  string  $labelTitle  The title/name of the label to add
     */
    protected function addLabel(Conversation $conversation, string $labelTitle): void
    {
        $this->labelService->addLabel($conversation, $labelTitle);
    }

    /**
     * Send email notification to team members about the conversation.
     *
     * Logs warnings if team ID is missing or team not found. Catches and logs
     * individual send failures without stopping other members from being notified.
     *
     * @param  Conversation  $conversation  The conversation context for the email
     * @param  array  $emailData  Email data with 'team_id', 'subject', 'message' keys
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
        $team = Team::with('members')->find($teamId);

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
     * Send a system-generated message in a conversation.
     *
     * Creates an outgoing message without a sender (null sender_id) to indicate
     * it was generated by automation rather than a team member.
     *
     * @param  Conversation  $conversation  The conversation to send the message in
     * @param  string  $content  The message content/body
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
