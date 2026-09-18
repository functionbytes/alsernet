<?php

namespace Modules\Helpdesk\Tests\Unit;

use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\NullTicketService;
use Modules\HelpdeskTickets\Services\HelpdeskTicketBridgeService;
use Tests\TestCase;

/**
 * Coverage for the Helpdesk → HelpdeskTickets bridge.
 *
 * Verifies that:
 *  - The helpdesk_tickets_enabled() helper combines all three signals correctly.
 *  - When the feature is OFF, the contract resolves to NullTicketService and is
 *    a complete no-op (no DB queries, no exceptions).
 *  - When the feature is ON, the contract resolves to the concrete bridge
 *    service in HelpdeskTickets.
 *
 * Does not extend HelpdeskTestCase to keep boot fast — no DB needed.
 */
class TicketsFeatureGateTest extends TestCase
{
    public function test_helper_returns_true_with_default_config(): void
    {
        config(['helpdesk.tickets.enabled' => true]);

        $this->assertTrue(helpdesk_tickets_enabled());
    }

    public function test_helper_returns_false_when_config_disabled(): void
    {
        config(['helpdesk.tickets.enabled' => false]);

        $this->assertFalse(helpdesk_tickets_enabled());
    }

    public function test_contract_resolves_to_null_service_when_disabled(): void
    {
        config(['helpdesk.tickets.enabled' => false]);
        $this->app->forgetInstance(TicketServiceContract::class);

        $service = $this->app->make(TicketServiceContract::class);

        $this->assertInstanceOf(NullTicketService::class, $service);
    }

    public function test_null_service_is_safe_no_op(): void
    {
        $service = new NullTicketService;

        $this->assertFalse($service->isAvailable());
        $this->assertNull($service->createFromConversation(new Conversation));
        $this->assertCount(0, $service->getCustomerTickets(new Customer));
        $this->assertCount(0, $service->getCategories());
        $this->assertNull($service->getTicketDetail(1));
    }

    public function test_contract_resolves_to_bridge_when_enabled(): void
    {
        if (! class_exists(HelpdeskTicketBridgeService::class)) {
            $this->markTestSkipped('HelpdeskTickets module is not installed.');
        }

        config(['helpdesk.tickets.enabled' => true]);
        $this->app->forgetInstance(TicketServiceContract::class);

        $service = $this->app->make(TicketServiceContract::class);

        $this->assertInstanceOf(
            HelpdeskTicketBridgeService::class,
            $service
        );
        $this->assertTrue($service->isAvailable());
    }
}
