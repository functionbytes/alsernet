<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Events\ConversationStatusChanged;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;

class ChangeStatusAction implements AutomationAction
{
    public static function actionType(): string
    {
        return 'change_status';
    }

    public static function paramSchema(): array
    {
        return [
            'status' => [
                'type' => 'string',
                'required' => true,
                'enum' => ['open', 'pending', 'resolved', 'snoozed'],
                'description' => 'Nuevo estado de la conversación',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $context
     */
    public function execute(array $params, array $context): void
    {
        $conversation = $context['conversation'] ?? null;

        if (! $conversation instanceof Conversation) {
            return;
        }

        $statusSlug = $params['status'] ?? null;

        if (! $statusSlug) {
            return;
        }

        $status = $this->resolveStatus($statusSlug);

        if (! $status) {
            return;
        }

        $previousStatusId = $conversation->status_id;

        $updates = ['status_id' => $status->id];

        if (! $status->is_open) {
            $updates['closed_at'] = now();
        } elseif ($previousStatusId !== $status->id) {
            $updates['closed_at'] = null;
        }

        $conversation->update($updates);

        event(new ConversationStatusChanged($conversation));
    }

    private function resolveStatus(string $slug): ?ConversationStatus
    {
        $cacheKey = "helpdesk:status-slug:{$slug}";

        return Cache::remember($cacheKey, 3600, function () use ($slug) {
            // Try matching by is_open boolean for common slugs first
            if ($slug === 'open') {
                return ConversationStatus::where('is_open', true)->orderBy('order')->first();
            }

            if (in_array($slug, ['resolved', 'closed'], true)) {
                return ConversationStatus::where('is_open', false)->orderBy('order')->first();
            }

            // Fall back to slug or name match
            return ConversationStatus::where('slug', $slug)
                ->orWhere('name', $slug)
                ->first();
        });
    }
}
