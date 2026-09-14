<?php

namespace Modules\HelpdeskIntegration\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskIntegration\Database\Seeders\HelpdeskIntegrationProvidersSeeder;
use Modules\HelpdeskIntegration\Jobs\ResyncCustomerIntegrationsJob;
use Modules\HelpdeskIntegration\Services\CustomerIntegrationService;

/**
 * Presupuesto de tiempo del sync masivo (CustomerIntegrationService::sync()):
 * las plataformas que no dan tiempo dentro del presupuesto quedan en estado
 * parcial 'pending' (visible en el payload del modal) y se encolan en
 * ResyncCustomerIntegrationsJob para reverificarse en background, en vez de
 * bloquear el request reverificando todo síncronamente.
 */
class SyncBudgetTest extends HelpdeskTestCase
{
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskIntegrationProvidersSeeder::class);

        $this->customer = Customer::factory()->create(['email' => 'budget-'.uniqid().'@example.com']);
        $this->customer->linkExternalId('prestashop', '1111');
        $this->customer->linkExternalId('erp', '2222');
    }

    public function test_links_beyond_budget_are_marked_pending_and_queued(): void
    {
        Queue::fake();

        // Presupuesto agotado desde el inicio: ninguna plataforma se procesa
        // en el request; todas quedan pendientes y encoladas.
        config(['helpdeskintegration.sync.budget_seconds' => 0]);

        $result = app(CustomerIntegrationService::class)->sync($this->customer);

        $this->assertTrue($result['linked']);

        $statuses = $this->customer->externalIds()->pluck('sync_status', 'platform');
        $this->assertSame('pending', $statuses['prestashop']);
        $this->assertSame('pending', $statuses['erp']);

        // El payload que consume el modal expone el estado parcial.
        $byPlatform = collect($result['payload']['integrations'])->keyBy('platform');
        $this->assertSame('pending', $byPlatform['prestashop']['sync_status']);
        $this->assertSame('pending', $byPlatform['erp']['sync_status']);

        Queue::assertPushed(ResyncCustomerIntegrationsJob::class, 1);
    }

    public function test_sync_within_budget_processes_all_links_and_queues_nothing(): void
    {
        Queue::fake();

        config(['helpdeskintegration.sync.budget_seconds' => 60]);

        $result = app(CustomerIntegrationService::class)->sync($this->customer);

        $this->assertTrue($result['linked']);

        foreach ($this->customer->externalIds()->get() as $link) {
            $this->assertNotNull($link->last_synced_at);
            // Con driver real el resultado depende del entorno, pero nunca
            // debe quedarse en 'pending' si entró en el presupuesto.
            $this->assertContains($link->sync_status, ['ok', 'not_found', 'error']);
        }

        Queue::assertNotPushed(ResyncCustomerIntegrationsJob::class);
    }

    public function test_background_job_resyncs_pending_links_to_their_real_state(): void
    {
        config(['helpdeskintegration.sync.budget_seconds' => 0]);

        Queue::fake();
        app(CustomerIntegrationService::class)->sync($this->customer);

        $pendingIds = $this->customer->externalIds()->pluck('id')->all();

        // Ejecuta el job en primer plano, como lo haría el worker.
        (new ResyncCustomerIntegrationsJob($this->customer->id, $pendingIds))
            ->handle(app(CustomerIntegrationService::class));

        foreach ($this->customer->externalIds()->get() as $link) {
            $this->assertNotSame('pending', $link->sync_status);
            $this->assertNotNull($link->last_synced_at);
        }
    }

    public function test_background_job_ignores_links_of_other_customers(): void
    {
        $other = Customer::factory()->create(['email' => 'other-'.uniqid().'@example.com']);
        $foreignLink = $other->linkExternalId('prestashop', '9999');
        $foreignLink->update(['sync_status' => 'pending']);

        (new ResyncCustomerIntegrationsJob($this->customer->id, [$foreignLink->id]))
            ->handle(app(CustomerIntegrationService::class));

        $this->assertSame('pending', $foreignLink->fresh()->sync_status);
    }
}
