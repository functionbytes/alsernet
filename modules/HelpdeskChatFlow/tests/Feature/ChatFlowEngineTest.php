<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Mockery\MockInterface;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Events\ChatFlowCompleted;
use Modules\HelpdeskChatFlow\Events\ChatFlowCsatRecorded;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowAiResponder;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;
use Modules\HelpdeskChatFlow\Services\ChatFlowHandoffSummary;
use Modules\HelpdeskChatFlow\Services\ChatFlowIdentityOtp;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\ChatFlowNodeExecutor;
use Modules\HelpdeskChatFlow\Services\ChatFlowSentiment;
use Modules\HelpdeskChatFlow\Services\ChatFlowTriggerResolver;
use Modules\HelpdeskChatFlow\Services\CustomerIdentityResolver;
use Modules\HelpdeskChatFlow\Tests\Support\InMemoryChatFlowSession;
use Tests\TestCase;

class ChatFlowEngineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEngine(
        ?MockInterface $executor = null,
        ?MockInterface $resolver = null,
        ?MockInterface $identityResolver = null,
        ?MockInterface $aiResponder = null,
    ): ChatFlowEngine {
        return new ChatFlowEngine(
            $executor ?? Mockery::mock(ChatFlowNodeExecutor::class),
            $resolver ?? Mockery::mock(ChatFlowTriggerResolver::class),
            $identityResolver ?? Mockery::mock(CustomerIdentityResolver::class),
            $aiResponder ?? Mockery::mock(ChatFlowAiResponder::class),
            new ChatFlowSentiment(null),
            new ChatFlowLocalizer(null),
            Mockery::mock(ChatFlowHandoffSummary::class),
            new ChatFlowIdentityOtp,
        );
    }

    private function makeConversation(int $id = 1, ?int $inboxId = null): Conversation
    {
        $conversation = new Conversation;
        $conversation->setRawAttributes(['id' => $id, 'inbox_id' => $inboxId]);

        return $conversation;
    }

    /**
     * Create an in-memory session double (no DB) for engine assertions.
     */
    private function makeSessionStub(ChatFlow $flow, array $attrs = []): InMemoryChatFlowSession
    {
        return new InMemoryChatFlowSession($flow, $attrs);
    }

    // ─── triggerFor ────────────────────────────────────────────────────────────

    public function test_trigger_for_returns_null_when_resolver_finds_no_flow(): void
    {
        $resolver = Mockery::mock(ChatFlowTriggerResolver::class);
        $conversation = $this->makeConversation();

        $resolver->shouldReceive('resolve')
            ->once()
            ->with($conversation, 'conversation_start', [])
            ->andReturn(null);

        $result = $this->makeEngine(resolver: $resolver)
            ->triggerFor($conversation, 'conversation_start');

        $this->assertNull($result);
    }

    // ─── A/B testing ───────────────────────────────────────────────────────────

    public function test_pick_ab_variant_returns_same_flow_without_variant(): void
    {
        $flow = new ChatFlow;
        $flow->trigger_conditions = [];

        $this->assertSame($flow, $this->makeEngine()->pickAbVariant($flow));
    }

    public function test_pick_ab_variant_keeps_flow_when_split_is_zero(): void
    {
        $flow = new ChatFlow;
        // ab_split 0 → random_int(1,100) is always > 0 → never switches to the variant.
        $flow->trigger_conditions = ['ab_variant_id' => 999999, 'ab_split' => 0];

        $this->assertSame($flow, $this->makeEngine()->pickAbVariant($flow));
    }

    /**
     * Regresión: antes el brazo A/B se re-tiraba con random_int() en cada
     * evaluación, así que una misma conversación podía ver base y luego variante
     * (contaminando la estadística). Ahora el bucket es determinista por
     * conversación: repetir la evaluación devuelve siempre el mismo brazo.
     */
    public function test_pick_ab_variant_is_deterministic_per_conversation(): void
    {
        $flow = new ChatFlow;
        $flow->id = 1;
        $flow->trigger_conditions = ['ab_variant_id' => 999999, 'ab_split' => 50];

        // Elegir una conversación cuyo bucket caiga en el brazo base (> split)
        // para no tocar la BD (ChatFlow::find del variante) y mantener el test unitario.
        $conversation = new Conversation;
        $conversation->id = 1;
        while (((crc32($flow->id.':'.$conversation->id) % 100) + 1) <= 50) {
            $conversation->id++;
        }

        $engine = $this->makeEngine();

        // Con random_int() esto fallaría ~1-0.5^40 de las veces; ahora es estable.
        for ($i = 0; $i < 40; $i++) {
            $this->assertSame($flow, $engine->pickAbVariant($flow, $conversation));
        }
    }

    // ─── processMessage ────────────────────────────────────────────────────────

    public function test_process_message_returns_early_for_inactive_session(): void
    {
        $executor = Mockery::mock(ChatFlowNodeExecutor::class);
        $executor->shouldNotReceive('execute');

        $engine = $this->makeEngine(executor: $executor);

        $session = Mockery::mock(ChatFlowSession::class);
        $session->shouldReceive('isActive')->once()->andReturn(false);

        $engine->processMessage($session, 'hello');
    }

    public function test_process_message_abandons_session_when_current_node_not_found(): void
    {
        $flow = new ChatFlow;
        $flow->nodes = [];

        $session = $this->makeSessionStub($flow, ['current_node_id' => 'nonexistent_node']);

        $this->makeEngine()->processMessage($session, 'hello');

        $this->assertNotEmpty($session->updates);
        $this->assertSame('abandoned', $session->updates[0]['status']);
    }

    public function test_process_message_returns_early_for_non_wait_type_node(): void
    {
        $executor = Mockery::mock(ChatFlowNodeExecutor::class);
        $executor->shouldNotReceive('execute');

        $engine = $this->makeEngine(executor: $executor);

        $flow = new ChatFlow;
        $flow->nodes = [
            ['id' => 'n1', 'type' => 'message', 'data' => ['text' => 'Hi']],
        ];

        $session = $this->makeSessionStub($flow, ['current_node_id' => 'n1']);

        // No exception = processMessage returned early without calling executor
        $engine->processMessage($session, 'user reply');

        $this->assertEmpty($session->updates);
    }

    // ─── processIdentification ─────────────────────────────────────────────────

    public function test_identification_increments_attempts_when_customer_not_found(): void
    {
        $identityResolver = Mockery::mock(CustomerIdentityResolver::class);
        $identityResolver->shouldReceive('resolve')->once()->andReturn(null);

        $engine = $this->makeEngine(identityResolver: $identityResolver);

        $flow = new ChatFlow;
        $flow->nodes = [
            ['id' => 'id_node', 'type' => 'identify_customer', 'data' => ['max_attempts' => 3, 'not_found_message' => 'No encontrado']],
        ];

        /** @var MockInterface&HasMany $hasManyMock */
        $hasManyMock = Mockery::mock(HasMany::class);
        $hasManyMock->shouldReceive('create')->once()->andReturn(null);

        $conversation = Mockery::mock(Conversation::class)->makePartial();
        $conversation->shouldReceive('items')->andReturn($hasManyMock);

        $session = $this->makeSessionStub($flow, [
            'current_node_id' => 'id_node',
            'conversation' => $conversation,
        ]);

        $engine->processMessage($session, 'invalid_input');

        $ctx = $session->getContextStore();
        $this->assertArrayHasKey('_identify_attempts_id_node', $ctx);
        $this->assertSame(1, $ctx['_identify_attempts_id_node']);
    }

    public function test_identification_transfers_session_after_max_attempts_reached(): void
    {
        $identityResolver = Mockery::mock(CustomerIdentityResolver::class);
        $identityResolver->shouldReceive('resolve')->once()->andReturn(null);

        $engine = $this->makeEngine(identityResolver: $identityResolver);

        $flow = new ChatFlow;
        $flow->nodes = [
            ['id' => 'id_node', 'type' => 'identify_customer', 'data' => ['max_attempts' => 3, 'transfer_on_failure' => true]],
        ];

        $session = $this->makeSessionStub($flow, [
            'current_node_id' => 'id_node',
            'context' => ['_identify_attempts_id_node' => 2], // 2 prior, next = 3 = max
        ]);

        $engine->processMessage($session, 'still_nothing');

        $this->assertContains('transferred', array_column($session->updates, 'status'));
        Event::assertDispatched(ChatFlowCompleted::class);
    }

    public function test_identification_sets_customer_context_values_when_otp_disabled(): void
    {
        // require_otp=false: flujo puramente informativo, sin verificación.
        $customerData = ['name' => 'Juan Lopez', 'email' => 'juan@test.com'];

        $identityResolver = Mockery::mock(CustomerIdentityResolver::class);
        $identityResolver->shouldReceive('resolve')->once()->andReturn($customerData);

        $engine = $this->makeEngine(identityResolver: $identityResolver);

        $flow = new ChatFlow;
        $flow->nodes = [
            ['id' => 'id_node', 'type' => 'identify_customer', 'data' => ['require_otp' => false]],
        ];

        $session = $this->makeSessionStub($flow, ['current_node_id' => 'id_node']);

        $engine->processMessage($session, 'juan@test.com');

        $ctx = $session->getContextStore();
        $this->assertSame('Juan Lopez', $ctx['customer_name']);
        $this->assertTrue($ctx['customer_identified']);
        $this->assertSame('juan@test.com', $ctx['customer_email']);
    }

    public function test_identification_requires_otp_by_default_before_marking_identified(): void
    {
        // Por defecto (require_otp implícito): resolver al cliente NO lo marca
        // identificado; se envía un código y se espera a que lo introduzca.
        Mail::fake();
        $customerData = ['name' => 'Juan Lopez', 'email' => 'juan@test.com'];

        $identityResolver = Mockery::mock(CustomerIdentityResolver::class);
        $identityResolver->shouldReceive('resolve')->once()->andReturn($customerData);

        $engine = $this->makeEngine(identityResolver: $identityResolver);

        $flow = new ChatFlow;
        $flow->nodes = [
            ['id' => 'id_node', 'type' => 'identify_customer', 'data' => []],
        ];
        /** @var MockInterface&HasMany $hasManyMock */
        $hasManyMock = Mockery::mock(HasMany::class);
        $hasManyMock->shouldReceive('create')->andReturn(null);
        $conversation = Mockery::mock(Conversation::class)->makePartial();
        $conversation->shouldReceive('items')->andReturn($hasManyMock);

        $session = $this->makeSessionStub($flow, [
            'current_node_id' => 'id_node',
            'conversation' => $conversation,
        ]);

        $engine->processMessage($session, 'juan@test.com');

        $ctx = $session->getContextStore();
        // Aún NO identificado: hay un OTP pendiente y el código se envió fuera
        // de banda (al email), nunca por el chat.
        $this->assertArrayNotHasKey('customer_identified', $ctx);
        $this->assertTrue($ctx['_otp_pending'] ?? false);
        $this->assertNotEmpty($ctx['_otp_hash'] ?? null);
    }

    public function test_identification_marks_identified_after_correct_otp(): void
    {
        Mail::fake();
        $customerData = ['name' => 'Juan Lopez', 'email' => 'juan@test.com'];

        $identityResolver = Mockery::mock(CustomerIdentityResolver::class);
        $identityResolver->shouldReceive('resolve')->once()->andReturn($customerData);

        $engine = $this->makeEngine(identityResolver: $identityResolver);

        $flow = new ChatFlow;
        $flow->nodes = [
            ['id' => 'id_node', 'type' => 'identify_customer', 'data' => []],
        ];
        /** @var MockInterface&HasMany $hasManyMock */
        $hasManyMock = Mockery::mock(HasMany::class);
        $hasManyMock->shouldReceive('create')->andReturn(null);
        $conversation = Mockery::mock(Conversation::class)->makePartial();
        $conversation->shouldReceive('items')->andReturn($hasManyMock);

        $session = $this->makeSessionStub($flow, [
            'current_node_id' => 'id_node',
            'conversation' => $conversation,
        ]);

        // 1) Identificación → envía OTP.
        $engine->processMessage($session, 'juan@test.com');

        // Recuperar el código real desdel hash es imposible; en su lugar se
        // fuerza un hash conocido en el contexto y se verifica la mecánica.
        $session->setContextValue('_otp_hash', Hash::make('123456'));

        // 2) Cliente introduce el código correcto → identificado.
        $engine->processMessage($session, '123456');

        $ctx = $session->getContextStore();
        $this->assertTrue($ctx['customer_identified'] ?? false);
        $this->assertSame('juan@test.com', $ctx['customer_email']);
        $this->assertNull($ctx['_otp_pending']);
    }

    // ─── node timeout ───────────────────────────────────────────────────────────

    public function test_node_timeout_close_abandons_the_session(): void
    {
        $flow = new ChatFlow;
        $session = $this->makeSessionStub($flow); // no conversation → null-safe

        $node = ['id' => 'n1', 'type' => 'collect_input', 'data' => ['timeout_action' => 'close']];

        $this->makeEngine()->handleNodeTimeout($session, $node);

        $this->assertNotEmpty($session->updates);
        $this->assertSame('abandoned', $session->updates[0]['status']);
        Event::assertDispatched(ChatFlowCompleted::class);
    }

    public function test_node_timeout_transfer_marks_session_transferred(): void
    {
        $flow = new ChatFlow;
        $session = $this->makeSessionStub($flow);

        $node = ['id' => 'n1', 'type' => 'collect_input', 'data' => ['timeout_action' => 'transfer']];

        $this->makeEngine()->handleNodeTimeout($session, $node);

        $this->assertSame('transferred', $session->updates[0]['status']);
        Event::assertDispatched(ChatFlowCompleted::class);
    }

    // ─── CSAT loop ───────────────────────────────────────────────────────────────

    public function test_low_csat_score_escalates_to_human_and_records_event(): void
    {
        $flow = new ChatFlow;
        $flow->trigger_conditions = [];
        $flow->nodes = [
            ['id' => 'csat1', 'type' => 'csat', 'data' => ['csat_low_action' => 'escalate', 'csat_low_threshold' => 2]],
        ];

        /** @var MockInterface&HasMany $hasMany */
        $hasMany = Mockery::mock(HasMany::class);
        $hasMany->shouldReceive('create')->andReturn(null);

        $conversation = Mockery::mock(Conversation::class)->makePartial();
        $conversation->shouldReceive('items')->andReturn($hasMany);
        $conversation->shouldReceive('update')->andReturn(true);
        $conversation->shouldReceive('releaseFromBot')->once();

        $session = $this->makeSessionStub($flow, [
            'current_node_id' => 'csat1',
            'conversation' => $conversation,
        ]);

        $this->makeEngine()->processMessage($session, '1');

        $this->assertContains('transferred', array_column($session->updates, 'status'));
        $this->assertSame(1, $session->getContextStore()['csat_score']);
        Event::assertDispatched(ChatFlowCsatRecorded::class);
    }

    public function test_high_csat_score_does_not_escalate(): void
    {
        $flow = new ChatFlow;
        $flow->trigger_conditions = [];
        $flow->nodes = [
            ['id' => 'csat1', 'type' => 'csat', 'data' => ['csat_low_action' => 'escalate', 'csat_low_threshold' => 2, 'thanks_message' => 'Gracias']],
        ];

        /** @var MockInterface&HasMany $hasMany */
        $hasMany = Mockery::mock(HasMany::class);
        $hasMany->shouldReceive('create')->andReturn(null);

        $conversation = Mockery::mock(Conversation::class)->makePartial();
        $conversation->shouldReceive('items')->andReturn($hasMany);
        $conversation->shouldReceive('update')->andReturn(true);
        $conversation->shouldNotReceive('releaseFromBot');

        $session = $this->makeSessionStub($flow, [
            'current_node_id' => 'csat1',
            'conversation' => $conversation,
        ]);

        $this->makeEngine()->processMessage($session, '5');

        $this->assertNotContains('transferred', array_column($session->updates, 'status'));
        Event::assertDispatched(ChatFlowCsatRecorded::class);
    }

    // ─── method surface ────────────────────────────────────────────────────────

    public function test_engine_exposes_required_public_methods(): void
    {
        $engine = $this->makeEngine();

        $this->assertTrue(method_exists($engine, 'hasActiveSession'));
        $this->assertTrue(method_exists($engine, 'getActiveSession'));
        $this->assertTrue(method_exists($engine, 'triggerFor'));
        $this->assertTrue(method_exists($engine, 'processMessage'));
        $this->assertTrue(method_exists($engine, 'start'));
    }
}
