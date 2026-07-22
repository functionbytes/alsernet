<?php

namespace Modules\Helpdesk\Tests\Feature;

use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

class CustomersExportTest extends HelpdeskTestCase
{
    private const ROUTE = 'manager.helpdesk.exports.customers';

    public function test_exports_customers_as_csv_with_whatsapp_column(): void
    {
        Customer::factory()->create([
            'name' => 'Cliente Solo WhatsApp',
            'phone' => null,
            'whatsapp_phone' => '34600111222',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route(self::ROUTE))
            ->assertOk();

        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('WhatsApp', $csv);
        $this->assertStringContainsString('34600111222', $csv);
    }
}
