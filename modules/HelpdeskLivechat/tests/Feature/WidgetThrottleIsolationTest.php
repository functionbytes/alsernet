<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * Cada grupo de rutas del widget tiene su propio cupo. Sin prefijo, Laravel
 * usa la misma clave sha1(dominio|IP) para todos los throttle:X,Y, y los
 * latidos agotaban el cupo de 10/min de crear conversación.
 */
class WidgetThrottleIsolationTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedOpenConversationStatus();
        Cache::flush();
    }

    public function test_heartbeats_do_not_consume_the_create_conversation_quota(): void
    {
        $web = WebFactory::new()->create();

        for ($i = 0; $i < 12; $i++) {
            $this->withHeaders(['X-Website-Token' => $web->website_token])
                ->postJson(route('helpdesk-livechat.widget.session.heartbeat'), [
                    'session_token' => 'sess_throttle_'.$i,
                    'url' => 'https://shop.example/p/'.$i,
                ])->assertOk();
        }

        $status = $this->withHeaders(['X-Website-Token' => $web->website_token])
            ->postJson(route('helpdesk-livechat.widget.conversation.store'), [])
            ->status();

        $this->assertNotSame(429, $status);
    }
}
