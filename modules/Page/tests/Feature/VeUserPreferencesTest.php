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
}
