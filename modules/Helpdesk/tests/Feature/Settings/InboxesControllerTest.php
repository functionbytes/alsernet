<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Inbox;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * C5-inboxes regression tests.
 *
 * Bugs fixed:
 *  T0.10: $inboxCounts must be passed by index(), channels() / channelList() / test()
 *         methods must exist on InboxesController.
 *  T0.14: Credential secrets must NOT appear in plain text inside any HTML
 *         value= attribute on the edit form (credentials are Crypt-encrypted in
 *         the DB; edit form uses disabled placeholder + "Cambiar" button pattern).
 */
class InboxesControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $manager;

    private User $plainUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);

        $this->plainUser = User::factory()->create();
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function makeInbox(array $attrs = []): Inbox
    {
        return Inbox::create(array_merge([
            'name' => 'Test Inbox',
            'channel_type' => Inbox::CHANNEL_WHATSAPP,
            'is_active' => true,
        ], $attrs));
    }

    // ── index (T0.10: $inboxCounts) ───────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('settings.helpdesk.inboxes.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $this->actingAs($this->plainUser)
            ->get(route('settings.helpdesk.inboxes.index'))
            ->assertForbidden();
    }

    public function test_index_returns_200_with_channel_counts(): void
    {
        // Create two inboxes in different channels to exercise $inboxCounts groupBy
        $this->makeInbox(['channel_type' => Inbox::CHANNEL_WHATSAPP, 'name' => 'WA inbox 1']);
        $this->makeInbox(['channel_type' => Inbox::CHANNEL_EMAIL,    'name' => 'Email inbox 1']);

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.inboxes.index'))
            ->assertOk();

        // The view consumes $inboxCounts — if the variable were missing the view
        // would throw an "Undefined variable $inboxCounts" error (500).
        $response->assertViewHas('inboxCounts');
        $response->assertViewHas('channelLabels');
        $response->assertViewHas('channelIcons');
    }

    public function test_index_counts_are_keyed_by_channel_type(): void
    {
        $this->makeInbox(['channel_type' => Inbox::CHANNEL_WHATSAPP, 'name' => 'WA A', 'is_active' => true]);
        $this->makeInbox(['channel_type' => Inbox::CHANNEL_WHATSAPP, 'name' => 'WA B', 'is_active' => false]);

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.inboxes.index'))
            ->assertOk();

        $counts = $response->viewData('inboxCounts');

        // At least one WhatsApp entry must appear in the counts
        $this->assertArrayHasKey(Inbox::CHANNEL_WHATSAPP, $counts->toArray());
        $waCounts = $counts->get(Inbox::CHANNEL_WHATSAPP);
        $this->assertGreaterThanOrEqual(2, (int) $waCounts->total);
    }

    // ── channels() (T0.10: method must exist) ─────────────────────────────────

    public function test_channels_page_returns_200(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.inboxes.channels'))
            ->assertOk();
    }

    // ── channelList() (T0.10: method must exist) ──────────────────────────────

    public function test_channel_list_returns_200_for_valid_type(): void
    {
        $this->makeInbox(['channel_type' => Inbox::CHANNEL_EMAIL, 'name' => 'Email Inbox']);

        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.inboxes.channel', Inbox::CHANNEL_EMAIL))
            ->assertOk();
    }

    public function test_channel_list_returns_404_for_invalid_type(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.inboxes.channel', 'invalid-channel'))
            ->assertNotFound();
    }

    // ── create ────────────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_access_create(): void
    {
        $this->actingAs($this->plainUser)
            ->get(route('settings.helpdesk.inboxes.create'))
            ->assertForbidden();
    }

    public function test_manager_can_view_create_form(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.inboxes.create'))
            ->assertOk()
            ->assertViewIs('helpdesk::settings.inboxes.form');
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function test_store_is_forbidden_for_user_without_permission(): void
    {
        $this->actingAs($this->plainUser)
            ->post(route('settings.helpdesk.inboxes.store'), [
                'name' => 'Inbox sin permiso',
                'channel_type' => Inbox::CHANNEL_EMAIL,
            ])
            ->assertForbidden();
    }

    public function test_store_fails_validation_without_name(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.inboxes.store'), [
                'channel_type' => Inbox::CHANNEL_EMAIL,
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_manager_can_create_inbox(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.inboxes.store'), [
                'name' => 'New Email Inbox',
                'channel_type' => Inbox::CHANNEL_EMAIL,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_inboxes', [
            'name' => 'New Email Inbox',
            'channel_type' => Inbox::CHANNEL_EMAIL,
        ], 'helpdesk');
    }

    // ── edit (T0.14: credentials must NOT appear in plain-text value=) ────────

    public function test_manager_can_access_edit_form(): void
    {
        $inbox = $this->makeInbox(['name' => 'Edit Test Inbox']);

        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.inboxes.edit', $inbox))
            ->assertOk()
            ->assertViewIs('helpdesk::settings.inboxes.form');
    }

    public function test_edit_form_does_not_expose_access_token_in_value_attribute(): void
    {
        $secret = 'EAAP_super_secret_access_token_XYZ987';

        $inbox = $this->makeInbox([
            'name' => 'WA Inbox With Secret',
            'channel_type' => Inbox::CHANNEL_WHATSAPP,
            'credentials' => [
                'access_token' => $secret,
                'phone_number_id' => '1234567890',
                'business_account_id' => '9876543210',
                'verify_token' => 'my-verify-token',
            ],
        ]);

        // Confirm the secret is actually stored encrypted (not in plain text) in the DB.
        $raw = DB::connection('helpdesk')
            ->table('helpdesk_inboxes')
            ->where('id', $inbox->id)
            ->value('credentials');

        $this->assertStringNotContainsString($secret, (string) $raw, 'Secret should be encrypted in the database, not stored in plain text.');

        // Confirm the secret does NOT appear anywhere in the HTML response.
        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.inboxes.edit', $inbox));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringNotContainsString(
            $secret,
            $html,
            'The raw access_token secret must not appear anywhere in the edit form HTML (T0.14 regression).'
        );
    }

    public function test_edit_form_does_not_expose_app_secret_in_value_attribute(): void
    {
        $appSecret = 'fb_app_secret_PLAINTEXT_SHOULD_NOT_APPEAR';

        $inbox = $this->makeInbox([
            'name' => 'WA App Secret Test',
            'channel_type' => Inbox::CHANNEL_WHATSAPP,
            'credentials' => [
                'access_token' => 'token_placeholder',
                'app_secret' => $appSecret,
                'phone_number_id' => '111222333',
                'business_account_id' => '444555666',
                'verify_token' => 'verify-abc',
            ],
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.inboxes.edit', $inbox));

        $response->assertOk();

        $this->assertStringNotContainsString(
            $appSecret,
            $response->getContent(),
            'The raw app_secret must not appear in the edit form HTML (T0.14 regression).'
        );
    }

    public function test_edit_form_does_not_expose_facebook_page_access_token(): void
    {
        $pageToken = 'EAAGm_facebook_page_access_token_PLAINTEXT';

        $inbox = $this->makeInbox([
            'name' => 'FB Inbox Token Test',
            'channel_type' => Inbox::CHANNEL_FACEBOOK,
            'credentials' => [
                'page_id' => '1234567',
                'page_access_token' => $pageToken,
                'verify_token' => 'verify-fb',
            ],
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.inboxes.edit', $inbox));

        $response->assertOk();

        $this->assertStringNotContainsString(
            $pageToken,
            $response->getContent(),
            'The raw page_access_token must not appear in the edit form HTML (T0.14 regression).'
        );
    }

    // ── 403 authorization checks ──────────────────────────────────────────────

    public function test_edit_is_forbidden_for_user_without_permission(): void
    {
        $inbox = $this->makeInbox();

        $this->actingAs($this->plainUser)
            ->get(route('settings.helpdesk.inboxes.edit', $inbox))
            ->assertForbidden();
    }

    public function test_update_is_forbidden_for_user_without_permission(): void
    {
        $inbox = $this->makeInbox();

        $this->actingAs($this->plainUser)
            ->put(route('settings.helpdesk.inboxes.update', $inbox), [
                'name' => 'Updated name',
                'channel_type' => Inbox::CHANNEL_WHATSAPP,
            ])
            ->assertForbidden();
    }

    public function test_destroy_is_forbidden_for_user_without_permission(): void
    {
        $inbox = $this->makeInbox();

        $this->actingAs($this->plainUser)
            ->delete(route('settings.helpdesk.inboxes.destroy', $inbox))
            ->assertForbidden();
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function test_manager_can_update_inbox_name(): void
    {
        $inbox = $this->makeInbox(['name' => 'Original name', 'channel_type' => Inbox::CHANNEL_EMAIL]);

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.inboxes.update', $inbox), [
                'name' => 'Updated name',
                'channel_type' => Inbox::CHANNEL_EMAIL,
            ])
            ->assertRedirect(route('settings.helpdesk.inboxes.edit', $inbox));

        $this->assertDatabaseHas('helpdesk_inboxes', [
            'id' => $inbox->id,
            'name' => 'Updated name',
        ], 'helpdesk');
    }

    // ── test() connection method (T0.10: method must exist) ───────────────────

    public function test_connection_test_endpoint_returns_json_for_whatsapp_inbox(): void
    {
        $inbox = $this->makeInbox([
            'name' => 'WA Test Connection',
            'channel_type' => Inbox::CHANNEL_WHATSAPP,
        ]);

        // No real credentials — expect ok:false (missing creds), but the
        // endpoint must exist and return JSON (not a 404 or 500).
        $this->actingAs($this->manager)
            ->postJson(route('settings.helpdesk.inboxes.test', $inbox))
            ->assertOk()
            ->assertJsonStructure(['ok', 'message']);
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_manager_can_soft_delete_inbox(): void
    {
        $inbox = $this->makeInbox(['name' => 'Delete Me']);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.inboxes.destroy', $inbox))
            ->assertRedirect(route('settings.helpdesk.inboxes.index'));

        $this->assertSoftDeleted('helpdesk_inboxes', ['id' => $inbox->id], 'helpdesk');
    }
}
