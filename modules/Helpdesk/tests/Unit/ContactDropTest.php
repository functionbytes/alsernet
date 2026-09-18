<?php

namespace Modules\Helpdesk\Tests\Unit;

use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\Templates\Drops\ContactDrop;
use Tests\TestCase;

class ContactDropTest extends TestCase
{
    public function test_phone_falls_back_to_whatsapp_phone_when_phone_is_missing(): void
    {
        $customer = new Customer([
            'phone' => null,
            'whatsapp_phone' => '34600111222',
        ]);

        $drop = new ContactDrop($customer);

        $this->assertSame('34600111222', $drop->phone());
    }

    public function test_phone_prefers_generic_phone_over_whatsapp_phone(): void
    {
        $customer = new Customer([
            'phone' => '34611222333',
            'whatsapp_phone' => '34600111222',
        ]);

        $drop = new ContactDrop($customer);

        $this->assertSame('34611222333', $drop->phone());
    }
}
