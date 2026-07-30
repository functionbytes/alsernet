<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Crypt;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Webhook;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * C6-webhooks regression tests.
 *
 * Bugs covered:
 *  T0.2  — index(Request $request) was missing `use Illuminate\Http\Request`, causing 500.
 *           Without the fix, test_manager_can_view_webhooks_index() fails with 500.
 *
 *  T0.14 — secret was echoed in value="" on the edit form, exposing the decrypted key.
 *           test_edit_form_does_not_expose_secret_in_html() asserts the decrypted secret
 *           is NOT present in the HTML response.
 *           test_store_encrypts_secret_in_database() asserts the stored secret is a
 *           Crypt ciphertext, not the raw value.
 *
 *  T0.15 — route-level gate (role:super-admin|super-settings) and controller-level gate
 *           (can:helpdesk.webhooks.*) must both be satisfied. A user with only the role
 *           but without the explicit permissions would be forbidden at the controller.
 *           test_user_with_role_but_without_permission_is_forbidden() documents this gap.
 */
class WebhooksControllerTest extends TestCase
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

    // ── index (T0.2: was 500 before `use Request` was added) ─────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('settings.helpdesk.webhooks.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_role_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.webhooks.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_webhooks_index(): void
    {
        // T0.2: Before the fix, Request was not imported and PHP threw a fatal error
        // (Class 'Request' not found), resulting in a 500 on this endpoint.
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.webhooks.index'))
            ->assertOk();
    }

    public function test_index_with_search_filter_returns_200(): void
    {
        Webhook::create([
            'name' => 'Slack webhook',
            'url' => 'https://hooks.slack.com/test',
            'integration_type' => 'slack',
            'events' => ['conversation.created'],
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.webhooks.index', ['search' => 'Slack']))
            ->assertOk()
            ->assertSee('Slack webhook');
    }

    // ── store (T0.14: secret must be encrypted, never raw in DB) ─────────────

    public function test_store_encrypts_secret_in_database(): void
    {
        // T0.14: The raw secret must never be stored in plaintext in the DB.
        // The Webhook model uses a Crypt accessor/mutator, so the DB column
        // must contain the encrypted ciphertext, not the literal value.
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.webhooks.store'), [
                'name' => 'Test webhook secreto',
                'url' => 'https://example.com/hook',
                'events' => ['conversation.created'],
                'secret' => 'mi-secreto-hmac',
                'is_active' => '1',
            ])
            ->assertRedirect(route('settings.helpdesk.webhooks.index'));

        $webhook = Webhook::query()
            ->where('name', 'Test webhook secreto')
            ->first();

        $this->assertNotNull($webhook, 'El webhook debe haberse creado');

        // Fetch the raw encrypted value directly from the DB (bypassing the accessor).
        $rawSecret = $webhook->getRawOriginal('secret');

        // Raw value must NOT be the plain-text secret.
        $this->assertNotSame(
            'mi-secreto-hmac',
            $rawSecret,
            'El secreto no debe almacenarse en texto plano'
        );

        // The raw value must be decryptable back to the original secret.
        $this->assertSame(
            'mi-secreto-hmac',
            Crypt::decryptString($rawSecret),
            'El secreto debe ser descifrable con Crypt'
        );
    }

    public function test_store_without_secret_creates_webhook_successfully(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.webhooks.store'), [
                'name' => 'Webhook sin secreto',
                'url' => 'https://example.com/no-secret',
                'events' => ['conversation.resolved'],
                'is_active' => '1',
            ])
            ->assertRedirect(route('settings.helpdesk.webhooks.index'));

        $this->assertDatabaseHas('helpdesk_webhooks', [
            'name' => 'Webhook sin secreto',
            'secret' => null,
        ], 'helpdesk');
    }

    public function test_store_fails_without_required_fields(): void
    {
        // T0.15 coverage: the 422 must come from the Form Request (StoreWebhookRequest),
        // which means the can:helpdesk.webhooks.create gate must pass first.
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.webhooks.store'), [])
            ->assertSessionHasErrors(['name', 'url', 'events']);
    }

    public function test_store_fails_with_invalid_event(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.webhooks.store'), [
                'name' => 'Webhook invalido',
                'url' => 'https://example.com/hook',
                'events' => ['evento.inexistente'],
            ])
            ->assertSessionHasErrors(['events.*']);
    }

    public function test_store_is_forbidden_for_user_without_create_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.webhooks.store'), [
                'name' => 'Sin permiso',
                'url' => 'https://example.com/hook',
                'events' => ['conversation.created'],
            ])
            ->assertForbidden();
    }

    // ── edit (T0.14: secret must not appear in the HTML response) ────────────

    public function test_edit_form_does_not_expose_secret_in_html(): void
    {
        // T0.14: The previous version used value="{{ old('secret', $webhook->secret) }}"
        // which echoed the decrypted secret into the HTML.
        // The fix is value="{{ old('secret') }}" — the secret is never echoed on edit.
        $webhook = Webhook::create([
            'name' => 'Webhook con secreto',
            'url' => 'https://example.com/secret-hook',
            'integration_type' => 'generic',
            'events' => ['conversation.created'],
            'secret' => 'super-secreto-privado',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.webhooks.edit', $webhook));

        $response->assertOk();

        // The decrypted secret must NOT appear anywhere in the HTML.
        $response->assertDontSee('super-secreto-privado');
    }

    public function test_edit_form_shows_masked_placeholder_when_secret_exists(): void
    {
        $webhook = Webhook::create([
            'name' => 'Webhook secreto visible',
            'url' => 'https://example.com/hook',
            'integration_type' => 'generic',
            'events' => ['conversation.closed'],
            'secret' => 'valor-cifrado-real',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.webhooks.edit', $webhook))
            ->assertOk()
            // The masked placeholder bullets must appear in the form.
            ->assertSee('••••••••••••');
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function test_manager_can_update_webhook(): void
    {
        $webhook = Webhook::create([
            'name' => 'Webhook original',
            'url' => 'https://example.com/original',
            'integration_type' => 'generic',
            'events' => ['conversation.created'],
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.webhooks.update', $webhook), [
                'name' => 'Webhook actualizado',
                'url' => 'https://example.com/actualizado',
                'events' => ['conversation.resolved'],
                'is_active' => '1',
            ])
            ->assertRedirect(route('settings.helpdesk.webhooks.index'));

        $webhook->refresh();

        $this->assertSame('Webhook actualizado', $webhook->name);
        $this->assertSame('https://example.com/actualizado', $webhook->url);
    }

    public function test_update_preserves_secret_when_blank_submitted(): void
    {
        // When the secret field is left blank on update, the existing secret must
        // be preserved (not wiped). The controller strips blank secret from $validated.
        $webhook = Webhook::create([
            'name' => 'Webhook con secreto original',
            'url' => 'https://example.com/preserve',
            'integration_type' => 'generic',
            'events' => ['conversation.created'],
            'secret' => 'secreto-original',
            'is_active' => true,
        ]);

        $rawBefore = $webhook->getRawOriginal('secret');

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.webhooks.update', $webhook), [
                'name' => 'Webhook con secreto original',
                'url' => 'https://example.com/preserve',
                'events' => ['conversation.created'],
                'secret' => '',  // blank = preserve existing
                'is_active' => '1',
            ])
            ->assertRedirect(route('settings.helpdesk.webhooks.index'));

        $webhook->refresh();
        $rawAfter = $webhook->getRawOriginal('secret');

        $this->assertSame($rawBefore, $rawAfter, 'El secreto debe preservarse cuando el campo queda vacío');
    }

    public function test_update_is_forbidden_for_user_without_permission(): void
    {
        $webhook = Webhook::create([
            'name' => 'Webhook protegido',
            'url' => 'https://example.com/protected',
            'integration_type' => 'generic',
            'events' => ['conversation.created'],
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('settings.helpdesk.webhooks.update', $webhook), [
                'name' => 'Hack',
                'url' => 'https://evil.com',
                'events' => ['conversation.created'],
            ])
            ->assertForbidden();
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_manager_can_delete_webhook(): void
    {
        $webhook = Webhook::create([
            'name' => 'Webhook eliminable',
            'url' => 'https://example.com/delete-me',
            'integration_type' => 'generic',
            'events' => ['conversation.created'],
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.webhooks.destroy', $webhook))
            ->assertRedirect(route('settings.helpdesk.webhooks.index'));

        $this->assertSoftDeleted('helpdesk_webhooks', ['id' => $webhook->id], 'helpdesk');
    }

    public function test_destroy_is_forbidden_for_user_without_permission(): void
    {
        $webhook = Webhook::create([
            'name' => 'Webhook no borrable',
            'url' => 'https://example.com/no-delete',
            'integration_type' => 'generic',
            'events' => ['conversation.created'],
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.helpdesk.webhooks.destroy', $webhook))
            ->assertForbidden();
    }

    // ── T0.15: role-only user without explicit permissions ────────────────────

    public function test_user_with_role_but_without_permission_is_forbidden_on_create(): void
    {
        // T0.15: route middleware passes (role:super-settings) but controller middleware
        // requires can:helpdesk.webhooks.create which is NOT assigned to this role.
        $roleOnly = Role::firstOrCreate(['name' => 'super-settings-no-perms', 'guard_name' => 'web']);
        // Intentionally: do NOT give webhook permissions to this role.

        $user = User::factory()->create();
        $user->assignRole($roleOnly);

        $this->actingAs($user)
            ->post(route('settings.helpdesk.webhooks.store'), [
                'name' => 'Sin permiso especifico',
                'url' => 'https://example.com/hook',
                'events' => ['conversation.created'],
            ])
            ->assertForbidden();
    }
}
