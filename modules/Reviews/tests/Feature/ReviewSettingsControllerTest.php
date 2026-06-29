<?php

namespace Modules\Reviews\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Modules\Core\Models\Setting;
use Modules\Reviews\Tests\TestCase;

class ReviewSettingsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Seed default settings for testing
        $this->seedSettings();
    }

    protected function seedSettings(): void
    {
        $defaults = [
            'reviews.sync_interval_minutes' => '15',
            'reviews.auto_publish_replies' => 'disabled',
            'reviews.default_moderation_visible' => '1',
            'reviews.rate_limit_per_minute' => '60',
        ];

        foreach ($defaults as $key => $value) {
            Setting::set($key, $value);
        }
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'sync_interval_minutes' => 15,
            'auto_publish_replies' => 'disabled',
            'default_moderation_visible' => true,
            'rate_limit_per_minute' => 60,
        ], $overrides);
    }

    public function test_user_can_view_settings_page(): void
    {
        $user = $this->createUser(['reviews.settings.manage']);

        $response = $this->actingAs($user)
            ->get(route('settings.reviews.config.index'));

        $response->assertOk()
            ->assertViewIs('reviews::settings.config')
            ->assertViewHas('settings');
    }

    public function test_unauthorized_user_cannot_view_settings(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->get(route('settings.reviews.config.index'));

        $response->assertForbidden();
    }

    public function test_user_can_update_sync_interval(): void
    {
        $user = $this->createUser(['reviews.settings.manage']);

        $response = $this->actingAs($user)
            ->put(route('settings.reviews.config.update'), $this->validPayload([
                'sync_interval_minutes' => 30,
            ]));

        $response->assertRedirect(route('settings.reviews.config.index'))
            ->assertSessionHas('success');

        $this->assertSame('30', setting('reviews.sync_interval_minutes'));
    }

    public function test_user_can_update_auto_publish_setting(): void
    {
        $user = $this->createUser(['reviews.settings.manage']);

        $response = $this->actingAs($user)
            ->put(route('settings.reviews.config.update'), $this->validPayload([
                'auto_publish_replies' => 'positive',
            ]));

        $response->assertRedirect(route('settings.reviews.config.index'))
            ->assertSessionHas('success');

        $this->assertSame('positive', setting('reviews.auto_publish_replies'));
    }

    public function test_user_can_update_default_moderation_visibility(): void
    {
        $user = $this->createUser(['reviews.settings.manage']);

        $response = $this->actingAs($user)
            ->put(route('settings.reviews.config.update'), $this->validPayload([
                'default_moderation_visible' => false,
            ]));

        $response->assertRedirect(route('settings.reviews.config.index'))
            ->assertSessionHas('success');

        $this->assertSame('0', setting('reviews.default_moderation_visible'));
    }

    public function test_user_can_update_rate_limit(): void
    {
        $user = $this->createUser(['reviews.settings.manage']);

        $response = $this->actingAs($user)
            ->put(route('settings.reviews.config.update'), $this->validPayload([
                'rate_limit_per_minute' => 50,
            ]));

        $response->assertRedirect(route('settings.reviews.config.index'))
            ->assertSessionHas('success');

        $this->assertSame('50', setting('reviews.rate_limit_per_minute'));
    }

    public function test_validation_fails_for_invalid_sync_interval(): void
    {
        $user = $this->createUser(['reviews.settings.manage']);

        // Test below minimum
        $this->actingAs($user)
            ->putJson(route('settings.reviews.config.update'), $this->validPayload([
                'sync_interval_minutes' => 4,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sync_interval_minutes']);

        // Test above maximum
        $this->actingAs($user)
            ->putJson(route('settings.reviews.config.update'), $this->validPayload([
                'sync_interval_minutes' => 1441,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sync_interval_minutes']);
    }

    public function test_validation_fails_for_invalid_rate_limit(): void
    {
        $user = $this->createUser(['reviews.settings.manage']);

        // Test below minimum
        $this->actingAs($user)
            ->putJson(route('settings.reviews.config.update'), $this->validPayload([
                'rate_limit_per_minute' => 9,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rate_limit_per_minute']);

        // Test above maximum
        $this->actingAs($user)
            ->putJson(route('settings.reviews.config.update'), $this->validPayload([
                'rate_limit_per_minute' => 101,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rate_limit_per_minute']);
    }

    public function test_validation_fails_for_invalid_auto_publish_value(): void
    {
        $user = $this->createUser(['reviews.settings.manage']);

        $this->actingAs($user)
            ->putJson(route('settings.reviews.config.update'), $this->validPayload([
                'auto_publish_replies' => 'invalid',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['auto_publish_replies']);
    }

    public function test_settings_are_persisted_after_update(): void
    {
        $user = $this->createUser(['reviews.settings.manage']);

        $this->actingAs($user)
            ->put(route('settings.reviews.config.update'), $this->validPayload([
                'sync_interval_minutes' => 45,
                'auto_publish_replies' => 'all',
                'default_moderation_visible' => false,
                'rate_limit_per_minute' => 80,
            ]));

        $this->assertSame('45', setting('reviews.sync_interval_minutes'));
        $this->assertSame('all', setting('reviews.auto_publish_replies'));
        $this->assertSame('0', setting('reviews.default_moderation_visible'));
        $this->assertSame('80', setting('reviews.rate_limit_per_minute'));
    }

    public function test_activity_log_records_settings_update(): void
    {
        $user = $this->createUser(['reviews.settings.manage']);

        $this->actingAs($user)
            ->put(route('settings.reviews.config.update'), $this->validPayload());

        $this->assertDatabaseHas('activity_log', [
            'causer_id' => $user->id,
            'description' => 'Review settings updated',
        ]);
    }

    public function test_missing_required_fields_fails_validation(): void
    {
        $user = $this->createUser(['reviews.settings.manage']);

        $response = $this->actingAs($user)
            ->putJson(route('settings.reviews.config.update'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'sync_interval_minutes',
                'auto_publish_replies',
                'default_moderation_visible',
                'rate_limit_per_minute',
            ]);
    }

    public function test_google_credentials_update_triggers_config_clear(): void
    {
        $user = $this->createUser(['reviews.settings.manage']);

        Artisan::spy();

        $this->actingAs($user)
            ->put(route('settings.reviews.config.update'), array_merge($this->validPayload(), [
                'google_client_id' => 'new-client-id',
                'google_client_secret' => 'new-client-secret',
            ]));

        Artisan::shouldHaveReceived('call')
            ->with('config:clear')
            ->twice();
    }

    public function test_all_valid_auto_publish_options_are_accepted(): void
    {
        $user = $this->createUser(['reviews.settings.manage']);

        foreach (['disabled', 'positive', 'negative', 'all'] as $option) {
            $this->actingAs($user)
                ->put(route('settings.reviews.config.update'), $this->validPayload([
                    'auto_publish_replies' => $option,
                ]))
                ->assertRedirect(route('settings.reviews.config.index'));

            $this->assertSame($option, setting('reviews.auto_publish_replies'));
        }
    }
}
