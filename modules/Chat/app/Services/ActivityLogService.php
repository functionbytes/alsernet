<?php

namespace Modules\Chat\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Chat\Models\Conversations\Conversation;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    /**
     * Get activity logs for a conversation with pagination.
     */
    public function getConversationLogs(Conversation $conversation, int $perPage = 20): LengthAwarePaginator
    {
        return Activity::query()
            ->where('subject_type', Conversation::class)
            ->where('subject_id', $conversation->id)
            ->with('causer')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all activity logs for a conversation without pagination.
     */
    public function getAllConversationLogs(Conversation $conversation): Collection
    {
        return Activity::query()
            ->where('subject_type', Conversation::class)
            ->where('subject_id', $conversation->id)
            ->with('causer')
            ->latest()
            ->get();
    }

    /**
     * Get activity logs by event type.
     */
    public function getLogsByEvent(string $eventName, ?int $accountId = null): LengthAwarePaginator
    {
        $query = Activity::query()
            ->where('log_name', 'default')
            ->where('description', $eventName)
            ->with(['causer', 'subject']);

        if ($accountId) {
            $query->whereHas('subject', function ($q) use ($accountId) {
                $q->where('account_id', $accountId);
            });
        }

        return $query->latest()->paginate(50);
    }

    /**
     * Get activity logs for status changes.
     */
    public function getStatusChangeLogs(?int $accountId = null): LengthAwarePaginator
    {
        return $this->getLogsByEvent('conversation_status_changed', $accountId);
    }

    /**
     * Get activity logs for agent reassignments.
     */
    public function getAgentReassignmentLogs(?int $accountId = null): LengthAwarePaginator
    {
        return $this->getLogsByEvent('conversation_agent_reassigned', $accountId);
    }

    /**
     * Get activity logs for macro executions.
     */
    public function getMacroExecutionLogs(?int $accountId = null): LengthAwarePaginator
    {
        return $this->getLogsByEvent('macro_executed', $accountId);
    }

    /**
     * Get activity logs for automation rule triggers.
     */
    public function getAutomationRuleLogs(?int $accountId = null): LengthAwarePaginator
    {
        return $this->getLogsByEvent('automation_rule_triggered', $accountId);
    }

    /**
     * Get activity logs for SLA breaches.
     */
    public function getSlaBreachLogs(?int $accountId = null): LengthAwarePaginator
    {
        return $this->getLogsByEvent('sla_breach_detected', $accountId);
    }

    /**
     * Get activity logs for priority changes.
     */
    public function getPriorityChangeLogs(?int $accountId = null): LengthAwarePaginator
    {
        return $this->getLogsByEvent('conversation_priority_changed', $accountId);
    }

    /**
     * Get activity logs for label changes.
     */
    public function getLabelChangeLogs(?int $accountId = null): LengthAwarePaginator
    {
        return $this->getLogsByEvent('conversation_labels_changed', $accountId);
    }

    /**
     * Get activity logs by user (causer).
     */
    public function getLogsByUser(int $userId, ?int $accountId = null): LengthAwarePaginator
    {
        $query = Activity::query()
            ->where('causer_id', $userId)
            ->where('causer_type', User::class)
            ->with(['causer', 'subject']);

        if ($accountId) {
            $query->whereHas('subject', function ($q) use ($accountId) {
                $q->where('account_id', $accountId);
            });
        }

        return $query->latest()->paginate(50);
    }

    /**
     * Get activity statistics for a date range.
     */
    public function getActivityStatistics(?string $from = null, ?string $to = null, ?int $accountId = null): array
    {
        $query = Activity::query();

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($accountId) {
            $query->whereHas('subject', function ($q) use ($accountId) {
                $q->where('account_id', $accountId);
            });
        }

        return [
            'total_activities' => $query->count(),
            'status_changes' => $query->clone()->where('description', 'conversation_status_changed')->count(),
            'agent_reassignments' => $query->clone()->where('description', 'conversation_agent_reassigned')->count(),
            'macro_executions' => $query->clone()->where('description', 'macro_executed')->count(),
            'automation_triggers' => $query->clone()->where('description', 'automation_rule_triggered')->count(),
            'sla_breaches' => $query->clone()->where('description', 'sla_breach_detected')->count(),
            'priority_changes' => $query->clone()->where('description', 'conversation_priority_changed')->count(),
            'label_changes' => $query->clone()->where('description', 'conversation_labels_changed')->count(),
        ];
    }

    /**
     * Delete old activity logs beyond retention period.
     */
    public function cleanupOldLogs(int $daysToRetain = 90): int
    {
        return Activity::query()
            ->where('created_at', '<', now()->subDays($daysToRetain))
            ->delete();
    }

    /**
     * Format activity log for display.
     */
    public function formatLogEntry(Activity $log): array
    {
        $formatted = [
            'id' => $log->id,
            'event' => $log->description,
            'causer_name' => $log->causer?->name ?? 'System',
            'created_at' => $log->created_at->toDateTimeString(),
            'properties' => $log->properties->toArray(),
        ];

        // Add human-readable description based on event type
        $formatted['human_description'] = match ($log->description) {
            'conversation_status_changed' => sprintf(
                'Status changed from %s to %s',
                $log->properties['old_status_name'] ?? 'Unknown',
                $log->properties['new_status_name'] ?? 'Unknown'
            ),
            'conversation_agent_reassigned' => sprintf(
                'Agent reassigned from %s to %s',
                $log->properties['old_assignee_name'] ?? 'Unassigned',
                $log->properties['new_assignee_name'] ?? 'Unassigned'
            ),
            'conversation_priority_changed' => sprintf(
                'Priority changed from %s to %s',
                $log->properties['old_priority_name'] ?? 'None',
                $log->properties['new_priority_name'] ?? 'None'
            ),
            'macro_executed' => sprintf(
                'Macro "%s" executed with %d actions',
                $log->properties['macro_name'] ?? 'Unknown',
                $log->properties['actions_count'] ?? 0
            ),
            'automation_rule_triggered' => sprintf(
                'Automation rule "%s" triggered by %s event',
                $log->properties['automation_name'] ?? 'Unknown',
                $log->properties['event_name'] ?? 'Unknown'
            ),
            'sla_breach_detected' => sprintf(
                'SLA breach detected: %s for policy "%s"',
                $log->properties['breach_type'] ?? 'Unknown',
                $log->properties['sla_policy_name'] ?? 'Unknown'
            ),
            'conversation_labels_changed' => sprintf(
                'Labels changed (Added: %d, Removed: %d)',
                count($log->properties['added_labels'] ?? []),
                count($log->properties['removed_labels'] ?? [])
            ),
            default => ucfirst(str_replace('_', ' ', $log->description)),
        };

        return $formatted;
    }
}
