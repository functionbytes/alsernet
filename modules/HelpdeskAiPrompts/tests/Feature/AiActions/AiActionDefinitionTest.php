<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature\AiActions;

use Illuminate\Validation\ValidationException;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Models\AiPromptVersion;
use PHPUnit\Framework\Attributes\DataProvider;

class AiActionDefinitionTest extends AiActionsTestCase
{
    #[DataProvider('forbiddenBridgeActions')]
    public function test_forbidden_bridge_actions_are_refused_at_save(string $forbidden): void
    {
        $this->expectException(ValidationException::class);

        $this->bridge(['key' => 'peligrosa', 'config' => ['action' => $forbidden, 'payload' => []]]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function forbiddenBridgeActions(): array
    {
        return array_map(fn (string $a) => [$a], array_combine([
            'order.change_status', 'order.set_tracking', 'order.set_address', 'order.add_note',
            'customer.create_voucher', 'cart.add_product', 'cart.apply_voucher', 'customer.address.create',
            'order.flag_for_erp_send', 'opslog.write', 'cartpay.pay', 'unknown.action',
        ], [
            'order.change_status', 'order.set_tracking', 'order.set_address', 'order.add_note',
            'customer.create_voucher', 'cart.add_product', 'cart.apply_voucher', 'customer.address.create',
            'order.flag_for_erp_send', 'opslog.write', 'cartpay.pay', 'unknown.action',
        ]));
    }

    public function test_config_allowlist_never_contains_dangerous_actions(): void
    {
        $allowed = array_keys(config('ai-actions.bridge_allowlist'));

        foreach ($allowed as $action) {
            $this->assertDoesNotMatchRegularExpression('/^(cart\.|address\.|customer\.address\.|opslog\.|cartpay\.)/', $action);
        }
        foreach (['order.change_status', 'order.set_tracking', 'order.set_address', 'order.add_note', 'customer.create_voucher', 'order.flag_for_erp_send'] as $never) {
            $this->assertNotContains($never, $allowed);
        }
    }

    public function test_allowlisted_action_saves(): void
    {
        $action = $this->bridge();

        $this->assertTrue($action->exists);
        $this->assertSame(1, $action->version);
    }

    public function test_write_actions_force_confirm_and_reject_ownership_none(): void
    {
        $action = $this->bridge([
            'key' => 'enviar',
            'config' => ['action' => 'order.send_email', 'payload' => ['order_id' => '{{order.id}}', 'type' => 'order_conf']],
            'rules' => ['ownership' => 'order_email_pair', 'confirm' => false],
        ]);

        $this->assertTrue($action->fresh()->rules['confirm']);

        $this->expectException(ValidationException::class);
        $this->bridge([
            'key' => 'enviar_none',
            'config' => ['action' => 'order.send_email', 'payload' => []],
            'rules' => ['ownership' => 'none'],
        ]);
    }

    public function test_customer_scoped_bridge_actions_cannot_use_ownership_none(): void
    {
        $this->expectException(ValidationException::class);

        $this->bridge(['rules' => ['ownership' => 'none']]);
    }

    public function test_public_catalog_actions_can_use_ownership_none(): void
    {
        $action = $this->bridge([
            'key' => 'buscar_catalogo',
            'config' => ['action' => 'product.search', 'payload' => ['query' => '{{args.q}}']],
            'parameters' => [['name' => 'q', 'type' => 'string', 'description' => 'x', 'required' => true]],
            'rules' => ['ownership' => 'none'],
        ]);

        $this->assertTrue($action->exists);
    }

    public function test_lookup_cannot_be_defined_in_a_payload_template(): void
    {
        $this->expectException(ValidationException::class);

        $this->bridge(['config' => ['action' => 'order.documents', 'payload' => ['lookup' => ['email' => 'x@y.com']]]]);
    }

    public function test_unknown_template_variables_are_refused(): void
    {
        $this->expectException(ValidationException::class);

        $this->bridge(['config' => ['action' => 'order.documents', 'payload' => ['order_id' => '{{args.inventado}}']]]);
    }

    public function test_key_cannot_collide_with_a_builtin_tool(): void
    {
        $this->expectException(ValidationException::class);

        $this->bridge(['key' => 'product_search']);
    }

    public function test_http_action_host_must_be_allowed_at_save(): void
    {
        $this->expectException(ValidationException::class);

        $this->http(['config' => ['method' => 'GET', 'url' => 'https://evil.example.org/x']]);
    }

    public function test_http_action_with_private_ip_is_refused_at_save(): void
    {
        $this->dns->map['api.example.com'] = ['10.0.0.5'];

        $this->expectException(ValidationException::class);

        $this->http();
    }

    public function test_http_host_cannot_contain_variables_nor_credentials(): void
    {
        $this->expectException(ValidationException::class);

        $this->http(['config' => ['method' => 'GET', 'url' => 'https://{{args.q}}.example.com/x']]);
    }

    public function test_timeout_above_ten_seconds_is_refused(): void
    {
        $this->expectException(ValidationException::class);

        $this->bridge(['rules' => ['ownership' => 'order_email_pair', 'timeout' => 30]]);
    }

    public function test_builtin_actions_only_accept_known_tool_names(): void
    {
        $ok = AiAction::query()->create(['key' => 'search_help', 'name' => 'x', 'description' => 'd', 'type' => 'builtin']);
        $this->assertTrue($ok->exists);

        $this->expectException(ValidationException::class);
        AiAction::query()->create(['key' => 'answer_customer', 'name' => 'x', 'description' => 'd', 'type' => 'builtin']);
    }

    public function test_version_snapshot_never_contains_secrets(): void
    {
        $action = $this->http([
            'config' => ['method' => 'GET', 'url' => 'https://api.example.com/x', 'auth' => ['type' => 'bearer', 'secret' => 'token']],
            'secrets' => ['token' => 'sk-super-secret-value'],
        ]);
        $action->update(['description' => 'Descripción nueva']);

        $versions = AiPromptVersion::query()->where('subject_type', 'action')->where('subject_id', $action->id)->get();

        $this->assertCount(2, $versions);
        foreach ($versions as $version) {
            $this->assertArrayNotHasKey('secrets', $version->snapshot);
            $this->assertStringNotContainsString('sk-super-secret-value', json_encode($version->snapshot));
        }
        $this->assertSame(2, $action->fresh()->version);
    }

    public function test_secrets_are_encrypted_at_rest_and_hidden(): void
    {
        $action = $this->http([
            'config' => ['method' => 'GET', 'url' => 'https://api.example.com/x', 'auth' => ['type' => 'bearer', 'secret' => 'token']],
            'secrets' => ['token' => 'sk-super-secret-value'],
        ]);

        $raw = (string) AiAction::query()->getConnection()->table('helpdesk_ai_actions')->where('id', $action->id)->value('secrets');

        $this->assertStringNotContainsString('sk-super-secret-value', $raw);
        $this->assertArrayNotHasKey('secrets', $action->toArray());
        $this->assertSame('sk-super-secret-value', $action->fresh()->secrets['token']);
    }

    public function test_restoring_a_version_keeps_the_current_secrets(): void
    {
        $action = $this->http([
            'config' => ['method' => 'GET', 'url' => 'https://api.example.com/x', 'auth' => ['type' => 'bearer', 'secret' => 'token']],
            'secrets' => ['token' => 'sk-super-secret-value'],
        ]);
        $action->update(['description' => 'cambiada']);

        $first = AiPromptVersion::query()->where('subject_type', 'action')->where('subject_id', $action->id)->where('version', 1)->firstOrFail();
        $action->fresh()->restoreVersion($first);

        $this->assertSame('Consulta un servicio externo', $action->fresh()->description);
        $this->assertSame('sk-super-secret-value', $action->fresh()->secrets['token']);
    }
}
