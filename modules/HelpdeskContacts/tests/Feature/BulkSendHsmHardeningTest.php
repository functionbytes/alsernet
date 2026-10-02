<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Modules\Helpdesk\Jobs\SendBulkHsmTemplateJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\HsmConversationService;
use Modules\HelpdeskContacts\Database\Seeders\HelpdeskContactsPermissionsSeeder;
use Tests\Concerns\SeedsCorePermissions;
use Tests\TestCase;

class BulkSendHsmHardeningTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsCorePermissions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCorePermissions();
        $this->seed(HelpdeskContactsPermissionsSeeder::class);
    }

    private function agent(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage']);

        return $user;
    }

    public function test_banned_customers_are_excluded_and_batch_id_is_passed(): void
    {
        Bus::fake();

        $ok = Customer::factory()->create();
        $banned = Customer::factory()->create(['banned_at' => now()]);

        $this->actingAs($this->agent())
            ->postJson(route('contacts.bulk-send-hsm'), [
                'customer_ids' => [$ok->id, $banned->id],
                'template_name' => 'welcome',
            ])
            ->assertOk()
            ->assertJsonPath('count', 1);

        Bus::assertDispatched(SendBulkHsmTemplateJob::class, 1);
    }

    public function test_more_than_500_customer_ids_is_rejected(): void
    {
        $this->actingAs($this->agent())
            ->postJson(route('contacts.bulk-send-hsm'), [
                'customer_ids' => range(1, 501),
                'template_name' => 'welcome',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_ids']);
    }

    public function test_job_is_idempotent_per_batch_customer_and_template(): void
    {
        $customer = Customer::factory()->create();
        $batch = 'batch-'.uniqid();
        Cache::forget("hsm-bulk:{$batch}:{$customer->id}:welcome");

        $service = Mockery::mock(HsmConversationService::class);
        $service->shouldReceive('findOrCreateWhatsAppConversation')->once()->andReturn(new Conversation);
        $service->shouldReceive('sendToConversation')->once();

        (new SendBulkHsmTemplateJob([$customer->id], 'welcome', [], null, $batch))->handle($service);
        (new SendBulkHsmTemplateJob([$customer->id], 'welcome', [], null, $batch))->handle($service);
    }
}
