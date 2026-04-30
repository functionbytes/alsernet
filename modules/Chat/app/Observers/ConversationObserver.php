<?php

namespace Modules\Chat\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\Chat\Events\ConversationAssigned;
use Modules\Chat\Events\ConversationCreated;
use Modules\Chat\Events\ConversationStatusChanged;
use Modules\Chat\Events\ConversationUpdated;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationPriority;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Teams\Team;
use Modules\Chat\Services\Analytics\DashboardMetricsService;
use Modules\Chat\Services\ConversationStatsService;

class ConversationObserver
{
    public function created(Conversation $conversation): void
    {
        ConversationCreated::dispatch($conversation);
        $this->invalidateDashboardCache($conversation->account_id);
    }

    public function updated(Conversation $conversation): void
    {
        $specificEventFired = false;

        if ($conversation->isDirty('status_id')) {
            $this->logStatusChange($conversation);
            ConversationStatusChanged::dispatch(
                $conversation,
                (string) $conversation->getOriginal('status_id'),
                (string) $conversation->status_id
            );
            $specificEventFired = true;
        }

        if ($conversation->isDirty('assignee_id')) {
            $this->logAssigneeChange($conversation);
            ConversationAssigned::dispatch(
                $conversation,
                $conversation->assignee_id ? User::find($conversation->assignee_id) : null
            );
            $specificEventFired = true;
        }

        if ($conversation->isDirty('priority_id')) {
            $this->logPriorityChange($conversation);
            ConversationUpdated::dispatch($conversation, 'priority_changed');
            $specificEventFired = true;
        }

        if ($conversation->isDirty('team_id')) {
            $this->logTeamChange($conversation);
            ConversationUpdated::dispatch($conversation, 'team_changed');
            $specificEventFired = true;
        }

        if ($conversation->isDirty('cached_label_list')) {
            $this->logLabelChange($conversation);
        }

        if (! $specificEventFired) {
            ConversationUpdated::dispatch($conversation, 'conversation_updated');
        }

        $this->invalidateDashboardCache($conversation->account_id);
    }

    public function deleted(Conversation $conversation): void
    {
        $this->invalidateDashboardCache($conversation->account_id);
    }

    private function invalidateDashboardCache(int $accountId): void
    {
        (new DashboardMetricsService($accountId))->invalidateCache();
        (new ConversationStatsService($accountId))->invalidateAllCaches();
        $this->invalidateIndexServiceCaches($accountId);
    }

    /**
     * Invalidate ConversationIndexService caches (inboxes, teams, labels).
     */
    private function invalidateIndexServiceCaches(int $accountId): void
    {
        $keys = [
            "chat:inboxes:{$accountId}",
            "chat:teams:{$accountId}",
            "chat:labels:{$accountId}",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    private function logStatusChange(Conversation $conversation): void
    {
        $oldStatusId = $conversation->getOriginal('status_id');

        activity()
            ->performedOn($conversation)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_status_id' => $oldStatusId,
                'new_status_id' => $conversation->status_id,
                'old_status_name' => $oldStatusId ? ConversationStatus::find($oldStatusId)?->name : null,
                'new_status_name' => $conversation->status?->name,
            ])
            ->log('conversation_status_changed');
    }

    private function logAssigneeChange(Conversation $conversation): void
    {
        $oldAssigneeId = $conversation->getOriginal('assignee_id');

        activity()
            ->performedOn($conversation)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_assignee_id' => $oldAssigneeId,
                'new_assignee_id' => $conversation->assignee_id,
                'old_assignee_name' => $oldAssigneeId ? User::find($oldAssigneeId)?->name : null,
                'new_assignee_name' => $conversation->assignee?->name,
            ])
            ->log('conversation_agent_reassigned');
    }

    private function logPriorityChange(Conversation $conversation): void
    {
        $oldPriorityId = $conversation->getOriginal('priority_id');

        activity()
            ->performedOn($conversation)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_priority_id' => $oldPriorityId,
                'new_priority_id' => $conversation->priority_id,
                'old_priority_name' => $oldPriorityId ? ConversationPriority::find($oldPriorityId)?->name : null,
                'new_priority_name' => $conversation->priority?->name,
            ])
            ->log('conversation_priority_changed');
    }

    private function logTeamChange(Conversation $conversation): void
    {
        $oldTeamId = $conversation->getOriginal('team_id');

        activity()
            ->performedOn($conversation)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_team_id' => $oldTeamId,
                'new_team_id' => $conversation->team_id,
                'old_team_name' => $oldTeamId ? Team::find($oldTeamId)?->name : null,
                'new_team_name' => $conversation->team?->name,
            ])
            ->log('conversation_team_changed');
    }

    private function logLabelChange(Conversation $conversation): void
    {
        $oldLabels = $conversation->getOriginal('cached_label_list') ?? '';
        $newLabels = $conversation->cached_label_list ?? '';

        $oldLabelArray = $oldLabels ? explode(',', $oldLabels) : [];
        $newLabelArray = $newLabels ? explode(',', $newLabels) : [];
        $addedLabels = array_diff($newLabelArray, $oldLabelArray);
        $removedLabels = array_diff($oldLabelArray, $newLabelArray);

        if (empty($addedLabels) && empty($removedLabels)) {
            return;
        }

        activity()
            ->performedOn($conversation)
            ->causedBy(auth()->user())
            ->withProperties([
                'added_labels' => array_values($addedLabels),
                'removed_labels' => array_values($removedLabels),
                'old_labels' => $oldLabelArray,
                'new_labels' => $newLabelArray,
            ])
            ->log('conversation_labels_changed');
    }
}
