<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature\AiActions;

use Illuminate\Support\Facades\Http;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

class CallAllowedActionTest extends AiActionsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('helpdeskprestashop.api_url', 'https://bridge.test/api.php');
        config()->set('helpdeskprestashop.webhook_secret', 'hmac-secret');
    }

    public function test_actions_outside_the_allowlist_are_rejected_without_calling_the_bridge(): void
    {
        Http::fake();

        foreach (['order.change_status', 'cart.add_product', 'customer.create_voucher', 'nada'] as $action) {
            try {
                app(PrestashopContextService::class)->callAllowedAction($action, []);
                $this->fail("{$action} no debería pasar");
            } catch (\InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
        Http::assertNothingSent();
    }

    public function test_read_action_is_signed_and_sent(): void
    {
        Http::fake(['bridge.test/*' => Http::response(['ok' => true, 'data' => ['returns' => []]])]);

        $result = app(PrestashopContextService::class)->callAllowedAction('customer.returns', ['lookup' => ['email' => 'a@b.com']]);

        $this->assertSame(['returns' => []], $result);
        Http::assertSent(fn ($r) => $r->header('X-Alsernet-Action') === ['customer.returns'] && $r->hasHeader('X-Alsernet-Signature') && ! $r->hasHeader('X-Alsernet-Idempotency-Key'));
    }

    public function test_write_action_carries_the_idempotency_key(): void
    {
        Http::fake(['bridge.test/*' => Http::response(['ok' => true, 'data' => ['sent' => true]])]);

        app(PrestashopContextService::class)->callAllowedAction('order.send_email', ['order_id' => 1], 'ai-key-1');

        Http::assertSent(fn ($r) => $r->header('X-Alsernet-Idempotency-Key') === ['ai-key-1']);
    }
}
