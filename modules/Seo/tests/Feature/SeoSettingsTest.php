<?php

namespace Modules\Seo\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Modules\Core\Models\Setting;
use Modules\Seo\Tests\TestCase;

class SeoSettingsTest extends TestCase
{
    public function test_requires_view_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('setting.seo.settings.edit'))
            ->assertForbidden();
    }

    public function test_renders_form_with_current_values(): void
    {
        Setting::set('seo.indexnow.key', 'abc123def456');

        $user = $this->createUser(['Seo.settings.view']);

        $this->actingAs($user)
            ->get(route('setting.seo.settings.edit'))
            ->assertOk()
            ->assertViewHas('settings')
            ->assertSee('abc123def456');
    }

    public function test_update_persists_setting(): void
    {
        $user = $this->createUser(['Seo.settings.view', 'Seo.settings.update']);

        $this->actingAs($user)
            ->put(route('setting.seo.settings.update'), [
                'indexnow_key' => 'mynewkey12345',
                'preconnect' => 'https://example.com',
                'html_cache_seconds' => 300,
            ])
            ->assertRedirect();

        $this->assertEquals('mynewkey12345', Setting::get('seo.indexnow.key'));
        $this->assertEquals('https://example.com', Setting::get('seo.performance.preconnect'));
    }

    public function test_update_rejects_invalid_indexnow_key(): void
    {
        $user = $this->createUser(['Seo.settings.view', 'Seo.settings.update']);

        $this->actingAs($user)
            ->put(route('setting.seo.settings.update'), [
                'indexnow_key' => 'has spaces & symbols!',
            ])
            ->assertSessionHasErrors('indexnow_key');
    }

    public function test_update_rejects_invalid_gtm_id(): void
    {
        $user = $this->createUser(['Seo.settings.view', 'Seo.settings.update']);

        $this->actingAs($user)
            ->put(route('setting.seo.settings.update'), [
                'gtm_container_id' => 'not-a-gtm-id',
            ])
            ->assertSessionHasErrors('gtm_container_id');
    }

    public function test_export_returns_json_download(): void
    {
        Setting::set('seo.indexnow.key', 'exportable');

        $user = $this->createUser(['Seo.settings.view']);

        $response = $this->actingAs($user)->get(route('setting.seo.settings.export'));
        $response->assertOk();
        $content = $response->streamedContent();
        $decoded = json_decode($content, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('settings', $decoded);
        $this->assertEquals('exportable', $decoded['settings']['seo.indexnow.key']);
    }

    public function test_import_restores_settings_from_json(): void
    {
        $user = $this->createUser(['Seo.settings.view', 'Seo.settings.update']);

        $payload = json_encode([
            'version' => 1,
            'settings' => [
                'seo.indexnow.key' => 'imported_key',
                'seo.performance.preconnect' => 'https://cdn.imported.com',
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('config.json', $payload);

        $this->actingAs($user)
            ->post(route('setting.seo.settings.import'), ['config_file' => $file])
            ->assertRedirect();

        $this->assertEquals('imported_key', Setting::get('seo.indexnow.key'));
        $this->assertEquals('https://cdn.imported.com', Setting::get('seo.performance.preconnect'));
    }

    public function test_import_rejects_invalid_json(): void
    {
        $user = $this->createUser(['Seo.settings.view', 'Seo.settings.update']);

        $file = UploadedFile::fake()->createWithContent('bad.json', '{garbage}');

        $this->actingAs($user)
            ->post(route('setting.seo.settings.import'), ['config_file' => $file])
            ->assertRedirect();

        $this->assertNull(Setting::get('seo.indexnow.key'));
    }
}
