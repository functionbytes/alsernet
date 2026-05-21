<?php

namespace Modules\Remarketing\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Support\StarterTemplates;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StarterTemplatesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_templates')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }
    }

    public function test_starters_returns_four_templates_with_required_keys(): void
    {
        $starters = StarterTemplates::all();

        $this->assertCount(4, $starters);

        foreach ($starters as $slug => $template) {
            $this->assertArrayHasKey('name', $template, "Missing 'name' for $slug");
            $this->assertArrayHasKey('type', $template, "Missing 'type' for $slug");
            $this->assertArrayHasKey('subject', $template, "Missing 'subject' for $slug");
            $this->assertArrayHasKey('html_content', $template, "Missing 'html_content' for $slug");
        }
    }

    public function test_starters_has_expected_slugs(): void
    {
        $slugs = array_keys(StarterTemplates::all());

        $this->assertContains('welcome', $slugs);
        $this->assertContains('abandoned-cart', $slugs);
        $this->assertContains('win-back', $slugs);
        $this->assertContains('promotional', $slugs);
    }

    public function test_get_returns_template_for_known_slug(): void
    {
        $template = StarterTemplates::get('welcome');

        $this->assertNotNull($template);
        $this->assertEquals('Bienvenida', $template['name']);
    }

    public function test_get_returns_null_for_unknown_slug(): void
    {
        $this->assertNull(StarterTemplates::get('non-existent-slug'));
    }

    public function test_clone_starter_creates_template_for_user_store(): void
    {
        $user = $this->userWithPermissions(['remarketing.templates.create']);
        $store = $this->createStore($user);

        $this->actingAs($user)
            ->post(route('remarketing.templates.starters.clone', 'welcome'))
            ->assertRedirect(route('remarketing.templates.index'));

        $this->assertDatabaseHas('remarketing_templates', [
            'store_id' => $store->id,
            'type' => 'welcome',
        ]);
    }

    public function test_clone_starter_returns_404_for_unknown_slug(): void
    {
        $user = $this->userWithPermissions(['remarketing.templates.create']);
        $this->createStore($user);

        $this->actingAs($user)
            ->post(route('remarketing.templates.starters.clone', 'does-not-exist'))
            ->assertNotFound();
    }

    public function test_guest_cannot_access_starters_page(): void
    {
        $this->get(route('remarketing.templates.starters'))
            ->assertRedirect();
    }

    public function test_guest_cannot_clone_starter(): void
    {
        $this->post(route('remarketing.templates.starters.clone', 'welcome'))
            ->assertRedirect();
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function createStore(User $user): Store
    {
        return Store::query()->create([
            'user_id' => $user->id,
            'platform' => 'manual',
            'name' => 'Test Store '.uniqid(),
            'domain' => 'test-'.uniqid().'.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ]);
    }
}
