<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\SlackIntegration;

class SlackNotificationService
{
    /**
     * Send a Slack notification to all active integrations that subscribe to the event.
     *
     * @param  array<string, mixed>  $payload
     */
    public function send(string $event, array $payload): void
    {
        $integrations = SlackIntegration::query()->active()->get();

        foreach ($integrations as $integration) {
            if (! $integration->supportsEvent($event)) {
                continue;
            }

            $this->dispatch($integration->webhook_url, $event, $payload);
        }
    }

    private function dispatch(string $webhookUrl, string $event, array $payload): void
    {
        try {
            $text = $this->buildMessage($event, $payload);

            Http::timeout(5)->post($webhookUrl, [
                'text' => $text,
                'username' => 'Helpdesk Bot',
                'icon_emoji' => ':bell:',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Slack notification failed', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }

    private function buildMessage(string $event, array $payload): string
    {
        $labels = SlackIntegration::AVAILABLE_EVENTS;
        $label = $labels[$event] ?? $event;

        $lines = ["*[Helpdesk]* {$label}"];

        foreach ($payload as $key => $value) {
            if (is_scalar($value) && $value !== null && $value !== '') {
                $lines[] = "> *{$key}:* {$value}";
            }
        }

        return implode("\n", $lines);
    }
}
