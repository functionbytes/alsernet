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
 * Pieza 27: formulario de dirección con país y provincia.
 * - GET ps/ext/address/countries (acción de lectura address.countries).
 * - POST customers/{customer}/ps/addresses reenvía país/provincia/DNI/por
 *   defecto al bridge y traduce su created=false a un 422 legible.
 * El bridge está simulado con Http::fake: nada llega a la tienda real.
 */
class AddressFormTest extends TestCase
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

    private function agent(string ...$extra): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(array_merge(['helpdesk.customers.view', 'helpdesk.customers.update', 'helpdesk.customers.manage'], $extra));

        return $user;
    }

    private function customer(): Customer
    {
        return Customer::factory()->create(['email' => 'dir-'.uniqid().'@example.com']);
    }

    private function address(array $overrides = []): array
    {
        return array_merge([
            'alias' => 'Casa',
            'firstname' => 'Ana',
            'lastname' => 'Pérez',
            'address1' => 'Calle Mayor 1',
            'postcode' => '28001',
            'city' => 'Madrid',
            'id_country' => 6,
            'id_state' => 353,
            'default' => 1,
        ], $overrides);
    }

    public function test_countries_come_from_the_bridge_and_are_cached(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'countries' => [
                ['id' => 6, 'name' => 'España', 'iso' => 'ES', 'has_states' => true, 'zip_required' => true, 'zip_format' => 'NNNNN', 'dni_required' => false],
                ['id' => 144, 'name' => 'México', 'iso' => 'MX', 'has_states' => true, 'zip_required' => true, 'zip_format' => 'NNNNN', 'dni_required' => true],
            ],
            'default_country_id' => 6,
            'supports_default' => true,
        ]])]);

        $user = $this->agent('helpdeskprestashop.addresses.manage');

        $this->actingAs($user)
            ->getJson(route('manager.helpdesk.ps.ext.address.countries'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('default_country_id', 6)
            ->assertJsonPath('supports_default', true)
            ->assertJsonPath('countries.1.dni_required', true);

        $this->actingAs($user)
            ->getJson(route('manager.helpdesk.ps.ext.address.countries'))
            ->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => ($r->data()['action'] ?? null) === 'address.countries');
    }

    public function test_countries_require_a_prestashop_permission(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.ps.ext.address.countries'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_countries_report_503_when_bridge_is_down(): void
    {
        Http::fake([$this->apiUrl => Http::response('', 500)]);

        $this->actingAs($this->agent('helpdeskprestashop.addresses.manage'))
            ->getJson(route('manager.helpdesk.ps.ext.address.countries'))
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }

    public function test_store_forwards_country_state_and_default_to_the_bridge(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['created' => true, 'id' => 991, 'default' => true]])]);

        $this->actingAs($this->agent('helpdeskprestashop.addresses.manage'))
            ->postJson(route('manager.helpdesk.customers.ps.addresses.store', $this->customer()), $this->address())
            ->assertOk()
            ->assertJsonPath('data.id', 991)
            ->assertJsonPath('data.default', true);

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'customer.address.create'
                && (int) ($data['id_country'] ?? 0) === 6
                && (int) ($data['id_state'] ?? 0) === 353
                && ($data['default'] ?? null) === true
                && isset($data['lookup'])
                && $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    public function test_bridge_validation_error_is_returned_as_422_with_its_message(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'created' => false, 'error' => 'invalid_postcode', 'message' => 'El código postal no tiene el formato de España (NNNNN).',
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.addresses.manage'))
            ->postJson(route('manager.helpdesk.customers.ps.addresses.store', $this->customer()), $this->address(['postcode' => '2800']))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'invalid_postcode')
            ->assertJsonPath('message', 'El código postal no tiene el formato de España (NNNNN).');
    }

    public function test_legacy_carts_manage_permission_still_creates_addresses(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['created' => true, 'id' => 7, 'default' => false]])]);

        $this->actingAs($this->agent('helpdeskprestashop.carts.manage'))
            ->postJson(route('manager.helpdesk.customers.ps.addresses.store', $this->customer()), $this->address(['default' => 0]))
            ->assertOk();
    }

    public function test_store_without_address_permission_is_forbidden(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->postJson(route('manager.helpdesk.customers.ps.addresses.store', $this->customer()), $this->address())
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_store_without_access_to_the_customer_is_forbidden(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $user->givePermissionTo(['helpdeskprestashop.addresses.manage', 'helpdesk.customers.update']);

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.customers.ps.addresses.store', $this->customer()), $this->address())
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_country_must_be_a_positive_integer(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.addresses.manage'))
            ->postJson(route('manager.helpdesk.customers.ps.addresses.store', $this->customer()), $this->address(['id_country' => 'ES']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('id_country');

        Http::assertNothingSent();
    }

    public function test_the_same_payload_for_two_customers_uses_different_idempotency_keys(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['created' => true, 'id' => 1, 'default' => false]])]);

        $user = $this->agent('helpdeskprestashop.addresses.manage');
        $this->actingAs($user)->postJson(route('manager.helpdesk.customers.ps.addresses.store', $this->customer()), $this->address())->assertOk();
        $this->actingAs($user)->postJson(route('manager.helpdesk.customers.ps.addresses.store', $this->customer()), $this->address())->assertOk();

        $keys = Http::recorded()->map(fn ($pair) => $pair[0]->header('X-Alsernet-Idempotency-Key')[0] ?? null)->filter()->unique();
        $this->assertCount(2, $keys);
    }

    // ─── update (PATCH) ───────────────────────────────────────────────────────

    public function test_update_forwards_changed_fields_and_address_id_to_the_bridge(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['id' => 991, 'updated' => true, 'default' => true]])]);

        $this->actingAs($this->agent('helpdeskprestashop.addresses.manage'))
            ->patchJson(
                route('manager.helpdesk.customers.ps.addresses.update', [$this->customer(), 991]),
                ['postcode' => '28002', 'id_state' => 353, 'default' => 1],
            )
            ->assertOk()
            ->assertJsonPath('data.id', 991)
            ->assertJsonPath('data.updated', true);

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'customer.address.update'
                && (int) ($data['address_id'] ?? 0) === 991
                && ($data['postcode'] ?? null) === '28002'
                && (int) ($data['id_state'] ?? 0) === 353
                && ($data['default'] ?? null) === true
                && isset($data['lookup'])
                && $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    public function test_update_bridge_validation_error_is_returned_as_422_with_its_message(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'updated' => false, 'error' => 'invalid_postcode', 'message' => 'El código postal no tiene el formato de España (NNNNN).',
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.addresses.manage'))
            ->patchJson(route('manager.helpdesk.customers.ps.addresses.update', [$this->customer(), 991]), ['postcode' => '2800'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'invalid_postcode')
            ->assertJsonPath('message', 'El código postal no tiene el formato de España (NNNNN).');
    }

    public function test_update_reports_generic_error_when_bridge_returns_null(): void
    {
        // El bridge responde null cuando la dirección no pertenece al cliente.
        Http::fake([$this->apiUrl => Http::response(['ok' => false])]);

        $this->actingAs($this->agent('helpdeskprestashop.addresses.manage'))
            ->patchJson(route('manager.helpdesk.customers.ps.addresses.update', [$this->customer(), 991]), ['city' => 'Sevilla'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No se pudo actualizar la dirección.');
    }

    public function test_legacy_carts_manage_permission_still_updates_addresses(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['id' => 7, 'updated' => true, 'default' => false]])]);

        $this->actingAs($this->agent('helpdeskprestashop.carts.manage'))
            ->patchJson(route('manager.helpdesk.customers.ps.addresses.update', [$this->customer(), 7]), ['city' => 'Bilbao'])
            ->assertOk();
    }

    public function test_update_without_address_permission_is_forbidden(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->patchJson(route('manager.helpdesk.customers.ps.addresses.update', [$this->customer(), 991]), ['city' => 'Sevilla'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_update_without_access_to_the_customer_is_forbidden(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $user->givePermissionTo(['helpdeskprestashop.addresses.manage', 'helpdesk.customers.update']);

        $this->actingAs($user)
            ->patchJson(route('manager.helpdesk.customers.ps.addresses.update', [$this->customer(), 991]), ['city' => 'Sevilla'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_update_country_must_be_a_positive_integer(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.addresses.manage'))
            ->patchJson(route('manager.helpdesk.customers.ps.addresses.update', [$this->customer(), 991]), ['id_country' => 'ES'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('id_country');

        Http::assertNothingSent();
    }

    public function test_update_rejects_customer_without_email(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.addresses.manage'))
            ->patchJson(
                route('manager.helpdesk.customers.ps.addresses.update', [Customer::factory()->create(['email' => '']), 991]),
                ['city' => 'Sevilla'],
            )
            ->assertStatus(422);

        Http::assertNothingSent();
    }
}
