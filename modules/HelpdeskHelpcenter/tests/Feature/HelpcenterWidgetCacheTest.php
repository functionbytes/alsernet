<?php

namespace Modules\HelpdeskHelpcenter\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskHelpcenter\Http\Controllers\SitemapController;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterCategory;
use Modules\HelpdeskHelpcenter\Services\HelpcenterWidgetService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * getWidgetData() arma categorías + populares + artículos en varias queries y se
 * llama en cada apertura del widget. Ahora se cachea (TTL 1h) y los observers de
 * artículo/traducción invalidan la clave al cambiar el contenido.
 */
class HelpcenterWidgetCacheTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(HelpcenterWidgetService::WIDGET_CACHE_KEY);
    }

    public function test_widget_data_is_cached_after_first_call(): void
    {
        $this->assertFalse(Cache::has(HelpcenterWidgetService::WIDGET_CACHE_KEY));

        app(HelpcenterWidgetService::class)->getWidgetData();

        $this->assertTrue(Cache::has(HelpcenterWidgetService::WIDGET_CACHE_KEY), 'La primera llamada debe poblar la caché del widget.');
    }

    public function test_saving_an_article_invalidates_the_widget_cache(): void
    {
        app(HelpcenterWidgetService::class)->getWidgetData();
        $this->assertTrue(Cache::has(HelpcenterWidgetService::WIDGET_CACHE_KEY));

        // El observer de artículo debe olvidar la clave al guardar.
        HelpCenterArticle::factory()->published()->create();

        $this->assertFalse(Cache::has(HelpcenterWidgetService::WIDGET_CACHE_KEY), 'Guardar un artículo debe invalidar la caché del widget.');
    }

    /**
     * Hallazgo de auditoría: clearWidgetCache() incrementaba una clave de
     * versión ('helpdesk:widget:version') que NADIE leía, así que las acciones
     * del manager que no pasan por los observers (p. ej. reordenar categorías
     * con updates directos por query) dejaban el payload del widget obsoleto
     * hasta 1h. Ahora borra la clave real (y la del sitemap público).
     */
    public function test_reordering_categories_refreshes_the_widget_payload(): void
    {
        $first = HelpCenterCategory::factory()->create(['name' => 'Primera '.uniqid(), 'position' => 0]);
        $second = HelpCenterCategory::factory()->create(['name' => 'Segunda '.uniqid(), 'position' => 1]);

        $indexOf = function (array $payload, HelpCenterCategory $category): int {
            $index = array_search((string) $category->id, array_column($payload['categories'], 'id'), true);
            $this->assertNotFalse($index, "La categoría {$category->name} debe estar en el payload del widget.");

            return $index;
        };

        // Prima la caché con el orden original.
        $payload = app(HelpcenterWidgetService::class)->getWidgetData();
        $this->assertLessThan($indexOf($payload, $second), $indexOf($payload, $first));

        Cache::put(SitemapController::CACHE_KEY, '<xml/>', 3600);

        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']));
        $user->givePermissionTo(Permission::firstOrCreate([
            'name' => 'helpdesk.helpcenter.categories.manage',
            'guard_name' => 'web',
        ]));

        $this->actingAs($user)
            ->postJson(route('manager.helpcenter.api.categories.reorder'), [
                'ids' => [$second->id, $first->id],
            ])
            ->assertOk();

        $this->assertFalse(
            Cache::has(HelpcenterWidgetService::WIDGET_CACHE_KEY),
            'Reordenar categorías debe invalidar la caché real del widget.'
        );
        $this->assertFalse(
            Cache::has(SitemapController::CACHE_KEY),
            'Reordenar también debe invalidar la caché del sitemap público.'
        );

        // El siguiente payload refleja el nuevo orden.
        $payload = app(HelpcenterWidgetService::class)->getWidgetData();
        $this->assertLessThan($indexOf($payload, $first), $indexOf($payload, $second));
    }
}
