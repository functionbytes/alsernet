<?php

namespace Modules\Helpdesk\Tests\Unit\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Services\AI\AiClient;
use Tests\TestCase;

/**
 * AiClient is the shared OpenAI gateway used by ChatFlow's AI services (see
 * ARCH-02). chatCompletion() specifically had zero test coverage despite
 * being an external AI provider integration — a change in OpenAI's response
 * shape or a regression in the request payload would go unnoticed.
 */
class AiClientTest extends TestCase
{
    public function test_chat_completion_returns_null_when_no_api_key_configured(): void
    {
        config(['services.openai.key' => '', 'services.openai.api_key' => '']);

        $client = new AiClient;
        $result = $client->chatCompletion([['role' => 'user', 'content' => 'hola']]);

        $this->assertNull($result);
    }

    public function test_chat_completion_returns_the_message_array_on_success(): void
    {
        config(['services.openai.key' => 'sk-test']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'Hola, ¿en qué puedo ayudarte?']],
                ],
            ], 200),
        ]);

        $client = new AiClient;
        $result = $client->chatCompletion([['role' => 'user', 'content' => 'hola']]);

        $this->assertSame([
            'role' => 'assistant',
            'content' => 'Hola, ¿en qué puedo ayudarte?',
        ], $result);
    }

    public function test_chat_completion_preserves_tool_calls_in_the_message(): void
    {
        config(['services.openai.key' => 'sk-test']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [
                            ['id' => 'call_1', 'function' => ['name' => 'lookup_order', 'arguments' => '{"id":42}']],
                        ],
                    ]],
                ],
            ], 200),
        ]);

        $client = new AiClient;
        $result = $client->chatCompletion([['role' => 'user', 'content' => 'donde esta mi pedido']]);

        $this->assertSame('call_1', $result['tool_calls'][0]['id']);
    }

    public function test_chat_completion_sends_the_requested_model_and_temperature(): void
    {
        config(['services.openai.key' => 'sk-test']);

        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'ok']]]], 200)]);

        $client = new AiClient;
        $client->chatCompletion(
            [['role' => 'user', 'content' => 'hola']],
            ['model' => 'gpt-4o', 'temperature' => 0.2, 'max_tokens' => 128]
        );

        Http::assertSent(function ($request) {
            return $request['model'] === 'gpt-4o'
                && $request['temperature'] === 0.2
                && $request['max_tokens'] === 128;
        });
    }

    public function test_chat_completion_returns_null_when_openai_responds_with_an_error(): void
    {
        config(['services.openai.key' => 'sk-test']);

        Http::fake(['api.openai.com/*' => Http::response('rate limited', 429)]);

        $client = new AiClient;
        $result = $client->chatCompletion([['role' => 'user', 'content' => 'hola']]);

        $this->assertNull($result);
    }

    public function test_chat_completion_returns_null_on_connection_exception(): void
    {
        config(['services.openai.key' => 'sk-test']);

        Http::fake(function () {
            throw new ConnectionException('timed out');
        });

        $client = new AiClient;
        $result = $client->chatCompletion([['role' => 'user', 'content' => 'hola']]);

        $this->assertNull($result);
    }

    public function test_chat_completion_falls_back_to_legacy_api_key_config(): void
    {
        config(['services.openai.key' => '', 'services.openai.api_key' => 'sk-legacy']);

        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'ok']]]], 200)]);

        $client = new AiClient;
        $result = $client->chatCompletion([['role' => 'user', 'content' => 'hola']]);

        $this->assertSame(['content' => 'ok'], $result);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk-legacy'));
    }
}
