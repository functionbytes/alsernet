<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\HelpdeskChatFlow\Services\ChatFlowAiResponder;
use Tests\TestCase;

class ChatFlowAiResponderTest extends TestCase
{
    public function test_returns_fallback_when_no_api_key(): void
    {
        config()->set('services.openai.key', '');

        $responder = new ChatFlowAiResponder(embeddings: null);
        $result = $responder->generate('¿Hola?', ['fallback_message' => 'Te paso con un agente.']);

        $this->assertSame('Te paso con un agente.', $result['answer']);
        $this->assertFalse($result['used_kb']);
        $this->assertEmpty($result['sources']);
    }

    public function test_calls_llm_and_returns_answer(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Para devolver, entra en Mis pedidos.']]],
            ], 200),
        ]);

        $responder = new ChatFlowAiResponder(embeddings: null);
        $result = $responder->generate('¿Cómo devuelvo?', ['use_knowledge_base' => false]);

        $this->assertSame('Para devolver, entra en Mis pedidos.', $result['answer']);
        $this->assertFalse($result['used_kb']);
    }

    public function test_grounds_answer_on_knowledge_base_results(): void
    {
        config()->set('services.openai.key', 'sk-test');
        config()->set('helpdeskchatflow.ai.min_similarity', 0.2);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Según el centro de ayuda: tienes 30 días.']]],
            ], 200),
        ]);

        $embeddings = new class
        {
            public function search(string $query, int $limit, ?string $locale = null): array
            {
                return [
                    ['article_id' => 7, 'similarity' => 0.91, 'chunk_text' => 'Las devoluciones se aceptan en 30 días.'],
                    ['article_id' => 3, 'similarity' => 0.05, 'chunk_text' => 'Texto irrelevante.'],
                ];
            }
        };

        $responder = new ChatFlowAiResponder($embeddings);
        $result = $responder->generate('¿Cuántos días tengo para devolver?', ['use_knowledge_base' => true]);

        $this->assertTrue($result['used_kb']);
        $this->assertCount(1, $result['sources']); // only the 0.91 chunk passes the threshold
        $this->assertSame(7, $result['sources'][0]['article_id']);

        // The KB context must be injected into the system prompt sent to OpenAI.
        Http::assertSent(function ($request) {
            $system = $request['messages'][0]['content'] ?? '';

            return str_contains($system, '30 días');
        });
    }

    public function test_includes_conversation_history_in_the_prompt(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Claro, sobre tu pedido...']]],
            ], 200),
        ]);

        $history = [
            ['role' => 'user', 'content' => 'Hola'],
            ['role' => 'assistant', 'content' => '¿En qué te ayudo?'],
            ['role' => 'foo', 'content' => 'se descarta'], // invalid role dropped
            ['role' => 'user', 'content' => '   '],          // empty dropped
        ];

        $responder = new ChatFlowAiResponder(embeddings: null);
        $responder->generate('Mi pedido', ['use_knowledge_base' => false], 'es', $history);

        Http::assertSent(function ($request) {
            $messages = $request['messages'];

            // system + 2 valid history turns + current question = 4
            return count($messages) === 4
                && $messages[0]['role'] === 'system'
                && $messages[1]['content'] === 'Hola'
                && $messages[2]['content'] === '¿En qué te ayudo?'
                && $messages[3]['content'] === 'Mi pedido';
        });
    }

    public function test_returns_fallback_when_llm_fails(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake(['api.openai.com/*' => Http::response('error', 500)]);

        $responder = new ChatFlowAiResponder(embeddings: null);
        $result = $responder->generate('¿Hola?', ['use_knowledge_base' => false, 'fallback_message' => 'Reintenta luego.']);

        $this->assertSame('Reintenta luego.', $result['answer']);
    }

    public function test_classify_intent_returns_matching_option_index(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => '3']]],
            ], 200),
        ]);

        $responder = new ChatFlowAiResponder(embeddings: null);
        $index = $responder->classifyIntent(
            'necesito saber dónde está mi paquete',
            ['Consultar producto', 'Subir documentación', 'Estado de pedido', 'Hablar con un agente']
        );

        $this->assertSame(3, $index);
    }

    public function test_classify_intent_returns_null_when_no_match(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => '0']]]], 200)]);

        $responder = new ChatFlowAiResponder(embeddings: null);
        $index = $responder->classifyIntent('hola qué tal', ['Ventas', 'Soporte']);

        $this->assertNull($index);
    }

    public function test_classify_intent_returns_null_without_api_key(): void
    {
        config()->set('services.openai.key', '');

        $responder = new ChatFlowAiResponder(embeddings: null);
        $this->assertNull($responder->classifyIntent('lo que sea', ['A', 'B']));
    }
}
