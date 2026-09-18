<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskEmailActivity\Models\EmailLogView;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Vistas guardadas del log de emails — mismo alcance/patrón de tests que
 * Modules\HelpdeskTickets\Tests\Feature\Managers\TicketMailViewsControllerTest,
 * adaptado a que EmailLogView vive en la conexión por defecto (no 'helpdesk').
 */
class EmailLogViewsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('helpdeskemailactivity.view', 'web');
        Permission::findOrCreate('helpdeskemailactivity.manage', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->viewer = User::factory()->create();
        $this->viewer->givePermissionTo('helpdeskemailactivity.view');
    }

    public function test_index_requires_view_permission(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson(route('helpdeskemailactivity.views.index'))
            ->assertForbidden();
    }

    public function test_store_creates_a_view_owned_by_the_current_user(): void
    {
        $response = $this->actingAs($this->viewer)
            ->postJson(route('helpdeskemailactivity.views.store'), [
                'name' => 'Rebotes de hoy',
                'filters' => ['status' => 'bounced', 'module' => 'HelpdeskTickets'],
            ])
            ->assertCreated();

        $response->assertJsonPath('view.name', 'Rebotes de hoy');

        $this->assertDatabaseHas('email_log_views', [
            'name' => 'Rebotes de hoy',
            'user_id' => $this->viewer->id,
        ], 'mysql');
    }

    public function test_user_with_manage_permission_can_create_a_public_view(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(['helpdeskemailactivity.view', 'helpdeskemailactivity.manage']);

        $response = $this->actingAs($manager)
            ->postJson(route('helpdeskemailactivity.views.store'), [
                'name' => 'Vista del equipo',
                'filters' => ['status' => 'bounced'],
                'is_public' => true,
            ])
            ->assertCreated();

        $response->assertJsonPath('view.is_public', true);

        $this->assertDatabaseHas('email_log_views', [
            'name' => 'Vista del equipo',
            'user_id' => $manager->id,
            'is_public' => true,
        ], 'mysql');
    }

    public function test_viewer_without_manage_permission_cannot_create_a_public_view(): void
    {
        $response = $this->actingAs($this->viewer)
            ->postJson(route('helpdeskemailactivity.views.store'), [
                'name' => 'Intento de vista pública',
                'filters' => ['status' => 'bounced'],
                'is_public' => true,
            ])
            ->assertCreated();

        $response->assertJsonPath('view.is_public', false);

        $this->assertDatabaseHas('email_log_views', [
            'name' => 'Intento de vista pública',
            'user_id' => $this->viewer->id,
            'is_public' => false,
        ], 'mysql');
    }

    public function test_index_lists_only_own_and_public_views(): void
    {
        $other = User::factory()->create();

        EmailLogView::create(['name' => 'Mía', 'filters' => [], 'user_id' => $this->viewer->id]);
        EmailLogView::create(['name' => 'De otro', 'filters' => [], 'user_id' => $other->id]);
        EmailLogView::create(['name' => 'Pública', 'filters' => [], 'user_id' => $other->id, 'is_public' => true]);

        $response = $this->actingAs($this->viewer)
            ->getJson(route('helpdeskemailactivity.views.index'))
            ->assertOk();

        $names = collect($response->json('views'))->pluck('name');

        $this->assertTrue($names->contains('Mía'));
        $this->assertTrue($names->contains('Pública'));
        $this->assertFalse($names->contains('De otro'));
    }

    public function test_owner_can_delete_their_own_view(): void
    {
        $view = EmailLogView::create(['name' => 'A borrar', 'filters' => [], 'user_id' => $this->viewer->id]);

        $this->actingAs($this->viewer)
            ->deleteJson(route('helpdeskemailactivity.views.destroy', $view))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('email_log_views', ['id' => $view->id], 'mysql');
    }

    public function test_cannot_delete_someone_elses_view(): void
    {
        $other = User::factory()->create();
        $view = EmailLogView::create(['name' => 'De otro', 'filters' => [], 'user_id' => $other->id]);

        $this->actingAs($this->viewer)
            ->deleteJson(route('helpdeskemailactivity.views.destroy', $view))
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('email_log_views', ['id' => $view->id], 'mysql');
    }

    public function test_cannot_delete_a_system_view(): void
    {
        $view = EmailLogView::create(['name' => 'Sistema', 'filters' => [], 'user_id' => $this->viewer->id, 'is_system' => true]);

        $this->actingAs($this->viewer)
            ->deleteJson(route('helpdeskemailactivity.views.destroy', $view))
            ->assertStatus(403);

        $this->assertDatabaseHas('email_log_views', ['id' => $view->id], 'mysql');
    }
}
