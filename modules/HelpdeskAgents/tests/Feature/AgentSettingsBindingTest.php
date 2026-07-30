<?php

namespace Modules\HelpdeskAgents\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskAgents\Models\AiAgent;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * T0.12 — Verify that the settings-tab blade reads from `parameters` (not `settings`)
 * and that the API key is never rendered in a value= attribute.
 */
class AgentSettingsBindingTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);
    }

    // ──────────────────────────────────────────────────────────
    // Happy path — saved parameters are displayed
    // ──────────────────────────────────────────────────────────

    public function test_settings_page_shows_saved_parameters_from_parameters_column(): void
    {
        $agent = AiAgent::factory()->default()->create([
            'parameters' => [
                'temperature' => 1.2,
                'max_tokens' => 4096,
                'top_p' => 0.8,
                'frequency_penalty' => 0.5,
                'presence_penalty' => 0,
            ],
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('helpdesk.ai.settings'));

        $response->assertOk();

        // The values from `parameters` must appear in the rendered HTML.
        $response->assertSee('value="1.2"', false);
        $response->assertSee('value="4096"', false);
        $response->assertSee('value="0.8"', false);
        $response->assertSee('value="0.5"', false);
    }

    public function test_settings_page_does_not_render_default_parameters_when_agent_has_saved_values(): void
    {
        AiAgent::factory()->default()->create([
            'parameters' => ['temperature' => 1.5, 'max_tokens' => 512],
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('helpdesk.ai.settings'));

        $response->assertOk();
        // Default 0.7 should NOT appear; the saved 1.5 should.
        $response->assertSee('value="1.5"', false);
        $response->assertDontSee('value="0.7"', false);
    }

    // ──────────────────────────────────────────────────────────
    // Security — API key must never appear in value= attribute
    // ──────────────────────────────────────────────────────────

    public function test_api_key_is_not_rendered_in_value_attribute(): void
    {
        $agent = AiAgent::factory()->default()->create([
            'api_key_encrypted' => 'sk-supersecretkey1234567890',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('helpdesk.ai.settings'));

        $response->assertOk();

        // The raw key must never appear anywhere in the HTML.
        $response->assertDontSee('sk-supersecretkey1234567890');

        // The masked placeholder must appear instead.
        $response->assertSee('••••••••••••••••••••');
    }

    public function test_api_key_input_has_no_value_attribute_when_key_is_saved(): void
    {
        AiAgent::factory()->default()->create([
            'api_key_encrypted' => 'sk-anothersecretkey',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('helpdesk.ai.settings'));

        $response->assertOk();

        // The input field must not carry a value= attribute with any key fragment.
        $html = $response->getContent();
        $this->assertStringNotContainsString('value="sk-', $html);
    }

    // ──────────────────────────────────────────────────────────
    // Authorization — guests and unpermissioned users are blocked
    // ──────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_settings(): void
    {
        $this->get(route('helpdesk.ai.settings'))->assertRedirect();
    }

    public function test_user_without_permission_receives_403_on_settings(): void
    {
        $unprivileged = User::factory()->create();

        $this->actingAs($unprivileged)
            ->get(route('helpdesk.ai.settings'))
            ->assertForbidden();
    }
}
