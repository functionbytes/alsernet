<?php

namespace Modules\Chat\Models\Concerns;

use App\Models\User;
use Modules\Chat\Jobs\Conversations\ActivityMessageJob;

trait ActivityMessageHandler
{
    /**
     * Boot the trait.
     */
    public static function bootActivityMessageHandler()
    {
        static::updated(function ($conversation) {
            $conversation->createActivityMessages();
        });
    }

    /**
     * Create activity messages for conversation changes.
     */
    protected function createActivityMessages()
    {
        $this->createStatusChangeActivity();
        $this->createPriorityChangeActivity();
        $this->createAssigneeChangeActivity();
        $this->createTeamChangeActivity();
        $this->createLabelChangeActivity();
        $this->createSlaPolicyChangeActivity();
    }

    /**
     * Create activity message for status change.
     */
    protected function createStatusChangeActivity()
    {
        if (! $this->wasChanged('status')) {
            return;
        }

        $oldStatus = $this->getOriginal('status');
        $newStatus = $this->status;
        $userName = auth()->user()->name ?? 'System';

        $content = match ($newStatus) {
            'open' => "{$userName} reopened this conversation",
            'resolved' => "{$userName} resolved this conversation",
            'pending' => "{$userName} marked this conversation as pending",
            default => "{$userName} changed status to {$newStatus}",
        };

        $this->createActivityMessage($content);
    }

    /**
     * Create activity message for priority change.
     */
    protected function createPriorityChangeActivity()
    {
        if (! $this->wasChanged('priority')) {
            return;
        }

        $newPriority = $this->priority ?? 'none';
        $userName = auth()->user()->name ?? 'System';

        $content = "{$userName} changed priority to {$newPriority}";

        $this->createActivityMessage($content);
    }

    /**
     * Create activity message for assignee change.
     */
    protected function createAssigneeChangeActivity()
    {
        if (! $this->wasChanged('assignee_id')) {
            return;
        }

        $userName = auth()->user()->name ?? 'System';

        if ($this->assignee_id) {
            $assigneeName = $this->assignee->name ?? 'Unknown';

            if (auth()->check() && $this->assignee_id === auth()->id()) {
                $content = "{$userName} assigned this conversation to themselves";
            } else {
                $content = "{$userName} assigned this conversation to {$assigneeName}";
            }
        } else {
            $content = "{$userName} unassigned this conversation";
        }

        $this->createActivityMessage($content);
    }

    /**
     * Create activity message for team change.
     */
    protected function createTeamChangeActivity()
    {
        if (! $this->wasChanged('team_id')) {
            return;
        }

        $userName = auth()->user()->name ?? 'System';

        if ($this->team_id) {
            $teamName = $this->team->name ?? 'Unknown';
            $content = "{$userName} assigned this conversation to team {$teamName}";
        } else {
            $content = "{$userName} removed team assignment";
        }

        $this->createActivityMessage($content);
    }

    /**
     * Create activity message for label changes.
     */
    protected function createLabelChangeActivity()
    {
        if (! $this->wasChanged('cached_label_list')) {
            return;
        }

        $oldLabels = $this->getOriginal('cached_label_list')
            ? explode(',', $this->getOriginal('cached_label_list'))
            : [];
        $newLabels = $this->cached_label_list
            ? explode(',', $this->cached_label_list)
            : [];

        $addedLabels = array_diff($newLabels, $oldLabels);
        $removedLabels = array_diff($oldLabels, $newLabels);

        $userName = auth()->user()->name ?? 'System';

        foreach ($addedLabels as $label) {
            $content = "{$userName} added label '{$label}'";
            $this->createActivityMessage($content);
        }

        foreach ($removedLabels as $label) {
            $content = "{$userName} removed label '{$label}'";
            $this->createActivityMessage($content);
        }
    }

    /**
     * Create activity message for SLA policy change.
     */
    protected function createSlaPolicyChangeActivity()
    {
        if (! $this->wasChanged('sla_id')) {
            return;
        }

        $userName = auth()->user()->name ?? 'System';

        if ($this->sla_id) {
            $policyName = $this->slaPolicy->name ?? 'Unknown';
            $content = "{$userName} applied SLA policy '{$policyName}'";
        } else {
            $content = "{$userName} removed SLA policy";
        }

        $this->createActivityMessage($content);
    }

    /**
     * Create an activity message using background job.
     */
    protected function createActivityMessage(string $content)
    {
        $senderId = auth()->check() ? auth()->id() : $this->assignee_id;
        $senderType = User::class;

        $messageParams = [
            'account_id' => $this->account_id,
            'inbox_id' => $this->inbox_id,
            'sender_id' => $senderId,
            'sender_type' => $senderType,
            'message_type' => 'activity',
            'content_type' => 'text',
            'content' => $content,
            'private' => false,
        ];

        // Queue the job to create activity message
        ActivityMessageJob::dispatch($this, $messageParams);
    }
}
