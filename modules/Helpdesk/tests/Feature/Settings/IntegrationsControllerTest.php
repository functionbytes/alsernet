<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Setting;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IntegrationsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    /**
     * Every toggleable integration key, in the order exposed by the controller.
     *
     * @var array<int, string>
     */
    private const array ALL_KEYS = [
        'tickets', 'livechat', 'chatflow', 'sla', 'compliance', 'erp', 'social',
        'translate', 'agents', 'campaigns', 'contacts', 'analytics', 'document',
        'prestashop', 'helpcenter', 'emaillog', 'integration',
    ];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);
    }

    public function test_index_exposes_every_helpdesk_integration_as_toggleable(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.integrations.index'));

        $response->assertOk();
        $response->assertViewHas('toggleableModules', function (array $modules) {
            $byKey = collect($modules)->keyBy('key');

            $this->assertSame(self::ALL_KEYS, $byKey->keys()->all());

            foreach ($modules as $module) {
                $this->assertArrayHasKey('name', $module);
                $this->assertArrayHasKey('description', $module);
                $this->assertArrayHasKey('installed', $module);
                $this->assertArrayHasKey('moduleEnabled', $module);
                $this->assertArrayHasKey('toggleEnabled', $module);
                $this->assertArrayHasKey('canToggle', $module);
            }

            return true;
        });
    }

    public function test_index_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.integrations.index'))
            ->assertForbidden();
    }

    public function test_update_persists_checked_toggles_and_clears_unchecked_ones(): void
    {
        $response = $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.integrations.update'), [
                'tickets_integration_enabled' => '1',
                'chatflow_integration_enabled' => '1',
                // the rest are omitted — simulates unchecked checkboxes
            ]);

        $response->assertRedirect();

        foreach (self::ALL_KEYS as $key) {
            $expected = in_array($key, ['tickets', 'chatflow'], true) ? '1' : '0';

            $this->assertSame($expected, Setting::get("{$key}.integration_enabled", 'missing'));
        }
    }

    public function test_update_stores_zero_when_all_checkboxes_unchecked(): void
    {
        $response = $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.integrations.update'), []);

        $response->assertRedirect();

        foreach (self::ALL_KEYS as $key) {
            $this->assertSame('0', Setting::get("{$key}.integration_enabled", '1'));
        }
    }

    public function test_update_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('settings.helpdesk.integrations.update'), [
                'tickets_integration_enabled' => '1',
            ])
            ->assertForbidden();
    }

    public function test_helper_functions_reflect_stored_toggle(): void
    {
        foreach (self::ALL_KEYS as $key) {
            $helper = "helpdesk_{$key}_enabled";
            $this->assertTrue(function_exists($helper), "Missing helper function {$helper}()");

            Setting::set("{$key}.integration_enabled", '0', 'integrations');
            $this->assertFalse($helper(), "{$helper}() should be false when the module is not installed/enabled");

            Setting::set("{$key}.integration_enabled", '1', 'integrations');
        }
    }

    public function test_tickets_helper_now_reflects_stored_toggle(): void
    {
        // Regression guard: previously helpdesk_tickets_enabled() ignored the
        // stored Setting entirely, making the settings-page switch a no-op.
        Setting::set('tickets.integration_enabled', '0', 'integrations');
        $this->assertFalse(helpdesk_tickets_enabled());

        Setting::set('tickets.integration_enabled', '1', 'integrations');
        $this->assertTrue(helpdesk_tickets_enabled());
    }
}
