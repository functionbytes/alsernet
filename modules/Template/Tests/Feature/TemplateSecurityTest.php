<?php

namespace Modules\Template\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Template\Models\Menu;
use Modules\Template\Models\MenuItem;
use Tests\TestCase;

class TemplateSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * Grant all gate checks for the current user in a test.
     */
    private function actingAsAdmin(): self
    {
        Gate::before(fn () => true);

        return $this->actingAs($this->user);
    }

    // -------------------------------------------------------------------------
    // Template preview sanitization
    // -------------------------------------------------------------------------

    public function test_template_preview_rejects_php_directive(): void
    {
        $this->actingAsAdmin()
            ->postJson(route('settings.templates.preview'), [
                'content' => '@php echo shell_exec(\'id\'); @endphp',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['error']);
    }

    public function test_template_preview_rejects_raw_output(): void
    {
        $this->actingAsAdmin()
            ->postJson(route('settings.templates.preview'), [
                'content' => '{!! $dangerousVar !!}',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['error']);
    }

    // -------------------------------------------------------------------------
    // CustomCss authorization
    // -------------------------------------------------------------------------

    public function test_unauthorized_user_cannot_access_custom_css(): void
    {
        // Do NOT grant all permissions — user lacks template.custom-code permission
        $this->actingAs($this->user)
            ->get(route('settings.templates.custom-css.edit'))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Menu item URL sanitization
    // -------------------------------------------------------------------------

    public function test_menu_item_javascript_url_is_sanitized(): void
    {
        $menu = Menu::factory()->create();

        $this->actingAsAdmin()
            ->postJson(route('settings.menus.items.store', $menu), [
                'title' => 'Malicious Link',
                'url' => 'javascript:alert(1)',
                'type' => 'custom',
            ])
            ->assertSuccessful();

        $item = MenuItem::where('menu_id', $menu->id)->latest('id')->first();

        $this->assertNotNull($item);
        $this->assertEquals('#', $item->url);
    }

    // -------------------------------------------------------------------------
    // Menu item CSS class validation
    // -------------------------------------------------------------------------

    public function test_menu_item_malicious_css_class_is_rejected(): void
    {
        $menu = Menu::factory()->create();

        $this->actingAsAdmin()
            ->postJson(route('settings.menus.items.store', $menu), [
                'title' => 'Safe Title',
                'url' => '/about',
                'type' => 'custom',
                'css_class' => '<script>alert()</script>',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['css_class']);
    }
}
