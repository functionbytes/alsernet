<?php

namespace Modules\Helpdesk\Services\Conversations;

use App\Models\User;
use Carbon\Carbon;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

class ActivityMessageService
{
    public function logAssigned(Conversation $conversation, User $assignee, ?User $by = null): ConversationItem
    {
        $text = $by && $by->id !== $assignee->id
            ? "La conversación fue asignada a {$assignee->name} por {$by->name}"
            : "La conversación fue asignada a {$assignee->name}";

        return $this->createActivity($conversation, 'assigned', $text, [
            'assignee_id' => $assignee->id,
            'assignee_name' => $assignee->name,
        ], $by);
    }

    public function logUnassigned(Conversation $conversation, ?User $by = null): ConversationItem
    {
        $text = $by
            ? "La conversación fue desasignada por {$by->name}"
            : 'La conversación fue desasignada';

        return $this->createActivity($conversation, 'unassigned', $text, [], $by);
    }

    public function logStatusChanged(Conversation $conversation, ?string $oldStatusName, string $newStatusName, ?User $by = null): ConversationItem
    {
        $text = $oldStatusName
            ? "El estado cambió de {$oldStatusName} a {$newStatusName}"
            : "El estado cambió a {$newStatusName}";

        if ($by) {
            $text .= " por {$by->name}";
        }

        return $this->createActivity($conversation, 'status_changed', $text, [
            'old_status' => $oldStatusName,
            'new_status' => $newStatusName,
        ], $by);
    }

    public function logPriorityChanged(Conversation $conversation, ?string $oldPriority, string $newPriority, ?User $by = null): ConversationItem
    {
        $labels = ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
        $oldLabel = $labels[$oldPriority] ?? ucfirst((string) $oldPriority);
        $newLabel = $labels[$newPriority] ?? ucfirst($newPriority);

        $text = $oldPriority
            ? "La prioridad cambió de {$oldLabel} a {$newLabel}"
            : "La prioridad se estableció en {$newLabel}";

        if ($by) {
            $text .= " por {$by->name}";
        }

        return $this->createActivity($conversation, 'priority_changed', $text, [
            'old_priority' => $oldPriority,
            'new_priority' => $newPriority,
        ], $by);
    }

    public function logLabelAdded(Conversation $conversation, string $label, ?User $by = null): ConversationItem
    {
        $text = $by
            ? "{$by->name} añadió la etiqueta \"{$label}\""
            : "Se añadió la etiqueta \"{$label}\"";

        return $this->createActivity($conversation, 'label_added', $text, [
            'label' => $label,
        ], $by);
    }

    public function logLabelRemoved(Conversation $conversation, string $label, ?User $by = null): ConversationItem
    {
        $text = $by
            ? "{$by->name} eliminó la etiqueta \"{$label}\""
            : "Se eliminó la etiqueta \"{$label}\"";

        return $this->createActivity($conversation, 'label_removed', $text, [
            'label' => $label,
        ], $by);
    }

    public function logTeamAssigned(Conversation $conversation, ?int $teamId, string $teamName, ?User $by = null): ConversationItem
    {
        $text = $by
            ? "{$by->name} asignó la conversación al equipo {$teamName}"
            : "La conversación fue asignada al equipo {$teamName}";

        return $this->createActivity($conversation, 'team_assigned', $text, [
            'team_id' => $teamId,
            'team_name' => $teamName,
        ], $by);
    }

    public function logSnoozed(Conversation $conversation, ?Carbon $until, ?User $by = null): ConversationItem
    {
        $untilText = $until ? $until->translatedFormat('d M Y H:i') : 'una fecha';
        $text = $by
            ? "{$by->name} pospuso la conversación hasta {$untilText}"
            : "La conversación fue pospuesta hasta {$untilText}";

        return $this->createActivity($conversation, 'snoozed', $text, [
            'snoozed_until' => $until?->toIso8601String(),
        ], $by);
    }

    public function logUnsnoozed(Conversation $conversation): ConversationItem
    {
        return $this->createActivity($conversation, 'unsnoozed', 'La conversación fue reactivada automáticamente', []);
    }

    public function logMuted(Conversation $conversation, ?User $by = null): ConversationItem
    {
        $text = $by
            ? "{$by->name} silenció la conversación"
            : 'La conversación fue silenciada';

        return $this->createActivity($conversation, 'muted', $text, [], $by);
    }

    private function createActivity(
        Conversation $conversation,
        string $activityType,
        string $body,
        array $data = [],
        ?User $by = null
    ): ConversationItem {
        return ConversationItem::create([
            'conversation_id' => $conversation->id,
            'item_type' => 'activity',
            'type' => 'activity',
            'activity_type' => $activityType,
            'activity_data' => $data,
            'body' => $body,
            'user_id' => $by?->id,
            'is_internal' => false,
        ]);
    }
}
