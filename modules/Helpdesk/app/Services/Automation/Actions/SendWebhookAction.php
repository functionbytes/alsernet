<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Jobs\DispatchWebhookJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Webhook;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;
use Modules\Helpdesk\Support\OutboundUrlGuard;

class SendWebhookAction implements AutomationAction
{
    public static function actionType(): string
    {
        return 'send_webhook';
    }

    public static function paramSchema(): array
    {
        return [
            'webhook_id' => ['type' => 'integer', 'nullable' => true, 'description' => 'ID de un webhook configurado en el sistema'],
            'url' => ['type' => 'string', 'nullable' => true, 'description' => 'URL directa si no se usa webhook_id'],
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

        $payload = $this->buildPayload($conversation, $context['event_name'] ?? 'automation.triggered');

        if (! empty($params['webhook_id'])) {
            $this->dispatchViaModel((int) $params['webhook_id'], $payload);

            return;
        }

        if (! empty($params['url'])) {
            $this->dispatchDirect((string) $params['url'], $payload);
        }
    }

    /** @param  array<string, mixed>  $payload */
    private function dispatchViaModel(int $webhookId, array $payload): void
    {
        $webhook = Webhook::find($webhookId);

        if (! $webhook || ! $webhook->is_active) {
            return;
        }

        DispatchWebhookJob::dispatch($webhook->id, $payload['event'] ?? 'automation.triggered', $payload);
    }

    /** @param  array<string, mixed>  $payload */
    private function dispatchDirect(string $url, array $payload): void
    {
        if (! OutboundUrlGuard::isSafe($url)) {
            Log::warning('SendWebhookAction: blocked unsafe URL (SSRF guard)', ['url' => $url]);

            return;
        }

        try {
            Http::timeout(10)->post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('SendWebhookAction: direct POST failed', ['url' => $url, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Conversation $conversation, string $eventName): array
    {
        return [
            'event' => $eventName,
            'conversation_id' => $conversation->id,
            'subject' => $conversation->subject,
            'priority' => $conversation->priority,
            'channel' => $conversation->channel,
            'assignee_id' => $conversation->assignee_id,
            'customer_id' => $conversation->customer_id,
            'created_at' => $conversation->created_at?->toIso8601String(),
        ];
    }
}
