<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowReplayService;
use Tests\TestCase;

class ChatFlowReplayServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cache.default', 'array');
    }

    private function flow(): ChatFlow
    {
        $flow = new ChatFlow;
        $flow->nodes = [
            ['id' => 'start', 'type' => 'start', 'parentId' => null, 'label' => 'Inicio', 'data' => []],
            ['id' => 'qr', 'type' => 'quick_replies', 'parentId' => 'start', 'label' => 'Menú',
                'data' => ['text' => 'Elige', 'options' => ['Ventas', 'Soporte']]],
            ['id' => 'v', 'type' => 'message', 'parentId' => 'qr', 'label' => 'Ventas', 'data' => ['text' => 'Bienvenido a ventas']],
            ['id' => 'end', 'type' => 'end', 'parentId' => 'v', 'label' => 'Fin', 'data' => ['action' => 'close']],
        ];

        return $flow;
    }

    public function test_replays_customer_inputs_against_the_flow(): void
    {
        $result = app(ChatFlowReplayService::class)->replayInputs($this->flow(), ['1']);

        $this->assertSame(['1'], $result['inputs']);
        $this->assertSame('completed', $result['final_status']);

        // The timeline must contain the customer turn and the bot's "ventas" reply.
        $allText = collect($result['timeline'])
            ->flatMap(fn ($e) => $e['role'] === 'customer' ? [$e['text']] : collect($e['messages'])->pluck('text')->all())
            ->implode(' ');

        $this->assertStringContainsString('1', $allText);
        $this->assertStringContainsString('Bienvenido a ventas', $allText);
    }

    public function test_replay_with_no_inputs_only_runs_the_start(): void
    {
        $result = app(ChatFlowReplayService::class)->replayInputs($this->flow(), []);

        $this->assertSame([], $result['inputs']);
        // Only the initial bot turn (welcome + options), still waiting.
        $this->assertSame('active', $result['final_status']);
        $this->assertCount(1, $result['timeline']);
    }
}
