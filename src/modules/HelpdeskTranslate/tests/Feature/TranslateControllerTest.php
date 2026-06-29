<?php

namespace Modules\HelpdeskTranslate\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Setting;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TranslateControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    private const ENDPOINT = '/panel/helpdesk/translate';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Permission::firstOrCreate(['name' => 'helpdesk-translate.use', 'guard_name' => 'web']);
    }

    public function test_unauthenticated_request_is_unauthorized(): void
    {
        $this->postJson(self::ENDPOINT, ['text' => 'Hello', 'to' => 'es'])
            ->assertUnauthorized();
    }

    public function test_user_without_permission_receives_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, ['text' => 'Hello', 'to' => 'es'])
            ->assertForbidden();
    }

    public function test_missing_text_returns_422(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, ['to' => 'es'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    }

    public function test_missing_target_language_returns_422(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, ['text' => 'Hello world'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    public function test_invalid_target_language_returns_422(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, ['text' => 'Hello', 'to' => 'not_a_lang_code!'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    public function test_deepl_provider_without_api_key_returns_503(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');

        // Force DeepL provider with no key and no LibreTranslate endpoint so
        // both primary and fallback providers fail → CachedTranslator returns null.
        Setting::set('helpdesktranslate.provider', 'deepl', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.key', '', 'helpdesktranslate');
        Setting::set('helpdesktranslate.libretranslate.endpoint', '', 'helpdesktranslate');

        // No HTTP stubs needed — the service skips HTTP when there's no key/endpoint.
        Http::fake([]);

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, ['text' => 'Hello world', 'to' => 'es'])
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }

    public function test_libretranslate_provider_returns_translated_text_and_provider(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');

        Setting::set('helpdesktranslate.provider', 'libretranslate', 'helpdesktranslate');
        Setting::set('helpdesktranslate.libretranslate.endpoint', 'http://fake-libretranslate.test/translate', 'helpdesktranslate');
        // Ensure no DeepL key so the fallback also won't hit a stray request.
        Setting::set('helpdesktranslate.deepl.key', '', 'helpdesktranslate');

        Http::fake([
            'fake-libretranslate.test/translate' => Http::response([
                'translatedText' => 'Hola mundo',
                'detectedLanguage' => ['language' => 'en', 'confidence' => 0.98],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->postJson(self::ENDPOINT, ['text' => 'Hello world', 'to' => 'es', 'from' => 'en']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'translated', 'provider']);

        // The response must NOT include a top-level `detected` key (removed per spec).
        $this->assertArrayNotHasKey('detected', $response->json());
    }

    public function test_libretranslate_provider_response_contains_correct_provider_name(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');

        Setting::set('helpdesktranslate.provider', 'libretranslate', 'helpdesktranslate');
        Setting::set('helpdesktranslate.libretranslate.endpoint', 'http://fake-libretranslate.test/translate', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.key', '', 'helpdesktranslate');

        Http::fake([
            'fake-libretranslate.test/translate' => Http::response([
                'translatedText' => 'Bonjour monde',
                'detectedLanguage' => ['language' => 'en', 'confidence' => 0.9],
            ], 200),
        ]);

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, ['text' => 'Hello world', 'to' => 'fr'])
            ->assertOk()
            ->assertJsonPath('provider', 'libretranslate');
    }
}
