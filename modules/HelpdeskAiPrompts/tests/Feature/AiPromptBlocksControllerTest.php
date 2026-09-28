<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use App\Models\User;
use Modules\HelpdeskAiPrompts\Database\Seeders\HelpdeskAiPromptsPermissionsSeeder;
use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Spatie\Permission\PermissionRegistrar;

class AiPromptBlocksControllerTest extends HelpdeskAiPromptsTestCase
{
    private User $viewer;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskAiPromptsPermissionsSeeder::class);

        $this->viewer = User::factory()->create();
        $this->viewer->givePermissionTo('helpdesk.ai-prompts.view');

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['helpdesk.ai-prompts.view', 'helpdesk.ai-prompts.manage']);
    }

    private function makeBlock(array $overrides = []): AiPromptBlock
    {
        return AiPromptBlock::query()->create(array_merge([
            'key' => 'envios',
            'kind' => 'knowledge',
            'name' => 'Conocimiento · Envíos',
            'content' => 'Entrega en 48 horas.',
            'is_active' => true,
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'key' => 'envios',
            'kind' => 'knowledge',
            'name' => 'Conocimiento · Envíos',
            'content' => 'Entrega en 48 horas, plazo general de 7 días laborables.',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_create_page_requires_manage_permission(): void
    {
        $this->actingAs($this->viewer)->get(route('helpdesk-ai-prompts.blocks.create'))->assertForbidden();
        $this->actingAs($this->manager)->get(route('helpdesk-ai-prompts.blocks.create'))->assertOk();
    }

    public function test_manager_creates_a_block(): void
    {
        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.blocks.store'), $this->payload())
            ->assertRedirect(route('helpdesk-ai-prompts.index', ['tab' => 'conocimiento']));

        $block = AiPromptBlock::query()->where('key', 'envios')->firstOrFail();
        $this->assertSame('knowledge', $block->kind);
        $this->assertTrue($block->is_active);
        $this->assertSame(1, $block->version);
    }

    public function test_base_block_redirects_to_the_prompt_base_tab(): void
    {
        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.blocks.store'), $this->payload(['kind' => 'base', 'key' => 'persona']))
            ->assertRedirect(route('helpdesk-ai-prompts.index', ['tab' => 'prompt-base']));
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.blocks.store'), $this->payload(['name' => '', 'content' => '']))
            ->assertSessionHasErrors(['name', 'content']);
    }

    public function test_key_must_be_unique_per_channel_and_locale(): void
    {
        $this->makeBlock(['key' => 'envios', 'channel' => null, 'locale' => null]);

        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.blocks.store'), $this->payload())
            ->assertSessionHasErrors('key');

        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.blocks.store'), $this->payload(['locale' => 'en']))
            ->assertSessionDoesntHaveErrors('key');
    }

    public function test_manager_updates_a_block_and_bumps_the_version(): void
    {
        $block = $this->makeBlock(['version' => 1]);

        $this->actingAs($this->manager)
            ->put(route('helpdesk-ai-prompts.blocks.update', $block), $this->payload(['name' => 'Nuevo nombre']))
            ->assertRedirect(route('helpdesk-ai-prompts.index', ['tab' => 'conocimiento']));

        $block->refresh();
        $this->assertSame('Nuevo nombre', $block->name);
        $this->assertSame(2, $block->version);
    }

    public function test_manager_deletes_a_block(): void
    {
        $block = $this->makeBlock();

        $this->actingAs($this->manager)
            ->delete(route('helpdesk-ai-prompts.blocks.destroy', $block))
            ->assertRedirect(route('helpdesk-ai-prompts.index', ['tab' => 'conocimiento']));

        $this->assertModelMissing($block);
    }

    public function test_toggle_active_flips_the_flag(): void
    {
        $block = $this->makeBlock(['is_active' => true]);

        $response = $this->actingAs($this->manager)
            ->patch(route('helpdesk-ai-prompts.blocks.toggle-active', $block))
            ->assertOk();

        $this->assertFalse($response->json('is_active'));
        $this->assertFalse($block->fresh()->is_active);
    }
}
