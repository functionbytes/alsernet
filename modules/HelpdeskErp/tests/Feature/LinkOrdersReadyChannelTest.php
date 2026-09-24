<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Broadcasting\LinkErpCustomerChannel;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Modules\HelpdeskErp\Events\ErpOrdersReady;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * "Pedidos listos" llega también al canal privado por contacto del helpdesk
 * (helpdesk.erp.customer.{id}), resuelto por el vínculo con Gestión y no por
 * el email: el email de Gestión que manda el manager no siempre coincide con
 * el del contacto, y entonces el canal por hash de email no llegaba a nadie.
 */
class LinkOrdersReadyChannelTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    private const SECRET = 'test-webhook-secret-32-chars-long';

    private const URL = '/api/helpdeskErp/webhooks/orders-ready';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(Pulse::class, new class
        {
            public function set(string $type, string $key, mixed $value, mixed $timestamp = null): object
            {
                return new \stdClass;
            }

            public function record(mixed ...$args): object
            {
                return new \stdClass;
            }
        });

        config([
            'helpdeskErp.manager_url' => 'http://manager.test',
            'helpdeskErp.webhook_secret' => self::SECRET,
            'helpdeskErp.link.auto_on_prestashop' => false,
        ]);
    }

    public function test_webhook_targets_helpdesk_customers_linked_to_the_erp_id(): void
    {
        if (! helpdesk_erp_enabled()) {
            $this->markTestSkipped('La integración con Gestión está desactivada en este entorno.');
        }

        Event::fake([ErpOrdersReady::class]);

        $erpId = random_int(900000000, 999999999);
        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $customer->linkExternalId('erp', (string) $erpId, ['linked_via' => 'prestashop_id']);

        // El email de Gestión es distinto del del contacto del helpdesk.
        $this->postSigned(['email' => $this->uniqueEmail(), 'customer_id' => $erpId])
            ->assertOk()
            ->assertJson(['ok' => true]);

        Event::assertDispatched(ErpOrdersReady::class, function (ErpOrdersReady $event) use ($customer, $erpId) {
            $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

            return $event->customerId === $erpId
                && in_array($customer->id, $event->helpdeskCustomerIds, true)
                && in_array('private-helpdesk.erp.customer.'.$customer->id, $names, true);
        });
    }

    public function test_webhook_also_targets_the_contact_with_the_same_email(): void
    {
        if (! helpdesk_erp_enabled()) {
            $this->markTestSkipped('La integración con Gestión está desactivada en este entorno.');
        }

        Event::fake([ErpOrdersReady::class]);

        $email = $this->uniqueEmail();
        $customer = Customer::factory()->create(['email' => $email]);

        $this->postSigned(['email' => $email, 'customer_id' => null])->assertOk();

        Event::assertDispatched(ErpOrdersReady::class, fn (ErpOrdersReady $e) => $e->helpdeskCustomerIds === [$customer->id]);
    }

    public function test_event_keeps_the_email_channel_and_adds_one_per_contact(): void
    {
        $event = new ErpOrdersReady('Cliente@Ejemplo.com ', 101544116, [19457, 19457, 0]);

        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertSame([
            'private-erp-orders-ready.'.md5('cliente@ejemplo.com'),
            'private-helpdesk.erp.customer.19457',
        ], $names);
        $this->assertSame('erp.orders.ready', $event->broadcastAs());
        $this->assertSame(101544116, $event->broadcastWith()['erp_customer_id']);
    }

    public function test_channel_requires_erp_view_permission(): void
    {
        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $user = User::factory()->create();

        $this->assertFalse((new LinkErpCustomerChannel)->join($user, $customer->id));
    }

    public function test_channel_requires_the_customer_in_an_inbox_of_the_agent(): void
    {
        $this->seedPermissions();

        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $user = User::factory()->create();
        $user->givePermissionTo('helpdeskerp.view');

        // Sin bandejas asignadas no comparte bandeja con nadie.
        $this->assertFalse((new LinkErpCustomerChannel)->join($user, $customer->id));
        $this->assertFalse((new LinkErpCustomerChannel)->join($user, 'abc'));
        $this->assertFalse((new LinkErpCustomerChannel)->join($user, 0));
    }

    public function test_helpdesk_manager_can_join(): void
    {
        $this->seedPermissions();

        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $user = User::factory()->create();
        $user->givePermissionTo(['helpdeskerp.view', 'helpdesk.manage']);

        $this->assertTrue((new LinkErpCustomerChannel)->join($user, $customer->id));
        $this->assertFalse((new LinkErpCustomerChannel)->join($user, 999999999));
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    private function seedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);

        Permission::findOrCreate('helpdesk.manage', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postSigned(array $payload): TestResponse
    {
        $timestamp = time();
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $timestamp.':'.$body, self::SECRET);

        return $this->call('POST', self::URL, [], [], [], [
            'HTTP_X_ERP_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_ERP_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body);
    }

    private function uniqueEmail(): string
    {
        return 'erp-ready-'.Str::lower(Str::random(12)).'@example.test';
    }
}
