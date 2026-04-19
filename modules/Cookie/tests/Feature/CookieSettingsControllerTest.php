<?php

namespace Modules\Cookie\Tests\Feature;

use Modules\Cookie\Models\CookieConsentLog;
use Modules\Cookie\Tests\TestCase;
use Modules\Core\Models\Setting;

class CookieSettingsControllerTest extends TestCase
{
    // ───── Helpers ────────────────────────────────────────────────────────────

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'enabled' => '1',
            'style' => 'full-width',
            'message' => 'Utilizamos cookies para mejorar tu experiencia.',
            'accept_btn_text' => 'Aceptar',
            'reject_btn_text' => 'Rechazar',
            'customize_btn_text' => 'Personalizar',
            'save_btn_text' => 'Guardar preferencias',
            'more_info_text' => 'Más información',
            'more_info_url' => 'https://ejemplo.com/cookies',
            'bg_color' => '#ffffff',
            'text_color' => '#212529',
            'btn_color' => '#90bb13',
            'max_width' => 1170,
            'position' => 'bottom',
            'google_analytics_enabled' => '1',
            'google_analytics_id' => 'G-ABCDE12345',
            'facebook_pixel_enabled' => '1',
            'facebook_pixel_id' => '123456789',
        ], $overrides);
    }

    // ───── Autenticación y autorización ──────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('settings.cookie.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_guest_cannot_update(): void
    {
        $this->patch(route('settings.cookie.update'), $this->validPayload())
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_view_settings(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('settings.cookie.index'))
            ->assertForbidden();
    }

    public function test_user_without_permission_cannot_update_settings(): void
    {
        $this->actingAs($this->createUser())
            ->patch(route('settings.cookie.update'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_user_with_view_permission_can_see_settings_page(): void
    {
        $user = $this->createUser(['cookie.settings.view']);

        $this->actingAs($user)
            ->get(route('settings.cookie.index'))
            ->assertOk()
            ->assertViewIs('cookie::settings.index');
    }

    // ───── Actualización exitosa ──────────────────────────────────────────────

    public function test_user_with_update_permission_can_save_settings(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)
            ->patch(route('settings.cookie.update'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_settings_are_persisted_to_database(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)->patch(route('settings.cookie.update'), $this->validPayload([
            'message' => 'Mensaje personalizado de cookies.',
            'bg_color' => '#123456',
            'btn_color' => '#90bb13',
        ]));

        $this->assertSame('Mensaje personalizado de cookies.', Setting::get('cookie.message'));
        $this->assertSame('#123456', Setting::get('cookie.bg_color'));
        $this->assertSame('#90bb13', Setting::get('cookie.btn_color'));
    }

    // ───── Checkboxes (estado no enviado = '0') ───────────────────────────────

    public function test_unchecked_enabled_saves_zero(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $payload = $this->validPayload();
        unset($payload['enabled']);

        $this->actingAs($user)->patch(route('settings.cookie.update'), $payload);

        $this->assertSame('0', Setting::get('cookie.enabled'));
    }

    public function test_unchecked_google_analytics_saves_zero(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $payload = $this->validPayload();
        unset($payload['google_analytics_enabled']);

        $this->actingAs($user)->patch(route('settings.cookie.update'), $payload);

        $this->assertSame('0', Setting::get('cookie.google_analytics_enabled'));
    }

    public function test_unchecked_facebook_pixel_saves_zero(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $payload = $this->validPayload();
        unset($payload['facebook_pixel_enabled']);

        $this->actingAs($user)->patch(route('settings.cookie.update'), $payload);

        $this->assertSame('0', Setting::get('cookie.facebook_pixel_enabled'));
    }

    // ───── Validación de colores hexadecimales ────────────────────────────────

    public function test_invalid_bg_color_is_rejected(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.cookie.update'), $this->validPayload(['bg_color' => 'rojo']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bg_color']);
    }

    public function test_invalid_text_color_is_rejected(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.cookie.update'), $this->validPayload(['text_color' => '#gg0000']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text_color']);
    }

    public function test_invalid_btn_color_is_rejected(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.cookie.update'), $this->validPayload(['btn_color' => '90bb13']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['btn_color']);
    }

    public function test_valid_hex_colors_are_accepted(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.cookie.update'), $this->validPayload([
                'bg_color' => '#AABBCC',
                'text_color' => '#000000',
                'btn_color' => '#90bb13',
            ]))
            ->assertRedirect();
    }

    // ───── Validación de IDs de tracking ─────────────────────────────────────

    public function test_invalid_google_analytics_id_is_rejected(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.cookie.update'), $this->validPayload([
                'google_analytics_id' => 'analytics-invalido',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['google_analytics_id']);
    }

    public function test_valid_google_analytics_id_formats_are_accepted(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        foreach (['G-ABCDE12345', 'UA-123456-1', 'AW-123456789'] as $id) {
            $this->actingAs($user)
                ->patchJson(route('settings.cookie.update'), $this->validPayload(['google_analytics_id' => $id]))
                ->assertRedirect();
        }
    }

    public function test_facebook_pixel_id_must_be_digits(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.cookie.update'), $this->validPayload([
                'facebook_pixel_id' => 'pixel-abc',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['facebook_pixel_id']);
    }

    // ───── Validación de límites ──────────────────────────────────────────────

    public function test_message_exceeding_1000_chars_is_rejected(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.cookie.update'), $this->validPayload([
                'message' => str_repeat('a', 1001),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);
    }

    public function test_max_width_below_200_is_rejected(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.cookie.update'), $this->validPayload(['max_width' => 100]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max_width']);
    }

    public function test_max_width_above_1920_is_rejected(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.cookie.update'), $this->validPayload(['max_width' => 2000]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max_width']);
    }

    public function test_invalid_position_is_rejected(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.cookie.update'), $this->validPayload(['position' => 'left']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['position']);
    }

    public function test_invalid_style_is_rejected(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.cookie.update'), $this->validPayload(['style' => 'popup']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['style']);
    }

    public function test_invalid_more_info_url_is_rejected(): void
    {
        $user = $this->createUser(['cookie.settings.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.cookie.update'), $this->validPayload(['more_info_url' => 'no-es-url']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['more_info_url']);
    }

    // ───── Export CSV ─────────────────────────────────────────────────────────

    public function test_export_requires_authentication(): void
    {
        $this->get(route('settings.cookie.export'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_export_requires_permission(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('settings.cookie.export'))
            ->assertForbidden();
    }

    public function test_export_returns_csv(): void
    {
        $user = $this->createUser(['cookie.settings.view']);

        $logData = [
            'session_id' => str_repeat('a', 40),
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent' => 'TestBrowser/1.0',
            'version' => '1.0',
        ];

        CookieConsentLog::create(array_merge($logData, ['action' => 'accept_all', 'accepted_categories' => []]));
        CookieConsentLog::create(array_merge($logData, ['action' => 'reject_all', 'accepted_categories' => []]));
        CookieConsentLog::create(array_merge($logData, ['action' => 'custom', 'accepted_categories' => ['analytics']]));

        $response = $this->actingAs($user)
            ->get(route('settings.cookie.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($content)));

        // Header row + 3 data rows
        $this->assertCount(4, array_values($lines));
        $this->assertStringContainsString('action', $lines[array_key_first($lines)]);
    }
}
