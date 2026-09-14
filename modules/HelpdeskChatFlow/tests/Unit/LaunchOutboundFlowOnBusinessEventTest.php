<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\HelpdeskChatFlow\Listeners\LaunchOutboundFlowOnBusinessEvent;
use Modules\HelpdeskChatFlow\Services\ChatFlowBusinessEventLauncher;
use Modules\HelpdeskErp\Events\ErpOrdersReady;
use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Tests\TestCase;

class LaunchOutboundFlowOnBusinessEventTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_cart_abandoned_launches_abandoned_cart_flow(): void
    {
        $launcher = Mockery::mock(ChatFlowBusinessEventLauncher::class);
        $launcher->shouldReceive('launch')
            ->once()
            ->with(
                'abandoned_cart',
                ['email' => 'a@b.com', 'ps_id' => 42],
                Mockery::on(fn ($ctx) => $ctx['cart_total'] === 99.5 && $ctx['items_count'] === 2 && $ctx['ps_cart_id'] === 7),
            );

        $event = new PsCartAbandoned([
            'customer_id' => 42, 'email' => 'a@b.com', 'total' => 99.5, 'cart_id' => 7,
            'items' => [['a'], ['b']],
        ]);

        (new LaunchOutboundFlowOnBusinessEvent($launcher))->handle($event);
    }

    public function test_order_status_changed_launches_order_status_flow(): void
    {
        $launcher = Mockery::mock(ChatFlowBusinessEventLauncher::class);
        $launcher->shouldReceive('launch')
            ->once()
            ->with(
                'order_status',
                ['ps_id' => 9],
                Mockery::on(fn ($ctx) => $ctx['order_id'] === 100 && $ctx['new_status'] === 'shipped'),
            );

        $event = new PsOrderStatusChanged([
            'customer_id' => 9, 'order_id' => 100, 'old_status' => 'paid', 'new_status' => 'shipped',
        ]);

        (new LaunchOutboundFlowOnBusinessEvent($launcher))->handle($event);
    }

    public function test_erp_orders_ready_launches_order_ready_flow(): void
    {
        $launcher = Mockery::mock(ChatFlowBusinessEventLauncher::class);
        $launcher->shouldReceive('launch')
            ->once()
            ->with(
                'order_ready',
                ['email' => 'erp@b.com', 'erp_id' => 55],
                ['erp_customer_id' => 55],
            );

        (new LaunchOutboundFlowOnBusinessEvent($launcher))->handle(new ErpOrdersReady('erp@b.com', 55));
    }
}
