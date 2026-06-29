<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Webhook;
use Modules\Helpdesk\Models\WebhookDelivery;

class DispatchWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 30;

    public function __construct(
        private readonly int $webhookId,
        private readonly string $event,
        private readonly array $payload,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $webhook = Webhook::find($this->webhookId);

        if (! $webhook || ! $webhook->is_active) {
            return;
        }

        $body = $this->formatBody($webhook->integration_type ?? 'generic', $this->event, $this->payload);

        $bodyJson = json_encode($body);
        $headers = $this->buildHeaders($webhook, $bodyJson);

        $start = microtime(true);

        try {
            $response = Http::withHeaders($headers)
                ->timeout(20)
                ->send('POST', $webhook->url, ['body' => $bodyJson]);

            $duration = (int) ((microtime(true) - $start) * 1000);

            WebhookDelivery::create([
                'webhook_id' => $webhook->id,
                'event' => $this->event,
                'payload' => $body,
                'response_status' => $response->status(),
                'response_body' => substr($response->body(), 0, 5000),
                'duration_ms' => $duration,
                'delivered_at' => now(),
            ]);

            if ($response->successful()) {
                $webhook->increment('success_count');
                $webhook->update(['last_triggered_at' => now(), 'last_error' => null]);
            } else {
                $webhook->increment('failure_count');
                $webhook->update(['last_error' => 'HTTP '.$response->status()]);
                throw new \RuntimeException("HTTP {$response->status()}");
            }
        } catch (\Throwable $e) {
            $webhook->increment('failure_count');
            $webhook->update(['last_error' => substr($e->getMessage(), 0, 500)]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook job failed', [
            'webhook_id' => $this->webhookId,
            'event' => $this->event,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function formatBody(string $type, string $event, array $payload): array
    {
        return match ($type) {
            'slack' => $this->formatSlack($event, $payload),
            'discord' => $this->formatDiscord($event, $payload),
            'teams' => $this->formatTeams($event, $payload),
            default => ['event' => $event, 'sent_at' => now()->toIso8601String(), 'data' => $payload],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function formatSlack(string $event, array $payload): array
    {
        $emoji = match (true) {
            str_contains($event, 'created') => ':new:',
            str_contains($event, 'resolved') => ':white_check_mark:',
            str_contains($event, 'breach') => ':warning:',
            default => ':bell:',
        };

        $title = $payload['title'] ?? $payload['subject'] ?? 'Notificacion';
        $ticketNumber = $payload['ticket_number'] ?? '';

        return [
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => ['type' => 'plain_text', 'text' => "{$emoji} {$event}"],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*{$ticketNumber}*\n{$title}",
                    ],
                ],
                [
                    'type' => 'context',
                    'elements' => [
                        ['type' => 'mrkdwn', 'text' => "Status: `{$payload['status']}` • Priority: `{$payload['priority']}`"],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function formatDiscord(string $event, array $payload): array
    {
        $color = match (true) {
            str_contains($event, 'created') => 3447003,
            str_contains($event, 'resolved') => 3066993,
            str_contains($event, 'breach') => 15158332,
            default => 9807270,
        };

        return [
            'embeds' => [[
                'title' => ($payload['ticket_number'] ?? '').' — '.($payload['title'] ?? ''),
                'description' => "Event: `{$event}`",
                'color' => $color,
                'fields' => [
                    ['name' => 'Status', 'value' => $payload['status'] ?? '—', 'inline' => true],
                    ['name' => 'Priority', 'value' => $payload['priority'] ?? '—', 'inline' => true],
                ],
                'timestamp' => now()->toIso8601String(),
            ]],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function formatTeams(string $event, array $payload): array
    {
        $theme = match (true) {
            str_contains($event, 'created') => '0078D4',
            str_contains($event, 'resolved') => '107C10',
            str_contains($event, 'breach') => 'D83B01',
            default => '616161',
        };

        return [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => $theme,
            'summary' => "Ticket {$event}",
            'sections' => [[
                'activityTitle' => ($payload['ticket_number'] ?? '').' — '.($payload['title'] ?? ''),
                'activitySubtitle' => "Event: {$event}",
                'facts' => [
                    ['name' => 'Status', 'value' => $payload['status'] ?? '—'],
                    ['name' => 'Priority', 'value' => $payload['priority'] ?? '—'],
                ],
            ]],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(Webhook $webhook, string $bodyJson): array
    {
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'User-Agent' => 'Helpdesk-Webhook/1.0',
            'X-Helpdesk-Event' => $this->event,
        ], $webhook->headers ?? []);

        if ($webhook->secret) {
            $headers['X-Helpdesk-Signature'] = 'sha256='.hash_hmac('sha256', $bodyJson, $webhook->secret);
        }

        return $headers;
    }
}
