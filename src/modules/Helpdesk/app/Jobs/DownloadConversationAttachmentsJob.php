<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\ConversationItem;

/**
 * Downloads media (image/audio/video/document) from a remote URL into the
 * configured storage disk and rewrites the conversation item's
 * `attachment_urls` to point at the local copy.
 *
 * Runs OUT OF BAND so that the user-facing webhook flow can broadcast the
 * message immediately with a temporary external CDN URL — the download is
 * usually 0.3–3s and would otherwise add that latency to every media message.
 */
class DownloadConversationAttachmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var array<int, int> */
    public array $backoff = [10, 30];

    /**
     * @param  array<int, array{type: string, original_url: string, name?: string}>  $attachments
     */
    public function __construct(
        public readonly int $itemId,
        public readonly array $attachments,
        public readonly string $platform,
        public readonly ?string $bearerToken = null,
    ) {
        $this->onQueue('helpdesk-webhooks');
    }

    public function handle(): void
    {
        $item = ConversationItem::query()->find($this->itemId);
        if (! $item) {
            return;
        }

        $downloaded = [];
        $localUrls = [];

        foreach ($this->attachments as $att) {
            $type = $att['type'] ?? 'file';
            $url = $att['original_url'] ?? $att['url'] ?? null;

            // For WhatsApp the webhook gives us a media_id, not a URL —
            // resolve it lazily (here, off the hot path).
            if (! $url && ! empty($att['media_id'])) {
                $url = $this->resolveWhatsAppMediaUrl($att['media_id']);
            }

            if (! $url) {
                continue;
            }

            $local = $this->downloadOne($url, $type);

            if (! $local) {
                continue;
            }

            $downloaded[] = array_merge(['type' => $type, 'original_url' => $url], $local);
            $localUrls[] = [
                'url' => $local['url'],
                'name' => $local['name'],
                'size' => $local['size'],
                'mime_type' => $local['mime_type'] ?? 'application/octet-stream',
            ];
        }

        if (empty($downloaded)) {
            return;
        }

        // Replace external CDN urls with locally-served urls
        $item->attachment_urls = $localUrls;
        $metadata = is_array($item->metadata) ? $item->metadata : [];
        $metadata['attachments'] = $downloaded;
        $item->metadata = $metadata;
        $item->save();

        // Re-broadcast so the open thread swaps the temporary CDN url for the
        // permanent local one (useful after the FB CDN token expires).
        broadcast(new ConversationMessageCreated($item, false));
    }

    /**
     * @return array{path: string, url: string, name: string, mime_type: string|null, size: int}|null
     */
    private function downloadOne(string $url, string $mediaType): ?array
    {
        try {
            $request = Http::timeout(45);

            $token = $this->resolveBearerToken();
            if (filled($token)) {
                $request = $request->withToken($token);
            }

            $response = $request->get($url);

            if ($response->failed()) {
                Log::warning('DownloadConversationAttachmentsJob: download failed', [
                    'platform' => $this->platform,
                    'url' => substr($url, 0, 100),
                    'status' => $response->status(),
                ]);

                return null;
            }

            $body = $response->body();
            $mime = $response->header('Content-Type') ?: null;
            $extension = $this->extensionFromMime($mime, $mediaType);
            $name = $this->platform.'_'.$mediaType.'_'.date('Ymd_His').'.'.$extension;
            $filename = 'helpdesk/social-media/'.$this->platform.'/'.date('Y/m/d').'/'.uniqid('', true).'.'.$extension;

            $disk = config('helpdesk.attachments.disk', 'public');
            Storage::disk($disk)->put($filename, $body);

            try {
                $publicUrl = Storage::disk($disk)->url($filename);
            } catch (\Throwable) {
                $publicUrl = $filename;
            }

            return [
                'path' => $filename,
                'url' => $publicUrl,
                'name' => $name,
                'mime_type' => $mime,
                'size' => strlen($body),
            ];
        } catch (\Throwable $e) {
            Log::error('DownloadConversationAttachmentsJob: exception', [
                'platform' => $this->platform,
                'url' => substr($url, 0, 100),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DownloadConversationAttachmentsJob failed', [
            'item_id' => $this->itemId,
            'platform' => $this->platform,
            'attachments_count' => count($this->attachments),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Resolve the bearer token used to authenticate media requests.
     *
     * Never serialized into the queue payload: the token is read from config at
     * runtime for WhatsApp. The optional constructor argument is kept only as an
     * explicit override and is null in the normal dispatch flow.
     */
    private function resolveBearerToken(): ?string
    {
        if (filled($this->bearerToken)) {
            return $this->bearerToken;
        }

        if ($this->platform === 'whatsapp') {
            return config('helpdesk.integrations.whatsapp.access_token');
        }

        return null;
    }

    /**
     * Resolve a WhatsApp media id to its short-lived download URL.
     * Returns null on failure; the job will then skip this attachment.
     */
    private function resolveWhatsAppMediaUrl(string $mediaId): ?string
    {
        $token = $this->resolveBearerToken();
        $apiUrl = config('helpdesk.integrations.whatsapp.api_url', 'https://graph.facebook.com/v19.0');

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get("{$apiUrl}/{$mediaId}");

            if ($response->failed()) {
                Log::warning('DownloadConversationAttachmentsJob: failed to resolve WA media id', [
                    'media_id' => $mediaId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $response->json('url');
        } catch (\Throwable $e) {
            Log::error('DownloadConversationAttachmentsJob: WA media resolve exception', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function extensionFromMime(?string $mime, string $fallbackType): string
    {
        $mime = $mime ? trim(strtolower(explode(';', $mime, 2)[0])) : '';

        $map = [
            'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png',
            'image/gif' => 'gif', 'image/webp' => 'webp', 'image/svg+xml' => 'svg',
            'audio/mpeg' => 'mp3', 'audio/mp3' => 'mp3', 'audio/ogg' => 'ogg',
            'audio/opus' => 'opus', 'audio/wav' => 'wav', 'audio/aac' => 'aac',
            'audio/mp4' => 'm4a', 'audio/webm' => 'webm', 'audio/3gpp' => '3gp',
            'video/mp4' => 'mp4', 'video/quicktime' => 'mov', 'video/webm' => 'webm',
            'video/3gpp' => '3gp',
            'application/pdf' => 'pdf', 'application/zip' => 'zip',
            'text/plain' => 'txt', 'text/csv' => 'csv',
        ];

        if (isset($map[$mime])) {
            return $map[$mime];
        }

        return match ($fallbackType) {
            'image' => 'jpg', 'audio', 'voice' => 'ogg', 'video' => 'mp4',
            'document', 'file' => 'bin', 'sticker' => 'webp',
            default => 'bin',
        };
    }
}
