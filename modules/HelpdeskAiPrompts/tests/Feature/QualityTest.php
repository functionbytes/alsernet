<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskAiPrompts\Database\Seeders\HelpdeskAiPromptsPermissionsSeeder;
use Modules\HelpdeskAiPrompts\Jobs\RunPromptRegressionJob;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Models\AiPromptRun;
use Modules\HelpdeskAiPrompts\Models\AiRegressionReport;
use Modules\HelpdeskAiPrompts\Notifications\AiPromptQualityAlertNotification;
use Modules\HelpdeskAiPrompts\Services\PromptRunRecorder;
use Modules\HelpdeskAiPrompts\Services\Quality\PromptRegressionRunner;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Spatie\Permission\PermissionRegistrar;

class QualityTest extends HelpdeskAiPromptsTestCase
{
    /** @var array<int, string> */
    public static array $traces = [];

    private function makeCase(array $overrides = []): AiPromptCase
    {
        return AiPromptCase::query()->create($overrides + [
            'key' => 'devoluciones', 'name' => 'Devoluciones', 'description' => 'd', 'priority' => 0,
            'is_active' => true, 'instructions' => 'inst',
            'test_questions' => [['question' => 'Pregunta de prueba']],
        ]);
    }

    private function bindFakeAgent(string $action = 'respond', array $tools = ['search_help']): void
    {
        self::$traces = [];
        $fake = new class($action, $tools)
        {
            public function __construct(private string $action, private array $tools) {}

            public function run(string $question, array $context, array $data, string $locale, ?object $catalog = null): array
            {
                QualityTest::$traces[] = $context['_trace_id'];

                return [
                    'action' => $this->action, 'text' => 'Respuesta nueva a: '.$question, 'used_tools' => $this->tools, 'products' => [],
                    'usage' => ['prompt_tokens' => 1000, 'completion_tokens' => 200, 'model' => 'gpt-4o-mini', 'calls' => 1],
                ];
            }
        };
        $this->app->instance(ChatFlowAgentService::class, $fake);
    }

    private function seedRealConversation(string $caseKey, string $question, string $answer): AiPromptRun
    {
        $conversation = Conversation::factory()->create();
        ConversationItem::factory()->create(['conversation_id' => $conversation->id, 'type' => 'message', 'item_type' => 'message', 'is_internal' => false, 'user_id' => null, 'body' => $question, 'metadata' => []]);
        $item = ConversationItem::factory()->create(['conversation_id' => $conversation->id, 'type' => 'message', 'item_type' => 'message', 'is_internal' => false, 'user_id' => null, 'body' => $answer, 'metadata' => ['ai_agent' => true]]);

        return AiPromptRun::query()->create([
            'trace_id' => 'real-'.uniqid(), 'conversation_id' => $conversation->id, 'item_id' => $item->id, 'case_key' => $caseKey,
            'routed_by' => 'keyword', 'action' => 'respond', 'used_tools' => ['search_help'], 'created_at' => now(),
        ]);
    }

    private function fakeJudge(int $score): void
    {
        config(['services.openai.key' => 'sk-test']);
        Http::fake(['api.openai.com/*' => Http::response([
            'model' => 'gpt-4o-mini',
            'usage' => ['prompt_tokens' => 300, 'completion_tokens' => 30],
            'choices' => [['message' => ['content' => '```json'."\n".'{"score": '.$score.', "reason": "Parecida"}'."\n".'```']]],
        ])]);
    }

    private function newReport(AiPromptCase $case): AiRegressionReport
    {
        return AiRegressionReport::query()->create(['case_key' => $case->key, 'case_version' => $case->version, 'status' => 'queued']);
    }

    public function test_regression_with_real_conversations_is_saved_and_judged(): void
    {
        $case = $this->makeCase();
        $this->seedRealConversation('devoluciones', '<p>¿Cómo devuelvo mi pedido?</p>', 'Tienes 15 días.');
        $this->bindFakeAgent();
        $this->fakeJudge(4);

        $report = app(PromptRegressionRunner::class)->execute($this->newReport($case), 10);

        $this->assertSame('completed', $report->status);
        $this->assertSame(2, $report->questions_total); // 1 real + 1 test_question
        $this->assertSame(0, $report->regressions);
        $this->assertSame(4.0, $report->avg_score);
        $this->assertGreaterThan(0, $report->cost_eur);

        $real = $report->results[0];
        $this->assertSame('real', $real['source']);
        $this->assertSame('¿Cómo devuelvo mi pedido?', $real['question']);
        $this->assertSame('Tienes 15 días.', $real['original']['answer']);
        $this->assertFalse($real['tools_changed']);
        $this->assertFalse($real['new']['escalated']);
        $this->assertSame(4, $real['score']);
        $this->assertSame('no_baseline', $report->results[1]['verdict']);
        $this->assertDatabaseHas('helpdesk_ai_regression_reports', ['id' => $report->id, 'status' => 'completed'], 'helpdesk');
    }

    public function test_low_judge_score_or_new_escalation_is_a_regression(): void
    {
        $case = $this->makeCase();
        $this->seedRealConversation('devoluciones', 'Pregunta uno', 'Respuesta uno');
        $this->bindFakeAgent('escalate', ['escalate_to_agent']);
        $this->fakeJudge(1);

        $report = app(PromptRegressionRunner::class)->execute($this->newReport($case), 5);

        $row = $report->results[0];
        $this->assertSame('regression', $row['verdict']);
        $this->assertTrue($row['escalation_changed']);
        $this->assertTrue($row['tools_changed']);
        $this->assertSame(1, $report->regressions);
        $this->assertSame(1, $report->summary['escalations_after']);
    }

    public function test_test_traces_do_not_count_in_metrics(): void
    {
        $case = $this->makeCase();
        $this->bindFakeAgent();
        config(['helpdeskaiprompts_quality.regression.judge_enabled' => false]);

        app(PromptRegressionRunner::class)->execute($this->newReport($case), 5);

        $this->assertNotEmpty(self::$traces);
        foreach (self::$traces as $trace) {
            $this->assertStringStartsWith('test-regression-', $trace);
            $this->assertNull(app(PromptRunRecorder::class)->record($trace, 'devoluciones', 'forced', 'respond', [], 1));
        }
        $this->assertSame(0, AiPromptRun::query()->count());
    }

    public function test_question_limit_is_capped_by_config(): void
    {
        $case = $this->makeCase(['test_questions' => collect(range(1, 30))->map(fn ($i) => ['question' => "Pregunta {$i}"])->all()]);
        $this->bindFakeAgent();
        config(['helpdeskaiprompts_quality.regression.judge_enabled' => false, 'helpdeskaiprompts_quality.regression.max_questions' => 20]);

        $report = app(PromptRegressionRunner::class)->execute($this->newReport($case), 999);

        $this->assertSame(20, $report->questions_total);
    }

    public function test_cost_cap_stops_the_run(): void
    {
        $case = $this->makeCase(['test_questions' => collect(range(1, 5))->map(fn ($i) => ['question' => "Pregunta {$i}"])->all()]);
        $this->bindFakeAgent();
        config(['helpdeskaiprompts_quality.regression.judge_enabled' => false, 'helpdeskaiprompts_quality.regression.max_cost_eur' => 0.0000001]);

        $report = app(PromptRegressionRunner::class)->execute($this->newReport($case), 5);

        $this->assertSame(1, $report->questions_total);
        $this->assertTrue($report->summary['truncated_by_cost']);
    }

    private function alertRuns(string $key, int $total, int $escalations, int $likes = 0, int $dislikes = 0, float $cost = 0.0): void
    {
        for ($i = 0; $i < $total; $i++) {
            $feedback = $i < $likes ? 1 : ($i < $likes + $dislikes ? -1 : null);
            AiPromptRun::query()->create([
                'trace_id' => "t-{$key}-{$i}", 'case_key' => $key, 'routed_by' => 'keyword',
                'action' => $i < $escalations ? 'escalate' : 'respond', 'feedback' => $feedback,
                'cost_eur' => $cost / $total, 'created_at' => now()->subHours(2),
            ]);
        }
    }

    private function manager(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskAiPromptsPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['helpdesk.ai-prompts.view', 'helpdesk.ai-prompts.manage']);

        return $user;
    }

    public function test_alerts_fire_over_thresholds_once_per_cooldown(): void
    {
        Notification::fake();
        Cache::flush();
        $manager = $this->manager();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('helpdesk.ai-prompts.view');
        $this->makeCase();
        // 10 runs: 5 escalated (50 %), 6 ratings with 3 dislikes (50 %), cost 6 EUR
        $this->alertRuns('devoluciones', 10, 5, 3, 3, 6.0);
        // healthy case
        $this->alertRuns('envios', 10, 1, 5, 0, 1.0);

        $this->artisan('ai-prompts:quality-alerts')->assertSuccessful();

        Notification::assertSentTo($manager, AiPromptQualityAlertNotification::class, 3);
        Notification::assertNotSentTo($viewer, AiPromptQualityAlertNotification::class);
        Notification::assertSentTimes(AiPromptQualityAlertNotification::class, 3 * User::permission('helpdesk.ai-prompts.manage')->count());

        // Second hourly run: same alerts, no repetition.
        $this->artisan('ai-prompts:quality-alerts')->assertSuccessful();
        Notification::assertSentTo($manager, AiPromptQualityAlertNotification::class, 3);
    }

    public function test_alerts_respect_minimum_samples(): void
    {
        Notification::fake();
        Cache::flush();
        $this->manager();
        // 2 runs 100 % escalated (< min 5), 2 dislikes (< min 5 ratings), low cost
        $this->alertRuns('devoluciones', 2, 2, 0, 2, 0.1);

        $this->artisan('ai-prompts:quality-alerts')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_page_permissions(): void
    {
        $manager = $this->manager();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('helpdesk.ai-prompts.view');
        $case = $this->makeCase();
        $this->alertRuns('devoluciones', 10, 6);
        Queue::fake();

        $this->get(route('helpdesk-ai-prompts.quality.index'))->assertRedirect();
        $this->actingAs(User::factory()->create())->get(route('helpdesk-ai-prompts.quality.index'))->assertForbidden();

        $this->actingAs($viewer)->get(route('helpdesk-ai-prompts.quality.index'))
            ->assertOk()
            ->assertSee('Devoluciones')
            ->assertDontSee(route('helpdesk-ai-prompts.quality.regression.run', $case));
        $this->actingAs($viewer)->postJson(route('helpdesk-ai-prompts.quality.regression.run', $case))->assertForbidden();
        Queue::assertNothingPushed();

        $this->actingAs($manager)->get(route('helpdesk-ai-prompts.quality.index'))
            ->assertOk()
            ->assertSee(route('helpdesk-ai-prompts.quality.regression.run', $case));
    }

    public function test_manager_queues_a_regression_and_duplicates_are_rejected(): void
    {
        Queue::fake();
        $manager = $this->manager();
        $case = $this->makeCase();

        $response = $this->actingAs($manager)->postJson(route('helpdesk-ai-prompts.quality.regression.run', $case), ['questions' => 7])->assertStatus(202);

        Queue::assertPushed(RunPromptRegressionJob::class, fn ($job) => $job->limit === 7);
        $this->assertDatabaseHas('helpdesk_ai_regression_reports', ['case_key' => 'devoluciones', 'status' => 'queued', 'triggered_by' => $manager->id], 'helpdesk');
        $this->actingAs($manager)->getJson($response->json('status_url'))->assertOk()->assertJsonPath('status', 'queued');

        $this->actingAs($manager)->postJson(route('helpdesk-ai-prompts.quality.regression.run', $case))->assertStatus(409);
        $this->actingAs($manager)->postJson(route('helpdesk-ai-prompts.quality.regression.run', $this->makeCase(['key' => 'otro', 'name' => 'Otro'])), ['questions' => 99])->assertStatus(422);
    }
}
