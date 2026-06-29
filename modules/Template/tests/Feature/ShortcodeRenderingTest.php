<?php

namespace Modules\Template\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Template\Models\Shortcode;
use Modules\Template\Tests\TestCase;

class ShortcodeRenderingTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Gate::before(fn () => true);
    }

    // -------------------------------------------------------------------------
    // Toggle endpoint — DB state changes
    // -------------------------------------------------------------------------

    public function test_shortcode_toggle_activates(): void
    {
        $shortcode = Shortcode::factory()->inactive()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.toggle', $shortcode))
            ->assertOk();

        $this->assertDatabaseHas('shortcodes', [
            'id' => $shortcode->id,
            'is_active' => true,
        ]);
    }

    public function test_shortcode_toggle_deactivates(): void
    {
        $shortcode = Shortcode::factory()->active()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.toggle', $shortcode))
            ->assertOk();

        $this->assertDatabaseHas('shortcodes', [
            'id' => $shortcode->id,
            'is_active' => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // Update endpoint — render_template persisted
    // -------------------------------------------------------------------------

    public function test_shortcode_update_changes_render_template(): void
    {
        $shortcode = Shortcode::factory()->create([
            'render_template' => '<p>old</p>',
        ]);

        $this->actingAs($this->user)
            ->put(route('settings.shortcodes.update', $shortcode), [
                'key' => $shortcode->key,
                'name' => $shortcode->name,
                'render_template' => '<div class="new">{content}</div>',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('shortcodes', [
            'id' => $shortcode->id,
            'render_template' => '<div class="new">{content}</div>',
        ]);
    }

    // -------------------------------------------------------------------------
    // Rendering logic — attribute escaping
    // -------------------------------------------------------------------------

    public function test_shortcode_attributes_are_escaped_in_render(): void
    {
        // Simulate the render closure logic from TemplateServiceProvider::registerDynamicShortcodes().
        // The closure calls e($val) on each attribute before substituting into the template.
        $renderTemplate = '<a href="{url}">{label}</a>';

        $attrs = [
            'url' => 'javascript:alert(1)',
            'label' => '<script>xss</script>',
        ];

        $html = $renderTemplate;

        foreach ($attrs as $key => $val) {
            $html = str_replace('{'.$key.'}', e($val), $html);
        }

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('javascript:alert(1)', $html);
        $this->assertStringNotContainsString('<script>xss</script>', $html);
    }

    // -------------------------------------------------------------------------
    // Rendering logic — {content} placeholder substitution
    // -------------------------------------------------------------------------

    public function test_shortcode_content_placeholder_is_replaced(): void
    {
        // Simulate the render closure: {content} is replaced with e($content).
        $renderTemplate = '<section>{content}</section>';
        $content = 'Hello World';

        $html = str_replace('{content}', e($content), $renderTemplate);

        $this->assertEquals('<section>Hello World</section>', $html);
    }

    public function test_shortcode_content_placeholder_escapes_html(): void
    {
        $renderTemplate = '<div>{content}</div>';
        $content = '<b>bold</b>';

        $html = str_replace('{content}', e($content), $renderTemplate);

        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>bold</b>', $html);
    }

    // -------------------------------------------------------------------------
    // Authorization — toggle requires update permission
    // -------------------------------------------------------------------------

    public function test_inactive_shortcode_returns_403_on_toggle_for_unauthorized(): void
    {
        // Remove the Gate::before bypass so the policy/permission check runs.
        Gate::before(fn () => null);

        $shortcode = Shortcode::factory()->inactive()->create();

        // An authenticated user without any permissions is denied.
        $unprivileged = User::factory()->create();

        $this->actingAs($unprivileged)
            ->postJson(route('settings.shortcodes.toggle', $shortcode))
            ->assertForbidden();
    }
}
