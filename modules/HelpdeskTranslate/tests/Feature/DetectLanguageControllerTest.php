<?php

namespace Modules\HelpdeskTranslate\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Setting;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DetectLanguageControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    private const ENDPOINT = '/panel/helpdesk/detect-language';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Permission::firstOrCreate(['name' => 'helpdesk-translate.use', 'guard_name' => 'web']);

        // Force LibreTranslate as the provider so detect calls go to a known URL.
        Setting::set('helpdesktranslate.provider', 'libretranslate', 'helpdesktranslate');
        Setting::set('helpdesktranslate.libretranslate.endpoint', 'http://fake-libretranslate.test/translate', 'helpdesktranslate');
    }

    public function test_unauthenticated_request_is_redirected(): void
    {
        $this->postJson(self::ENDPOINT, ['text' => 'Hello'])
            ->assertUnauthorized();
    }

    public function test_user_without_permission_receives_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, ['text' => 'Hello world'])
            ->assertForbidden();
    }

    public function test_missing_text_field_returns_422(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    }

    public function test_empty_text_returns_422(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, ['text' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    }

    public function test_text_exceeding_max_length_returns_422(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, ['text' => str_repeat('a', 2001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    }

    public function test_valid_request_returns_detected_language(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');

        Http::fake([
            'fake-libretranslate.test/detect' => Http::response(
                [['language' => 'en', 'confidence' => 0.98]],
                200
            ),
        ]);

        $response = $this->actingAs($user)
            ->postJson(self::ENDPOINT, ['text' => 'Hello, I need help with my order']);

        $response->assertOk()
            ->assertJsonStructure(['success', 'detected'])
            ->assertJsonPath('success', true);
    }

    public function test_response_structure_contains_success_and_detected_keys(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');

        Http::fake([
            'fake-libretranslate.test/detect' => Http::response(
                [['language' => 'fr', 'confidence' => 0.95]],
                200
            ),
        ]);

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, ['text' => 'Bonjour, comment allez-vous?'])
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
