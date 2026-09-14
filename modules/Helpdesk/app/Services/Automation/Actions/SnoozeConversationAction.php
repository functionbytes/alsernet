<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use Carbon\Carbon;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;

class SnoozeConversationAction implements AutomationAction
{
    public static function actionType(): string
    {
        return 'snooze_conversation';
    }

    public static function paramSchema(): array
    {
        return [
            'until' => ['type' => 'string', 'nullable' => true, 'description' => 'Fecha/hora relativa (+24 hours, +1 day) o absoluta ISO8601'],
            'hours' => ['type' => 'integer', 'nullable' => true, 'description' => 'Número de horas a posponer (alternativa a until)'],
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

        $until = $this->resolveUntil($params);

        if (! $until) {
            return;
        }

        $conversation->update([
            'snoozed_until' => $until,
            'snoozed_by' => auth()->id(),
        ]);
    }

    private function resolveUntil(array $params): ?Carbon
    {
        if (! empty($params['hours'])) {
            return now()->addHours((int) $params['hours']);
        }

        $until = $params['until'] ?? null;

        if (! $until) {
            return null;
        }

        // Relative modifier: "+24 hours", "+1 day", etc.
        if (str_starts_with($until, '+')) {
            try {
                return now()->modify($until);
            } catch (\Throwable) {
                return null;
            }
        }

        // Absolute ISO8601
        try {
            return Carbon::parse($until);
        } catch (\Throwable) {
            return null;
        }
    }
}
