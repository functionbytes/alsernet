<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature\AiActions;

use Modules\HelpdeskAiPrompts\Models\AiAction;

class PanelActionsPermissionsTest extends PanelActionsTestCase
{
    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('helpdesk-ai-prompts.index', ['tab' => 'acciones']))->assertRedirect();
    }

    public function test_viewer_sees_the_tab_but_not_the_write_actions(): void
    {
        $action = $this->bridge();

        $this->actingAs($this->viewer)
            ->get(route('helpdesk-ai-prompts.index', ['tab' => 'acciones']))
            ->assertOk()
            ->assertSee('documentos_pedido')
            ->assertDontSee(route('helpdesk-ai-prompts.actions.edit', $action));

        $this->actingAs($this->viewer)->get(route('helpdesk-ai-prompts.actions.create'))->assertForbidden();
        $this->actingAs($this->viewer)->postJson(route('helpdesk-ai-prompts.actions.store'), $this->bridgePayload(['key' => 'otra']))->assertForbidden();
        $this->actingAs($this->viewer)->putJson(route('helpdesk-ai-prompts.actions.update', $action), $this->bridgePayload())->assertForbidden();
        $this->actingAs($this->viewer)->patchJson(route('helpdesk-ai-prompts.actions.toggle-active', $action))->assertForbidden();
        $this->actingAs($this->viewer)->postJson(route('helpdesk-ai-prompts.actions.test', $action))->assertForbidden();
        $this->actingAs($this->viewer)->get(route('helpdesk-ai-prompts.actions.history', $action))->assertForbidden();
        $this->actingAs($this->viewer)->deleteJson(route('helpdesk-ai-prompts.actions.destroy', $action))->assertForbidden();

        $this->assertTrue($action->fresh()->is_active);
        $this->assertSame(1, AiAction::query()->count());
    }

    public function test_manager_sees_stats_and_row_actions(): void
    {
        $action = $this->bridge();

        $this->actingAs($this->manager)
            ->get(route('helpdesk-ai-prompts.index', ['tab' => 'acciones']))
            ->assertOk()
            ->assertSee(route('helpdesk-ai-prompts.actions.edit', $action))
            ->assertSee(route('helpdesk-ai-prompts.actions.test', $action))
            ->assertViewHas('actionsPanel', fn (array $panel) => $panel['stats']['active_actions'] === 1 && count($panel['rows']) === 1);
    }
}
