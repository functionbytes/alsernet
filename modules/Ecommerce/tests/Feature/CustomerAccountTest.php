<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\CustomerAddress;
use Modules\Ecommerce\Models\Order;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::factory()->create();
    }

    public function test_guest_is_redirected_from_account_dashboard(): void
    {
        $response = $this->get(route('account.dashboard'));

        $response->assertRedirect();
    }

    public function test_customer_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->customer, 'ecommerce')
            ->get(route('account.dashboard'));

        $response->assertOk();
    }

    public function test_dashboard_shows_recent_orders(): void
    {
        Order::factory()->count(3)->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->get(route('account.dashboard'));

        $response->assertOk();
        $response->assertSee('Mis ordenes');
    }

    public function test_customer_can_list_orders(): void
    {
        Order::factory()->count(2)->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->get(route('account.orders'));

        $response->assertOk();
        $response->assertSee('Mis ordenes');
    }

    public function test_customer_can_view_own_order_detail(): void
    {
        $order = Order::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->get(route('account.order-detail', $order));

        $response->assertOk();
        $response->assertSee($order->code);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $other = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $other->id]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->get(route('account.order-detail', $order));

        $response->assertRedirect(route('account.orders'));
    }

    public function test_customer_can_view_profile(): void
    {
        $response = $this->actingAs($this->customer, 'ecommerce')
            ->get(route('account.profile'));

        $response->assertOk();
    }

    public function test_customer_can_update_profile(): void
    {
        $response = $this->actingAs($this->customer, 'ecommerce')
            ->put(route('account.profile.update'), [
                'name' => 'Nombre Actualizado',
                'phone' => '555-9999',
            ]);

        $response->assertRedirect(route('account.profile'));
        $this->assertDatabaseHas('ecommerce_customers', [
            'id' => $this->customer->id,
            'name' => 'Nombre Actualizado',
        ]);
    }

    public function test_customer_can_update_password(): void
    {
        $response = $this->actingAs($this->customer, 'ecommerce')
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect(route('account.profile'));
    }

    public function test_update_password_fails_with_wrong_current_password(): void
    {
        $response = $this->actingAs($this->customer, 'ecommerce')
            ->put(route('account.password.update'), [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    public function test_customer_can_list_addresses(): void
    {
        CustomerAddress::factory()->count(2)->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->get(route('account.addresses'));

        $response->assertOk();
        $response->assertSee('Mis direcciones');
    }

    public function test_customer_can_add_address(): void
    {
        $response = $this->actingAs($this->customer, 'ecommerce')
            ->post(route('account.addresses.store'), [
                'name' => 'Casa',
                'address' => 'Calle 123',
                'city' => 'Bogotá',
                'country' => 'Colombia',
            ]);

        $response->assertRedirect(route('account.addresses'));
        $this->assertDatabaseHas('ecommerce_customer_addresses', [
            'customer_id' => $this->customer->id,
            'city' => 'Bogotá',
        ]);
    }

    public function test_customer_can_delete_own_address(): void
    {
        $address = CustomerAddress::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->delete(route('account.addresses.destroy', $address));

        $response->assertRedirect(route('account.addresses'));
        $this->assertDatabaseMissing('ecommerce_customer_addresses', ['id' => $address->id]);
    }

    public function test_customer_cannot_delete_another_customers_address(): void
    {
        $other = Customer::factory()->create();
        $address = CustomerAddress::factory()->create(['customer_id' => $other->id]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->delete(route('account.addresses.destroy', $address));

        $response->assertRedirect(route('account.addresses'));
        $this->assertDatabaseHas('ecommerce_customer_addresses', ['id' => $address->id]);
    }

    public function test_customer_can_set_default_address(): void
    {
        $address1 = CustomerAddress::factory()->default()->create(['customer_id' => $this->customer->id]);
        $address2 = CustomerAddress::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->post(route('account.addresses.default', $address2));

        $response->assertRedirect(route('account.addresses'));
        $this->assertDatabaseHas('ecommerce_customer_addresses', ['id' => $address2->id, 'is_default' => true]);
        $this->assertDatabaseHas('ecommerce_customer_addresses', ['id' => $address1->id, 'is_default' => false]);
    }
}
