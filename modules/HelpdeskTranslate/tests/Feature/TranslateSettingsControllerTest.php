<?php

namespace Modules\HelpdeskTranslate\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTranslate\Models\TranslateUsage;
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

    // ─── usage report ─────────────────────────────────────────────────────────

    public function test_usage_endpoint_without_view_permission_receives_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(self::INDEX_URL.'/usage')
            ->assertForbidden();
    }

    public function test_usage_endpoint_returns_zero_totals_when_no_usage_logged(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        config(['services.deepl.key' => '', 'helpdesktranslate.deepl.key' => '']);

        $this->actingAs($user)
            ->getJson(self::INDEX_URL.'/usage')
            ->assertOk()
            ->assertJsonPath('totals.characters', 0)
            ->assertJsonPath('totals.calls', 0)
            ->assertJsonPath('quota', null);
    }

    public function test_usage_endpoint_aggregates_characters_and_call_outcomes(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        config(['services.deepl.key' => '', 'helpdesktranslate.deepl.key' => '']);

        TranslateUsage::query()->create([
            'provider' => 'deepl', 'operation' => 'translate', 'feature' => 'auto_incoming',
            'characters' => 100, 'source_lang' => 'en', 'target_lang' => 'es', 'success' => true,
        ]);
        TranslateUsage::query()->create([
            'provider' => 'deepl', 'operation' => 'translate', 'feature' => 'manual',
            'characters' => 50, 'source_lang' => 'en', 'target_lang' => 'es', 'success' => false,
        ]);

        $this->actingAs($user)
            ->getJson(self::INDEX_URL.'/usage')
            ->assertOk()
            ->assertJsonPath('totals.characters', 150)
            ->assertJsonPath('totals.calls', 2)
            ->assertJsonPath('totals.success_calls', 1)
            ->assertJsonPath('totals.failed_calls', 1);
    }

    public function test_usage_endpoint_excludes_rows_outside_default_thirty_day_range(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        config(['services.deepl.key' => '', 'helpdesktranslate.deepl.key' => '']);

        $old = TranslateUsage::query()->create([
            'provider' => 'deepl', 'operation' => 'translate', 'feature' => 'manual', 'characters' => 999, 'success' => true,
        ]);
        $old->forceFill(['created_at' => now()->subDays(60)])->save();

        TranslateUsage::query()->create([
            'provider' => 'deepl', 'operation' => 'translate', 'feature' => 'manual', 'characters' => 10, 'success' => true,
        ]);

        $this->actingAs($user)
            ->getJson(self::INDEX_URL.'/usage')
            ->assertOk()
            ->assertJsonPath('totals.characters', 10);
    }

    public function test_usage_endpoint_filters_by_explicit_date_range(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        config(['services.deepl.key' => '', 'helpdesktranslate.deepl.key' => '']);

        $row = TranslateUsage::query()->create([
            'provider' => 'deepl', 'operation' => 'translate', 'feature' => 'manual', 'characters' => 500, 'success' => true,
        ]);
        $row->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->actingAs($user)
            ->getJson(self::INDEX_URL.'/usage?'.http_build_query([
                'from' => now()->subDays(15)->toDateString(),
                'to' => now()->subDays(5)->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonPath('totals.characters', 500);

        $this->actingAs($user)
            ->getJson(self::INDEX_URL.'/usage?'.http_build_query([
                'from' => now()->subDays(4)->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonPath('totals.characters', 0);
    }

    public function test_usage_endpoint_rejects_to_date_before_from_date(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        $this->actingAs($user)
            ->getJson(self::INDEX_URL.'/usage?'.http_build_query([
                'from' => now()->toDateString(),
                'to' => now()->subDay()->toDateString(),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    public function test_usage_endpoint_breaks_down_totals_by_feature(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        config(['services.deepl.key' => '', 'helpdesktranslate.deepl.key' => '']);

        TranslateUsage::query()->create([
            'provider' => 'deepl', 'operation' => 'translate', 'feature' => 'auto_incoming', 'characters' => 30, 'success' => true,
        ]);
        TranslateUsage::query()->create([
            'provider' => 'deepl', 'operation' => 'translate', 'feature' => 'auto_incoming', 'characters' => 20, 'success' => true,
        ]);
        TranslateUsage::query()->create([
            'provider' => 'deepl', 'operation' => 'translate', 'feature' => 'manual', 'characters' => 5, 'success' => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson(self::INDEX_URL.'/usage')
            ->assertOk();

        $byFeature = collect($response->json('by_feature'))->keyBy('feature');

        $this->assertSame(50, $byFeature['auto_incoming']['characters']);
        $this->assertSame(2, $byFeature['auto_incoming']['calls']);
        $this->assertSame(5, $byFeature['manual']['characters']);
    }

    public function test_usage_endpoint_includes_live_quota_when_deepl_key_configured(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        Setting::set('helpdesktranslate.deepl.key', 'valid-test-api-key', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.url', 'https://api-free.deepl.com', 'helpdesktranslate');

        Http::fake([
            'api-free.deepl.com/v2/usage' => Http::response([
                'character_count' => 4321,
                'character_limit' => 500000,
            ], 200),
        ]);

        $this->actingAs($user)
            ->getJson(self::INDEX_URL.'/usage')
            ->assertOk()
            ->assertJsonPath('quota.character_count', 4321)
            ->assertJsonPath('quota.character_limit', 500000);
    }

    // ── cost estimate ─────────────────────────────────────────────────────────

    public function test_estimated_cost_is_zero_when_no_deepl_usage_logged(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        $this->actingAs($user)
            ->getJson(self::INDEX_URL.'/usage')
            ->assertOk()
            ->assertJsonPath('totals.estimated_cost_eur', 0);
    }

    public function test_estimated_cost_counts_only_successful_deepl_calls(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        TranslateUsage::query()->create([
            'provider' => 'deepl', 'operation' => 'translate', 'feature' => 'manual',
            'characters' => 1_000_000, 'success' => true,
        ]);
        // LibreTranslate is self-hosted/free — must not be billed.
        TranslateUsage::query()->create([
            'provider' => 'libretranslate', 'operation' => 'translate', 'feature' => 'manual',
            'characters' => 1_000_000, 'success' => true,
        ]);
        // Failed call never reached DeepL's billing — must not be billed.
        TranslateUsage::query()->create([
            'provider' => 'deepl', 'operation' => 'translate', 'feature' => 'manual',
            'characters' => 1_000_000, 'success' => false,
        ]);

        $this->actingAs($user)
            ->getJson(self::INDEX_URL.'/usage')
            ->assertOk()
            ->assertJsonPath('totals.estimated_cost_eur', 4.99);
    }

    // ── daily breakdown ──────────────────────────────────────────────────────

    public function test_usage_includes_daily_breakdown_for_chart(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        $row = TranslateUsage::query()->create([
            'provider' => 'deepl', 'operation' => 'translate', 'feature' => 'manual',
            'characters' => 42, 'success' => true,
        ]);
        $row->forceFill(['created_at' => now()->subDays(3)->startOfDay()->addHours(9)])->save();

        $response = $this->actingAs($user)
            ->getJson(self::INDEX_URL.'/usage')
            ->assertOk();

        $daily = collect($response->json('daily'))->keyBy('date');

        $this->assertSame(42, $daily[now()->subDays(3)->toDateString()]['characters']);
    }

    // ── CSV export ────────────────────────────────────────────────────────────

    public function test_manager_can_export_usage_csv(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.view');

        $row = TranslateUsage::query()->create([
            'provider' => 'deepl', 'operation' => 'translate', 'feature' => 'manual',
            'characters' => 99, 'success' => true,
        ]);
        $row->forceFill(['created_at' => now()->subDays(1)])->save();

        $response = $this->actingAs($user)->get(self::INDEX_URL.'/usage/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Fecha,Caracteres,Llamadas,Exitosas,Fallidas', $content);
        $this->assertStringContainsString(now()->subDays(1)->toDateString(), $content);
    }

    public function test_user_without_view_permission_cannot_export_usage_csv(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(self::INDEX_URL.'/usage/export')
            ->assertForbidden();
    }
}
