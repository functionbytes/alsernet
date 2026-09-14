<?php

namespace Modules\Helpdesk\Tests\Feature;

use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

class CustomerProfileControllerTest extends HelpdeskTestCase
{
    public function test_profile_falls_back_to_whatsapp_phone_when_phone_is_missing(): void
    {
        $customer = Customer::factory()->create([
            'phone' => null,
            'whatsapp_phone' => '34600111222',
        ]);

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.profile-data', $customer))
            ->assertOk()
            ->assertJsonPath('customer.phone', '34600111222');
    }
}
