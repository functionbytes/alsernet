<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Tests\TestCase;

class BackfillErpLinksCommandTest extends TestCase
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

    public function test_backfill_dispatches_job_only_for_specified_unlinked_ids(): void
    {
        Queue::fake();

        $unlinked = Customer::factory()->count(3)->create([
            'email' => fn () => $this->uniqueEmail(),
        ]);

        $linked = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $linked->linkExternalId('erp', (string) random_int(1000000, 9999999));

        $ids = $unlinked->pluck('id')->merge([$linked->id])->all();

        $this->artisan('helpdeskerp:backfill-links', ['--id' => $ids])
            ->expectsOutputToContain('A procesar: 3')
            ->assertExitCode(0);

        Queue::assertPushed(LinkCustomerToErpJob::class, 3);

        foreach ($unlinked as $customer) {
            Queue::assertPushed(
                LinkCustomerToErpJob::class,
                fn ($job) => $this->getJobCustomerId($job) === $customer->id
            );
        }

        Queue::assertNotPushed(LinkCustomerToErpJob::class, function ($job) use ($linked) {
            return $this->getJobCustomerId($job) === $linked->id;
        });
    }

    public function test_backfill_sync_mode_links_customer_inline(): void
    {
        $email = $this->uniqueEmail();
        $erpId = random_int(1000000, 9999999);

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => $erpId, 'label' => 'X', 'surnames' => '', 'email' => $email, 'cif' => '']],
            ]),
        ]);

        $customer = Customer::factory()->create([
            'email' => $email,
            'phone' => null,
            'whatsapp_phone' => null,
        ]);

        $this->artisan('helpdeskerp:backfill-links', ['--sync' => true, '--id' => [$customer->id]])
            ->expectsOutputToContain('Vinculados: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $customer->id,
            'platform' => 'erp',
            'external_id' => (string) $erpId,
        ], 'helpdesk');
    }

    public function test_backfill_respects_limit(): void
    {
        Queue::fake();

        $customers = Customer::factory()->count(5)->create([
            'email' => fn () => $this->uniqueEmail(),
        ]);

        $this->artisan('helpdeskerp:backfill-links', [
            '--limit' => 2,
            '--id' => $customers->pluck('id')->all(),
        ])
            ->expectsOutputToContain('A procesar: 2')
            ->assertExitCode(0);

        Queue::assertPushed(LinkCustomerToErpJob::class, 2);
    }

    public function test_backfill_reports_zero_when_target_ids_already_linked(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $customer->linkExternalId('erp', (string) random_int(1000000, 9999999));

        $this->artisan('helpdeskerp:backfill-links', ['--id' => [$customer->id]])
            ->expectsOutputToContain('A procesar: 0')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    private function uniqueEmail(): string
    {
        return Str::lower(Str::replace('-', '', Str::uuid())).'@test.example';
    }

    private function getJobCustomerId(LinkCustomerToErpJob $job): int
    {
        $ref = new \ReflectionProperty($job, 'customerId');

        return $ref->getValue($job);
    }
}
