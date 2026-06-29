<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowTestRunner;
use Tests\TestCase;

class ChatFlowTestRunnerTest extends TestCase
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
            ['id' => 'v', 'type' => 'message', 'parentId' => 'qr', 'label' => 'Ventas', 'data' => ['text' => 'Bienvenido al área de ventas']],
            ['id' => 's', 'type' => 'message', 'parentId' => 'qr', 'label' => 'Soporte', 'data' => ['text' => 'Te ayuda soporte']],
        ];

        return $flow;
    }

    public function test_passes_when_reply_contains_expected_text(): void
    {
        $runner = app(ChatFlowTestRunner::class);

        $result = $runner->run($this->flow(), [
            ['input' => '1', 'expect_contains' => 'ventas'],
        ]);

        $this->assertTrue($result['passed']);
        $this->assertTrue($result['steps'][0]['matched']);
        $this->assertStringContainsString('ventas', $result['steps'][0]['got']);
    }

    public function test_fails_when_reply_does_not_contain_expected_text(): void
    {
        $runner = app(ChatFlowTestRunner::class);

        $result = $runner->run($this->flow(), [
            ['input' => '2', 'expect_contains' => 'ventas'], // option 2 routes to soporte
        ]);

        $this->assertFalse($result['passed']);
        $this->assertFalse($result['steps'][0]['matched']);
    }

    public function test_empty_expectation_always_matches(): void
    {
        $runner = app(ChatFlowTestRunner::class);

        $result = $runner->run($this->flow(), [
            ['input' => '1', 'expect_contains' => ''],
        ]);

        $this->assertTrue($result['passed']);
    }

    public function test_reports_error_for_flow_without_start(): void
    {
        $flow = new ChatFlow;
        $flow->nodes = [['id' => 'm', 'type' => 'message', 'parentId' => null, 'label' => 'X', 'data' => []]];

        $result = app(ChatFlowTestRunner::class)->run($flow, [['input' => 'hola']]);

        $this->assertFalse($result['passed']);
        $this->assertNotNull($result['error']);
    }
}
