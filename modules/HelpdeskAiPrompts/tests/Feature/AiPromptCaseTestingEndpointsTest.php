<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use App\Models\User;
use Modules\HelpdeskAiPrompts\Database\Seeders\HelpdeskAiPromptsPermissionsSeeder;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Spatie\Permission\PermissionRegistrar;

/**
 * "Probar borrador" (unsaved form data) and "Probar" (a saved case) from the
 * cases list, both backed by PromptTestRunner against a faked agent.
 */
class AiPromptCaseTestingEndpointsTest extends HelpdeskAiPromptsTestCase
{
    private User $manager;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskAiPromptsPermissionsSeeder::class);

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['helpdesk.ai-prompts.view', 'helpdesk.ai-prompts.manage']);

        $this->viewer = User::factory()->create();
        $this->viewer->givePermissionTo('helpdesk.ai-prompts.view');
    }

    private function bindFakeAgent(): void
    {
        $fake = new class
        {
            public function run(string $question, array $context, array $data, string $locale, ?object $catalog = null): array
            {
                if (str_contains($question, 'roto')) {
                    return ['action' => 'escalate', 'text' => 'Te paso con un agente.', 'used_tools' => ['escalate_to_agent'], 'products' => []];
                }

                return ['action' => 'respond', 'text' => 'Tienes 15 días naturales.', 'used_tools' => ['search_help'], 'products' => []];
            }
        };

        $this->app->instance(ChatFlowAgentService::class, $fake);
    }

    public function test_test_draft_evaluates_unsaved_form_data_without_persisting_it(): void
    {
        $this->bindFakeAgent();

        $payload = [
            'key' => 'devoluciones', 'name' => 'Devoluciones', 'description' => 'd', 'priority' => 0,
            'is_active' => '1', 'instructions' => 'inst', 'escalation' => 'on_doubt',
            'test_questions' => [
                ['question' => '¿Cómo devuelvo un producto?', 'expect_tools' => ['search_help'], 'expect_escalate' => '0', 'must_contain' => ['15 días'], 'must_not_contain' => []],
                ['question' => 'Me ha llegado roto', 'expect_tools' => ['escalate_to_agent'], 'expect_escalate' => '1', 'must_contain' => [], 'must_not_contain' => []],
            ],
        ];

        $response = $this->actingAs($this->manager)
            ->postJson(route('helpdesk-ai-prompts.cases.test-draft'), $payload)
            ->assertOk();

        $results = $response->json('results');
        $this->assertCount(2, $results);
        $this->assertTrue($results[0]['passed']);
        $this->assertTrue($results[1]['passed']);
        $this->assertSame(0, AiPromptCase::query()->count());
    }

    public function test_test_draft_requires_manage_permission(): void
    {
        $this->actingAs($this->viewer)
            ->postJson(route('helpdesk-ai-prompts.cases.test-draft'), ['key' => 'x'])
            ->assertForbidden();
    }

    public function test_test_draft_reports_failed_expectations(): void
    {
        $this->bindFakeAgent();

        $payload = [
            'key' => 'devoluciones', 'name' => 'Devoluciones', 'description' => 'd', 'priority' => 0,
            'is_active' => '1', 'instructions' => 'inst', 'escalation' => 'on_doubt',
            'test_questions' => [
                ['question' => '¿Cómo devuelvo un producto?', 'expect_tools' => ['product_search'], 'expect_escalate' => '1', 'must_contain' => ['30 días'], 'must_not_contain' => ['devolver']],
            ],
        ];

        $response = $this->actingAs($this->manager)
            ->postJson(route('helpdesk-ai-prompts.cases.test-draft'), $payload)
            ->assertOk();

        $this->assertFalse($response->json('results.0.passed'));
        $this->assertNotEmpty($response->json('results.0.failures'));
    }

    public function test_running_a_saved_case_uses_its_stored_test_questions(): void
    {
        $this->bindFakeAgent();

        $case = AiPromptCase::query()->create([
            'key' => 'devoluciones', 'name' => 'Devoluciones', 'description' => 'd', 'priority' => 0,
            'is_active' => true, 'instructions' => 'inst',
            'test_questions' => [
                ['question' => '¿Cómo devuelvo un producto?', 'expect_tools' => [], 'expect_escalate' => false, 'must_contain' => [], 'must_not_contain' => []],
            ],
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson(route('helpdesk-ai-prompts.cases.test', $case))
            ->assertOk();

        $this->assertCount(1, $response->json('results'));
        $this->assertSame('¿Cómo devuelvo un producto?', $response->json('results.0.question'));
    }
}
