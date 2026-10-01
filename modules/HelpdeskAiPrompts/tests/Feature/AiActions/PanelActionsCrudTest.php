<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature\AiActions;

use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Models\AiPromptVersion;
use Modules\HelpdeskAiPrompts\Support\ToolCatalog;

class PanelActionsCrudTest extends PanelActionsTestCase
{
    private const SECRET = 'sekret-123';

    public function test_a_bridge_action_is_created_and_updated(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('helpdesk-ai-prompts.actions.store'), $this->bridgePayload())
            ->assertCreated()
            ->assertJsonPath('redirect', route('helpdesk-ai-prompts.index', ['tab' => 'acciones']));

        $action = AiAction::query()->where('key', 'documentos_pedido')->firstOrFail();
        $this->assertSame('order.documents', $action->config['action']);
        $this->assertSame(['order_id' => '{{order.id}}'], $action->config['payload']);
        $this->assertSame($this->manager->id, $action->updated_by);

        $this->actingAs($this->manager)
            ->putJson(route('helpdesk-ai-prompts.actions.update', $action), $this->bridgePayload(['name' => 'Documentos 2']))
            ->assertOk();

        $this->assertSame('Documentos 2', $action->fresh()->name);
        $this->assertSame(2, $action->fresh()->version);
    }

    public function test_a_bridge_action_outside_the_allowlist_is_refused_from_the_panel(): void
    {
        $payload = $this->bridgePayload(['config' => ['action' => 'order.change_status', 'payload' => '{}']]);

        $this->actingAs($this->manager)
            ->postJson(route('helpdesk-ai-prompts.actions.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['config.action']);

        $this->assertSame(0, AiAction::query()->count());
    }

    public function test_write_actions_force_confirmation_and_need_an_owner(): void
    {
        $write = $this->bridgePayload([
            'key' => 'reenviar',
            'config' => ['action' => 'order.send_email', 'payload' => '{"type": "order_conf"}'],
            'rules' => ['ownership' => 'none', 'max_per_conversation' => 2, 'timeout' => 8],
        ]);

        $this->actingAs($this->manager)->postJson(route('helpdesk-ai-prompts.actions.store'), $write)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rules.ownership']);

        $write['rules']['ownership'] = 'verified';
        $this->actingAs($this->manager)->postJson(route('helpdesk-ai-prompts.actions.store'), $write)->assertCreated();

        $this->assertTrue(AiAction::query()->where('key', 'reenviar')->first()->rules['confirm']);
    }

    public function test_invalid_json_and_parameters_return_errors_per_field(): void
    {
        $payload = $this->bridgePayload([
            'config' => ['action' => 'order.documents', 'payload' => '{no es json'],
            'parameters' => [['name' => 'Mal Nombre', 'type' => 'string']],
        ]);

        $this->actingAs($this->manager)->postJson(route('helpdesk-ai-prompts.actions.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['config.payload']);

        $payload['config']['payload'] = '{}';
        $this->actingAs($this->manager)->postJson(route('helpdesk-ai-prompts.actions.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['parameters.0']);
    }

    public function test_an_http_action_never_exposes_its_secret(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson(route('helpdesk-ai-prompts.actions.store'), $this->httpPayload())
            ->assertCreated();
        $this->assertStringNotContainsString(self::SECRET, $response->getContent());

        $action = AiAction::query()->where('key', 'consulta_externa')->firstOrFail();
        $this->assertSame(self::SECRET, $action->secrets['token']);
        $this->assertSame(['type' => 'bearer', 'secret' => 'token'], $action->config['auth']);
        $this->assertStringNotContainsString(self::SECRET, (string) $action->getRawOriginal('secrets'));

        foreach ([
            route('helpdesk-ai-prompts.actions.edit', $action),
            route('helpdesk-ai-prompts.actions.history', $action),
            route('helpdesk-ai-prompts.index', ['tab' => 'acciones']),
        ] as $url) {
            $this->actingAs($this->manager)->get($url)->assertOk()->assertDontSee(self::SECRET);
        }

        $version = AiPromptVersion::query()->where('subject_type', 'action')->where('subject_id', $action->id)->first();
        $this->assertArrayNotHasKey('secrets', $version->snapshot);
    }

    public function test_a_blank_secret_keeps_the_stored_one_and_a_new_one_replaces_it(): void
    {
        $this->actingAs($this->manager)->postJson(route('helpdesk-ai-prompts.actions.store'), $this->httpPayload())->assertCreated();
        $action = AiAction::query()->where('key', 'consulta_externa')->firstOrFail();

        $keep = $this->httpPayload(['auth' => ['type' => 'bearer', 'value' => ''], 'name' => 'Renombrada']);
        $this->actingAs($this->manager)->putJson(route('helpdesk-ai-prompts.actions.update', $action), $keep)->assertOk();
        $this->assertSame(self::SECRET, $action->fresh()->secrets['token']);

        $replace = $this->httpPayload(['auth' => ['type' => 'bearer', 'value' => 'nuevo-456']]);
        $this->actingAs($this->manager)->putJson(route('helpdesk-ai-prompts.actions.update', $action), $replace)->assertOk();
        $this->assertSame('nuevo-456', $action->fresh()->secrets['token']);
    }

    public function test_http_hosts_and_forbidden_headers_are_refused(): void
    {
        $badHost = $this->httpPayload(['config' => ['method' => 'GET', 'url' => 'https://evil.example.org/x']]);
        $this->actingAs($this->manager)->postJson(route('helpdesk-ai-prompts.actions.store'), $badHost)
            ->assertUnprocessable()->assertJsonValidationErrors(['config.url']);

        $badHeader = $this->httpPayload(['config' => ['method' => 'GET', 'url' => 'https://api.example.com/x', 'headers' => '{"Authorization": "x"}']]);
        $this->actingAs($this->manager)->postJson(route('helpdesk-ai-prompts.actions.store'), $badHeader)
            ->assertUnprocessable()->assertJsonValidationErrors(['config.headers']);

        $this->assertSame(0, AiAction::query()->count());
    }

    public function test_builtin_actions_only_toggle_and_override_the_description(): void
    {
        $builtin = AiAction::query()->create([
            'key' => 'show_cart', 'name' => 'Ver la cesta', 'description' => 'x', 'type' => 'builtin',
            'is_active' => true, 'parameters' => [], 'config' => ['description_overridden' => false],
        ]);

        $this->actingAs($this->manager)->putJson(route('helpdesk-ai-prompts.actions.update', $builtin), [
            'is_active' => 0, 'description' => 'Mi descripción', 'name' => 'Hackeado', 'type' => 'http',
        ])->assertOk();

        $builtin->refresh();
        $this->assertFalse($builtin->is_active);
        $this->assertSame('Mi descripción', $builtin->description);
        $this->assertTrue($builtin->config['description_overridden']);
        $this->assertSame('Ver la cesta', $builtin->name);
        $this->assertSame('builtin', $builtin->type);

        $this->actingAs($this->manager)->putJson(route('helpdesk-ai-prompts.actions.update', $builtin), ['is_active' => 1, 'description' => ''])->assertOk();
        $this->assertFalse($builtin->fresh()->config['description_overridden']);
        $this->assertSame(ToolCatalog::TOOLS['show_cart'], $builtin->fresh()->description);

        $this->actingAs($this->manager)->delete(route('helpdesk-ai-prompts.actions.destroy', $builtin))->assertForbidden();
        $this->assertNotNull(AiAction::query()->find($builtin->id));
    }

    public function test_the_toggle_flips_the_active_flag(): void
    {
        $action = $this->bridge();

        $this->actingAs($this->manager)->patchJson(route('helpdesk-ai-prompts.actions.toggle-active', $action))
            ->assertOk()->assertJson(['is_active' => false]);
        $this->assertFalse($action->fresh()->is_active);
    }

    public function test_a_custom_action_can_be_deleted(): void
    {
        $action = $this->http();

        $this->actingAs($this->manager)->delete(route('helpdesk-ai-prompts.actions.destroy', $action))
            ->assertRedirect(route('helpdesk-ai-prompts.index', ['tab' => 'acciones']));
        $this->assertNull(AiAction::query()->find($action->id));
    }

    public function test_restoring_a_version_brings_it_back_without_touching_the_secret(): void
    {
        $this->actingAs($this->manager)->postJson(route('helpdesk-ai-prompts.actions.store'), $this->httpPayload())->assertCreated();
        $action = AiAction::query()->where('key', 'consulta_externa')->firstOrFail();
        $first = $action->versions()->first();

        $this->actingAs($this->manager)->putJson(route('helpdesk-ai-prompts.actions.update', $action), $this->httpPayload(['name' => 'Otro nombre', 'auth' => ['type' => 'bearer', 'value' => 'nuevo-456']]))->assertOk();

        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.actions.versions.restore', [$action, $first]))
            ->assertRedirect(route('helpdesk-ai-prompts.actions.history', $action));

        $action->refresh();
        $this->assertSame('Consulta externa', $action->name);
        $this->assertSame('nuevo-456', $action->secrets['token']);
        $this->assertSame(3, $action->version);
    }

    public function test_a_version_of_another_action_cannot_be_restored(): void
    {
        $a = $this->bridge();
        $b = $this->http();

        $this->actingAs($this->manager)
            ->post(route('helpdesk-ai-prompts.actions.versions.restore', [$a, $b->versions()->first()]))
            ->assertNotFound();
    }

    public function test_case_form_offers_the_catalog_tools_and_accepts_them(): void
    {
        AiAction::query()->create([
            'key' => 'show_cart', 'name' => 'Ver la cesta', 'description' => 'x', 'type' => 'builtin',
            'is_active' => true, 'parameters' => [], 'config' => ['description_overridden' => true],
        ]);
        $this->bridge(['description' => 'Descripción de documentos']);
        $this->http(['is_active' => false, 'description' => 'Servicio apagado']);

        $tools = $this->actingAs($this->manager)->get(route('helpdesk-ai-prompts.cases.create'))
            ->assertOk()->viewData('tools');

        $this->assertArrayHasKey('product_search', $tools);
        $this->assertSame('Descripción de documentos', $tools['documentos_pedido']);
        $this->assertArrayNotHasKey('consulta_externa', $tools);

        $this->actingAs($this->manager)->post(route('helpdesk-ai-prompts.cases.store'), [
            'key' => 'docs', 'name' => 'Docs', 'description' => 'd', 'instructions' => 'i', 'escalation' => 'never', 'priority' => 0,
            'allowed_tools' => ['documentos_pedido', 'answer_customer'],
        ])->assertRedirect();

        $this->assertSame(['documentos_pedido', 'answer_customer'], AiPromptCase::query()->where('key', 'docs')->first()->allowed_tools);
    }
}
