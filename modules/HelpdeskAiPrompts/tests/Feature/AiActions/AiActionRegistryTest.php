<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature\AiActions;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionRegistry;

class AiActionRegistryTest extends AiActionsTestCase
{
    private function names(array $tools): array
    {
        return array_map(fn ($t) => $t['function']['name'], $tools);
    }

    public function test_tools_without_parameters_serialize_properties_as_an_object(): void
    {
        $this->bridge([
            'key' => 'mis_cupones',
            'config' => ['action' => 'customer.vouchers', 'payload' => []],
            'rules' => ['ownership' => 'verified'],
        ]);

        $tool = app(ActionRegistry::class)->toolsFor(['verified' => true, 'channel' => 'widget'])[0];

        $this->assertSame('function', $tool['type']);
        $this->assertStringContainsString('"properties":{}', json_encode($tool));
        $this->assertStringContainsString('"required":[]', json_encode($tool));
    }

    public function test_pair_ownership_adds_order_ref_and_email_and_verified_drops_email(): void
    {
        $this->bridge();
        $registry = app(ActionRegistry::class);

        $anon = $registry->toolsFor(['verified' => false, 'channel' => 'widget'])[0]['function']['parameters'];
        $this->assertSame(['order_ref', 'email'], $anon['required']);

        $verified = $registry->toolsFor(['verified' => true, 'channel' => 'widget'])[0]['function']['parameters'];
        $this->assertSame(['order_ref'], $verified['required']);
        $this->assertArrayNotHasKey('email', $verified['properties']);
    }

    public function test_write_actions_get_a_required_customer_confirmed_boolean(): void
    {
        $this->bridge([
            'key' => 'enviar',
            'config' => ['action' => 'order.send_email', 'payload' => ['order_id' => '{{order.id}}', 'type' => 'order_conf']],
        ]);

        $params = app(ActionRegistry::class)->toolsFor(['verified' => false, 'channel' => 'widget'])[0]['function']['parameters'];

        $this->assertContains('customer_confirmed', $params['required']);
        $this->assertSame('boolean', $params['properties']['customer_confirmed']['type']);
    }

    public function test_only_active_bridge_and_http_actions_for_the_channel_are_offered(): void
    {
        $this->bridge();
        $this->bridge(['key' => 'inactiva', 'is_active' => false]);
        $this->http(['channels' => ['email']]);
        AiAction::query()->create(['key' => 'search_help', 'name' => 'x', 'description' => 'd', 'type' => 'builtin', 'is_active' => true]);

        $this->assertSame(['documentos_pedido'], $this->names(app(ActionRegistry::class)->toolsFor(['verified' => false, 'channel' => 'widget'])));
        $this->assertEqualsCanonicalizing(['consulta_externa', 'documentos_pedido'], $this->names(app(ActionRegistry::class)->toolsFor(['verified' => false, 'channel' => 'email'])));
    }

    public function test_builtin_overrides(): void
    {
        AiAction::query()->create(['key' => 'search_help', 'name' => 'x', 'description' => 'Mi descripción', 'type' => 'builtin', 'is_active' => false, 'config' => ['description_overridden' => true]]);
        AiAction::query()->create(['key' => 'show_cart', 'name' => 'x', 'description' => 'Informativa', 'type' => 'builtin', 'is_active' => true, 'config' => ['description_overridden' => false]]);

        $overrides = app(ActionRegistry::class)->builtinOverrides();

        $this->assertSame(['enabled' => false, 'description' => 'Mi descripción'], $overrides['search_help']);
        $this->assertSame(['enabled' => true, 'description' => null], $overrides['show_cart']);
    }

    public function test_is_custom_only_for_active_bridge_http_actions(): void
    {
        $this->bridge();
        AiAction::query()->create(['key' => 'search_help', 'name' => 'x', 'description' => 'd', 'type' => 'builtin', 'is_active' => true]);
        $registry = app(ActionRegistry::class);

        $this->assertTrue($registry->isCustom('documentos_pedido'));
        $this->assertFalse($registry->isCustom('search_help'));
        $this->assertFalse($registry->isCustom('nada'));
    }

    public function test_cache_is_invalidated_on_save_and_never_stores_secrets(): void
    {
        $action = $this->http([
            'config' => ['method' => 'GET', 'url' => 'https://api.example.com/x', 'auth' => ['type' => 'bearer', 'secret' => 'token']],
            'secrets' => ['token' => 'sk-cache-secret'],
        ]);
        $registry = app(ActionRegistry::class);

        $this->assertTrue($registry->isCustom('consulta_externa'));
        $this->assertStringNotContainsString('sk-cache-secret', serialize(Cache::get(ActionRegistry::CACHE_KEY_ACTIVE)));

        $action->update(['is_active' => false]);

        $this->assertFalse($registry->isCustom('consulta_externa'));
    }
}
