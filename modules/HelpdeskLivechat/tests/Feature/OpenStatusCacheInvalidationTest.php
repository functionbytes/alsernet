<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\HelpdeskLivechat\Services\Widget\WidgetConversationService;
use Tests\TestCase;

/**
 * La reutilización de conversaciones del widget cachea 30 min los ids de
 * estados abiertos (WidgetConversationService::OPEN_STATUS_IDS_CACHE_KEY).
 * HelpdeskLivechatServiceProvider registra la invalidación sobre los eventos
 * saved/deleted de ConversationStatus para que un cambio de estados se refleje
 * en el widget de inmediato (antes tardaba hasta media hora).
 */
class OpenStatusCacheInvalidationTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private const KEY = WidgetConversationService::OPEN_STATUS_IDS_CACHE_KEY;

    private function makeStatus(): ConversationStatus
    {
        return ConversationStatus::create([
            'name' => 'Cache Invalidation Test',
            'slug' => 'cache-inv-test-'.uniqid(),
            'color' => '#123456',
            'is_open' => true,
        ]);
    }

    public function test_creating_a_status_invalidates_the_cache(): void
    {
        Cache::put(self::KEY, [999999], 60);

        $this->makeStatus();

        $this->assertFalse(Cache::has(self::KEY));
    }

    public function test_updating_a_status_invalidates_the_cache(): void
    {
        $status = $this->makeStatus();

        Cache::put(self::KEY, [999999], 60);

        $status->update(['is_open' => false]);

        $this->assertFalse(Cache::has(self::KEY));
    }

    public function test_deleting_a_status_invalidates_the_cache(): void
    {
        $status = $this->makeStatus();

        Cache::put(self::KEY, [999999], 60);

        $status->delete();

        $this->assertFalse(Cache::has(self::KEY));
    }
}
