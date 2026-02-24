<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
        ]);

        // Give admin all permissions
        $this->admin->givePermissionTo('reviews.templates.view');
    }

    public function test_can_view_templates_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/reviews/templates');

        $response->assertStatus(200);
        $response->assertViewIs('reviews::replies.templates.index');
    }
}
