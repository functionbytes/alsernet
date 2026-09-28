<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use App\Models\User;
use Modules\HelpdeskAiPrompts\Database\Seeders\HelpdeskAiPromptsPermissionsSeeder;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Spatie\Permission\PermissionRegistrar;

class AiPromptCasesControllerTest extends HelpdeskAiPromptsTestCase
{
    private User $viewer;

    private User $manager;

    private User $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskAiPromptsPermissionsSeeder::class);

        $this->viewer = User::factory()->create();
        $this->viewer->givePermissionTo('helpdesk.ai-prompts.view');

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['helpdesk.ai-prompts.view', 'helpdesk.ai-prompts.manage']);

        $this->stranger = User::factory()->create();
    }

    private function makeCase(array $overrides = []): AiPromptCase
    {
        return AiPromptCase::query()->create(array_merge([
            'key' => 'devoluciones',
            'name' => 'Devoluciones',
            'description' => 'desc',
            'priority' => 0,
            'is_active' => true,
            'instructions' => 'inst',
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'key' => 'devoluciones',
            'name' => 'Devoluciones',
            'description' => 'El cliente quiere devolver un producto.',
            'priority' => 10,
            'is_active' => '1',
            'instructions' => 'Explica el plazo de 15 días naturales.',
            'allowed_tools' => ['answer_customer', 'search_help'],
            'escalation' => 'on_doubt',
            'escalation_message' => 'Te paso con un agente.',
            'keywords' => ['devolver', 'devolución'],
            'examples' => [['question' => '¿Cómo devuelvo?', 'answer' => 'Tienes 15 días.']],
            'test_questions' => [
                ['question' => '¿Cuánto tengo para devolver?', 'expect_tools' => [], 'expect_escalate' => '', 'must_contain' => [], 'must_not_contain' => []],
            ],
        ], $overrides);
    }

    public function test_pages_require_the_right_permission(): void
    {
        $this->actingAs($this->stranger)->get(route('helpdesk-ai-prompts.index'))->assertForbidden();
        $this->actingAs($this->viewer)->get(route('helpdesk-ai-prompts.index'))->assertOk();
        $this->actingAs($this->viewer)->get(route('helpdesk-ai-prompts.cases.create'))->assertForbidden();
        $this->actingAs($this->manager)->get(route('helpdesk-ai-prompts.cases.create'))->assertOk();
    }

    public function test_manager_creates_a_case(): void
    {
        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.cases.store'), $this->payload())
            ->assertRedirect(route('helpdesk-ai-prompts.index', ['tab' => 'casos']));

        $case = AiPromptCase::query()->where('key', 'devoluciones')->firstOrFail();
        $this->assertSame('Devoluciones', $case->name);
        $this->assertTrue($case->is_active);
        $this->assertSame(['answer_customer', 'search_help'], $case->allowed_tools);
        $this->assertSame(['devolver', 'devolución'], $case->keywords);
        $this->assertCount(1, $case->examples);
        $this->assertCount(1, $case->versions);
        $this->assertSame(1, $case->version);
    }

    public function test_viewer_cannot_create_a_case(): void
    {
        $this->actingAs($this->viewer)
            ->post(route('helpdesk-ai-prompts.cases.store'), $this->payload())
            ->assertForbidden();

        $this->assertSame(0, AiPromptCase::query()->count());
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.cases.store'), $this->payload(['name' => '', 'instructions' => '']))
            ->assertSessionHasErrors(['name', 'instructions']);
    }

    public function test_key_must_be_a_valid_slug(): void
    {
        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.cases.store'), $this->payload(['key' => 'Not A Slug!']))
            ->assertSessionHasErrors('key');
    }

    public function test_escalation_must_be_a_known_value(): void
    {
        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.cases.store'), $this->payload(['escalation' => 'sometimes']))
            ->assertSessionHasErrors('escalation');
    }

    public function test_allowed_tools_must_be_from_the_catalog(): void
    {
        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.cases.store'), $this->payload(['allowed_tools' => ['hack_the_mainframe']]))
            ->assertSessionHasErrors('allowed_tools.0');
    }

    public function test_key_must_be_unique_per_channel(): void
    {
        $this->makeCase(['key' => 'devoluciones', 'channel' => null]);

        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.cases.store'), $this->payload())
            ->assertSessionHasErrors('key');

        // Same key is fine on a different channel (it's an override, not a duplicate).
        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.cases.store'), $this->payload(['channel' => 'whatsapp']))
            ->assertSessionDoesntHaveErrors('key');
    }

    public function test_manager_updates_a_case_and_bumps_the_version(): void
    {
        $case = $this->makeCase(['key' => 'devoluciones', 'name' => 'Old name', 'version' => 1]);

        $this->actingAs($this->manager)
            ->put(route('helpdesk-ai-prompts.cases.update', $case), $this->payload(['name' => 'New name']))
            ->assertRedirect(route('helpdesk-ai-prompts.index', ['tab' => 'casos']));

        $case->refresh();
        $this->assertSame('New name', $case->name);
        $this->assertSame(2, $case->version);
        $this->assertCount(2, $case->versions);
    }

    public function test_manager_deletes_a_case(): void
    {
        $case = $this->makeCase();

        $this->actingAs($this->manager)
            ->delete(route('helpdesk-ai-prompts.cases.destroy', $case))
            ->assertRedirect(route('helpdesk-ai-prompts.index', ['tab' => 'casos']));

        $this->assertModelMissing($case);
    }

    public function test_toggle_active_flips_the_flag(): void
    {
        $case = $this->makeCase(['is_active' => true]);

        $response = $this->actingAs($this->manager)
            ->patch(route('helpdesk-ai-prompts.cases.toggle-active', $case))
            ->assertOk();

        $this->assertFalse($response->json('is_active'));
        $this->assertFalse($case->fresh()->is_active);
    }

    public function test_duplicate_creates_a_channel_override(): void
    {
        $case = $this->makeCase(['key' => 'devoluciones', 'channel' => null]);

        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.cases.duplicate', $case), ['channel' => 'whatsapp'])
            ->assertRedirect();

        $copy = AiPromptCase::query()->where('key', 'devoluciones')->where('channel', 'whatsapp')->firstOrFail();
        $this->assertNotSame($case->id, $copy->id);
        $this->assertSame($case->name, $copy->name);
    }

    public function test_duplicate_requires_a_valid_channel(): void
    {
        $case = $this->makeCase();

        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.cases.duplicate', $case), ['channel' => 'carrier-pigeon'])
            ->assertSessionHasErrors('channel');
    }
}
