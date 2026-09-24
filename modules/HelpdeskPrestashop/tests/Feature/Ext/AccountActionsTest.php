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
 * Extensión "account": ficha editable, grupo, restablecer contraseña y RGPD.
 * El puente está simulado con Http::fake — ninguna prueba toca la tienda.
 */
class AccountActionsTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private string $apiUrl = 'https://ps.test/modules/alsernetbridge/api.php';

    private const VERSION = '2026-07-09 12:05:51';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'helpdeskprestashop.api_url' => $this->apiUrl,
            'helpdeskprestashop.webhook_secret' => 'test-secret-for-hmac',
        ]);

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        foreach (['helpdesk.customers.view', 'helpdesk.customers.manage', 'helpdesk.customers.update'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function agent(string ...$extra): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(array_merge(
            ['helpdesk.customers.view', 'helpdesk.customers.manage', 'helpdesk.customers.update', 'helpdeskprestashop.view'],
            $extra
        ));

        return $user;
    }

    private function customer(): Customer
    {
        return Customer::factory()->create(['email' => 'cuenta-'.uniqid().'@example.com']);
    }

    private function profile(array $overrides = []): array
    {
        return array_replace_recursive([
            'id' => 812519,
            'email' => 'cliente@example.com',
            'firstname' => 'Ana',
            'lastname' => 'García',
            'id_lang' => 1,
            'languages' => [['id' => 1, 'name' => 'Español', 'iso' => 'es']],
            'newsletter' => false,
            'newsletter_date_add' => null,
            'optin' => false,
            'active' => true,
            'is_guest' => false,
            'version' => self::VERSION,
            'phone' => ['address_id' => 496325, 'alias' => 'Casa', 'value' => '600111222', 'version' => '2024-02-29 11:53:00'],
            'group' => ['id' => 3, 'name' => 'Cliente', 'reduction' => 0, 'tax_excluded' => false, 'show_prices' => true],
            'groups' => [],
            'member_of' => [3],
            'access' => ['can_reset' => true],
            'gdpr' => ['consents' => []],
        ], $overrides);
    }

    /**
     * Puente simulado por acción: lo que cada acción debe responder.
     *
     * @param  array<string, array>  $byAction
     */
    private function fakeBridge(array $byAction): void
    {
        Http::fake(function (Request $request) use ($byAction) {
            $action = $request->data()['action'] ?? '';

            return Http::response(['ok' => true, 'data' => $byAction[$action] ?? null]);
        });
    }

    public function test_show_requires_prestashop_view_permission(): void
    {
        Http::fake();
        $user = User::factory()->create();
        $user->givePermissionTo(['helpdesk.customers.view', 'helpdesk.customers.manage']);

        $this->actingAs($user)
            ->getJson(route('manager.helpdesk.ps.ext.account.show', $this->customer()))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_show_returns_profile_and_abilities_of_the_agent(): void
    {
        $this->fakeBridge(['account.profile' => $this->profile()]);

        $this->actingAs($this->agent('helpdeskprestashop.account.update'))
            ->getJson(route('manager.helpdesk.ps.ext.account.show', $this->customer()))
            ->assertOk()
            ->assertJsonPath('data.version', self::VERSION)
            ->assertJsonPath('can.update', true)
            ->assertJsonPath('can.group', false)
            ->assertJsonPath('can.gdpr_export', false);
    }

    public function test_update_without_permission_is_forbidden_and_never_reaches_prestashop(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->patchJson(route('manager.helpdesk.ps.ext.account.update', $this->customer()), ['version' => self::VERSION, 'firstname' => 'Eva'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_update_sends_only_the_changed_fields_and_never_the_email(): void
    {
        $this->fakeBridge([
            'account.profile' => $this->profile(),
            'account.update' => ['updated' => true, 'changed' => ['firstname', 'newsletter'], 'version' => '2026-09-24 10:00:00'],
        ]);

        $this->actingAs($this->agent('helpdeskprestashop.account.update'))
            ->patchJson(route('manager.helpdesk.ps.ext.account.update', $this->customer()), [
                'version' => self::VERSION,
                'firstname' => 'Eva',
                'newsletter' => 1,
                'email' => 'otro@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.changed', ['firstname', 'newsletter']);

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'account.update'
                && $data['firstname'] === 'Eva'
                && $data['newsletter'] === true
                && $data['version'] === self::VERSION
                && ! array_key_exists('email', $data)
                && ! array_key_exists('lastname', $data)
                && $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    public function test_update_needs_at_least_one_change(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.account.update'))
            ->patchJson(route('manager.helpdesk.ps.ext.account.update', $this->customer()), ['version' => self::VERSION])
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_phone_change_requires_the_address_it_belongs_to(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.account.update'))
            ->patchJson(route('manager.helpdesk.ps.ext.account.update', $this->customer()), ['version' => self::VERSION, 'phone' => '600 999 888'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address_id', 'address_version']);

        Http::assertNothingSent();
    }

    public function test_version_conflict_is_reported_as_409(): void
    {
        $this->fakeBridge([
            'account.profile' => $this->profile(),
            'account.update' => ['ok_semantic' => false, 'error' => 'version_conflict', 'version' => '2026-09-24 09:00:00'],
        ]);

        $this->actingAs($this->agent('helpdeskprestashop.account.update'))
            ->patchJson(route('manager.helpdesk.ps.ext.account.update', $this->customer()), ['version' => self::VERSION, 'lastname' => 'López'])
            ->assertStatus(409)
            ->assertJsonPath('conflict', true);
    }

    public function test_partial_save_reports_what_was_saved(): void
    {
        $this->fakeBridge([
            'account.profile' => $this->profile(),
            'account.update' => ['ok_semantic' => false, 'error' => 'phone_save_failed', 'changed' => ['lastname'], 'version' => '2026-09-24 10:00:00'],
        ]);

        $this->actingAs($this->agent('helpdeskprestashop.account.update'))
            ->patchJson(route('manager.helpdesk.ps.ext.account.update', $this->customer()), [
                'version' => self::VERSION,
                'lastname' => 'López',
                'phone' => '600 999 888',
                'address_id' => 496325,
                'address_version' => '2024-02-29 11:53:00',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'phone_save_failed')
            ->assertJsonPath('saved', ['lastname']);
    }

    public function test_agent_without_group_permission_cannot_change_group(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.account.update'))
            ->postJson(route('manager.helpdesk.ps.ext.account.group', $this->customer()), ['group_id' => 5, 'version' => self::VERSION, 'confirmed' => 1])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_group_change_must_be_confirmed(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.account.group'))
            ->postJson(route('manager.helpdesk.ps.ext.account.group', $this->customer()), ['group_id' => 5, 'version' => self::VERSION])
            ->assertStatus(422)
            ->assertJsonValidationErrors('confirmed');

        Http::assertNothingSent();
    }

    public function test_supervisor_changes_group(): void
    {
        $this->fakeBridge(['account.set_group' => [
            'updated' => true,
            'version' => '2026-09-24 10:00:00',
            'previous' => ['id' => 3, 'name' => 'Cliente'],
            'group' => ['id' => 5, 'name' => 'Pago securizado'],
        ]]);

        $this->actingAs($this->agent('helpdeskprestashop.account.group'))
            ->postJson(route('manager.helpdesk.ps.ext.account.group', $this->customer()), ['group_id' => 5, 'version' => self::VERSION, 'confirmed' => 1])
            ->assertOk()
            ->assertJsonPath('data.group.id', 5);

        Http::assertSent(fn (Request $r) => ($r->data()['action'] ?? null) === 'account.set_group'
            && $r->data()['group_id'] === 5
            && $r->hasHeader('X-Alsernet-Idempotency-Key'));
    }

    public function test_password_reset_too_soon_is_explained(): void
    {
        $this->fakeBridge(['account.password_reset' => ['ok_semantic' => false, 'error' => 'too_soon', 'next_reset_at' => '2026-09-24 16:00:00']]);

        $this->actingAs($this->agent('helpdeskprestashop.account.password_reset'))
            ->postJson(route('manager.helpdesk.ps.ext.account.password-reset', $this->customer()))
            ->assertStatus(422)
            ->assertJsonPath('error', 'too_soon')
            ->assertJsonPath('next_reset_at', '2026-09-24 16:00:00');
    }

    public function test_password_reset_never_returns_a_token(): void
    {
        $this->fakeBridge(['account.password_reset' => ['sent' => true, 'to' => 'cliente@example.com', 'valid_until' => '2026-09-25 10:00:00']]);

        $response = $this->actingAs($this->agent('helpdeskprestashop.account.password_reset'))
            ->postJson(route('manager.helpdesk.ps.ext.account.password-reset', $this->customer()))
            ->assertOk()
            ->assertJsonPath('data.sent', true);

        $this->assertStringNotContainsString('token', $response->getContent());
    }

    public function test_gdpr_export_requires_its_own_permission(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.account.update', 'helpdeskprestashop.account.gdpr_request'))
            ->getJson(route('manager.helpdesk.ps.ext.account.gdpr-export', $this->customer()))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_gdpr_export_downloads_json(): void
    {
        $this->fakeBridge(['account.gdpr_export' => [
            'customer' => ['id' => 812519, 'email' => 'cliente@example.com'],
            'orders' => [],
            'truncated' => ['orders' => false],
        ]]);

        $response = $this->actingAs($this->agent('helpdeskprestashop.account.gdpr_export'))
            ->get(route('manager.helpdesk.ps.ext.account.gdpr-export', $this->customer()))
            ->assertOk()
            ->assertJsonPath('customer.id', 812519);

        $this->assertStringContainsString('attachment; filename="rgpd-cliente-PS812519-', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_erasure_request_never_touches_prestashop(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.account.gdpr_request'))
            ->postJson(route('manager.helpdesk.ps.ext.account.erasure-request', $this->customer()), ['reason' => 'Lo pide por chat'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_requested', false);

        Http::assertNothingSent();
    }

    public function test_customer_without_email_nor_link_is_rejected(): void
    {
        Http::fake();
        $customer = Customer::factory()->create(['email' => null]);

        $this->actingAs($this->agent('helpdeskprestashop.account.password_reset'))
            ->postJson(route('manager.helpdesk.ps.ext.account.password-reset', $customer))
            ->assertStatus(422);

        Http::assertNothingSent();
    }
}
