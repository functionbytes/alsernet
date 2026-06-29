<?php

namespace Modules\Backup\Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Http\Middleware\Authenticate;
use Modules\Auth\Http\Middleware\CheckSession;
use Modules\Backup\Models\BackupSchedule;
use Modules\Core\Http\Middleware\EnsureModuleIsActive;
use Modules\Core\Http\Middleware\SecurityHeaders;
use Modules\Core\Http\Middleware\VerifyCsrfToken;
use Modules\Page\Http\Middleware\PageCacheMiddleware;
use Modules\Template\Http\Middleware\RegisterTemplateViewPath;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Tests\TestCase;

class BackupScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            Authorize::class,
            PermissionMiddleware::class,
            Authenticate::class,
            CheckSession::class,
            EnsureModuleIsActive::class,
            RegisterTemplateViewPath::class,
            PageCacheMiddleware::class,
            SecurityHeaders::class,
        ]);

        $this->user = User::factory()->create();
    }

    // =========================================================================
    // GET /setting/backups/schedules (index)
    // =========================================================================

    public function test_index_returns_200(): void
    {
        $this->actingAs($this->user)
            ->get('/setting/backups/schedules')
            ->assertStatus(200);
    }

    // =========================================================================
    // GET /setting/backups/schedules/create
    // =========================================================================

    public function test_create_returns_200(): void
    {
        $this->actingAs($this->user)
            ->get('/setting/backups/schedules/create')
            ->assertStatus(200);
    }

    // =========================================================================
    // POST /setting/backups/schedules (store)
    // =========================================================================

    public function test_store_creates_schedule(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/setting/backups/schedules', [
                'name' => 'Daily Config Backup',
                'enabled' => '1',
                'frequency' => 'daily',
                'scheduled_time' => '10:00',
                'backup_types' => ['config'],
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('backup_schedules', [
            'name' => 'Daily Config Backup',
            'frequency' => 'daily',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/setting/backups/schedules', []);

        // Laravel returns 302 redirect back with errors, or 422 for JSON
        $this->assertTrue(
            in_array($response->getStatusCode(), [302, 422]),
            "Expected 302 or 422, got {$response->getStatusCode()}"
        );
    }

    // =========================================================================
    // GET /setting/backups/schedules/{schedule}/edit
    // =========================================================================

    public function test_edit_returns_200(): void
    {
        $schedule = BackupSchedule::create([
            'name' => 'Edit Test',
            'enabled' => true,
            'frequency' => 'daily',
            'scheduled_time' => '08:00:00',
            'backup_types' => ['config'],
        ]);

        $this->actingAs($this->user)
            ->get("/setting/backups/schedules/{$schedule->id}/edit")
            ->assertStatus(200);
    }

    // =========================================================================
    // PUT /setting/backups/schedules/{schedule} (update)
    // =========================================================================

    public function test_update_modifies_schedule(): void
    {
        $schedule = BackupSchedule::create([
            'name' => 'Original Name',
            'enabled' => true,
            'frequency' => 'daily',
            'scheduled_time' => '08:00:00',
            'backup_types' => ['config'],
        ]);

        $this->actingAs($this->user)
            ->put("/setting/backups/schedules/{$schedule->id}", [
                'name' => 'Updated Name',
                'enabled' => '1',
                'frequency' => 'daily',
                'scheduled_time' => '10:00',
                'backup_types' => ['config'],
            ])
            ->assertStatus(302);

        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
            'name' => 'Updated Name',
        ]);
    }

    // =========================================================================
    // DELETE /setting/backups/schedules/{schedule} (destroy)
    // =========================================================================

    public function test_destroy_soft_deletes_schedule(): void
    {
        $schedule = BackupSchedule::create([
            'name' => 'Delete Me',
            'enabled' => true,
            'frequency' => 'daily',
            'scheduled_time' => '08:00:00',
            'backup_types' => ['config'],
        ]);

        $this->actingAs($this->user)
            ->delete("/setting/backups/schedules/{$schedule->id}")
            ->assertStatus(302);

        $this->assertSoftDeleted('backup_schedules', ['id' => $schedule->id]);
    }

    // =========================================================================
    // POST /setting/backups/schedules/{schedule}/toggle
    // =========================================================================

    public function test_toggle_switches_enabled_state(): void
    {
        $schedule = BackupSchedule::create([
            'name' => 'Toggle Test',
            'enabled' => true,
            'frequency' => 'daily',
            'scheduled_time' => '08:00:00',
            'backup_types' => ['config'],
        ]);

        $this->actingAs($this->user)
            ->post("/setting/backups/schedules/{$schedule->id}/toggle");

        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
            'enabled' => false,
        ]);
    }
}
