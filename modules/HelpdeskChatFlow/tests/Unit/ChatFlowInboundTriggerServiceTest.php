<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;
use Modules\HelpdeskChatFlow\Services\ChatFlowInboundTriggerService;
use Modules\HelpdeskChatFlow\Services\ChatFlowTriggerResolver;
use Modules\HelpdeskChatFlow\Tests\TestCase;

class ChatFlowInboundTriggerServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function conversation(array $attrs = []): Conversation
    {
        $conversation = new Conversation;
        $conversation->setRawAttributes(array_merge([
            'id' => 10, 'inbox_id' => 3, 'assignee_id' => null, 'metadata' => null,
        ], $attrs));

        return $conversation;
    }

    private function flow(int $id, string $type): ChatFlow
    {
        $flow = new ChatFlow;
        $flow->setRawAttributes(['id' => $id, 'trigger_type' => $type]);

        return $flow;
    }

    /**
     * @param  array<string, ChatFlow|null>  $flowsByType
     */
    private function service(array $flowsByType, ?ChatFlowEngine $engine = null, bool $recent = false, bool $agents = false): ChatFlowInboundTriggerService
    {
        $resolver = Mockery::mock(ChatFlowTriggerResolver::class);
        $resolver->shouldReceive('resolve')->andReturnUsing(
            fn ($c, string $type) => $flowsByType[$type] ?? null
        );

        $engine ??= Mockery::mock(ChatFlowEngine::class);

        return new class($engine, $resolver, $recent, $agents) extends ChatFlowInboundTriggerService
        {
            public function __construct(ChatFlowEngine $e, ChatFlowTriggerResolver $r, private bool $recent, private bool $agents)
            {
                parent::__construct($e, $r);
            }

            protected function endedRecently(array $flowIds, int $conversationId): bool
            {
                return $this->recent;
            }

            protected function inboxHasAvailableAgents(int $inboxId): bool
            {
                return $this->agents;
            }
        };
    }

    private function engineExpectingStart(ChatFlow $flow, string $type): ChatFlowEngine
    {
        $engine = Mockery::mock(ChatFlowEngine::class);
        $engine->shouldReceive('pickAbVariant')->andReturnUsing(fn ($f) => $f);
        $engine->shouldReceive('start')
            ->once()
            ->with($flow, Mockery::type(Conversation::class), $type, ['first_message' => 'quiero devolver', 'last_input' => 'quiero devolver'])
            ->andReturn(new ChatFlowSession);

        return $engine;
    }

    public function test_keyword_flow_starts_on_any_message_with_message_as_seed(): void
    {
        $flow = $this->flow(1, 'keyword');
        $service = $this->service(['keyword' => $flow], $this->engineExpectingStart($flow, 'keyword'));

        $this->assertNotNull($service->triggerFromMessage($this->conversation(), 'quiero devolver'));
    }

    public function test_keyword_wins_over_intent_and_no_agent(): void
    {
        $keyword = $this->flow(1, 'keyword');
        $service = $this->service([
            'keyword' => $keyword,
            'intent' => $this->flow(2, 'intent'),
            'no_agent' => $this->flow(3, 'no_agent'),
        ], $this->engineExpectingStart($keyword, 'keyword'));

        $service->triggerFromMessage($this->conversation(), 'quiero devolver');
    }

    public function test_intent_used_when_no_keyword_matches(): void
    {
        $intent = $this->flow(2, 'intent');
        $service = $this->service(['intent' => $intent], $this->engineExpectingStart($intent, 'intent'));

        $service->triggerFromMessage($this->conversation(), 'quiero devolver');
    }

    public function test_does_not_relaunch_flow_inside_cooldown_window(): void
    {
        $engine = Mockery::mock(ChatFlowEngine::class);
        $engine->shouldReceive('pickAbVariant')->andReturnUsing(fn ($f) => $f);
        $engine->shouldNotReceive('start');

        $service = $this->service(['keyword' => $this->flow(1, 'keyword')], $engine, recent: true);

        $this->assertNull($service->triggerFromMessage($this->conversation(), 'quiero devolver'));
    }

    public function test_does_not_start_when_assigned_to_a_human_agent(): void
    {
        $resolver = Mockery::mock(ChatFlowTriggerResolver::class);
        $resolver->shouldNotReceive('resolve');
        $engine = Mockery::mock(ChatFlowEngine::class);
        $engine->shouldNotReceive('start');

        $service = new ChatFlowInboundTriggerService($engine, $resolver);

        $this->assertNull($service->triggerFromMessage($this->conversation(['assignee_id' => 7]), 'quiero devolver'));
    }

    public function test_does_not_start_when_conversation_is_handled_by_bot(): void
    {
        $resolver = Mockery::mock(ChatFlowTriggerResolver::class);
        $resolver->shouldNotReceive('resolve');
        $service = new ChatFlowInboundTriggerService(Mockery::mock(ChatFlowEngine::class), $resolver);

        $conversation = $this->conversation(['metadata' => json_encode(['handled_by_bot' => true])]);
        $conversation->metadata = ['handled_by_bot' => true];

        $this->assertNull($service->triggerFromMessage($conversation, 'hola'));
    }

    public function test_no_agent_flow_only_starts_when_inbox_has_no_available_agents(): void
    {
        $flow = $this->flow(3, 'no_agent');

        $withAgents = $this->service(['no_agent' => $flow], agents: true);
        $this->assertNull($withAgents->triggerFromMessage($this->conversation(), 'quiero devolver'));

        $without = $this->service(['no_agent' => $flow], $this->engineExpectingStart($flow, 'no_agent'));
        $this->assertNotNull($without->triggerFromMessage($this->conversation(), 'quiero devolver'));
    }

    public function test_procedure_flows_are_never_started_from_a_message(): void
    {
        $this->assertNotContains('procedure', ChatFlowInboundTriggerService::TRIGGER_ORDER);

        // Even if a resolver wrongly returned a procedure flow it is rejected.
        $engine = Mockery::mock(ChatFlowEngine::class);
        $engine->shouldNotReceive('start');
        $service = $this->service(['keyword' => $this->flow(9, 'procedure')], $engine);

        $this->assertNull($service->triggerFromMessage($this->conversation(), 'quiero devolver'));
    }

    public function test_empty_message_starts_nothing(): void
    {
        $service = $this->service(['keyword' => $this->flow(1, 'keyword')]);

        $this->assertNull($service->triggerFromMessage($this->conversation(), '   '));
    }
}
