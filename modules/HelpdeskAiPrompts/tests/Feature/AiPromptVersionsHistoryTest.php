<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use App\Models\User;
use Modules\HelpdeskAiPrompts\Database\Seeders\HelpdeskAiPromptsPermissionsSeeder;
use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Spatie\Permission\PermissionRegistrar;

class AiPromptVersionsHistoryTest extends HelpdeskAiPromptsTestCase
{
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskAiPromptsPermissionsSeeder::class);

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['helpdesk.ai-prompts.view', 'helpdesk.ai-prompts.manage']);
    }

    public function test_case_history_lists_every_saved_version(): void
    {
        $case = AiPromptCase::query()->create([
            'key' => 'devoluciones', 'name' => 'v1', 'description' => 'd', 'priority' => 0,
            'is_active' => true, 'instructions' => 'inst',
        ]);
        $case->update(['name' => 'v2']);
        $case->update(['name' => 'v3']);

        $response = $this->actingAs($this->manager)
            ->get(route('helpdesk-ai-prompts.cases.history', $case))
            ->assertOk();

        $this->assertCount(3, $response->viewData('versions'));
        $this->assertSame(3, $response->viewData('currentVersion'));
    }

    public function test_restoring_a_case_version_brings_back_its_content_as_a_new_version(): void
    {
        $case = AiPromptCase::query()->create([
            'key' => 'devoluciones', 'name' => 'Original', 'description' => 'd', 'priority' => 0,
            'is_active' => true, 'instructions' => 'Instrucciones originales',
        ]);
        $originalVersion = $case->versions()->first();

        $case->update(['name' => 'Cambiado', 'instructions' => 'Instrucciones cambiadas']);
        $this->assertSame(2, $case->version);

        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.cases.versions.restore', [$case, $originalVersion]))
            ->assertRedirect(route('helpdesk-ai-prompts.cases.history', $case));

        $case->refresh();
        $this->assertSame('Original', $case->name);
        $this->assertSame('Instrucciones originales', $case->instructions);
        $this->assertSame(3, $case->version);
        $this->assertCount(3, $case->versions);
    }

    public function test_restoring_a_block_version_brings_back_its_content(): void
    {
        $block = AiPromptBlock::query()->create([
            'key' => 'envios', 'kind' => 'knowledge', 'name' => 'Envíos', 'content' => 'Contenido original', 'is_active' => true,
        ]);
        $originalVersion = $block->versions()->first();

        $block->update(['content' => 'Contenido cambiado']);

        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.blocks.versions.restore', [$block, $originalVersion]))
            ->assertRedirect(route('helpdesk-ai-prompts.blocks.history', $block));

        $this->assertSame('Contenido original', $block->fresh()->content);
    }

    public function test_a_version_that_does_not_belong_to_the_case_cannot_be_restored(): void
    {
        $caseA = AiPromptCase::query()->create([
            'key' => 'a', 'name' => 'A', 'description' => 'd', 'priority' => 0, 'is_active' => true, 'instructions' => 'inst',
        ]);
        $caseB = AiPromptCase::query()->create([
            'key' => 'b', 'name' => 'B', 'description' => 'd', 'priority' => 0, 'is_active' => true, 'instructions' => 'inst',
        ]);
        $versionOfB = $caseB->versions()->first();

        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.cases.versions.restore', [$caseA, $versionOfB]))
            ->assertNotFound();
    }
}
