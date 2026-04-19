<?php

namespace Modules\Page\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VeUserPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_preferences(): void
    {
        $this->get('/panel/pages/ve/preferences/shortcode_favorites')
            ->assertRedirect();
    }

    public function test_user_can_read_and_write_shortcode_favorites(): void
    {
        $user = User::factory()->create();

        $read = $this->actingAs($user)->get('/panel/pages/ve/preferences/shortcode_favorites');
        $read->assertOk();
        $read->assertJsonPath('value', []);

        $write = $this->actingAs($user)->post('/panel/pages/ve/preferences/shortcode_favorites', [
            'value' => ['form', 'form-link', 'alert'],
        ]);
        $write->assertOk();
        $write->assertJsonPath('success', true);

        $readBack = $this->actingAs($user)->get('/panel/pages/ve/preferences/shortcode_favorites');
        $readBack->assertOk();
        $this->assertEquals(['form', 'form-link', 'alert'], $readBack->json('value'));
    }

    public function test_disallowed_key_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/panel/pages/ve/preferences/some_random_key')
            ->assertStatus(422);
    }

    public function test_bulk_show_returns_all_requested_keys(): void
    {
        $user = User::factory()->create();

        // Seed two prefs
        $this->actingAs($user)->post('/panel/pages/ve/preferences/last_panel', ['value' => ['inspector']]);
        $this->actingAs($user)->post('/panel/pages/ve/preferences/last_breakpoint', ['value' => ['tablet']]);

        $res = $this->actingAs($user)->postJson('/panel/pages/ve/preferences/bulk-show', [
            'keys' => ['last_panel', 'last_breakpoint', 'wireframe_enabled'],
        ]);

        $res->assertOk();
        $res->assertJsonPath('data.last_panel', ['inspector']);
        $res->assertJsonPath('data.last_breakpoint', ['tablet']);
        $res->assertJsonPath('data.wireframe_enabled', []);
    }

    public function test_bulk_store_writes_multiple_keys_in_one_request(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->postJson('/panel/pages/ve/preferences/bulk-store', [
            'values' => [
                'last_panel' => ['layout'],
                'last_breakpoint' => ['mobile'],
                'wireframe_enabled' => ['1'],
            ],
        ]);

        $res->assertOk();
        $res->assertJsonPath('success', true);

        $this->assertEquals(['layout'], $this->actingAs($user)->get('/panel/pages/ve/preferences/last_panel')->json('value'));
        $this->assertEquals(['mobile'], $this->actingAs($user)->get('/panel/pages/ve/preferences/last_breakpoint')->json('value'));
        $this->assertEquals(['1'], $this->actingAs($user)->get('/panel/pages/ve/preferences/wireframe_enabled')->json('value'));
    }

    public function test_bulk_store_silently_skips_unknown_keys(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/panel/pages/ve/preferences/bulk-store', [
            'values' => [
                'last_panel' => ['inspector'],
                'not_allowed' => ['x'],
            ],
        ])->assertOk();

        // The allowed key was written, the disallowed one was silently dropped.
        $this->assertEquals(['inspector'], $this->actingAs($user)->get('/panel/pages/ve/preferences/last_panel')->json('value'));
    }
}
