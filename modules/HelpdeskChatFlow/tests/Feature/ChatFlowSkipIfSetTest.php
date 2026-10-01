<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowAiResponder;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;
use Modules\HelpdeskChatFlow\Services\ChatFlowHandoffSummary;
use Modules\HelpdeskChatFlow\Services\ChatFlowIdentityOtp;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\ChatFlowNodeExecutor;
use Modules\HelpdeskChatFlow\Services\ChatFlowScheduler;
use Modules\HelpdeskChatFlow\Services\ChatFlowSentiment;
use Modules\HelpdeskChatFlow\Services\ChatFlowTestSimulator;
use Modules\HelpdeskChatFlow\Services\ChatFlowTriggerResolver;
use Modules\HelpdeskChatFlow\Services\CustomerIdentityResolver;
use Modules\HelpdeskChatFlow\Services\Input\CsatInputHandler;
use Modules\HelpdeskChatFlow\Services\Input\DocumentUploadInputHandler;
use Modules\HelpdeskChatFlow\Services\Input\IdentificationInputHandler;
use Modules\HelpdeskChatFlow\Tests\Support\InMemoryChatFlowSession;
use Modules\HelpdeskChatFlow\Tests\TestCase;
use ReflectionMethod;

class ChatFlowSkipIfSetTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function engine(ChatFlowNodeExecutor $executor): ChatFlowEngine
    {
        return new ChatFlowEngine(
            $executor,
            Mockery::mock(ChatFlowTriggerResolver::class),
            Mockery::mock(ChatFlowAiResponder::class),
            new ChatFlowSentiment(null),
            new ChatFlowLocalizer(null),
            Mockery::mock(ChatFlowHandoffSummary::class),
            new IdentificationInputHandler(Mockery::mock(CustomerIdentityResolver::class), new ChatFlowIdentityOtp),
            new DocumentUploadInputHandler(new ChatFlowLocalizer(null)),
            new CsatInputHandler(new ChatFlowLocalizer(null)),
            new ChatFlowScheduler,
        );
    }

    private function flow(array $askData): ChatFlow
    {
        $flow = new ChatFlow;
        $flow->nodes = [
            ['id' => 'ask', 'type' => 'collect_input', 'parentId' => null, 'data' => $askData + ['variable_name' => 'customer_email', 'question' => 'Email?']],
            ['id' => 'next', 'type' => 'message', 'parentId' => 'ask', 'data' => ['text' => 'Gracias']],
        ];

        return $flow;
    }

    private function runFlow(ChatFlowEngine $engine, InMemoryChatFlowSession $session, ChatFlow $flow): void
    {
        $m = new ReflectionMethod(ChatFlowEngine::class, 'runFrom');
        $m->setAccessible(true);
        $m->invoke($engine, $session, $flow->nodes[0]);
    }

    public function test_engine_skips_the_question_when_the_variable_is_known(): void
    {
        Event::fake();
        $flow = $this->flow(['skip_if_set' => true]);
        $session = new InMemoryChatFlowSession($flow, ['context' => ['customer_email' => 'a@b.es']]);

        $executor = Mockery::mock(ChatFlowNodeExecutor::class);
        $executor->shouldReceive('execute')->once()->withArgs(fn ($node) => $node['id'] === 'next')->andReturn(null);

        $this->runFlow($this->engine($executor), $session, $flow);
    }

    public function test_engine_still_asks_when_unknown_or_flag_off(): void
    {
        Event::fake();

        foreach ([[['skip_if_set' => true], []], [['skip_if_set' => false], ['customer_email' => 'a@b.es']]] as [$data, $context]) {
            $flow = $this->flow($data);
            $session = new InMemoryChatFlowSession($flow, ['context' => $context]);

            $executor = Mockery::mock(ChatFlowNodeExecutor::class);
            $executor->shouldReceive('execute')->once()->withArgs(fn ($node) => $node['id'] === 'ask')->andReturn(null);

            $this->runFlow($this->engine($executor), $session, $flow);
        }
    }

    public function test_simulator_skips_a_known_variable_and_asks_otherwise(): void
    {
        config()->set('cache.default', 'array');
        $sim = new ChatFlowTestSimulator;
        $node = fn (string $id, ?string $parent, array $data, string $type = 'collect_input') => ['id' => $id, 'type' => $type, 'parentId' => $parent, 'label' => $id, 'data' => $data];

        $nodes = [
            $node('s', null, [], 'start'),
            $node('a1', 's', ['question' => 'Email?', 'variable_name' => 'email']),
            $node('a2', 'a1', ['question' => 'Otra vez el email?', 'variable_name' => 'email', 'skip_if_set' => true]),
            $node('m', 'a2', ['text' => 'Listo {{email}}'], 'message'),
        ];

        $start = $sim->start($nodes);
        $reply = $sim->reply($start['session_key'], 'ada@x.es');
        $text = implode("\n", array_map(fn ($m) => $m['text'] ?? '', $reply['messages']));

        $this->assertStringNotContainsString('Otra vez', $text);
        $this->assertStringContainsString('Listo ada@x.es', $text);
        $this->assertSame('completed', $reply['status']);

        $nodes[2]['data']['skip_if_set'] = false;
        $start = $sim->start($nodes);
        $reply = $sim->reply($start['session_key'], 'ada@x.es');
        $this->assertStringContainsString('Otra vez', implode("\n", array_map(fn ($m) => $m['text'] ?? '', $reply['messages'])));
        $this->assertSame('active', $reply['status']);
    }

    public function test_simulator_validates_order_ref_and_enum(): void
    {
        config()->set('cache.default', 'array');
        $sim = new ChatFlowTestSimulator;
        $nodes = [
            ['id' => 's', 'type' => 'start', 'parentId' => null, 'label' => 's', 'data' => []],
            ['id' => 'a', 'type' => 'collect_input', 'parentId' => 's', 'label' => 'a', 'data' => ['question' => 'Pedido?', 'variable_name' => 'ref', 'validation' => 'order_ref']],
            ['id' => 'm', 'type' => 'message', 'parentId' => 'a', 'label' => 'm', 'data' => ['text' => 'Ref {{ref}}']],
        ];

        $start = $sim->start($nodes);
        $bad = $sim->reply($start['session_key'], 'nope');
        $this->assertSame('active', $bad['status']);

        $ok = $sim->reply($start['session_key'], 'abcdefghi');
        $this->assertStringContainsString('Ref ABCDEFGHI', implode("\n", array_map(fn ($m) => $m['text'] ?? '', $ok['messages'])));
    }
}
