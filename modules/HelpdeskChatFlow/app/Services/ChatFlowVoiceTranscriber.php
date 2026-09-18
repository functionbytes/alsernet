<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskChatFlow\Services\Concerns\GuardsAgainstSsrf;

/**
 * Transcribes inbound voice notes (WhatsApp, Instagram, Telegram…) to text so
 * the chatbot can treat them like any other message. Reuses the helpdesk's
 * Whisper-backed AiClient when available; degrades to null otherwise.
 */
class ChatFlowVoiceTranscriber
{
    use GuardsAgainstSsrf;

    private const AUDIO_EXTENSIONS = ['ogg', 'oga', 'opus', 'mp3', 'm4a', 'mp4', 'wav', 'amr', 'webm', 'aac', 'flac'];

    /**
     * @param  object|null  $aiClient  Helpdesk AiClient with a transcribe(string $path): ?string method
     */
    public function __construct(
        private readonly ?object $aiClient = null,
    ) {}

    /**
     * Transcribe the first audio attachment of an inbound message. Returns null
     * when the item carries no voice note or transcription is unavailable.
     */
    public function transcribeItem(ConversationItem $item): ?string
    {
        if ($this->aiClient === null) {
            return null;
        }

        $audioUrl = $this->audioAttachment($item->attachment_urls ?? []);
        if ($audioUrl === null) {
            return null;
        }

        $localPath = $this->resolveLocalPath($audioUrl);
        if ($localPath === null) {
            return null;
        }

        try {
            $text = $this->aiClient->transcribe($localPath);

            return is_string($text) && trim($text) !== '' ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::warning('ChatFlowVoiceTranscriber: transcription failed', ['error' => $e->getMessage()]);

            return null;
        } finally {
            $this->cleanupTemp($localPath);
        }
    }

    /**
     * First attachment URL that looks like an audio file.
     *
     * @param  array<int, string>  $urls
     */
    private function audioAttachment(array $urls): ?string
    {
        foreach ($urls as $url) {
            if (! is_string($url) || $url === '') {
                continue;
            }

            $path = parse_url($url, PHP_URL_PATH) ?: $url;
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if (in_array($ext, self::AUDIO_EXTENSIONS, true)) {
                return $url;
            }
        }

        return null;
    }

    /**
     * Resolve an attachment URL to a readable absolute path. Remote URLs are
     * downloaded to a temp file (cleaned up by the caller); local /storage/
     * paths are mapped to the public disk.
     */
    private function resolveLocalPath(string $url): ?string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $appHost = parse_url(config('app.url') ?: '', PHP_URL_HOST);
            $urlHost = parse_url($url, PHP_URL_HOST);

            // Same-host /storage/ URL → read straight from the public disk.
            $path = parse_url($url, PHP_URL_PATH) ?: '';
            if ($appHost && $urlHost === $appHost && str_starts_with($path, '/storage/')) {
                return $this->publicPathOrNull($path);
            }

            return $this->download($url);
        }

        if (str_starts_with($url, '/storage/')) {
            return $this->publicPathOrNull($url);
        }

        return null;
    }

    private function publicPathOrNull(string $path): ?string
    {
        $absolute = public_path(ltrim($path, '/'));

        return is_file($absolute) ? $absolute : null;
    }

    private function download(string $url): ?string
    {
        // SSRF guard: attachment URLs come from inbound channel webhooks. Block
        // private/reserved hosts and pin the connection to the validated IP.
        $curl = $this->ssrfSafeCurlOptions($url);
        if ($curl === null) {
            Log::warning('ChatFlowVoiceTranscriber: blocked unsafe audio URL', ['url' => $url]);

            return null;
        }

        try {
            $response = Http::timeout(30)
                ->withoutRedirecting()
                ->withOptions(['curl' => $curl])
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('ChatFlowVoiceTranscriber: audio download failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $maxBytes = (int) config('helpdeskchatflow.voice.max_bytes', 26214400); // 25 MB

        // Reject early when the server advertises an oversized payload.
        $declared = (int) ($response->header('Content-Length') ?: 0);
        if ($declared > $maxBytes) {
            Log::warning('ChatFlowVoiceTranscriber: audio exceeds max size', ['url' => $url, 'bytes' => $declared]);

            return null;
        }

        $body = $response->body();
        if ($body === '') {
            return null;
        }

        if (strlen($body) > $maxBytes) {
            Log::warning('ChatFlowVoiceTranscriber: audio exceeds max size', ['url' => $url, 'bytes' => strlen($body)]);

            return null;
        }

        // tempnam() reserves a file with no extension; rename to the audio
        // extension so Whisper sniffs the format, and so no 0-byte stub leaks.
        $base = tempnam(sys_get_temp_dir(), 'chatflow_voice_');
        if ($base === false) {
            return null;
        }

        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION)) ?: 'ogg';
        $path = $base.'.'.$ext;
        if (! @rename($base, $path)) {
            $path = $base;
        }

        if (file_put_contents($path, $body) === false) {
            $this->cleanupTemp($path);

            return null;
        }

        return $path;
    }

    private function cleanupTemp(string $path): void
    {
        if (str_starts_with($path, sys_get_temp_dir()) && is_file($path)) {
            @unlink($path);
        }
    }
}
