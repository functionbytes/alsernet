<?php

namespace Modules\Helpdesk\Tests\Feature\Webhooks;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Jobs\ProcessSocialWebhookJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class FacebookInboundTypesTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        // Los listeners de inbound consultan User::role('helpdesk-agent').
        $this->seedHelpdeskRoles();
    }

    private function payload(array $messaging): array
    {
        return ['object' => 'page', 'entry' => [['messaging' => [$messaging]]]];
    }

    public function test_parser_emite_evento_reaction(): void
    {
        $events = (new FacebookMessengerService)->parseWebhookPayload($this->payload([
            'sender' => ['id' => 'psid_1'],
            'timestamp' => time(),
            'reaction' => ['mid' => 'm.agent', 'emoji' => '😍', 'action' => 'react'],
        ]));

        $this->assertSame('reaction', $events[0]['type']);
        $this->assertSame('psid_1', $events[0]['psid']);
        $this->assertSame('m.agent', $events[0]['message_id']);
        $this->assertSame('😍', $events[0]['emoji']);
        $this->assertSame('react', $events[0]['action']);
    }

    public function test_parser_captura_referral_click_to_messenger(): void
    {
        $events = (new FacebookMessengerService)->parseWebhookPayload($this->payload([
            'sender' => ['id' => 'psid_1'],
            'timestamp' => time(),
            'message' => ['mid' => 'm.1', 'text' => 'Hola'],
            'referral' => ['ref' => 'promo', 'source' => 'ADS', 'type' => 'OPEN_THREAD'],
        ]));

        $this->assertSame('message', $events[0]['type']);
        $this->assertSame('promo', $events[0]['referral']['ref'] ?? null);
    }

    public function test_reaction_se_adjunta_al_mensaje_saliente_del_agente(): void
    {
        $psid = 'psid_'.uniqid();
        $messageId = 'm.'.uniqid();

        $customer = Customer::factory()->create(['facebook_psid' => $psid]);
        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1],
        );
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'facebook',
            'external_sender_id' => $psid,
            'status_id' => $status->id,
        ]);
        $agent = User::factory()->create();
        $item = ConversationItem::factory()->fromAgent($agent->id)->create([
            'conversation_id' => $conversation->id,
            'external_id' => $messageId,
            'body' => 'Respuesta del agente',
        ]);

        $event = [
            'type' => 'reaction',
            'psid' => $psid,
            'message_id' => $messageId,
            'emoji' => '😍',
            'action' => 'react',
            'timestamp' => time(),
        ];

        (new ProcessSocialWebhookJob('facebook', 'reaction', $event))->handle(
            $this->app->make(FacebookMessengerService::class),
        );

        $reactions = $item->fresh()->metadata['customer_reactions'] ?? [];
        $this->assertNotEmpty($reactions);
        $this->assertSame('😍', $reactions[0]['emoji'] ?? null);
    }
}
