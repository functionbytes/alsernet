<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Theme\Services\NavService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * T0.21 regression: HelpdeskServiceProvider registered a settings sidebar item
 * pointing to route 'settings.helpdesk.ai' which does not exist, causing a
 * dead link or route() exception in the sidebar navigation.
 *
 * Fix: the item was removed from registerSettingsSidebar().
 */
class NavDeadlinkTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
    }

    /** Collect all route names registered in a given sidebar, across all sections. */
    private function collectSidebarRoutes(string $sidebarId): array
    {
        $sidebar = NavService::getSidebar($sidebarId);

        if ($sidebar === null) {
            return [];
        }

        $routes = [];

        foreach ($sidebar['sections'] ?? [] as $section) {
            foreach ($section['items'] ?? [] as $item) {
                if (isset($item['route'])) {
                    $routes[] = $item['route'];
                }
            }
        }

        return $routes;
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_settings_helpdesk_ai_route_is_not_registered(): void
    {
        $this->assertFalse(
            \Route::has('settings.helpdesk.ai'),
            'Route settings.helpdesk.ai should not be registered.'
        );
    }

    public function test_settings_sidebar_does_not_contain_dead_ai_link(): void
    {
        $routes = $this->collectSidebarRoutes('settings');

        $this->assertNotContains(
            'settings.helpdesk.ai',
            $routes,
            'The settings sidebar must not reference the non-existent route settings.helpdesk.ai.'
        );
    }

    public function test_all_helpdesk_settings_sidebar_routes_are_resolvable(): void
    {
        $routes = $this->collectSidebarRoutes('settings');

        $helpdeskRoutes = array_filter(
            $routes,
            fn (string $r) => str_starts_with($r, 'settings.helpdesk') || str_starts_with($r, 'manager.helpdesk')
        );

        $missing = [];
        foreach ($helpdeskRoutes as $routeName) {
            if (! \Route::has($routeName)) {
                $missing[] = $routeName;
            }
        }

        $this->assertEmpty(
            $missing,
            'Found dead-link route(s) in Helpdesk settings sidebar: '.implode(', ', $missing)
        );
    }
}
