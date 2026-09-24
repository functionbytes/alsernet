<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Tests\TestCase;

/**
 * Al crear el vínculo de un contacto con PrestaShop (CustomerExternalId
 * platform='prestashop') se encola LinkCustomerToErpJob con force=true.
 * Lo registra HelpdeskErpServiceProvider como observer de Eloquent.
 */
class LinkAutoOnPrestashopTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

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
            'helpdeskErp.link.auto_on_prestashop' => true,
        ]);

        if (! helpdesk_erp_enabled()) {
            $this->markTestSkipped('La integración con Gestión está desactivada en este entorno.');
        }
    }

    public function test_linking_prestashop_dispatches_forced_erp_link_job(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $customer->linkExternalId('prestashop', (string) random_int(900000000, 999999999));

        Queue::assertPushed(LinkCustomerToErpJob::class, function (LinkCustomerToErpJob $job) use ($customer) {
            return $this->prop($job, 'customerId') === $customer->id
                && $this->prop($job, 'force') === true;
        });
    }

    public function test_other_platforms_do_not_dispatch(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $customer->linkExternalId('erp', (string) random_int(900000000, 999999999));

        Queue::assertNotPushed(LinkCustomerToErpJob::class);
    }

    public function test_customer_already_linked_to_erp_does_not_dispatch(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $customer->linkExternalId('erp', (string) random_int(900000000, 999999999));
        $customer->linkExternalId('prestashop', (string) random_int(900000000, 999999999));

        Queue::assertNotPushed(LinkCustomerToErpJob::class);
    }

    public function test_switch_off_does_not_dispatch(): void
    {
        config(['helpdeskErp.link.auto_on_prestashop' => false]);
        Queue::fake();

        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $customer->linkExternalId('prestashop', (string) random_int(900000000, 999999999));

        Queue::assertNotPushed(LinkCustomerToErpJob::class);
    }

    private function prop(object $job, string $name): mixed
    {
        return (new \ReflectionProperty($job, $name))->getValue($job);
    }

    private function uniqueEmail(): string
    {
        return 'erp-auto-'.Str::lower(Str::random(12)).'@example.test';
    }
}
