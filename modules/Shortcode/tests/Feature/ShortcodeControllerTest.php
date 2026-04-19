<?php

namespace Modules\Shortcode\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ShortcodeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'shortcode.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'shortcode.manage', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Web: index & reference
    // -------------------------------------------------------------------------

    public function test_guest_cannot_access_index(): void
    {
        $this->get(route('setting.shortcode.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $this->actingAs($this->user)
            ->get(route('setting.shortcode.index'))
            ->assertForbidden();
    }

    public function test_user_with_shortcode_view_can_access_index(): void
    {
        $this->user->givePermissionTo('shortcode.view');

        $this->actingAs($this->user)
            ->get(route('setting.shortcode.index'))
            ->assertOk()
            ->assertViewIs('shortcode::admin.index');
    }

    public function test_user_with_shortcode_view_can_access_reference(): void
    {
        $this->user->givePermissionTo('shortcode.view');

        $this->actingAs($this->user)
            ->get(route('setting.shortcode.reference'))
            ->assertOk()
            ->assertViewIs('shortcode::admin.reference');
    }

    // -------------------------------------------------------------------------
    // API: compile / strip / list / registered / check / clear-cache
    // -------------------------------------------------------------------------

    public function test_compile_endpoint_requires_authentication(): void
    {
        $this->postJson(route('api.shortcode.compile'), ['content' => '[alert]hi[/alert]'])
            ->assertUnauthorized();
    }

    public function test_compile_returns_compiled_html(): void
    {
        $this->user->givePermissionTo('shortcode.view');

        $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.shortcode.compile'), ['content' => '[alert type="success"]Hecho[/alert]'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'original' => '[alert type="success"]Hecho[/alert]',
            ])
            ->assertJsonPath('compiled', fn ($html) => str_contains($html, 'alert-success'));
    }

    public function test_compile_rejects_user_without_permission(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.shortcode.compile'), ['content' => '[alert]hi[/alert]'])
            ->assertForbidden();
    }

    public function test_compile_validates_content_is_required(): void
    {
        $this->user->givePermissionTo('shortcode.view');

        $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.shortcode.compile'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    public function test_strip_removes_shortcodes(): void
    {
        $this->user->givePermissionTo('shortcode.view');

        $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.shortcode.strip'), ['content' => 'antes [alert]x[/alert] después'])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('stripped', 'antes  después');
    }

    public function test_list_returns_shortcode_names(): void
    {
        $this->user->givePermissionTo('shortcode.view');

        $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.shortcode.list'))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['shortcodes', 'count']);
    }

    public function test_registered_returns_metadata_array(): void
    {
        $this->user->givePermissionTo('shortcode.view');

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.shortcode.registered'))
            ->assertOk();

        $data = $response->json();

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('description', $data[0]);
        $this->assertArrayHasKey('example', $data[0]);
        $this->assertArrayHasKey('attributes', $data[0]);
    }

    public function test_check_validates_name_format(): void
    {
        $this->user->givePermissionTo('shortcode.view');

        $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.shortcode.check'), ['name' => 'invalid name with spaces'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_check_accepts_hyphenated_shortcode_names(): void
    {
        $this->user->givePermissionTo('shortcode.view');

        $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.shortcode.check'), ['name' => 'contact-form'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'name' => 'contact-form',
                'exists' => true,
            ]);
    }

    public function test_clear_cache_requires_shortcode_manage(): void
    {
        $this->user->givePermissionTo('shortcode.view');

        $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.shortcode.clear-cache'))
            ->assertForbidden();
    }

    public function test_clear_cache_succeeds_with_manage_permission(): void
    {
        $this->user->givePermissionTo('shortcode.manage');

        $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.shortcode.clear-cache'))
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
