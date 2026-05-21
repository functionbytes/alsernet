<?php

namespace Modules\Remarketing\Tests\Feature\Services;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Remarketing\Jobs\SendEmailJob;
use Modules\Remarketing\Models\Automation;
use Modules\Remarketing\Models\AutomationRun;
use Modules\Remarketing\Models\AutomationStep;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Services\AutomationService;
use Tests\TestCase;

class AutomationServiceTest extends TestCase
{
    use DatabaseTransactions;

    private AutomationService $service;

    private Store $store;

    private ?User $testUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_stores')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        $this->testUser = User::factory()->create();
        $this->service = new AutomationService;
        $this->store = $this->createStore();
    }

    public function test_trigger_creates_run_when_none_exists(): void
    {
        $automation = $this->createAutomation();
        $customer = $this->createCustomer();

        $run = $this->service->trigger($automation, $customer);

        $this->assertNotNull($run);
        $this->assertEquals($automation->id, $run->automation_id);
        $this->assertEquals($customer->id, $run->customer_id);
        $this->assertEquals('running', $run->status);
        $this->assertEquals(0, $run->current_step);
    }

    public function test_trigger_does_not_duplicate_active_runs(): void
    {
        $automation = $this->createAutomation();
        $customer = $this->createCustomer();

        AutomationRun::query()->create([
            'automation_id' => $automation->id,
            'customer_id' => $customer->id,
            'current_step' => 0,
            'status' => 'running',
            'context' => [],
            'next_step_at' => now(),
            'started_at' => now(),
        ]);

        $result = $this->service->trigger($automation, $customer);

        $this->assertNull($result);
        $this->assertEquals(1, AutomationRun::query()
            ->where('automation_id', $automation->id)
            ->where('customer_id', $customer->id)
            ->where('status', 'running')
            ->count());
    }

    public function test_advance_executes_wait_step_setting_next_step_at(): void
    {
        $automation = $this->createAutomation();
        AutomationStep::query()->create([
            'automation_id' => $automation->id,
            'sort_order' => 0,
            'type' => 'wait',
            'config' => ['hours' => 24],
        ]);
        AutomationStep::query()->create([
            'automation_id' => $automation->id,
            'sort_order' => 1,
            'type' => 'wait',
            'config' => ['hours' => 12],
        ]);

        $customer = $this->createCustomer();
        $run = AutomationRun::query()->create([
            'automation_id' => $automation->id,
            'customer_id' => $customer->id,
            'current_step' => 0,
            'status' => 'running',
            'context' => [],
            'next_step_at' => now(),
            'started_at' => now(),
        ]);

        $before = now();
        $this->service->advance($run);
        $run->refresh();

        $this->assertGreaterThan($before->addHours(23), $run->next_step_at);
        $this->assertEquals(1, $run->current_step);
    }

    public function test_advance_executes_send_email_step_dispatching_job(): void
    {
        Queue::fake();

        $automation = $this->createAutomation();
        AutomationStep::query()->create([
            'automation_id' => $automation->id,
            'sort_order' => 0,
            'type' => 'send_email',
            'config' => ['template_id' => 1, 'subject' => 'Test'],
        ]);
        AutomationStep::query()->create([
            'automation_id' => $automation->id,
            'sort_order' => 1,
            'type' => 'wait',
            'config' => ['hours' => 1],
        ]);

        $customer = $this->createCustomer();
        $run = AutomationRun::query()->create([
            'automation_id' => $automation->id,
            'customer_id' => $customer->id,
            'current_step' => 0,
            'status' => 'running',
            'context' => [],
            'next_step_at' => now(),
            'started_at' => now(),
        ]);

        $this->service->advance($run);

        Queue::assertPushed(SendEmailJob::class);
        $run->refresh();
        $this->assertEquals(1, $run->current_step);
    }

    public function test_advance_marks_completed_after_last_step(): void
    {
        Queue::fake();

        $automation = $this->createAutomation();
        AutomationStep::query()->create([
            'automation_id' => $automation->id,
            'sort_order' => 0,
            'type' => 'wait',
            'config' => ['hours' => 0],
        ]);

        $customer = $this->createCustomer();
        // Run is already on step 0, after advance it should advance to step 1 (> total steps)
        $run = AutomationRun::query()->create([
            'automation_id' => $automation->id,
            'customer_id' => $customer->id,
            'current_step' => 0,
            'status' => 'running',
            'context' => [],
            'next_step_at' => now(),
            'started_at' => now(),
        ]);

        $this->service->advance($run);

        $run->refresh();
        $this->assertEquals('completed', $run->status);
        $this->assertNotNull($run->completed_at);
    }

    public function test_cancel_marks_run_cancelled(): void
    {
        $automation = $this->createAutomation();
        $customer = $this->createCustomer();
        $run = AutomationRun::query()->create([
            'automation_id' => $automation->id,
            'customer_id' => $customer->id,
            'current_step' => 1,
            'status' => 'running',
            'context' => [],
            'next_step_at' => now(),
            'started_at' => now(),
        ]);

        $this->service->cancel($run);

        $run->refresh();
        $this->assertEquals('cancelled', $run->status);
        $this->assertNotNull($run->completed_at);
    }

    private function createStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'user_id' => $this->testUser?->id ?? User::factory()->create()->id,
            'platform' => 'manual',
            'name' => 'Test Store '.uniqid(),
            'domain' => 'test-'.uniqid().'.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ], $attributes));
    }

    private function createCustomer(): Customer
    {
        return Customer::query()->create([
            'store_id' => $this->store->id,
            'email' => 'auto-'.uniqid().'@example.com',
            'email_hash' => hash('sha256', uniqid()),
            'status' => 'subscribed',
            'consent_marketing' => true,
        ]);
    }

    private function createAutomation(array $attributes = []): Automation
    {
        return Automation::query()->create(array_merge([
            'store_id' => $this->store->id,
            'name' => 'Test Automation '.uniqid(),
            'trigger' => 'subscription',
            'status' => 'active',
            'runs_total' => 0,
        ], $attributes));
    }
}
