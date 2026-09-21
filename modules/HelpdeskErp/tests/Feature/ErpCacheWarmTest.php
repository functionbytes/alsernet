<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Modules\HelpdeskErp\Jobs\WarmErpCacheJob;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for POST /api/helpdeskErp/cache/warm
 *
 * Requires permission: helpdeskerp.refresh
 */
class ErpCacheWarmTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('helpdeskerp.refresh');
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->postJson('/api/helpdeskErp/cache/warm', ['emails' => ['a@example.com']])
            ->assertUnauthorized();
    }

    public function test_user_without_permission_returns_403(): void
    {
        $noPermUser = User::factory()->create();

        $this->actingAs($noPermUser, 'sanctum')
            ->postJson('/api/helpdeskErp/cache/warm', ['emails' => ['a@example.com']])
            ->assertForbidden();
    }

    public function test_user_with_permission_dispatches_warm_job_for_valid_emails(): void
    {
        Queue::fake();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/helpdeskErp/cache/warm', [
                'emails' => ['valid@example.com', 'not-an-email', 'other@example.com'],
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'queued' => 2]);

        Queue::assertPushed(WarmErpCacheJob::class);
    }

    /**
     * Un lote de 7 emails se reparte en jobs de WarmErpCacheJob::EMAILS_PER_JOB
     * (3+3+1): un único job con todos revienta su timeout con el ERP caído.
     */
    public function test_a_large_email_list_is_split_into_small_jobs(): void
    {
        Queue::fake();

        $emails = array_map(fn (int $i): string => "user{$i}@example.com", range(1, 7));

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/helpdeskErp/cache/warm', ['emails' => $emails])
            ->assertOk()
            ->assertJson(['ok' => true, 'queued' => 7]);

        Queue::assertPushed(WarmErpCacheJob::class, 3);
    }

    public function test_user_with_permission_and_no_emails_returns_zero_queued_without_dispatch(): void
    {
        Queue::fake();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/helpdeskErp/cache/warm', ['emails' => []])
            ->assertOk()
            ->assertJson(['ok' => true, 'queued' => 0]);

        Queue::assertNotPushed(WarmErpCacheJob::class);
    }
}
