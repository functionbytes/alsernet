<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskChatFlow\Listeners\LaunchOutboundFlowOnBusinessEvent;
use Modules\HelpdeskChatFlow\Services\ChatFlowBusinessEventLauncher;
use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Tests\TestCase;

/**
 * Regression coverage for the Settings → Integraciones kill switch
 * (`chatflow.integration_enabled`) on the proactive outbound flow launcher.
 * Requires DB access (unlike LaunchOutboundFlowOnBusinessEventTest) because
 * the helpdesk_chatflow_enabled() helper reads the toggle from the database.
 */
class LaunchOutboundFlowIntegrationToggleTest extends TestCase
{
    use DatabaseTransactions, MockeryPHPUnitIntegration;

    protected array $connectionsToTransact = ['helpdesk'];

    private function makeEvent(): PsCartAbandoned
    {
        return new PsCartAbandoned([
            'customer_id' => 42, 'email' => 'a@b.com', 'total' => 99.5, 'cart_id' => 7, 'items' => [],
        ]);
    }

    public function test_outbound_flow_does_not_launch_when_integration_disabled(): void
    {
        Setting::set('chatflow.integration_enabled', '0', 'integrations');

        $launcher = Mockery::mock(ChatFlowBusinessEventLauncher::class);
        $launcher->shouldNotReceive('launch');

        (new LaunchOutboundFlowOnBusinessEvent($launcher))->handle($this->makeEvent());
    }

    public function test_outbound_flow_launches_when_integration_enabled_by_default(): void
    {
        $launcher = Mockery::mock(ChatFlowBusinessEventLauncher::class);
        $launcher->shouldReceive('launch')
            ->once()
            ->with('abandoned_cart', ['email' => 'a@b.com', 'ps_id' => 42], Mockery::type('array'));

        (new LaunchOutboundFlowOnBusinessEvent($launcher))->handle($this->makeEvent());
    }
}
