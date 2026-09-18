<?php

namespace Modules\Helpdesk\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Events\ConversationUpdated;
use Modules\Helpdesk\Models\AutomationRule;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Macro;
use Modules\Helpdesk\Services\Automation\Actions\ChangePriorityAction;
use Modules\Helpdesk\Services\Automation\AutomationActionRegistry;
use Modules\Helpdesk\Services\Automation\AutomationEngine;
use Modules\Helpdesk\Services\Automation\ConditionEvaluator;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;
use Modules\Helpdesk\Services\Macros\MacroExecutorService;
use Modules\Helpdesk\Services\Templates\LiquidRenderer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Atomicidad de la ejecución de acciones (AutomationEngine::executeActions y
 * MacroExecutorService::apply): todas las mutaciones van en una transacción
 * sobre la conexión helpdesk. Si una acción falla a mitad de la lista, las
 * acciones anteriores se revierten (nada de estado parcial) y el manejo de
 * errores existente se mantiene (log + last_error / lista failed).
 */
class AutomationExecutionTransactionTest extends HelpdeskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // El broadcast de ConversationUpdated corre inline (queue sync) en
        // tests y su broadcastWith() no es el objeto de esta suite.
        Event::fake([ConversationUpdated::class]);
    }

    private function makeRegistry(): AutomationActionRegistry
    {
        $registry = new AutomationActionRegistry;
        $registry->register('change_priority', ChangePriorityAction::class);
        $registry->register(FailingActionStub::actionType(), FailingActionStub::class);

        return $registry;
    }

    public function test_automation_rule_failure_rolls_back_previous_actions_and_records_last_error(): void
    {
        $conversation = Conversation::factory()->create(['priority' => 'low']);

        $rule = AutomationRule::create([
            'name' => 'Atomic rule',
            'trigger_event' => 'conversation.created',
            'conditions' => [],
            'actions' => [
                ['type' => 'change_priority', 'params' => ['priority' => 'urgent']],
                ['type' => FailingActionStub::actionType(), 'params' => []],
            ],
            'is_active' => true,
            'user_id' => $this->manager->id,
        ]);

        $engine = new AutomationEngine(new ConditionEvaluator, $this->makeRegistry());
        $engine->runFor('conversation.created', ['conversation' => $conversation]);

        // La primera acción se revirtió: sin estado parcial.
        $this->assertSame('low', $conversation->fresh()->priority);

        // El manejo de errores existente se mantiene: last_error persistido
        // (fuera de la transacción revertida) y la regla queda marcada como ejecutada.
        $rule->refresh();
        $this->assertStringContainsString(FailingActionStub::MESSAGE, (string) $rule->last_error);
        $this->assertEquals(1, $rule->run_count);
        $this->assertNotNull($rule->last_run_at);
    }

    public function test_automation_rule_commits_when_all_actions_succeed(): void
    {
        $conversation = Conversation::factory()->create(['priority' => 'low']);

        AutomationRule::create([
            'name' => 'Happy rule',
            'trigger_event' => 'conversation.created',
            'conditions' => [],
            'actions' => [
                ['type' => 'change_priority', 'params' => ['priority' => 'urgent']],
            ],
            'is_active' => true,
            'user_id' => $this->manager->id,
        ]);

        $engine = new AutomationEngine(new ConditionEvaluator, $this->makeRegistry());
        $engine->runFor('conversation.created', ['conversation' => $conversation]);

        $this->assertSame('urgent', $conversation->fresh()->priority);
    }

    public function test_macro_failure_rolls_back_and_reports_failed_action(): void
    {
        $conversation = Conversation::factory()->create(['priority' => 'low']);

        // 'change_status' del macro mapea al tipo de automatización
        // 'change_status', que aquí registramos con el stub que falla.
        $registry = new AutomationActionRegistry;
        $registry->register('change_priority', ChangePriorityAction::class);
        $registry->register('change_status', FailingActionStub::class);

        $macro = Macro::factory()->create([
            'actions' => [
                ['type' => 'change_priority', 'params' => ['priority' => 'urgent']],
                ['type' => 'change_status', 'params' => ['status' => 'resolved']],
            ],
        ]);

        $service = new MacroExecutorService($registry, app(LiquidRenderer::class));
        $result = $service->apply($macro, $conversation);

        // Rollback total: ni la prioridad cambió ni hay acciones "ejecutadas".
        $this->assertSame('low', $conversation->fresh()->priority);
        $this->assertSame([], $result['executed']);
        $this->assertCount(1, $result['failed']);
        $this->assertSame('change_status', $result['failed'][0]['type']);
        $this->assertStringContainsString(FailingActionStub::MESSAGE, $result['failed'][0]['reason']);

        // El contador de uso se actualiza fuera de la transacción revertida.
        $this->assertEquals(1, $macro->fresh()->usage_count);
    }

    public function test_macro_unknown_action_type_does_not_abort_the_rest(): void
    {
        $conversation = Conversation::factory()->create(['priority' => 'low']);

        $registry = new AutomationActionRegistry;
        $registry->register('change_priority', ChangePriorityAction::class);

        $macro = Macro::factory()->create([
            'actions' => [
                ['type' => 'does_not_exist', 'params' => []],
                ['type' => 'change_priority', 'params' => ['priority' => 'urgent']],
            ],
        ]);

        $service = new MacroExecutorService($registry, app(LiquidRenderer::class));
        $result = $service->apply($macro, $conversation);

        $this->assertSame('urgent', $conversation->fresh()->priority);
        $this->assertSame(['change_priority'], $result['executed']);
        $this->assertSame([['type' => 'does_not_exist', 'reason' => 'unknown action type']], $result['failed']);
    }
}

/**
 * Acción que siempre falla, para simular un fallo a mitad de la lista.
 */
class FailingActionStub implements AutomationAction
{
    public const MESSAGE = 'stub action exploded';

    public static function actionType(): string
    {
        return 'failing_stub';
    }

    public static function paramSchema(): array
    {
        return [];
    }

    public function execute(array $params, array $context): void
    {
        throw new \RuntimeException(self::MESSAGE);
    }
}
