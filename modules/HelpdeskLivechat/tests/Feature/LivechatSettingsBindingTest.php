<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * T0.13 — Verifies that the LivechatSettingsController passes $backups (not $settings)
 * so that saved toggles reappear checked and the website_token is exposed in the
 * install snippet.
 */
class LivechatSettingsBindingTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Runs inside the DatabaseTransactions wrapper → rolled back after each test.
        \DB::connection('helpdesk')->table('helpdesk_channel_webs')->delete();

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole($role);
    }

    // -----------------------------------------------------------------------
    // Happy path — view renders with saved values
    // -----------------------------------------------------------------------

    public function test_settings_index_renders_saved_enable_file_upload_value(): void
    {
        WebFactory::new()->create(['enable_file_upload' => false]);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.helpdesk-livechat.index'));

        $response->assertOk();
        // The view must receive $backups with the saved value so the checkbox
        // renders unchecked when enable_file_upload is false.
        $response->assertViewHas('backups', function (array $backups) {
            return $backups['enable_file_upload'] === false;
        });
    }

    public function test_settings_index_renders_saved_enable_emoji_value(): void
    {
        WebFactory::new()->create(['enable_emoji' => false]);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.helpdesk-livechat.index'));

        $response->assertOk();
        $response->assertViewHas('backups', function (array $backups) {
            return $backups['enable_emoji'] === false;
        });
    }

    public function test_settings_index_renders_saved_allowed_file_types(): void
    {
        WebFactory::new()->create(['allowed_file_types' => ['video', 'audio']]);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.helpdesk-livechat.index'));

        $response->assertOk();
        $response->assertViewHas('backups', function (array $backups) {
            return $backups['allowed_file_types'] === ['video', 'audio'];
        });
    }

    public function test_settings_index_exposes_website_token_in_backups(): void
    {
        $web = WebFactory::new()->create(['website_token' => 'abc123tokenXYZ']);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.helpdesk-livechat.index'));

        $response->assertOk();
        $response->assertViewHas('backups', function (array $backups) use ($web) {
            return $backups['website_token'] === $web->website_token;
        });
        // The install snippet must contain the real token
        $response->assertSee('abc123tokenXYZ');
    }

    public function test_settings_index_shows_placeholder_when_no_web_inbox_exists(): void
    {
        // No Web record exists
        $response = $this->actingAs($this->admin)
            ->get(route('settings.helpdesk-livechat.index'));

        $response->assertOk();
        $response->assertViewHas('backups', function (array $backups) {
            return $backups['website_token'] === null;
        });
        $response->assertSee('TU_WEBSITE_TOKEN');
    }

    // -----------------------------------------------------------------------
    // Authorization path
    // -----------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_view_settings(): void
    {
        $this->get(route('settings.helpdesk-livechat.index'))
            ->assertRedirect();
    }

    public function test_user_without_permission_cannot_view_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk-livechat.index'))
            ->assertForbidden();
    }
}
