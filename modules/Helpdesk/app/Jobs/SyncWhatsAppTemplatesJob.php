<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Campaigns\WhatsAppTemplate;

class SyncWhatsAppTemplatesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 30;

    public function __construct()
    {
        $this->onQueue('helpdesk');
    }

    public function handle(): void
    {
        $accessToken = config('helpdesk.integrations.whatsapp.access_token');
        $businessAccountId = config('helpdesk.integrations.whatsapp.business_account_id');
        $apiVersion = config('helpdesk.integrations.whatsapp.api_version', 'v19.0');

        if (! filled($accessToken) || ! filled($businessAccountId)) {
            Log::warning('SyncWhatsAppTemplatesJob: WhatsApp credentials not configured — aborting sync');

            return;
        }

        $url = "https://graph.facebook.com/{$apiVersion}/{$businessAccountId}/message_templates";

        $cursor = null;
        $synced = 0;

        do {
            $params = ['limit' => 100];

            if ($cursor) {
                $params['after'] = $cursor;
            }

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get($url, $params);

            if ($response->failed()) {
                Log::error('SyncWhatsAppTemplatesJob: Meta API error', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                throw new \RuntimeException('Meta API error: '.$response->json('error.message', 'Unknown error'));
            }

            $data = $response->json('data', []);

            foreach ($data as $item) {
                $this->upsertTemplate($item);
                $synced++;
            }

            $cursor = $response->json('paging.cursors.after');
            $hasNext = filled($cursor) && filled($response->json('paging.next'));

        } while ($hasNext);

        Log::info('SyncWhatsAppTemplatesJob: sync completed', ['synced' => $synced]);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function upsertTemplate(array $item): void
    {
        $externalId = $item['name'] ?? null;

        if (! $externalId) {
            return;
        }

        $components = $item['components'] ?? [];
        $headerType = null;
        $headerValue = null;
        $bodyTemplate = null;
        $footerText = null;

        foreach ($components as $component) {
            $type = strtolower($component['type'] ?? '');

            match ($type) {
                'header' => [$headerType, $headerValue] = $this->parseHeader($component),
                'body' => $bodyTemplate = $component['text'] ?? null,
                'footer' => $footerText = $component['text'] ?? null,
                default => null,
            };
        }

        WhatsAppTemplate::query()->updateOrCreate(
            ['external_id' => $externalId],
            [
                'display_name' => $item['name'] ?? $externalId,
                'language' => $item['language'] ?? 'es',
                'category' => strtolower($item['category'] ?? 'utility'),
                'status' => strtolower($item['status'] ?? 'pending'),
                'body_template' => $bodyTemplate,
                'header_type' => $headerType,
                'header_value' => $headerValue,
                'footer_text' => $footerText,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $component
     * @return array{0: string|null, 1: string|null}
     */
    private function parseHeader(array $component): array
    {
        $format = strtolower($component['format'] ?? 'text');
        $value = null;

        if ($format === 'text') {
            $value = $component['text'] ?? null;
        } elseif (in_array($format, ['image', 'document', 'video'], true)) {
            $value = $component['example']['header_handle'][0] ?? null;
        }

        return [$format, $value];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncWhatsAppTemplatesJob: job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
