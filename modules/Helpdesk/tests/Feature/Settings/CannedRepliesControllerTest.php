<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\CannedReply;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * T0.7 regression: helpdesk_canned_replies.content was NOT NULL with no default.
 *
 * Bug fixed:
 *  - CannedReply::create() failed in strict mode because the legacy "content"
 *    column (NOT NULL, no default) was never supplied by the application.
 *  - Migration 2026_06_13_000001 makes "content" nullable so inserts succeed.
 *
 * Without the migration, test_manager_can_create_canned_reply_with_body() and
 * test_manager_can_create_canned_reply_without_optional_fields() would both
 * fail with an integrity constraint violation on the "content" column.
 */
class CannedRepliesControllerTest extends TestCase
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
        $this->get(route('settings.helpdesk.canned-replies.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.canned-replies.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_canned_replies_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.canned-replies.index'))
            ->assertOk();
    }

    // ── store (T0.7 core regression) ─────────────────────────────────────────

    public function test_manager_can_create_canned_reply_with_body(): void
    {
        // Before the fix, this INSERT would fail with an integrity constraint
        // violation because the legacy "content" column was NOT NULL with no
        // default and the application never supplies it.
        $payload = [
            'title' => 'Bienvenida al soporte',
            'body' => 'Hola, gracias por contactarnos. ¿En qué podemos ayudarte?',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.canned-replies.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.canned-replies.index'));

        $this->assertDatabaseHas('helpdesk_canned_replies', [
            'title' => 'Bienvenida al soporte',
            'body' => 'Hola, gracias por contactarnos. ¿En qué podemos ayudarte?',
        ], 'helpdesk');
    }

    public function test_manager_can_create_canned_reply_without_optional_fields(): void
    {
        // Validates that only required fields (title + body) are sufficient.
        $payload = [
            'title' => 'Respuesta minima',
            'body' => 'Este es el cuerpo minimo requerido.',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.canned-replies.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.canned-replies.index'));

        $reply = CannedReply::query()->where('title', 'Respuesta minima')->first();

        $this->assertNotNull($reply);
        $this->assertNull($reply->shortcut);
        $this->assertNull($reply->category);
        $this->assertFalse($reply->is_global);
    }

    public function test_manager_can_create_global_canned_reply_with_all_fields(): void
    {
        $payload = [
            'title' => 'Cierre estandar',
            'body' => 'Gracias por contactarnos. Si necesitas más ayuda, no dudes en escribir.',
            'shortcut' => 'cierr',
            'category' => 'closing',
            'is_global' => '1',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.canned-replies.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.canned-replies.index'));

        $reply = CannedReply::query()->where('shortcut', 'cierr')->first();

        $this->assertNotNull($reply);
        $this->assertTrue($reply->is_global);
        $this->assertSame('closing', $reply->category);
    }

    // ── store validation (422) ────────────────────────────────────────────────

    public function test_store_fails_without_title(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.canned-replies.store'), [
                'body' => 'Cuerpo sin titulo',
            ])
            ->assertSessionHasErrors(['title']);
    }

    public function test_store_fails_without_body(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.canned-replies.store'), [
                'title' => 'Titulo sin cuerpo',
            ])
            ->assertSessionHasErrors(['body']);
    }

    public function test_store_fails_with_duplicate_shortcut(): void
    {
        CannedReply::factory()->create([
            'shortcut' => 'dup',
            'user_id' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.canned-replies.store'), [
                'title' => 'Otro titulo',
                'body' => 'Otro cuerpo',
                'shortcut' => 'dup',
            ])
            ->assertSessionHasErrors(['shortcut']);
    }

    // ── store authorization (403) ─────────────────────────────────────────────

    public function test_store_is_forbidden_for_user_without_create_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.canned-replies.store'), [
                'title' => 'Sin permiso',
                'body' => 'Cuerpo de prueba',
            ])
            ->assertForbidden();
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function test_manager_can_update_canned_reply(): void
    {
        $reply = CannedReply::factory()->create([
            'user_id' => $this->manager->id,
            'title' => 'Titulo original',
            'body' => 'Cuerpo original',
            'category' => 'general',
        ]);

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.canned-replies.update', $reply), [
                'title' => 'Titulo actualizado',
                'body' => 'Cuerpo actualizado con nueva información.',
                'category' => 'technical',
            ])
            ->assertRedirect(route('settings.helpdesk.canned-replies.index'));

        $reply->refresh();

        $this->assertSame('Titulo actualizado', $reply->title);
        $this->assertSame('Cuerpo actualizado con nueva información.', $reply->body);
        $this->assertSame('technical', $reply->category);
    }

    public function test_update_fails_for_user_without_permission(): void
    {
        $reply = CannedReply::factory()->create([
            'user_id' => $this->manager->id,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('settings.helpdesk.canned-replies.update', $reply), [
                'title' => 'Cambio no autorizado',
                'body' => 'Cuerpo no autorizado',
            ])
            ->assertForbidden();
    }

    public function test_update_fails_without_required_fields(): void
    {
        $reply = CannedReply::factory()->create([
            'user_id' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.canned-replies.update', $reply), [])
            ->assertSessionHasErrors(['title', 'body']);
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_manager_can_soft_delete_canned_reply(): void
    {
        $reply = CannedReply::factory()->create([
            'user_id' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.canned-replies.destroy', $reply))
            ->assertRedirect(route('settings.helpdesk.canned-replies.index'));

        $this->assertSoftDeleted('helpdesk_canned_replies', ['id' => $reply->id], 'helpdesk');
    }

    public function test_destroy_is_forbidden_for_user_without_permission(): void
    {
        $reply = CannedReply::factory()->create([
            'user_id' => $this->manager->id,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.helpdesk.canned-replies.destroy', $reply))
            ->assertForbidden();
    }
}
