<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Mockery;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Listeners\LinkContactOnPrestashopSignup;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use Modules\HelpdeskIntegration\Services\CustomerIntegrationService;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Ronda "ERP + PrestaShop" de Contactos 360: vínculos e identidad, alta en la
 * tienda, pestaña ERP con el id vinculado y sincronizar con el buscador
 * completo de Gestión.
 */
class ContactLinksAndErpTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = [null, 'mysql', 'helpdesk'];

    private User $manager;

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

        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);

        foreach (['contacts.view', 'contacts.update', 'helpdesk.manage', 'helpdesk.integrations.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage']);
    }

    private function customer(array $attributes = []): Customer
    {
        return Customer::factory()->create(array_merge([
            'email' => 'ct-'.Str::lower(Str::random(12)).'@test.invalid',
            'phone' => null,
        ], $attributes));
    }

    private function requireIntegration(): void
    {
        if (! helpdesk_integration_enabled() || ! class_exists(CustomerIntegrationService::class)) {
            $this->markTestSkipped('HelpdeskIntegration apagado.');
        }
    }

    public function test_links_show_lists_linked_platforms_history_and_permissions(): void
    {
        $this->requireIntegration();
        $customer = $this->customer();
        $customer->linkExternalId('prestashop', '424242');

        $data = $this->actingAs($this->manager)
            ->getJson(route('contacts.links.show', $customer))
            ->assertOk()
            ->json('data');

        $this->assertSame(['prestashop'], array_column($data['integrations'], 'platform'));
        $this->assertSame('424242', $data['integrations'][0]['externalId']);
        $this->assertFalse($data['can']['unlink'], 'sin helpdesk.integrations.manage no se desvincula');
        $this->assertTrue($data['can']['link']);
        $this->assertArrayHasKey('verified', $data['identity']);
    }

    public function test_unlink_requires_integrations_manage(): void
    {
        $this->requireIntegration();
        $customer = $this->customer();
        $customer->linkExternalId('prestashop', '515151');

        $this->actingAs($this->manager)
            ->postJson(route('contacts.links.unlink', $customer), ['platform' => 'prestashop'])
            ->assertForbidden();

        $this->assertSame('515151', $customer->fresh('externalIds')->externalIdFor('prestashop'));
    }

    public function test_unlink_removes_the_link_and_records_history(): void
    {
        $this->requireIntegration();
        $this->manager->givePermissionTo('helpdesk.integrations.manage');
        $customer = $this->customer();
        $customer->linkExternalId('prestashop', '616161');

        $data = $this->actingAs($this->manager)
            ->postJson(route('contacts.links.unlink', $customer), ['platform' => 'prestashop'])
            ->assertOk()
            ->json('data');

        $this->assertNull($customer->fresh('externalIds')->externalIdFor('prestashop'));
        $this->assertSame([], $data['integrations']);
        $this->assertNotEmpty($data['history'], 'el desvincular queda en el historial');
    }

    public function test_unlink_rejects_a_platform_that_is_not_linked(): void
    {
        $this->requireIntegration();
        $this->manager->givePermissionTo('helpdesk.integrations.manage');

        $this->actingAs($this->manager)
            ->postJson(route('contacts.links.unlink', $this->customer()), ['platform' => 'erp'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('platform');
    }

    public function test_suggestions_propose_erp_client_whose_internet_code_is_the_linked_shop_account(): void
    {
        $this->requireIntegration();
        $customer = $this->customer();
        $customer->linkExternalId('prestashop', '777001');

        $mock = Mockery::mock(CustomerIntegrationService::class);
        $mock->shouldReceive('search')->once()->with('erp', '777001', 'auto')->andReturn(['ok' => true, 'results' => [
            ['id' => '90001', 'name' => 'Otro', 'email' => '', 'code_internet' => '123'],
            ['id' => '90002', 'name' => 'Cliente Bueno', 'email' => 'bueno@test.invalid', 'code_internet' => '777001'],
        ]]);
        $this->app->instance(CustomerIntegrationService::class, $mock);

        $data = $this->actingAs($this->manager)
            ->getJson(route('contacts.links.suggestions', $customer))
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $data);
        $this->assertSame(['erp', '90002'], [$data[0]['platform'], $data[0]['externalId']]);
    }

    public function test_suggestions_propose_shop_account_from_erp_internet_code(): void
    {
        $this->requireIntegration();
        $customer = $this->customer();
        $customer->linkExternalId('erp', '55001');

        $mock = Mockery::mock(CustomerIntegrationService::class);
        $mock->shouldReceive('detail')->once()->andReturn(['record' => ['name' => 'Cliente ERP', 'code_internet' => '888002']]);
        $this->app->instance(CustomerIntegrationService::class, $mock);

        $data = $this->actingAs($this->manager)
            ->getJson(route('contacts.links.suggestions', $customer))
            ->assertOk()
            ->json('data');

        $this->assertSame([['prestashop', '888002']], array_map(fn ($s) => [$s['platform'], $s['externalId']], $data));
    }

    public function test_links_endpoints_respect_contact_visibility(): void
    {
        $this->requireIntegration();
        $stranger = User::factory()->create();
        $stranger->givePermissionTo(['contacts.view']);

        $this->actingAs($stranger)
            ->getJson(route('contacts.links.show', $this->customer()))
            ->assertForbidden();
    }

    public function test_shop_signup_links_the_existing_contact_by_email(): void
    {
        $customer = $this->customer(['email' => 'Alta-'.Str::random(6).'@Test.invalid']);

        (new LinkContactOnPrestashopSignup)->handle($this->signupEvent(313131, strtolower($customer->email)));

        $this->assertSame('313131', $customer->fresh('externalIds')->externalIdFor('prestashop'));
    }

    public function test_shop_signup_does_not_overwrite_an_existing_link_nor_create_contacts(): void
    {
        $customer = $this->customer();
        $customer->linkExternalId('prestashop', '1000');
        $before = Customer::query()->count();

        (new LinkContactOnPrestashopSignup)->handle($this->signupEvent(2000, $customer->email));
        (new LinkContactOnPrestashopSignup)->handle($this->signupEvent(3000, 'nadie-'.Str::random(8).'@test.invalid'));

        $this->assertSame('1000', $customer->fresh('externalIds')->externalIdFor('prestashop'));
        $this->assertSame($before, Customer::query()->count());
    }

    public function test_erp_tab_uses_the_linked_erp_id_and_reports_lookup_status(): void
    {
        if (! class_exists('Modules\\HelpdeskErp\\Services\\ErpContextService')) {
            $this->markTestSkipped('HelpdeskErp no instalado.');
        }

        $customer = $this->customer();
        $customer->linkExternalId('erp', '4321');
        $customer->recordErpLookup('not_found');

        $erp = Mockery::mock('Modules\\HelpdeskErp\\Services\\ErpContextService');
        // Caché por email con "no encontrado" de antes del vínculo: se olvida.
        $erp->shouldReceive('peekCachedContext')->once()->with($customer->email)->andReturn(['customer' => ['found' => false]]);
        $erp->shouldReceive('forgetCache')->once()->with($customer->email);
        $erp->shouldReceive('getCustomerContext')->once()
            ->with($customer->email, null, $customer->id, 4321)
            ->andReturn(['customer' => ['found' => true, 'id' => 4321]]);
        $this->app->instance('Modules\\HelpdeskErp\\Services\\ErpContextService', $erp);

        $aggregator = app(ContactAggregatorService::class);
        $result = $aggregator->erp($customer->fresh('externalIds'));

        if (($result['available'] ?? false) === false) {
            $this->markTestSkipped('HelpdeskErp apagado en este entorno.');
        }

        $this->assertSame(4321, $result['customer']['id']);
        $this->assertSame('not_found', $result['lookup']['status']);
        $this->assertTrue($result['lookup']['linked']);
    }

    public function test_erp_tab_without_email_nor_phone_explains_why(): void
    {
        $customer = $this->customer(['email' => null, 'phone' => null, 'whatsapp_phone' => null]);

        $result = app(ContactAggregatorService::class)->erp($customer);

        if (($result['available'] ?? false) === false) {
            $this->markTestSkipped('HelpdeskErp apagado en este entorno.');
        }

        $this->assertTrue($result['noIdentifiers']);
        $this->assertFalse($result['customer']['found']);
    }

    public function test_sync_uses_the_full_erp_linker(): void
    {
        $linker = 'Modules\\HelpdeskErp\\Services\\ErpCustomerLinkerService';
        if (! class_exists($linker) || ! Module::find('HelpdeskErp')?->isEnabled()) {
            $this->markTestSkipped('HelpdeskErp no instalado o apagado.');
        }

        $customer = $this->customer();
        $mock = Mockery::mock($linker);
        $mock->shouldReceive('linkCustomer')->once()->with(Mockery::on(fn ($c) => $c->id === $customer->id))->andReturn(null);
        $this->app->instance($linker, $mock);

        app(ContactAggregatorService::class)->syncIntegrations($customer, 'erp');
    }

    private function signupEvent(int $psId, string $email): object
    {
        return new class($psId, $email)
        {
            public function __construct(private int $id, private string $mail) {}

            public function customerId(): ?int
            {
                return $this->id;
            }

            public function email(): ?string
            {
                return $this->mail;
            }
        };
    }
}
