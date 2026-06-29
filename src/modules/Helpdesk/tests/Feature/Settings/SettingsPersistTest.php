<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Setting;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * T0.1 regression: Setting::allAsArray returns prefixed keys (e.g. "email.smtp_host")
 * which never override DEFAULTS flat keys ("smtp_host") in array_merge.
 *
 * Fix: allAsFlatArray() strips the group prefix before merging.
 */
class SettingsPersistTest extends TestCase
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

    // ── Setting model ──────────────────────────────────────────────────────────

    public function test_all_as_flat_array_strips_group_prefix(): void
    {
        Setting::set('email.smtp_host', 'smtp.example.com', 'email');
        Setting::set('email.smtp_port', '587', 'email');

        $flat = Setting::allAsFlatArray('email');

        $this->assertArrayHasKey('smtp_host', $flat);
        $this->assertArrayHasKey('smtp_port', $flat);
        $this->assertArrayNotHasKey('email.smtp_host', $flat);
        $this->assertSame('smtp.example.com', $flat['smtp_host']);
    }

    public function test_all_as_flat_array_handles_bare_key_without_prefix(): void
    {
        // Keys stored without the group prefix still come back correctly
        Setting::query()->where('group', 'email')->delete();

        // Manually insert a bare key (edge case)
        Setting::create(['key' => 'smtp_host', 'value' => 'bare.example.com', 'group' => 'email']);

        $flat = Setting::allAsFlatArray('email');

        $this->assertArrayHasKey('smtp_host', $flat);
        $this->assertSame('bare.example.com', $flat['smtp_host']);
    }

    // ── EmailSettingsController ───────────────────────────────────────────────

    public function test_email_settings_index_reflects_saved_values(): void
    {
        Setting::set('email.email_from_name', 'Acme Support', 'email');
        Setting::set('email.smtp_host', 'mail.acme.com', 'email');
        Setting::set('email.smtp_port', '465', 'email');

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.email'));

        $response->assertOk();

        // The view receives $backups; confirm saved values — not defaults — are present
        $response->assertViewHas('backups', function (array $backups) {
            return $backups['email_from_name'] === 'Acme Support'
                && $backups['smtp_host'] === 'mail.acme.com'
                && $backups['smtp_port'] === '465';
        });
    }

    public function test_email_settings_index_falls_back_to_defaults_when_db_empty(): void
    {
        Setting::query()->where('group', 'email')->delete();

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.email'));

        $response->assertOk();
        $response->assertViewHas('backups', function (array $backups) {
            return $backups['smtp_port'] === 587
                && $backups['smtp_encryption'] === 'tls'
                && $backups['imap_folder'] === 'INBOX';
        });
    }

    public function test_email_settings_index_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.email'))
            ->assertForbidden();
    }

    // ── NotificationSettingsController ────────────────────────────────────────

    public function test_notification_settings_index_reflects_saved_values(): void
    {
        Setting::set('notifications.daily_digest_time', '14:30', 'notifications');
        Setting::set('notifications.daily_digest_enabled', '1', 'notifications');

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.notifications'));

        $response->assertOk();
        $response->assertViewHas('settings', function (array $settings) {
            return $settings['daily_digest_time'] === '14:30'
                && $settings['daily_digest_enabled'] === '1';
        });
    }

    public function test_notification_settings_index_falls_back_to_defaults(): void
    {
        Setting::query()->where('group', 'notifications')->delete();

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.notifications'));

        $response->assertOk();
        $response->assertViewHas('settings', function (array $settings) {
            return $settings['daily_digest_time'] === '08:00'
                && $settings['notify_new_conversation'] === true;
        });
    }

    public function test_notification_settings_index_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.notifications'))
            ->assertForbidden();
    }

    // ── SettingsController (uploading) ────────────────────────────────────────

    public function test_uploading_settings_index_reflects_saved_values(): void
    {
        Setting::set('uploading.max_file_size_mb', '50', 'uploading');
        Setting::set('uploading.image_quality', '70', 'uploading');

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.uploading'));

        $response->assertOk();
        $response->assertViewHas('settings', function (array $settings) {
            return $settings['max_file_size_mb'] === '50'
                && $settings['image_quality'] === '70';
        });
    }

    public function test_uploading_settings_index_falls_back_to_defaults(): void
    {
        Setting::query()->where('group', 'uploading')->delete();

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.uploading'));

        $response->assertOk();
        $response->assertViewHas('settings', function (array $settings) {
            return $settings['max_file_size_mb'] === 25
                && $settings['image_quality'] === 85;
        });
    }

    public function test_uploading_settings_index_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.uploading'))
            ->assertForbidden();
    }

    // ── T0.2: imap_open tolerance (already present, regression guard) ─────────

    public function test_test_imap_returns_false_when_imap_extension_unavailable(): void
    {
        if (function_exists('imap_open')) {
            $this->markTestSkipped('imap_open is available on this server; skip unavailability test.');
        }

        $response = $this->actingAs($this->manager)
            ->postJson(route('settings.helpdesk.email.test-imap'), [
                'host' => 'imap.example.com',
                'port' => 993,
                'username' => 'user@example.com',
                'password' => 'secret',
                'encryption' => 'ssl',
                'folder' => 'INBOX',
            ]);

        $response->assertOk();
        $response->assertJson(['status' => false]);
    }
}
