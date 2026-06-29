<?php

namespace Modules\Engagement\Tests\Feature;

use Modules\Engagement\Services\PlatformWebhookHandler;
use Modules\Engagement\Tests\TestCase;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\CustomerExternalId;
use Modules\Helpdesk\Models\Inbox;

class PlatformWebhookHandlerResolveCustomerTest extends TestCase
{
    private PlatformWebhookHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        // Web + Inbox are not strictly needed by resolveCustomer but the helpdesk
        // connection must be available for Customer queries.
        $web = Web::create([
            'website_url' => 'https://example.com',
            'widget_color' => '#90bb13',
            'widget_position' => 'right',
            'welcome_title' => 'Hi',
            'welcome_tagline' => 'Hello',
        ]);

        Inbox::create([
            'name' => 'Test',
            'channel_type' => 'web',
            'channel_id' => $web->id,
            'is_active' => true,
        ]);

        $this->handler = app(PlatformWebhookHandler::class);
    }

    public function test_resolves_by_email_when_customer_exists(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Ana',
            'email' => 'ana@example.com',
        ]);

        $id = $this->handler->resolveCustomer('prestashop', 'ana@example.com', null);

        $this->assertSame($customer->id, $id);
    }

    public function test_links_external_id_when_email_matches_and_external_id_provided(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Ana',
            'email' => 'ana@example.com',
        ]);

        $this->handler->resolveCustomer('prestashop', 'ana@example.com', '555');

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'prestashop',
            'external_id' => '555',
        ]);
    }

    public function test_resolves_by_external_id_when_email_not_provided(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ]);

        CustomerExternalId::query()->create([
            'customer_id' => $customer->id,
            'platform' => 'erp',
            'external_id' => '999',
        ]);

        $id = $this->handler->resolveCustomer('erp', null, '999');

        $this->assertSame($customer->id, $id);
    }

    public function test_autocreates_visitor_when_no_match_with_lookup(): void
    {
        $id = $this->handler->resolveCustomer('prestashop', 'visitor@example.com', '777');

        $this->assertNotNull($id);
        $this->assertDatabaseHas('helpdesk_customers', [
            'id' => $id,
            'name' => 'Visitor',
        ]);
        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $id,
            'platform' => 'prestashop',
            'external_id' => '777',
        ]);
    }

    public function test_autocreates_visitor_with_generated_email_when_only_external_id(): void
    {
        $id = $this->handler->resolveCustomer('erp', null, '888');

        $this->assertNotNull($id);
        $this->assertDatabaseHas('helpdesk_customers', ['id' => $id, 'name' => 'Visitor']);
        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $id,
            'external_id' => '888',
        ]);
    }

    public function test_returns_null_when_no_lookup_provided(): void
    {
        $id = $this->handler->resolveCustomer('prestashop', null, null);

        $this->assertNull($id);
    }

    public function test_does_not_duplicate_external_id_link_on_repeated_calls(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Ana',
            'email' => 'ana@example.com',
        ]);

        $this->handler->resolveCustomer('prestashop', 'ana@example.com', '555');
        $this->handler->resolveCustomer('prestashop', 'ana@example.com', '555');

        $count = CustomerExternalId::query()
            ->where('customer_id', $customer->id)
            ->where('platform', 'prestashop')
            ->where('external_id', '555')
            ->count();

        $this->assertSame(1, $count);
    }
}
