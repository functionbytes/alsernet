<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Reintento manual desde el aviso "Sin cliente en gestión".
 *
 * Es la salida del callejón sin salida que había antes: el vínculo automático
 * no encontraba al cliente, no dejaba rastro, y el agente no tenía forma de
 * pedir que se volviera a mirar cuando el alta en gestión ya estaba hecha.
 */
class ErpRelinkEndpointTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    private User $user;

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

        config(['helpdeskErp.manager_url' => 'http://manager.test']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('helpdeskerp.view');
        $this->user->givePermissionTo('helpdeskerp.prospect.view');
        // El endpoint reutiliza ScopesCustomerByInbox, igual que la consulta de
        // contexto ERP: un agente solo puede reintentar sobre clientes de sus
        // bandejas. 'helpdesk.customers.manage' es el atajo que usa la propia
        // policy para los perfiles que las ven todas.
        $this->user->givePermissionTo('helpdesk.customers.manage');
    }

    public function test_agent_can_ask_for_the_lookup_again(): void
    {
        Queue::fake();

        $customer = $this->customer();

        $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.erp.customers.relink', ['customerId' => $customer->id]))
            ->assertOk()
            ->assertJsonPath('success', true);

        // force: el agente sabe algo que el automatismo no — acaban de dar de
        // alta al cliente, o el ERP ya volvió a estar en pie.
        Queue::assertPushed(LinkCustomerToErpJob::class, function (LinkCustomerToErpJob $job) use ($customer) {
            return (new \ReflectionProperty($job, 'customerId'))->getValue($job) === $customer->id
                && (new \ReflectionProperty($job, 'force'))->getValue($job) === true;
        });
    }

    /** El gate por bandeja es el mismo que protege la consulta de contexto. */
    public function test_agent_cannot_relink_a_customer_outside_their_inboxes(): void
    {
        Queue::fake();

        $agent = User::factory()->create();
        $agent->givePermissionTo('helpdeskerp.view');
        $agent->givePermissionTo('helpdeskerp.prospect.view');

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.erp.customers.relink', ['customerId' => $this->customer()->id]))
            ->assertForbidden();

        Queue::assertNotPushed(LinkCustomerToErpJob::class);
    }

    public function test_without_permission_there_is_no_relink(): void
    {
        Queue::fake();

        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->postJson(route('manager.helpdesk.erp.customers.relink', ['customerId' => $this->customer()->id]))
            ->assertForbidden();

        Queue::assertNotPushed(LinkCustomerToErpJob::class);
    }

    public function test_unknown_customer_returns_not_found(): void
    {
        Queue::fake();

        $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.erp.customers.relink', ['customerId' => 99999999]))
            ->assertNotFound();

        Queue::assertNotPushed(LinkCustomerToErpJob::class);
    }

    private function customer(): Customer
    {
        return Customer::factory()->create([
            'email' => Str::lower(Str::replace('-', '', Str::uuid())).'@test.example',
        ]);
    }
}
