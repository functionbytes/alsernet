<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;
use Modules\HelpdeskChatFlow\Services\ChatFlowOutboundService;
use Tests\TestCase;

class ChatFlowOutboundServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function flow(string $status): ChatFlow
    {
        $flow = new ChatFlow;
        $flow->status = $status;

        return $flow;
    }

    private function conversation(): Conversation
    {
        $conversation = new Conversation;
        $conversation->setRawAttributes(['id' => 7]);

        return $conversation;
    }

    public function test_launch_starts_flow_with_seed_context(): void
    {
        $flow = $this->flow('active');
        $conversation = $this->conversation();
        $context = ['customer_name' => 'Ada', 'order' => 'A-100'];
        $session = new ChatFlowSession;

        $engine = Mockery::mock(ChatFlowEngine::class);
        $engine->shouldReceive('hasActiveSession')->once()->with($conversation)->andReturn(false);
        $engine->shouldReceive('start')->once()
            ->with($flow, $conversation, 'outbound', $context)
            ->andReturn($session);

        $result = (new ChatFlowOutboundService($engine))->launch($flow, $conversation, $context);

        $this->assertSame($session, $result);
    }

    public function test_launch_returns_null_when_flow_inactive(): void
    {
        $engine = Mockery::mock(ChatFlowEngine::class);
        $engine->shouldNotReceive('start');

        $result = (new ChatFlowOutboundService($engine))
            ->launch($this->flow('draft'), $this->conversation());

        $this->assertNull($result);
    }

    public function test_launch_returns_null_when_session_already_active(): void
    {
        $conversation = $this->conversation();

        $engine = Mockery::mock(ChatFlowEngine::class);
        $engine->shouldReceive('hasActiveSession')->once()->andReturn(true);
        $engine->shouldNotReceive('start');

        $result = (new ChatFlowOutboundService($engine))
            ->launch($this->flow('active'), $conversation);

        $this->assertNull($result);
    }
}
