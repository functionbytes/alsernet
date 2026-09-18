<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\HelpdeskErp\Services\ErpCustomerLinkerService;
use Tests\TestCase;

/**
 * Regression coverage for the `erp.integration_enabled` admin toggle
 * (panel/settings/helpdesk/integrations). Verifies that both HelpdeskErp
 * entry points into the ERP linking flow — the DispatchErpLinkJob listener
 * and LinkCustomerToErpJob itself — honour helpdesk_erp_enabled() and
 * become no-ops while disabled.
 */
class ErpIntegrationToggleTest extends TestCase
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

        config(['helpdeskErp.manager_url' => 'http://manager.test']);
    }

    protected function tearDown(): void
    {
        Setting::set('erp.integration_enabled', '1', 'integrations');

        parent::tearDown();
    }

    public function test_dispatch_listener_skips_link_job_when_toggle_is_disabled(): void
    {
        Setting::set('erp.integration_enabled', '0', 'integrations');
        Queue::fake();

        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $conversation = $this->makeConversation($customer);

        event(new ConversationCreated($conversation));

        Queue::assertNotPushed(LinkCustomerToErpJob::class);
    }

    public function test_dispatch_listener_pushes_link_job_when_toggle_is_enabled(): void
    {
        Setting::set('erp.integration_enabled', '1', 'integrations');
        Queue::fake();

        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $conversation = $this->makeConversation($customer);

        event(new ConversationCreated($conversation));

        Queue::assertPushed(LinkCustomerToErpJob::class, function ($job) use ($customer) {
            return $this->getJobCustomerId($job) === $customer->id;
        });
    }

    public function test_link_job_does_not_call_erp_when_toggle_is_disabled(): void
    {
        Setting::set('erp.integration_enabled', '0', 'integrations');

        $email = $this->uniqueEmail();
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $id, 'label' => 'Noa', 'surnames' => '', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $email,
            'phone' => null,
            'whatsapp_phone' => null,
        ]);

        (new LinkCustomerToErpJob($customer->id))->handle(
            app(ErpCustomerLinkerService::class)
        );

        Http::assertNothingSent();
        $this->assertDatabaseMissing('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
        ], 'helpdesk');
    }

    public function test_link_job_links_customer_when_toggle_is_enabled(): void
    {
        Setting::set('erp.integration_enabled', '1', 'integrations');

        $email = $this->uniqueEmail();
        $id = $this->uniqueErpId();

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $id, 'label' => 'Olga', 'surnames' => '', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $email,
            'phone' => null,
            'whatsapp_phone' => null,
        ]);

        (new LinkCustomerToErpJob($customer->id))->handle(
            app(ErpCustomerLinkerService::class)
        );

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
            'external_id' => (string) $id,
        ], 'helpdesk');
    }

    /** Email único por UUID para evitar colisiones de unique key entre ejecuciones. */
    private function uniqueEmail(): string
    {
        return Str::lower(Str::replace('-', '', Str::uuid())).'@test.example';
    }

    /** ID ERP aleatorio para evitar colisiones de unique key entre ejecuciones. */
    private function uniqueErpId(): int
    {
        return random_int(1000000, 9999999);
    }

    private function makeConversation(Customer $customer): Conversation
    {
        return Conversation::factory()->create([
            'customer_id' => $customer->id,
        ]);
    }

    private function getJobCustomerId(LinkCustomerToErpJob $job): int
    {
        $ref = new \ReflectionProperty($job, 'customerId');

        return $ref->getValue($job);
    }
}
