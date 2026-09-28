<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskAiPrompts\Listeners\RecordAiAnswerFeedback;
use Modules\HelpdeskAiPrompts\Models\AiPromptRun;
use Modules\HelpdeskAiPrompts\Observers\ConversationItemAiCaseObserver;
use Modules\HelpdeskAiPrompts\Services\PromptRunRecorder;
use Modules\HelpdeskLivechat\Events\AiAnswerRated;

class PromptRunRecorderTest extends HelpdeskAiPromptsTestCase
{
    use MockeryPHPUnitIntegration;

    public function test_record_creates_a_run(): void
    {
        $run = app(PromptRunRecorder::class)->record('trace-123', 'devoluciones', 'keyword', 'respond', ['answer_customer'], 250);

        $this->assertNotNull($run);
        $this->assertDatabaseHas('helpdesk_ai_prompt_runs', [
            'id' => $run->id,
            'trace_id' => 'trace-123',
            'case_key' => 'devoluciones',
            'routed_by' => 'keyword',
            'action' => 'respond',
            'latency_ms' => 250,
        ], 'helpdesk');
    }

    public function test_record_ignores_test_runner_traces(): void
    {
        $run = app(PromptRunRecorder::class)->record('test-abc123', 'devoluciones', 'forced', 'respond', [], 10);

        $this->assertNull($run);
        $this->assertDatabaseMissing('helpdesk_ai_prompt_runs', ['trace_id' => 'test-abc123'], 'helpdesk');
    }

    public function test_link_conversation_item_is_a_noop_without_ai_agent_metadata(): void
    {
        $item = $this->fakeItem(['metadata' => []]);

        app(PromptRunRecorder::class)->linkConversationItem($item);

        $this->assertArrayNotHasKey('ai_case', $item->metadata ?? []);
    }

    public function test_link_conversation_item_is_a_noop_when_there_is_no_flow_session(): void
    {
        // In this environment HelpdeskChatFlow is disabled, so
        // helpdesk_chat_flow_sessions does not exist: the real lookup must
        // fail closed instead of throwing.
        $item = $this->fakeItem(['metadata' => ['ai_agent' => true]]);

        app(PromptRunRecorder::class)->linkConversationItem($item);

        $this->assertArrayNotHasKey('ai_case', $item->metadata ?? []);
    }

    public function test_link_conversation_item_updates_the_matching_run_and_tags_the_item(): void
    {
        $run = AiPromptRun::query()->create([
            'trace_id' => 'trace-xyz', 'case_key' => 'devoluciones', 'routed_by' => 'keyword',
            'action' => 'respond', 'used_tools' => [], 'created_at' => now(),
        ]);

        $recorder = new class extends PromptRunRecorder
        {
            protected function latestSessionTraceId(?int $conversationId): ?string
            {
                return 'trace-xyz';
            }
        };

        $item = $this->fakeItem(['metadata' => ['ai_agent' => true]]);

        $recorder->linkConversationItem($item);

        $this->assertSame('devoluciones', $item->metadata['ai_case']);
        $this->assertSame($item->id, $run->fresh()->item_id);
        $this->assertSame($item->conversation_id, $run->fresh()->conversation_id);
    }

    public function test_observer_delegates_to_the_recorder(): void
    {
        $item = $this->fakeItem();
        $recorder = Mockery::mock(PromptRunRecorder::class);
        $recorder->shouldReceive('linkConversationItem')->once()->with($item);

        (new ConversationItemAiCaseObserver($recorder))->created($item);
    }

    public function test_feedback_listener_updates_the_matching_run(): void
    {
        $item = $this->fakeItem(['id' => 555]);

        $run = AiPromptRun::query()->create([
            'trace_id' => 'trace-fb', 'case_key' => 'devoluciones', 'routed_by' => 'keyword',
            'action' => 'respond', 'used_tools' => [], 'item_id' => $item->id, 'created_at' => now(),
        ]);

        (new RecordAiAnswerFeedback(app(PromptRunRecorder::class)))->handle(new AiAnswerRated($item, 1));

        $this->assertSame(1, $run->fresh()->feedback);
    }

    private function fakeItem(array $attrs = []): ConversationItem
    {
        $item = new ConversationItem;
        $item->forceFill(array_merge([
            'id' => random_int(100000, 999999),
            'conversation_id' => random_int(100000, 999999),
            'type' => 'message',
            'metadata' => ['ai_agent' => true],
        ], $attrs));
        $item->exists = true;
        $item->syncOriginal();

        return $item;
    }
}
