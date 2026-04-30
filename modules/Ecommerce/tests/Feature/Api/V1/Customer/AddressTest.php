<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Customer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\CustomerAddress;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_list_own_addresses(): void
    {
        $customer = Customer::factory()->create();
        CustomerAddress::factory()->count(2)->create(['customer_id' => $customer->id]);
        Sanctum::actingAs($customer, ['*']);

        $response = $this->getJson('/api/v1/ecommerce/addresses');

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_customer_can_create_address(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/ecommerce/addresses', [
            'name' => 'Casa',
            'phone' => '555-1234',
            'country' => 'Colombia',
            'state' => 'Cundinamarca',
            'city' => 'Bogotá',
            'address' => 'Cra 1 #2-3',
            'zip_code' => '110111',
            'is_default' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('ecommerce_customer_addresses', [
            'customer_id' => $customer->id,
            'name' => 'Casa',
            'is_default' => 1,
        ]);
    }

    public function test_customer_cannot_view_others_address_idor(): void
    {
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();
        $otherAddress = CustomerAddress::factory()->create(['customer_id' => $other->id]);
        Sanctum::actingAs($customer, ['*']);

        $response = $this->getJson('/api/v1/ecommerce/addresses/'.$otherAddress->id);

        $response->assertForbidden();
    }

    public function test_customer_can_set_default_address(): void
    {
        $customer = Customer::factory()->create();
        $a1 = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'is_default' => true]);
        $a2 = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'is_default' => false]);
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/ecommerce/addresses/'.$a2->id.'/default');

        $response->assertOk();
        $this->assertFalse($a1->fresh()->is_default);
        $this->assertTrue($a2->fresh()->is_default);
    }
}
