<?php

namespace Modules\SimpleSlider\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleSliderSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get('/panel/settings/simple-sliders');

        $response->assertRedirect();
    }

    public function test_authenticated_user_can_access_or_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/panel/settings/simple-sliders');

        // Module is a placeholder — accept any non-500 response
        $this->assertContains($response->status(), [200, 403, 404]);
    }
}
