<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
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

    // ── create/store — creación remota vía Graph API ────────────────────────────

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('settings.helpdesk.whatsapp-templates.create'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_view_create_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.whatsapp-templates.create'))
            ->assertForbidden();
    }

    public function test_manager_can_view_create_form(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.whatsapp-templates.create'))
            ->assertOk();
    }

    public function test_manager_can_create_template_and_it_is_sent_to_meta(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'id' => '123456789',
            'status' => 'PENDING',
            'category' => 'UTILITY',
        ], 200)]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.whatsapp-templates.store'), [
                'name' => 'bienvenida_cliente_v2',
                'language' => 'es',
                'category' => 'utility',
                'body' => 'Hola {{1}}, tu numero de caso es {{2}}.',
                'body_examples' => 'Ana, CASE-42',
            ])
            ->assertRedirect(route('settings.helpdesk.whatsapp-templates.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_whatsapp_templates', [
            'external_id' => 'bienvenida_cliente_v2',
            'language' => 'es',
            'category' => 'utility',
            'status' => 'pending',
        ], 'helpdesk');

        Http::assertSent(fn ($request) => $request['name'] === 'bienvenida_cliente_v2'
            && $request['category'] === 'UTILITY'
            && $request['components'][0]['example']['body_text'][0] === ['Ana', 'CASE-42']);
    }

    public function test_manager_can_create_template_without_variables(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '1', 'status' => 'PENDING'], 200)]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.whatsapp-templates.store'), [
                'name' => 'saludo_simple',
                'language' => 'es',
                'category' => 'utility',
                'body' => 'Hola, gracias por contactarnos.',
            ])
            ->assertRedirect(route('settings.helpdesk.whatsapp-templates.index'));

        $this->assertDatabaseHas('helpdesk_whatsapp_templates', [
            'external_id' => 'saludo_simple',
        ], 'helpdesk');
    }

    public function test_store_validation_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.whatsapp-templates.store'), [])
            ->assertSessionHasErrors(['name', 'language', 'category', 'body']);
    }

    public function test_store_validation_rejects_name_with_uppercase_or_spaces(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.whatsapp-templates.store'), [
                'name' => 'Bienvenida Cliente',
                'language' => 'es',
                'category' => 'utility',
                'body' => 'Hola.',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_store_validation_requires_body_examples_matching_variable_count(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.whatsapp-templates.store'), [
                'name' => 'con_variables',
                'language' => 'es',
                'category' => 'utility',
                'body' => 'Hola {{1}}, caso {{2}}.',
                'body_examples' => 'SoloUnEjemplo',
            ])
            ->assertSessionHasErrors(['body_examples']);
    }

    public function test_store_shows_meta_error_and_does_not_persist_locally(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['message' => 'Invalid parameter', 'code' => 100],
        ], 400)]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.whatsapp-templates.store'), [
                'name' => 'plantilla_rechazada',
                'language' => 'es',
                'category' => 'utility',
                'body' => 'Hola.',
            ])
            ->assertRedirect(route('settings.helpdesk.whatsapp-templates.create'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('helpdesk_whatsapp_templates', [
            'external_id' => 'plantilla_rechazada',
        ], 'helpdesk');
    }

    public function test_guest_cannot_create_template(): void
    {
        $this->post(route('settings.helpdesk.whatsapp-templates.store'), [
            'name' => 'x', 'language' => 'es', 'category' => 'utility', 'body' => 'Hola.',
        ])->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_create_template(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.whatsapp-templates.store'), [
                'name' => 'x', 'language' => 'es', 'category' => 'utility', 'body' => 'Hola.',
            ])
            ->assertForbidden();
    }
}
