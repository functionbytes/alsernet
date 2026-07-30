<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationView;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * T0.19 regression: form used is_shared (column is is_public); sort_by/sort_direction
 * were not validated nor persisted because the columns didn't exist and they were
 * missing from fillable.
 *
 * Bugs fixed:
 *  - ConversationView uses is_public (not is_shared). The form now posts is_public.
 *  - Migration 2026_06_13_000002 adds sort_by and sort_direction columns.
 *  - Both fields added to ConversationView::$fillable.
 *  - StoreConversationViewRequest and UpdateConversationViewRequest include
 *    sort_by and sort_direction validation rules.
 */
class ViewsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        // Ensure an open ConversationStatus exists so ViewsController::create/edit
        // can call ConversationStatus::active()->ordered()->get() without crashing.
        ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            [
                'name' => 'Open',
                'color' => '#13C672',
                'is_open' => true,
                'is_default' => true,
                'order' => 1,
            ]
        );

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);
    }

    // ── index ─────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('settings.helpdesk.views.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.views.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.views.index'))
            ->assertOk();
    }

    // ── store: is_public persists (T0.19 — was is_shared) ────────────────────

    public function test_store_public_view_persists_is_public_true(): void
    {
        $payload = [
            'name' => 'Vista compartida',
            'description' => 'Vista para todo el equipo',
            'is_public' => '1',
            'is_default' => '0',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.views.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.views.index'));

        $view = ConversationView::query()->where('name', 'Vista compartida')->first();

        $this->assertNotNull($view, 'La vista debe crearse en la BD');
        $this->assertTrue((bool) $view->is_public, 'is_public debe ser true cuando se envía is_public=1');
    }

    public function test_store_private_view_persists_is_public_false(): void
    {
        $payload = [
            'name' => 'Vista privada',
            'is_public' => '0',
            'is_default' => '0',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.views.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.views.index'));

        $view = ConversationView::query()->where('name', 'Vista privada')->first();

        $this->assertNotNull($view);
        $this->assertFalse((bool) $view->is_public, 'is_public debe ser false cuando se envía is_public=0');
    }

    // ── store: sort_by / sort_direction persist (T0.19) ──────────────────────

    public function test_store_persists_sort_by_and_sort_direction(): void
    {
        $payload = [
            'name' => 'Vista con ordenacion',
            'is_public' => '0',
            'is_default' => '0',
            'sort_by' => 'priority',
            'sort_direction' => 'asc',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.views.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.views.index'));

        $view = ConversationView::query()->where('name', 'Vista con ordenacion')->first();

        $this->assertNotNull($view);
        $this->assertSame('priority', $view->sort_by, 'sort_by debe persistirse en BD');
        $this->assertSame('asc', $view->sort_direction, 'sort_direction debe persistirse en BD');
    }

    public function test_store_accepts_null_sort_by(): void
    {
        $payload = [
            'name' => 'Vista sin ordenacion',
            'is_public' => '0',
            'is_default' => '0',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.views.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.views.index'));

        $view = ConversationView::query()->where('name', 'Vista sin ordenacion')->first();

        $this->assertNotNull($view);
        $this->assertNull($view->sort_by, 'sort_by debe ser null cuando no se envía');
    }

    // ── store: is_default persists ────────────────────────────────────────────

    public function test_store_marks_view_as_default(): void
    {
        $payload = [
            'name' => 'Vista predeterminada',
            'is_public' => '0',
            'is_default' => '1',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.views.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.views.index'));

        $view = ConversationView::query()->where('name', 'Vista predeterminada')->first();

        $this->assertNotNull($view);
        $this->assertTrue((bool) $view->is_default, 'is_default debe ser true cuando se envía is_default=1');
    }

    // ── store: 422 validation ─────────────────────────────────────────────────

    public function test_store_fails_without_name(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.views.store'), [
                'is_public' => '0',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_store_fails_with_invalid_sort_by(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.views.store'), [
                'name' => 'Vista inválida',
                'sort_by' => 'invalid_column',
            ])
            ->assertSessionHasErrors(['sort_by']);
    }

    public function test_store_fails_with_invalid_sort_direction(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.views.store'), [
                'name' => 'Vista inválida',
                'sort_direction' => 'sideways',
            ])
            ->assertSessionHasErrors(['sort_direction']);
    }

    // ── store: 403 ───────────────────────────────────────────────────────────

    public function test_store_forbidden_for_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.views.store'), [
                'name' => 'No autorizado',
            ])
            ->assertForbidden();
    }

    // ── update: sort_by / sort_direction persist ──────────────────────────────

    public function test_update_persists_sort_by_change(): void
    {
        $view = ConversationView::factory()
            ->state([
                'user_id' => $this->manager->id,
                'sort_by' => 'created_at',
                'sort_direction' => 'desc',
            ])
            ->create();

        $payload = [
            'name' => $view->name,
            'is_public' => '0',
            'is_default' => '0',
            'sort_by' => 'updated_at',
            'sort_direction' => 'asc',
        ];

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.views.update', $view), $payload)
            ->assertRedirect(route('settings.helpdesk.views.index'));

        $view->refresh();

        $this->assertSame('updated_at', $view->sort_by, 'sort_by debe actualizarse a updated_at');
        $this->assertSame('asc', $view->sort_direction, 'sort_direction debe actualizarse a asc');
    }

    // ── update: is_public persists ────────────────────────────────────────────

    public function test_update_changes_private_to_public(): void
    {
        $view = ConversationView::factory()
            ->state([
                'user_id' => $this->manager->id,
                'is_public' => false,
            ])
            ->create();

        $payload = [
            'name' => $view->name,
            'is_public' => '1',
            'is_default' => '0',
        ];

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.views.update', $view), $payload)
            ->assertRedirect(route('settings.helpdesk.views.index'));

        $this->assertTrue((bool) $view->fresh()->is_public, 'is_public debe cambiar a true');
    }

    // ── update: 403 ──────────────────────────────────────────────────────────

    public function test_update_forbidden_for_user_without_permission(): void
    {
        $view = ConversationView::factory()
            ->state(['user_id' => $this->manager->id])
            ->create();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('settings.helpdesk.views.update', $view), [
                'name' => 'No autorizado',
            ])
            ->assertForbidden();
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_manager_can_delete_own_view(): void
    {
        $view = ConversationView::factory()
            ->state([
                'user_id' => $this->manager->id,
                'is_system' => false,
            ])
            ->create();

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.views.destroy', $view))
            ->assertRedirect(route('settings.helpdesk.views.index'));

        $this->assertNull(ConversationView::find($view->id), 'La vista debe eliminarse de la BD');
    }

    public function test_cannot_delete_system_view(): void
    {
        $view = ConversationView::factory()
            ->state([
                'user_id' => null,
                'is_system' => true,
            ])
            ->create();

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.views.destroy', $view))
            ->assertRedirect();

        $this->assertNotNull(ConversationView::find($view->id), 'Las vistas de sistema no deben eliminarse');
    }

    public function test_destroy_forbidden_for_user_without_permission(): void
    {
        $view = ConversationView::factory()
            ->state([
                'user_id' => $this->manager->id,
                'is_system' => false,
            ])
            ->create();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.helpdesk.views.destroy', $view))
            ->assertForbidden();
    }
}
