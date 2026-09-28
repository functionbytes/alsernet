<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use App\Models\User;
use Modules\HelpdeskAiPrompts\Database\Seeders\HelpdeskAiPromptsPermissionsSeeder;
use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Spatie\Permission\PermissionRegistrar;

class AiPromptTesterEndpointTest extends HelpdeskAiPromptsTestCase
{
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskAiPromptsPermissionsSeeder::class);

        $this->viewer = User::factory()->create();
        $this->viewer->givePermissionTo('helpdesk.ai-prompts.view');

        AiPromptBlock::query()->create(['key' => 'persona', 'kind' => 'base', 'name' => 'Persona', 'content' => 'BASE PROMPT']);
    }

    public function test_detect_finds_the_case_by_keyword_and_returns_the_assembled_prompt(): void
    {
        AiPromptCase::query()->create([
            'key' => 'devoluciones', 'name' => 'Devoluciones', 'description' => 'd', 'priority' => 10,
            'is_active' => true, 'instructions' => 'Explica el plazo de devolución.', 'keywords' => ['devolver'],
        ]);

        $response = $this->actingAs($this->viewer)
            ->postJson(route('helpdesk-ai-prompts.tester.detect'), ['question' => 'Quiero devolver un producto'])
            ->assertOk();

        $response->assertJson(['case_key' => 'devoluciones', 'routed_by' => 'keyword']);
        $this->assertStringContainsString('BASE PROMPT', $response->json('system'));
        $this->assertStringContainsString('Explica el plazo de devolución.', $response->json('system'));
    }

    public function test_detect_returns_no_case_when_nothing_matches(): void
    {
        $response = $this->actingAs($this->viewer)
            ->postJson(route('helpdesk-ai-prompts.tester.detect'), ['question' => 'Pregunta sin ningún caso configurado'])
            ->assertOk();

        $this->assertNull($response->json('case_key'));
        $this->assertSame('none', $response->json('routed_by'));
    }

    public function test_detect_requires_a_question(): void
    {
        $this->actingAs($this->viewer)
            ->postJson(route('helpdesk-ai-prompts.tester.detect'), [])
            ->assertStatus(422);
    }

    public function test_anonymous_users_cannot_use_the_tester(): void
    {
        $this->postJson(route('helpdesk-ai-prompts.tester.detect'), ['question' => 'hola'])->assertUnauthorized();
    }

    public function test_execute_forces_the_detected_case_and_runs_the_real_agent(): void
    {
        AiPromptCase::query()->create([
            'key' => 'devoluciones', 'name' => 'Devoluciones', 'description' => 'd', 'priority' => 10,
            'is_active' => true, 'instructions' => 'inst', 'keywords' => ['devolver'],
        ]);

        $fake = new class
        {
            public array $lastData = [];

            public function run(string $question, array $context, array $data, string $locale, ?object $catalog = null): array
            {
                $this->lastData = $data;

                return ['action' => 'respond', 'text' => 'Tienes 15 días.', 'used_tools' => ['search_help'], 'products' => []];
            }
        };
        $this->app->instance(ChatFlowAgentService::class, $fake);

        $response = $this->actingAs($this->viewer)
            ->postJson(route('helpdesk-ai-prompts.tester.execute'), ['question' => 'Quiero devolver un producto'])
            ->assertOk();

        $response->assertJson([
            'case_key' => 'devoluciones',
            'answer' => 'Tienes 15 días.',
            'action' => 'respond',
            'used_tools' => ['search_help'],
        ]);
        $this->assertSame('devoluciones', $fake->lastData['_forced_case']);
    }
}
