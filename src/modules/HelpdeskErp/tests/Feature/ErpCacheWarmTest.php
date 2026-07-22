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
