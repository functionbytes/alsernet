<?php

namespace Modules\HelpdeskTranslate\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Setting;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TranslateSettingsControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    private const INDEX_URL = '/panel/settings/helpdesk-translate';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Permission::firstOrCreate(['name' => 'helpdesk-translate.settings.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk-translate.settings.update', 'guard_name' => 'web']);
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_unauthenticated_user_is_redirected_from_settings(): void
    {
        $this->get(self::INDEX_URL)
            ->assertRedirect();
    }

    public function test_user_without_view_permission_receives_403_on_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(self::INDEX_URL)
            ->assertForbidden();
    }

    public function test_user_with_view_permission_can_access_settings(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        $this->actingAs($user)
            ->get(self::INDEX_URL)
            ->assertOk();
    }

    public function test_settings_view_does_not_expose_api_key_in_plain_text(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        // Store a recognisable key value in the DB; the view must not render it.
        Setting::set('helpdesktranslate.deepl.key', 'super-secret-deepl-key-12345', 'helpdesktranslate');

        $response = $this->actingAs($user)
            ->get(self::INDEX_URL);

        $response->assertOk();
        // The raw key must NOT appear in the rendered HTML.
        $response->assertDontSee('super-secret-deepl-key-12345', false);
    }

    public function test_settings_view_receives_has_deepl_key_variable_not_key_value(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        Setting::set('helpdesktranslate.deepl.key', 'some-key', 'helpdesktranslate');

        $response = $this->actingAs($user)
            ->get(self::INDEX_URL);

        $response->assertOk()
            ->assertViewHas('backups');

        // The `backups` array passed to the view must contain `has_deepl_key`
        // (a boolean flag) but must NOT contain the raw `deepl_key` string value.
        $backups = $response->viewData('backups');
        $this->assertArrayHasKey('has_deepl_key', $backups, 'View data must include has_deepl_key flag');
        $this->assertArrayNotHasKey('deepl_key', $backups, 'View data must not expose the raw deepl_key');
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_user_without_update_permission_receives_403_on_put(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(self::INDEX_URL, [
                'provider' => 'deepl',
                'default_target' => 'es',
            ])
            ->assertForbidden();
    }

    public function test_user_with_view_but_without_update_permission_receives_403_on_put(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        $this->actingAs($user)
            ->put(self::INDEX_URL, [
                'provider' => 'deepl',
                'default_target' => 'es',
            ])
            ->assertForbidden();
    }

    public function test_valid_update_persists_provider_setting(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.update');

        $this->actingAs($user)
            ->put(self::INDEX_URL, [
                'provider' => 'libretranslate',
                'default_target' => 'en',
                'auto_translate_incoming' => false,
                'auto_translate_outgoing' => false,
            ])
            ->assertRedirect();

        $this->assertSame(
            'libretranslate',
            Setting::get('helpdesktranslate.provider'),
            'Provider setting must be persisted after a valid update'
        );
    }

    public function test_valid_update_sets_session_success_flash(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.update');

        $this->actingAs($user)
            ->put(self::INDEX_URL, [
                'provider' => 'deepl',
                'default_target' => 'es',
            ])
            ->assertSessionHas('success');
    }

    public function test_update_with_invalid_provider_returns_422(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.update');

        $this->actingAs($user)
            ->putJson(self::INDEX_URL, [
                'provider' => 'google-translate',
                'default_target' => 'es',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);
    }

    /**
     * Sin este guard, un titular de settings.update podia apuntar el
     * endpoint de LibreTranslate a una IP interna o al endpoint de metadata
     * de la nube — se dispara en cada mensaje entrante/saliente via el
     * listener de traduccion automatica.
     */
    public function test_update_rejects_libretranslate_endpoint_pointing_to_internal_ip(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.update');

        $this->actingAs($user)
            ->putJson(self::INDEX_URL, [
                'provider' => 'libretranslate',
                'default_target' => 'en',
                'libretranslate_endpoint' => 'http://127.0.0.1:8000/translate',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['libretranslate_endpoint']);
    }

    public function test_update_rejects_libretranslate_endpoint_pointing_to_cloud_metadata(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.update');

        $this->actingAs($user)
            ->putJson(self::INDEX_URL, [
                'provider' => 'libretranslate',
                'default_target' => 'en',
                'libretranslate_endpoint' => 'http://169.254.169.254/latest/meta-data/',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['libretranslate_endpoint']);
    }

    public function test_update_accepts_public_libretranslate_endpoint(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.update');

        $this->actingAs($user)
            ->put(self::INDEX_URL, [
                'provider' => 'libretranslate',
                'default_target' => 'en',
                'libretranslate_endpoint' => 'https://libretranslate.com/translate',
            ])
            ->assertRedirect();

        $this->assertSame('https://libretranslate.com/translate', Setting::get('helpdesktranslate.libretranslate.endpoint'));
    }

    // ─── test connection endpoint ─────────────────────────────────────────────

    public function test_test_endpoint_without_update_permission_receives_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(self::INDEX_URL.'/test')
            ->assertForbidden();
    }

    public function test_test_endpoint_without_api_key_returns_422(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.update');

        // Ensure there is no key anywhere.
        Setting::set('helpdesktranslate.deepl.key', '', 'helpdesktranslate');

        // Also clear any config value to prevent env bleeds in test env.
        config(['helpdesktranslate.deepl.key' => '', 'services.deepl.key' => '']);

        Http::fake([]);

        $this->actingAs($user)
            ->postJson(self::INDEX_URL.'/test')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_test_endpoint_with_valid_key_returns_ok_when_deepl_responds(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.update');

        Setting::set('helpdesktranslate.deepl.key', 'valid-test-api-key', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.url', 'https://api-free.deepl.com', 'helpdesktranslate');

        Http::fake([
            'api-free.deepl.com/v2/usage' => Http::response([
                'character_count' => 1234,
                'character_limit' => 500000,
            ], 200),
        ]);

        $this->actingAs($user)
            ->postJson(self::INDEX_URL.'/test')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'usage']);
    }
}
