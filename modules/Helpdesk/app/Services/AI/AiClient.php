<?php

namespace Modules\Helpdesk\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiClient
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

    private const TRANSCRIPTION_URL = 'https://api.openai.com/v1/audio/transcriptions';

    public function chat(array $messages, ?string $model = null): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post(self::API_URL, [
                    'model' => $model ?? config('services.openai.model', 'gpt-4o-mini'),
                    'messages' => $messages,
                    'temperature' => 0.7,
                ]);

            if ($response->failed()) {
                Log::warning('AiClient: OpenAI chat failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json('choices.0.message.content');
        } catch (\Throwable $e) {
            Log::error('AiClient: OpenAI chat exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function transcribe(string $absolutePath): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->attach('file', file_get_contents($absolutePath), basename($absolutePath))
                ->post(self::TRANSCRIPTION_URL, [
                    'model' => 'whisper-1',
                ]);

            if ($response->failed()) {
                Log::warning('AiClient: Whisper transcription failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json('text');
        } catch (\Throwable $e) {
            Log::error('AiClient: Whisper transcription exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Call OpenAI with function/tool calling for the AI autonomous agent.
     *
     * Returns an array: ['action' => 'respond', 'text' => '...']
     *                or ['action' => 'escalate', 'reason' => '...']
     */
    public function chatWithTools(array $messages, array $tools, ?string $model = null): array
    {
        if (! $this->isEnabled()) {
            return ['action' => 'escalate', 'reason' => 'ai_disabled'];
        }

        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            return ['action' => 'escalate', 'reason' => 'no_api_key'];
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->post(self::API_URL, [
                    'model' => $model ?? config('services.openai.model', 'gpt-4o-mini'),
                    'messages' => $messages,
                    'tools' => $tools,
                    'tool_choice' => 'required',
                    'temperature' => 0.5,
                ]);

            if ($response->failed()) {
                Log::warning('AiClient: chatWithTools failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['action' => 'escalate', 'reason' => 'api_error'];
            }

            return $this->parseToolCallResponse($response->json());
        } catch (\Throwable $e) {
            Log::error('AiClient: chatWithTools exception', ['error' => $e->getMessage()]);

            return ['action' => 'escalate', 'reason' => 'exception'];
        }
    }

    private function parseToolCallResponse(array $data): array
    {
        $toolCalls = data_get($data, 'choices.0.message.tool_calls', []);

        if (empty($toolCalls)) {
            return ['action' => 'escalate', 'reason' => 'no_tool_call'];
        }

        $call = $toolCalls[0];
        $name = data_get($call, 'function.name');
        $args = json_decode(data_get($call, 'function.arguments', '{}'), true);

        if ($name === 'respond_to_customer') {
            return ['action' => 'respond', 'text' => $args['text'] ?? ''];
        }

        if ($name === 'escalate_to_human') {
            return ['action' => 'escalate', 'reason' => $args['reason'] ?? 'ai_decided'];
        }

        return ['action' => 'escalate', 'reason' => 'unknown_tool'];
    }

    public function isEnabled(): bool
    {
        return (bool) config('helpdesk.ai.enabled', true);
    }
}
