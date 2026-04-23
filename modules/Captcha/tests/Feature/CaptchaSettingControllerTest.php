<?php

declare(strict_types=1);

namespace Modules\Captcha\Tests\Feature;

use Modules\Captcha\Tests\TestCase;
use Modules\Core\Models\Setting;

class CaptchaSettingControllerTest extends TestCase
{
    public function test_guest_cannot_access_settings_page(): void
    {
        $this->get(route('captcha.settings'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_permission_cannot_access_settings_page(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('captcha.settings'))
            ->assertForbidden();
    }

    public function test_admin_can_access_settings_page(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('captcha.settings'))
            ->assertOk()
            ->assertViewIs('captcha::settings.edit');
    }

    public function test_settings_update_is_encrypted_and_persisted(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->put(route('captcha.settings.update'), [
                'enable_captcha' => '1',
                'captcha_type' => 'v3',
                'captcha_site_key' => 'test-site-key',
                'captcha_secret' => 'test-secret-key',
                'captcha_score' => '0.7',
                'enable_math_captcha' => '0',
                'captcha_hide_badge' => '0',
                'captcha_show_disclaimer' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('settings', [
            'key' => 'enable_captcha',
            'value' => '1',
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'captcha_site_key',
            'value' => 'test-site-key',
        ]);

        $storedSecret = Setting::where('key', 'captcha_secret')->value('value');
        $this->assertStringStartsWith('encrypted:', $storedSecret);
        $this->assertEquals('test-secret-key', decrypt(substr($storedSecret, 10)));
    }

    public function test_empty_secret_does_not_overwrite_existing_value(): void
    {
        $admin = $this->createAdmin();

        Setting::set('captcha_secret', 'encrypted:'.encrypt('existing-secret'));

        $this->actingAs($admin)
            ->put(route('captcha.settings.update'), [
                'enable_captcha' => '1',
                'captcha_type' => 'v2',
                'captcha_site_key' => 'site-key',
                'captcha_secret' => '',
                'captcha_score' => '0.5',
                'enable_math_captcha' => '0',
                'captcha_hide_badge' => '0',
                'captcha_show_disclaimer' => '0',
            ])
            ->assertRedirect();

        $storedSecret = Setting::where('key', 'captcha_secret')->value('value');
        $this->assertEquals('existing-secret', decrypt(substr($storedSecret, 10)));
    }

    public function test_validation_rejects_invalid_captcha_type(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->put(route('captcha.settings.update'), [
                'enable_captcha' => '1',
                'captcha_type' => 'invalid',
                'captcha_site_key' => 'site-key',
                'captcha_secret' => 'secret-key',
                'captcha_score' => '0.5',
                'enable_math_captcha' => '0',
                'captcha_hide_badge' => '0',
                'captcha_show_disclaimer' => '0',
            ])
            ->assertSessionHasErrors('captcha_type');
    }

    public function test_validation_rejects_invalid_score_for_v3(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->put(route('captcha.settings.update'), [
                'enable_captcha' => '1',
                'captcha_type' => 'v3',
                'captcha_site_key' => 'site-key',
                'captcha_secret' => 'secret-key',
                'captcha_score' => '1.5',
                'enable_math_captcha' => '0',
                'captcha_hide_badge' => '0',
                'captcha_show_disclaimer' => '0',
            ])
            ->assertSessionHasErrors('captcha_score');
    }

    public function test_validation_rejects_invalid_site_key_format(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->put(route('captcha.settings.update'), [
                'enable_captcha' => '1',
                'captcha_type' => 'v2',
                'captcha_site_key' => 'not-a-valid-key-123',
                'captcha_secret' => 'secret-key-that-is-long-enough-to-pass',
                'captcha_score' => '0.5',
                'enable_math_captcha' => '0',
                'captcha_hide_badge' => '0',
                'captcha_show_disclaimer' => '0',
            ])
            ->assertSessionHasErrors('captcha_site_key');
    }

    public function test_validation_rejects_invalid_secret_key_format(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->put(route('captcha.settings.update'), [
                'enable_captcha' => '1',
                'captcha_type' => 'v2',
                'captcha_site_key' => '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
                'captcha_secret' => 'short',
                'captcha_score' => '0.5',
                'enable_math_captcha' => '0',
                'captcha_hide_badge' => '0',
                'captcha_show_disclaimer' => '0',
            ])
            ->assertSessionHasErrors('captcha_secret');
    }

    public function test_validation_accepts_valid_recaptcha_keys(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->put(route('captcha.settings.update'), [
                'enable_captcha' => '1',
                'captcha_type' => 'v2',
                'captcha_site_key' => '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
                'captcha_secret' => '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe',
                'captcha_score' => '0.5',
                'enable_math_captcha' => '0',
                'captcha_hide_badge' => '0',
                'captcha_show_disclaimer' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }
}
