<?php

namespace Modules\HelpdeskHelpcenter\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskHelpcenter\Services\EmbeddingsService;
use Tests\TestCase;

/**
 * Regression coverage for the `helpcenter.integration_enabled` admin toggle
 * (panel/settings/helpdesk/integrations) on EmbeddingsService::search(), the
 * semantic search entry point consumed by HelpdeskChatFlow's RAG responder
 * and agent tool ("search_help"). While disabled, no OpenAI embedding call
 * should ever be made.
 */
class EmbeddingsServiceIntegrationToggleTest extends TestCase
{
    use DatabaseTransactions, MockeryPHPUnitIntegration;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function tearDown(): void
    {
        Setting::set('helpcenter.integration_enabled', '1', 'integrations');

        parent::tearDown();
    }

    public function test_search_returns_empty_and_skips_embedding_api_when_toggle_disabled(): void
    {
        Setting::set('helpcenter.integration_enabled', '0', 'integrations');

        $service = Mockery::mock(EmbeddingsService::class)->makePartial();
        $service->shouldNotReceive('callEmbeddingApi');

        $this->assertSame([], $service->search('how do I reset my password?'));
    }

    public function test_search_calls_embedding_api_when_toggle_enabled(): void
    {
        Setting::set('helpcenter.integration_enabled', '1', 'integrations');

        $service = Mockery::mock(EmbeddingsService::class)->makePartial();
        $service->shouldReceive('callEmbeddingApi')
            ->once()
            ->andReturn([]);

        $service->search('how do I reset my password?');
    }
}
