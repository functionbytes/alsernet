<?php

namespace Modules\Chat\Services\Conversations;

use Modules\Chat\Models\Conversations\Conversation;

class ConversationLabelService
{
    /**
     * Add a label to the conversation if not already present.
     */
    public function addLabel(Conversation $conversation, string $labelTitle): void
    {
        $currentLabels = $this->getCurrentLabels($conversation);

        if (in_array($labelTitle, $currentLabels)) {
            return;
        }

        $currentLabels[] = $labelTitle;
        $this->updateLabels($conversation, $currentLabels);
    }

    /**
     * Remove a label from the conversation if present.
     */
    public function removeLabel(Conversation $conversation, string $labelTitle): void
    {
        $currentLabels = $this->getCurrentLabels($conversation);
        $filteredLabels = array_diff($currentLabels, [$labelTitle]);

        $this->updateLabels($conversation, $filteredLabels);
    }

    /**
     * Replace all labels with the specified list.
     */
    public function syncLabels(Conversation $conversation, array $labelTitles): void
    {
        $uniqueLabels = array_unique($labelTitles);
        $this->updateLabels($conversation, $uniqueLabels);
    }

    /**
     * Check if conversation has a specific label.
     */
    public function hasLabel(Conversation $conversation, string $labelTitle): bool
    {
        return in_array($labelTitle, $this->getCurrentLabels($conversation));
    }

    /**
     * Get current labels from conversation.
     */
    protected function getCurrentLabels(Conversation $conversation): array
    {
        if (! $conversation->cached_label_list) {
            return [];
        }

        return explode(',', $conversation->cached_label_list);
    }

    /**
     * Update conversation with new label list.
     */
    protected function updateLabels(Conversation $conversation, array $labels): void
    {
        $filteredLabels = array_filter($labels);
        $labelString = ! empty($filteredLabels) ? implode(',', $filteredLabels) : null;

        $conversation->update(['cached_label_list' => $labelString]);
    }
}
