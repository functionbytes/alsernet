<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Services\ErpCustomerLinkerService;
use Tests\TestCase;

/**
 * Gestión → tienda: al vincular un contacto con Gestión, si la ficha del ERP
 * trae CODIGO_INTERNET (id de cliente PrestaShop) y el contacto no tiene
 * vínculo con la tienda, se crea. Nunca pisa un vínculo existente.
 */
class LinkStoreFromErpTest extends TestCase
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
            'helpdeskErp.link.auto_on_prestashop' => false,
            'helpdeskErp.link.store_from_erp' => true,
        ]);
    }

    public function test_linking_by_email_also_links_the_store_from_code_internet(): void
    {
        $erpId = $this->uniqueId();
        $psId = $this->uniqueId();
        $email = $this->uniqueEmail();

        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => [[
                'id' => $erpId, 'label' => 'ANA', 'surnames' => 'PRUEBA', 'email' => $email,
                'cif' => null, 'code_internet' => (string) $psId,
            ]]]),
            '*' => Http::response(['success' => false], 404),
        ]);

        $customer = Customer::factory()->create(['email' => $email, 'phone' => null, 'whatsapp_phone' => null]);

        $this->assertSame($erpId, app(ErpCustomerLinkerService::class)->linkCustomer($customer->load('externalIds')));

        $store = $customer->externalIds()->where('platform', 'prestashop')->first();
        $this->assertNotNull($store);
        $this->assertSame((string) $psId, (string) $store->external_id);
        $this->assertSame('erp_code_internet', $store->metadata['linked_via']);
    }

    public function test_existing_store_link_is_never_replaced(): void
    {
        $erpId = $this->uniqueId();
        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $customer->linkExternalId('prestashop', '111', null);

        Http::fake(['*' => Http::response(['success' => true, 'data' => ['id' => $erpId, 'code_internet' => '222']])]);

        $this->assertFalse(app(ErpCustomerLinkerService::class)->linkStoreFromErp($customer, $erpId));
        $this->assertSame(['111'], $customer->externalIds()->where('platform', 'prestashop')->pluck('external_id')->all());
        Http::assertNothingSent();
    }

    public function test_record_of_another_erp_customer_is_ignored_and_summary_is_fetched(): void
    {
        $erpId = $this->uniqueId();
        $psId = $this->uniqueId();
        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);

        Http::fake(["*/erp/customer/{$erpId}" => Http::response(['success' => true, 'data' => ['id' => $erpId, 'code_internet' => (string) $psId]])]);

        $linked = app(ErpCustomerLinkerService::class)
            ->linkStoreFromErp($customer, $erpId, ['id' => $erpId + 1, 'code_internet' => '999']);

        $this->assertTrue($linked);
        $this->assertSame((string) $psId, (string) $customer->externalIds()->where('platform', 'prestashop')->value('external_id'));
    }

    public function test_disabled_by_config(): void
    {
        config(['helpdeskErp.link.store_from_erp' => false]);
        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        Http::fake();

        $this->assertFalse(app(ErpCustomerLinkerService::class)->linkStoreFromErp($customer, 123, ['id' => 123, 'code_internet' => '456']));
        $this->assertFalse($customer->externalIds()->where('platform', 'prestashop')->exists());
    }

    private function uniqueId(): int
    {
        return random_int(900000000, 999999999);
    }

    private function uniqueEmail(): string
    {
        return 'erp-store-'.Str::lower(Str::random(12)).'@example.test';
    }
}
