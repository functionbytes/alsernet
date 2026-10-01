<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature\AiActions;

use Modules\HelpdeskAiPrompts\Database\Seeders\AiActionCatalogSeeder;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Support\ToolCatalog;

class AiActionCatalogSeederTest extends AiActionsTestCase
{
    public function test_seeds_builtins_and_bridge_actions_idempotently_without_overwriting_edits(): void
    {
        $this->seed(AiActionCatalogSeeder::class);
        $this->assertSame(11, AiAction::query()->where('type', 'builtin')->count());
        $this->assertSame(7, AiAction::query()->where('type', 'bridge')->count());

        AiAction::query()->where('key', 'search_help')->first()->update(['is_active' => false]);
        $this->seed(AiActionCatalogSeeder::class);

        $this->assertSame(18, AiAction::query()->count());
        $this->assertFalse(AiAction::query()->where('key', 'search_help')->first()->is_active);
    }

    public function test_builtin_keys_are_existing_tools_and_bridge_seeds_are_inactive_and_allowlisted(): void
    {
        $this->seed(AiActionCatalogSeeder::class);

        foreach (AiAction::query()->where('type', 'builtin')->pluck('key') as $key) {
            $this->assertContains($key, ToolCatalog::keys());
        }
        foreach (AiAction::query()->where('type', 'bridge')->get() as $action) {
            $this->assertFalse($action->is_active);
            $this->assertArrayHasKey($action->config['action'], config('ai-actions.bridge_allowlist'));
        }
        $this->assertTrue(AiAction::query()->where('key', 'aviso_stock')->first()->rules['confirm']);
    }
}
