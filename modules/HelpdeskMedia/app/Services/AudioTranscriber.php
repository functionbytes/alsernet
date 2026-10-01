<?php

namespace Modules\HelpdeskMedia\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Transcripción con OpenAI (gpt-4o-mini-transcribe, con whisper-1 de reserva).
 * Si el formato no lo acepta OpenAI (amr, aac, 3gp…) y hay ffmpeg, se convierte
 * antes a mp3.
 */
class AudioTranscriber
{
    private const URL = 'https://api.openai.com/v1/audio/transcriptions';

    /** MIME real => extensión con la que se envía a OpenAI. */
    private const ACCEPTED = [
        'audio/ogg' => 'ogg',
        'application/ogg' => 'ogg',
        'audio/opus' => 'ogg',
        'audio/mpeg' => 'mp3',
        'audio/mp3' => 'mp3',
        'audio/mp4' => 'm4a',
        'audio/x-m4a' => 'm4a',
        'audio/m4a' => 'm4a',
        'video/mp4' => 'mp4',
        'audio/webm' => 'webm',
        'video/webm' => 'webm',
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
        'audio/wave' => 'wav',
        'audio/flac' => 'flac',
        'audio/x-flac' => 'flac',
    ];

    public function enabled(): bool
    {
        return (bool) config('helpdeskmedia.transcription.enabled', true) && $this->apiKey() !== null;
    }

    /**
     * @throws TransientMediaException cuando conviene reintentar el job
     */
    public function transcribe(string $localPath, string $mime): TranscriptResult
    {
        $converted = null;
        $uploadPath = $localPath;
        $extension = self::ACCEPTED[$mime] ?? null;

        if ($extension === null) {
            $converted = $this->convertToMp3($localPath);

            if ($converted === null) {
                return new TranscriptResult(null, error: 'unsupported_format_without_ffmpeg');
            }

            $uploadPath = $converted;
            $extension = 'mp3';
        }

        try {
            return $this->requestWithFallback($uploadPath, $extension);
        } finally {
            if ($converted !== null && is_file($converted)) {
                @unlink($converted);
            }
        }
    }

    private function requestWithFallback(string $path, string $extension): TranscriptResult
    {
        $models = array_values(array_unique(array_filter([
            (string) config('helpdeskmedia.transcription.model', 'gpt-4o-mini-transcribe'),
            (string) config('helpdeskmedia.transcription.fallback_model', 'whisper-1'),
        ])));

        $transient = null;
        $lastError = 'no_model';

        foreach ($models as $model) {
            try {
                $result = $this->request($path, $extension, $model);
            } catch (TransientMediaException $e) {
                $transient = $e;

                continue;
            }

            if ($result->ok()) {
                return $result;
            }

            $lastError = $result->error ?? 'empty_transcript';
        }

        if ($transient !== null) {
            throw $transient;
        }

        return new TranscriptResult(null, error: $lastError);
    }

    private function request(string $path, string $extension, string $model): TranscriptResult
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return new TranscriptResult(null, error: 'unreadable');
        }

        $fields = [
            'model' => $model,
            // Solo whisper-1 devuelve el idioma detectado (verbose_json).
            'response_format' => $model === 'whisper-1' ? 'verbose_json' : 'json',
        ];

        $language = config('helpdeskmedia.transcription.language');

        if (filled($language)) {
            $fields['language'] = (string) $language;
        }

        try {
            $response = Http::withToken((string) $this->apiKey())
                ->connectTimeout(10)
                ->timeout((int) config('helpdeskmedia.transcription.timeout', 120))
                ->attach('file', $handle, 'audio.'.$extension)
                ->post(self::URL, $fields);
        } catch (ConnectionException $e) {
            throw new TransientMediaException('openai connection: '.$e->getMessage(), 0, $e);
        } finally {
            fclose($handle);
        }

        return $this->parse($response, $model);
    }

    private function parse(Response $response, string $model): TranscriptResult
    {
        if ($response->status() === 429 || $response->serverError()) {
            throw new TransientMediaException("openai {$model} HTTP {$response->status()}");
        }

        if ($response->failed()) {
            Log::warning('HelpdeskMedia: transcription rejected', [
                'model' => $model,
                'status' => $response->status(),
                'error' => substr((string) $response->body(), 0, 300),
            ]);

            return new TranscriptResult(null, error: "http_{$response->status()}");
        }

        $text = trim((string) $response->json('text', ''));
        $language = $response->json('language');

        if (! is_string($language) || $language === '') {
            $hint = config('helpdeskmedia.transcription.language');
            $language = filled($hint) ? (string) $hint : null;
        }

        return new TranscriptResult(
            $text !== '' ? $text : null,
            language: $language,
            model: $model,
            error: $text === '' ? 'empty_transcript' : null,
        );
    }

    private function convertToMp3(string $path): ?string
    {
        $binary = (new ExecutableFinder)->find((string) config('helpdeskmedia.transcription.ffmpeg_path', 'ffmpeg'));

        if ($binary === null) {
            return null;
        }

        $output = tempnam(sys_get_temp_dir(), 'hdaud_').'.mp3';

        $process = new Process([$binary, '-y', '-i', $path, '-vn', '-ac', '1', '-ar', '16000', '-b:a', '48k', $output]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($output) || filesize($output) === 0) {
            Log::warning('HelpdeskMedia: ffmpeg conversion failed', ['error' => substr($process->getErrorOutput(), -300)]);
            @unlink($output);

            return null;
        }

        return $output;
    }

    private function apiKey(): ?string
    {
        $key = config('services.openai.key') ?: config('services.openai.api_key');

        return is_string($key) && $key !== '' ? $key : null;
    }
}
