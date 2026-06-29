<?php

namespace Modules\Backup\Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Http\Middleware\Authenticate;
use Modules\Auth\Http\Middleware\CheckSession;
use Modules\Backup\Jobs\CreateBackupJob;
use Modules\Core\Http\Middleware\EnsureModuleIsActive;
use Modules\Core\Http\Middleware\SecurityHeaders;
use Modules\Core\Http\Middleware\VerifyCsrfToken;
use Modules\Page\Http\Middleware\PageCacheMiddleware;
use Modules\Template\Http\Middleware\RegisterTemplateViewPath;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Tests\TestCase;

class BackupControllerTest extends TestCase
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
    // GET /setting/backups (index)
    // =========================================================================

    public function test_index_returns_200(): void
    {
        $this->actingAs($this->user)
            ->get('/setting/backups')
            ->assertStatus(200);
    }

    // =========================================================================
    // GET /setting/backups/create
    // =========================================================================

    public function test_create_returns_200(): void
    {
        $this->actingAs($this->user)
            ->get('/setting/backups/create')
            ->assertStatus(200);
    }

    // =========================================================================
    // POST /setting/backups (store)
    // =========================================================================

    public function test_store_requires_backup_types(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/setting/backups', []);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_store_dispatches_job_with_valid_types(): void
    {
        Queue::fake();

        $this->actingAs($this->user)
            ->post('/setting/backups', [
                'backup_types' => ['config'],
            ]);

        Queue::assertPushed(CreateBackupJob::class);
    }

    // =========================================================================
    // GET /setting/backups/status
    // =========================================================================

    public function test_status_returns_json(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/setting/backups/status');

        $response->assertStatus(200)
            ->assertJsonStructure(['count']);
    }

    // =========================================================================
    // DELETE /setting/backups/{filename}
    // =========================================================================

    public function test_destroy_returns_404_for_missing_file(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/setting/backups/nonexistent.zip');

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }
}
