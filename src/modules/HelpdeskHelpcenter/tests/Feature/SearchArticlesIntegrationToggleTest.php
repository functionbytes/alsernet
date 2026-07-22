<?php

namespace Modules\HelpdeskHelpcenter\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Tests\TestCase;

/**
 * Regression coverage for the `helpcenter.integration_enabled` admin toggle
 * (panel/settings/helpdesk/integrations) on
 * HelpCenterController::searchArticles(), the composer's "Buscar artículo de
 * ayuda" endpoint (manager.helpcenter.articles.search). While disabled, the
 * endpoint must return an empty result set without querying for articles.
 */
class SearchArticlesIntegrationToggleTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo('helpdesk.helpcenter.articles.view');
    }

    protected function tearDown(): void
    {
        Setting::set('helpcenter.integration_enabled', '1', 'integrations');

        parent::tearDown();
    }

    public function test_search_returns_empty_results_when_toggle_disabled(): void
    {
        Setting::set('helpcenter.integration_enabled', '0', 'integrations');

        HelpCenterArticle::factory()->published()->create([
            'title' => 'How to reset your password',
        ]);

        $this->actingAs($this->agent)
            ->getJson(route('manager.helpcenter.articles.search', ['q' => 'reset']))
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_search_returns_matching_articles_when_toggle_enabled(): void
    {
        Setting::set('helpcenter.integration_enabled', '1', 'integrations');

        HelpCenterArticle::factory()->published()->create([
            'title' => 'How to reset your password',
        ]);

        $response = $this->actingAs($this->agent)
            ->getJson(route('manager.helpcenter.articles.search', ['q' => 'reset']))
            ->assertOk();

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsStringIgnoringCase('reset', $data[0]['title']);
    }
}
