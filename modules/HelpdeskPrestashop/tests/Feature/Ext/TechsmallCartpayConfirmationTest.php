<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * techsmall · interruptor "Enviar el correo de confirmación" al convertir el
 * carrito (cartpay.convert + hook actionEmailSendBefore del puente 1.2.5).
 * El puente está simulado con Http::fake: nada llega a la tienda real.
 */
class TechsmallCartpayConfirmationTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private string $apiUrl = 'https://ps.test/modules/alsernetbridge/api.php';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'helpdeskprestashop.api_url' => $this->apiUrl,
            'helpdeskprestashop.webhook_secret' => 'test-secret-for-hmac',
        ]);

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        foreach (['helpdesk.customers.view', 'helpdesk.customers.update', 'helpdesk.customers.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function agent(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['helpdesk.customers.view', 'helpdesk.customers.update', 'helpdesk.customers.manage', 'helpdeskprestashop.cartpay.convert']);

        return $user;
    }

    private function customer(): Customer
    {
        return Customer::factory()->create(['email' => 'techsmall-'.uniqid().'@example.com']);
    }

    private function created(array $extra = []): array
    {
        return array_merge([
            'created' => true, 'order_id' => 900010, 'reference' => 'TECHSMALL', 'state_id' => 10,
            'state_name' => 'En espera de pago por transferencia bancaria', 'state_error' => false,
            'completed_with_errors' => false, 'total' => 99.9, 'payment' => 'Pagos por transferencia bancaria',
        ], $extra);
    }

    public function test_without_the_field_the_bridge_receives_no_flag_and_the_email_is_sent(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => $this->created(['confirmation_email_sent' => true])])]);

        // Otros llamantes (Contactos 360) no mandan send_confirmation.
        $this->actingAs($this->agent())
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.convert', [$this->customer(), 5830]), ['state' => 'bankwire'])
            ->assertOk()
            ->assertJsonPath('data.order_id', 900010)
            ->assertJsonPath('warning', null);

        Http::assertSent(fn (Request $r) => ($r->data()['action'] ?? null) === 'cartpay.convert'
            && ! array_key_exists('send_confirmation', $r->data()));
    }

    public function test_unchecked_box_asks_the_bridge_to_skip_order_conf(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => $this->created([
            'confirmation_requested' => false, 'confirmation_email_sent' => false,
        ])])]);

        $this->actingAs($this->agent())
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.convert', [$this->customer(), 5831]), ['state' => 'bankwire', 'send_confirmation' => false])
            ->assertOk()
            ->assertJsonPath('data.confirmation_email_sent', false)
            ->assertJsonPath('warning', null);

        Http::assertSent(fn (Request $r) => ($r->data()['action'] ?? null) === 'cartpay.convert'
            && ($r->data()['send_confirmation'] ?? null) === false);
    }

    public function test_form_encoded_zero_from_the_panel_also_skips(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => $this->created(['confirmation_email_sent' => false])])]);

        $this->actingAs($this->agent())
            ->post(route('manager.helpdesk.ps.ext.cartpay.convert', [$this->customer(), 5832]), ['state' => 'bankwire', 'send_confirmation' => '0'], ['Accept' => 'application/json'])
            ->assertOk();

        Http::assertSent(fn (Request $r) => ($r->data()['send_confirmation'] ?? null) === false);
    }

    public function test_bridge_without_the_hook_sends_anyway_and_the_agent_is_warned(): void
    {
        // Puente sin upgrade 1.2.5: ignora el campo y no informa del correo.
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => $this->created()])]);

        $this->actingAs($this->agent())
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.convert', [$this->customer(), 5833]), ['state' => 'bankwire', 'send_confirmation' => false])
            ->assertOk()
            ->assertJsonPath('warning', 'La tienda ha enviado igualmente el correo de confirmación: el puente de esta tienda todavía no permite omitirlo.');
    }

    public function test_non_boolean_value_is_rejected(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->postJson(route('manager.helpdesk.ps.ext.cartpay.convert', [$this->customer(), 5834]), ['state' => 'bankwire', 'send_confirmation' => 'quizá'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('send_confirmation');

        Http::assertNothingSent();
    }

    public function test_preview_says_whether_the_email_can_be_skipped(): void
    {
        Http::fake([$this->apiUrl => Http::sequence()
            ->push(['ok' => true, 'data' => ['cart_id' => 5835, 'blocking' => [], 'states' => [], 'confirmation_email' => true, 'confirmation_email_optional' => true]])
            ->push(['ok' => true, 'data' => ['cart_id' => 5836, 'blocking' => [], 'states' => [], 'confirmation_email' => true]]),
        ]);

        $user = $this->agent();
        $customer = $this->customer();

        $this->actingAs($user)
            ->getJson(route('manager.helpdesk.ps.ext.cartpay.preview', [$customer, 5835]))
            ->assertOk()
            ->assertJsonPath('data.confirmation_email_optional', true);

        // Puente anterior a 1.2.5: no lo informa = no se ofrece la casilla.
        $this->actingAs($user)
            ->getJson(route('manager.helpdesk.ps.ext.cartpay.preview', [$customer, 5836]))
            ->assertOk()
            ->assertJsonPath('data.confirmation_email_optional', false);
    }
}
