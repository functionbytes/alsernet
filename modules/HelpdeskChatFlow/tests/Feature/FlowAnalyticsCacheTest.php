<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskChatFlow\Events\ChatFlowCompleted;
use Modules\HelpdeskChatFlow\Http\Controllers\ChatFlowsController;
use Modules\HelpdeskChatFlow\Listeners\InvalidateFlowAnalyticsCache;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Tests\TestCase;

/**
 * analytics() cachea los bloques de métricas por (flow, days). Cuando una sesión
 * del flujo finaliza, InvalidateFlowAnalyticsCache olvida las claves para que el
 * dashboard refleje la nueva sesión sin esperar al TTL.
 */
class FlowAnalyticsCacheTest extends TestCase
{
    public function test_cache_key_is_scoped_by_flow_and_days(): void
    {
        $this->assertSame('helpdeskchatflow:analytics:7:30', ChatFlowsController::analyticsCacheKey(7, 30));
        $this->assertNotSame(
            ChatFlowsController::analyticsCacheKey(7, 30),
            ChatFlowsController::analyticsCacheKey(7, 90),
            'Distinta ventana => distinta clave.'
        );
    }

    public function test_completing_a_session_invalidates_all_windows_for_that_flow(): void
    {
        $flowId = 987654;

        foreach (ChatFlowsController::ANALYTICS_DAY_KEYS as $days) {
            Cache::put(ChatFlowsController::analyticsCacheKey($flowId, $days), ['cached'], 600);
        }
        // Otro flujo no debe verse afectado.
        Cache::put(ChatFlowsController::analyticsCacheKey(111, 30), ['keep'], 600);

        $session = new ChatFlowSession;
        $session->chat_flow_id = $flowId;
        (new InvalidateFlowAnalyticsCache)->handle(new ChatFlowCompleted($session));

        foreach (ChatFlowsController::ANALYTICS_DAY_KEYS as $days) {
            $this->assertFalse(
                Cache::has(ChatFlowsController::analyticsCacheKey($flowId, $days)),
                "La ventana {$days} del flujo debe invalidarse."
            );
        }

        $this->assertTrue(Cache::has(ChatFlowsController::analyticsCacheKey(111, 30)), 'Otros flujos no deben tocarse.');
    }
}
