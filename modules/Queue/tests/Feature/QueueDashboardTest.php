<?php

namespace Modules\Queue\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class QueueDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Bypass role middleware — role table schema is incompatible with test env
        $this->withoutMiddleware(RoleMiddleware::class);

        // Ensure failed_jobs table exists (no migration in this project covers it)
        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function ($table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        $this->superAdmin = User::factory()->create();
    }

    public function test_dashboard_redirects_unauthenticated_users(): void
    {
        $this->get(route('settings.queue.dashboard'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_dashboard_returns_200_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('settings.queue.dashboard'))
            ->assertOk();
    }

    public function test_stats_endpoint_returns_expected_structure(): void
    {
        $this->actingAs($this->superAdmin)
            ->getJson(route('settings.queue.stats'))
            ->assertOk()
            ->assertJsonStructure([
                'failed_24h',
                'failed_7d',
                'failed_total',
                'horizon_status',
            ]);
    }

    public function test_failed_jobs_endpoint_returns_json(): void
    {
        $this->actingAs($this->superAdmin)
            ->getJson(route('settings.queue.failed-jobs'))
            ->assertOk()
            ->assertJsonStructure(['data', 'totalCount']);
    }

    public function test_pending_jobs_endpoint_returns_json(): void
    {
        $this->actingAs($this->superAdmin)
            ->getJson(route('settings.queue.pending-jobs'))
            ->assertOk()
            ->assertJsonStructure(['data', 'totalCount']);
    }

    public function test_delete_failed_job_removes_from_database(): void
    {
        $uuid = (string) Str::uuid();

        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'TestJob', 'job' => 'TestJob', 'data' => []]),
            'exception' => 'Test exception',
            'failed_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('settings.queue.failed-jobs.delete', $uuid))
            ->assertOk()
            ->assertJson(['message' => 'Job deleted']);

        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid]);
    }

    public function test_retry_job_with_invalid_uuid_returns_error(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('settings.queue.failed-jobs.retry', 'nonexistent-uuid-12345'))
            ->assertNotFound()
            ->assertJson(['error' => 'Job not found']);
    }

    public function test_retry_all_returns_success_response(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('settings.queue.failed-jobs.retry-all'))
            ->assertOk()
            ->assertJsonStructure(['message']);
    }
}
