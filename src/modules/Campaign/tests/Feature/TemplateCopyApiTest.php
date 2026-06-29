<?php

namespace Modules\Campaign\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Campaign\Models\Template\Template;
use Tests\TestCase;

class TemplateCopyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_can_copy_template(): void
    {
        $this->authenticate();

        $template = Template::forceCreate([
            'uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a13',
            'name' => 'Original',
            'content' => '<p>Hello</p>',
        ]);

        $response = $this->postJson("/api/campaign/templates/{$template->uid}/copy");
        $response->assertCreated();

        $data = $response->json('data');
        $this->assertSame('Original (copy)', $data['name']);
        $this->assertSame('<p>Hello</p>', $data['content']);
        $this->assertNotSame($template->uid, $data['uid']);

        $this->assertDatabaseHas('campaign_templates', ['name' => 'Original (copy)']);
    }

    public function test_copy_returns_404_for_missing_template(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/campaign/templates/nonexistent/copy');
        $response->assertNotFound();
    }
}
