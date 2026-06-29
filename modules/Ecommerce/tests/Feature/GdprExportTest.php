<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Ecommerce\Jobs\GenerateGdprExportJob;
use Modules\Ecommerce\Models\Customer;
use Tests\TestCase;

class GdprExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_customer_can_request_export(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'ecommerce')
            ->post(route('account.gdpr.request'));

        Queue::assertPushed(GenerateGdprExportJob::class);
    }

    public function test_invalid_signature_returns_forbidden(): void
    {
        $response = $this->get(route('shop.gdpr.download', ['token' => 'invalid', 'customer' => 1]));
        $response->assertForbidden();
    }
}
