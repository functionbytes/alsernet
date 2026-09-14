<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Modules\HelpdeskChatFlow\Services\ChatFlowOrderLookup;
use Tests\TestCase;

class ChatFlowAgentServiceTest extends TestCase
{
    public function test_escalates_without_api_key(): void
    {
        config()->set('services.openai.key', '');

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $result = $agent->run('hola', [], ['fallback_message' => 'Te paso con un agente.']);

        $this->assertSame('escalate', $result['action']);
        $this->assertSame('Te paso con un agente.', $result['text']);
    }

    public function test_answers_directly_when_llm_returns_content(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Claro, te ayudo con eso.', 'tool_calls' => []]]],
            ], 200),
        ]);

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $result = $agent->run('una pregunta', [], []);

        $this->assertSame('respond', $result['action']);
        $this->assertSame('Claro, te ayudo con eso.', $result['text']);
    }

    public function test_runs_order_lookup_tool_then_answers(): void
    {
        config()->set('services.openai.key', 'sk-test');

        // 1st call: the model asks to call lookup_order. 2nd call: it answers.
        Http::fakeSequence('api.openai.com/*')
            ->push(['choices' => [['message' => [
                'content' => null,
                'tool_calls' => [[
                    'id' => 'call_1',
                    'type' => 'function',
                    'function' => ['name' => 'lookup_order', 'arguments' => '{"order_id":"100245"}'],
                ]],
            ]]]], 200)
            ->push(['choices' => [['message' => ['content' => 'Tu pedido #100245 está enviado.', 'tool_calls' => []]]]], 200);

        $erp = new class
        {
            public function getOrderDetail(int $customerId, int $orderId): ?array
            {
                return ['id' => $orderId, 'status' => 'Enviado', 'total' => '49,90 €'];
            }
        };

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup($erp, null), null);
        $result = $agent->run('¿dónde está mi pedido 100245?', ['customer_erp_id' => 10], []);

        $this->assertSame('respond', $result['action']);
        $this->assertStringContainsString('enviado', mb_strtolower($result['text']));
        $this->assertContains('lookup_order', $result['used_tools']);
    }

    public function test_agent_can_escalate_via_tool(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [['message' => [
                'content' => null,
                'tool_calls' => [[
                    'id' => 'call_x',
                    'type' => 'function',
                    'function' => ['name' => 'escalate_to_agent', 'arguments' => '{"message":"Te paso con un agente."}'],
                ]],
            ]]]], 200),
        ]);

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $result = $agent->run('quiero una persona', [], []);

        $this->assertSame('escalate', $result['action']);
        $this->assertContains('escalate_to_agent', $result['used_tools']);
    }
}
