<?php

namespace Modules\HelpdeskCampaigns\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Services\FrequencyCapService;
use Tests\TestCase;

/**
 * FrequencyCapService usaba Cache::store('redis') fijo, así que cualquier
 * despliegue sin Redis configurado explotaba con una excepción en el hot-path
 * público de tracking. Ahora el store sale de
 * config('helpdeskcampaigns.cache_store') con fallback al store por defecto.
 */
class FrequencyCapCacheStoreTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['activitylog.enabled' => false]);
    }

    public function test_should_show_works_without_a_redis_store(): void
    {
        // Sin store dedicado configurado y con 'array' como default: antes
        // esto lanzaba porque Cache::store('redis') estaba hardcodeado.
        config([
            'helpdeskcampaigns.cache_store' => null,
            'cache.default' => 'array',
        ]);

        $campaign = Campaign::factory()->active()->create([
            'max_impressions_per_user' => 3,
            'cooldown_minutes' => 5,
        ]);

        $result = (new FrequencyCapService)->shouldShow($campaign, ['ip_address' => '203.0.113.10']);

        $this->assertTrue($result, 'A visitor with no prior impressions must be allowed to see the campaign');
    }

    public function test_invalidate_works_without_a_redis_store(): void
    {
        config([
            'helpdeskcampaigns.cache_store' => null,
            'cache.default' => 'array',
        ]);

        $visitorKey = 'i'.substr(md5('203.0.113.10'), 0, 12);
        Cache::store('array')->put("hc:cap:123:{$visitorKey}", ['count' => 1, 'last' => null], 60);

        (new FrequencyCapService)->invalidate(123, ['ip_address' => '203.0.113.10']);

        $this->assertNull(Cache::store('array')->get("hc:cap:123:{$visitorKey}"));
    }

    public function test_uses_the_store_configured_for_the_module(): void
    {
        config(['helpdeskcampaigns.cache_store' => 'array']);

        $campaign = Campaign::factory()->active()->create([
            'max_impressions_per_user' => 3,
            'cooldown_minutes' => 5,
        ]);

        (new FrequencyCapService)->shouldShow($campaign, ['ip_address' => '203.0.113.99']);

        $visitorKey = 'i'.substr(md5('203.0.113.99'), 0, 12);

        $this->assertNotNull(
            Cache::store('array')->get("hc:cap:{$campaign->id}:{$visitorKey}"),
            'Frequency data must be cached in the store configured via helpdeskcampaigns.cache_store'
        );
    }
}
