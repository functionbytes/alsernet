<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Jobs\SyncWhatsAppTemplatesJob;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * T0.16 regression: WhatsAppTemplatesController::sync() called the non-existent
 * Artisan command 'helpdesk:sync-wa-templates', causing a CommandNotFoundException.
 *
 * Fix: sync() now dispatches SyncWhatsAppTemplatesJob (ShouldQueue) and returns
 * a redirect with an informational flash message instead of a completion message.
 */
class WhatsAppTemplatesSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);
    }

    // ── index ─────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('settings.helpdesk.whatsapp-templates.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_view_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.whatsapp-templates.index'))
            ->assertForbidden();
    }

    public function test_manager_can_access_whatsapp_templates_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.whatsapp-templates.index'))
            ->assertOk();
    }

    // ── sync — happy path ──────────────────────────────────────────────────────

    public function test_manager_can_trigger_sync_and_job_is_dispatched(): void
    {
        Queue::fake();

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.whatsapp-templates.sync'))
            ->assertRedirect(route('settings.helpdesk.whatsapp-templates.index'))
            ->assertSessionHas('success');

        Queue::assertPushed(SyncWhatsAppTemplatesJob::class);
    }

    public function test_sync_dispatches_exactly_one_job(): void
    {
        Queue::fake();

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.whatsapp-templates.sync'));

        Queue::assertPushed(SyncWhatsAppTemplatesJob::class, 1);
    }

    // ── sync — authorization ──────────────────────────────────────────────────

    public function test_guest_cannot_trigger_sync(): void
    {
        Queue::fake();

        $this->post(route('settings.helpdesk.whatsapp-templates.sync'))
            ->assertRedirect(route('auth.login'));

        Queue::assertNotPushed(SyncWhatsAppTemplatesJob::class);
    }

    public function test_user_with_no_permissions_cannot_trigger_sync(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.whatsapp-templates.sync'))
            ->assertForbidden();

        Queue::assertNotPushed(SyncWhatsAppTemplatesJob::class);
    }
}
