<?php

namespace Modules\EcommercePayment\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Order;
use Modules\EcommercePayment\Database\Seeders\EcommercePaymentDatabaseSeeder;
use Modules\EcommercePayment\Enums\PaymentStatus;
use Modules\EcommercePayment\Models\Payment;
use Tests\TestCase;

class PaymentAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EcommercePaymentDatabaseSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo([
            'ecommerce-payment.view',
            'ecommerce-payment.confirm',
            'ecommerce-payment.refund',
        ]);
    }

    public function test_admin_can_view_payments_list(): void
    {
        $this->actingAs($this->admin)
            ->get(route('ecommerce-payment.payments.index'))
            ->assertOk();
    }

    public function test_admin_without_permission_cannot_view_payments(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('ecommerce-payment.payments.index'))
            ->assertForbidden();
    }

    public function test_admin_can_confirm_pending_cod_payment(): void
    {
        $order = Order::factory()->create();

        $payment = Payment::factory()->pending()->create([
            'payment_channel' => 'cod',
            'order_id' => $order->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('ecommerce-payment.payments.confirm', $payment))
            ->assertRedirect();

        $this->assertDatabaseHas('ecommerce_payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::COMPLETED->value,
        ]);
    }

    public function test_admin_cannot_confirm_completed_payment(): void
    {
        $payment = Payment::factory()->completed()->create([
            'payment_channel' => 'cod',
        ]);

        $this->actingAs($this->admin)
            ->post(route('ecommerce-payment.payments.confirm', $payment))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_admin_cannot_confirm_wompi_payment(): void
    {
        $payment = Payment::factory()->pending()->create([
            'payment_channel' => 'wompi',
        ]);

        $this->actingAs($this->admin)
            ->post(route('ecommerce-payment.payments.confirm', $payment))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_admin_can_filter_payments_by_channel(): void
    {
        $this->actingAs($this->admin)
            ->get(route('ecommerce-payment.payments.index', ['payment_channel' => 'cod']))
            ->assertOk();
    }
}
