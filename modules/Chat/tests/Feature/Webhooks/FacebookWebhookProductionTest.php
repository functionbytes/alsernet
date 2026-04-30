<?php

namespace Modules\Chat\Tests\Feature\Webhooks;

use Illuminate\Support\Facades\Http;
use Modules\Chat\Models\Channels\Facebook;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Inbox\Inbox;
use Tests\TestCase;

class FacebookWebhookProductionTest extends TestCase
{
    protected Facebook $facebookPage;

    protected Inbox $inbox;

    protected function setUp(): void
    {
        parent::setUp();

        // Use real inbox 16 if it exists
        $this->inbox = Inbox::find(16);

        if (! $this->inbox) {
            // Create test inbox if 16 doesn't exist
            $this->facebookPage = Facebook::create([
                'account_id' => 1,
                'page_id' => '109442377559389_production_'.time(),
                'page_name' => 'Production Test Page',
                'page_access_token' => 'test_token_'.time(),
            ]);

            $this->inbox = Inbox::create([
                'account_id' => 1,
                'channel_id' => $this->facebookPage->id,
                'channel_type' => Facebook::class,
                'name' => 'Facebook Production Test',
                'timezone' => 'UTC',
            ]);
        } else {
            // Get the Facebook page from inbox 16
            $this->facebookPage = Facebook::where('id', $this->inbox->channel_id)->first();
        }
    }

    public function test_facebook_webhook_saves_message_to_database()
    {
        $senderId = 'usuario_real_'.time();

        // Mock Facebook API for user profile
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => $senderId,
                'first_name' => 'Usuario',
                'last_name' => 'Real',
                'email' => 'usuario@real.example.com',
                'profile_pic' => 'https://example.com/avatar.jpg',
            ]),
        ]);

        // Simulate Facebook webhook POST
        $webhookPayload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => $this->facebookPage->page_id,
                    'time' => now()->timestamp,
                    'messaging' => [
                        [
                            'sender' => ['id' => $senderId],
                            'recipient' => ['id' => $this->facebookPage->page_id],
                            'timestamp' => now()->timestamp * 1000,
                            'message' => [
                                'mid' => 'msg_production_'.time(),
                                'text' => '✅ MENSAJE DE PRUEBA REAL DESDE FACEBOOK - '.now()->format('Y-m-d H:i:s'),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Make POST request to webhook
        $response = $this->postJson('/api/chat/webhooks/facebook', $webhookPayload, [
            'X-Hub-Signature-256' => $this->generateSignature($webhookPayload),
        ]);

        // Webhook should accept (return 200)
        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        // Verify customer was created
        $customer = Customer::where('account_id', 1)
            ->where('identifier', 'facebook_'.$senderId)
            ->first();
        $this->assertNotNull($customer, 'Customer should be created from Facebook sender ID');

        // Verify conversation was created
        $conversation = Conversation::where('account_id', 1)
            ->where('customer_id', $customer->id)
            ->where('inbox_id', $this->inbox->id)
            ->whereNull('closed_at')
            ->first();
        $this->assertNotNull($conversation, 'Conversation should be created for inbox');

        // Verify message was stored
        $message = $conversation->messages()
            ->where('content', 'like', '%MENSAJE DE PRUEBA REAL%')
            ->first();
        $this->assertNotNull($message, 'Message should be created in conversation');

        echo "\n\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║                  ✅ PRUEBA EXITOSA EN PRODUCCIÓN            ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n";
        echo "\n📱 DATOS GUARDADOS EN LA BASE DE DATOS:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Inbox: {$this->inbox->name} (ID: {$this->inbox->id})\n";
        echo "Cliente: {$customer->name} (ID: {$customer->id})\n";
        echo "Email: {$customer->email}\n";
        echo "Identificador: {$customer->identifier}\n";
        echo "Conversación: ID {$conversation->id}\n";
        echo "Mensaje: '{$message->content}'\n";
        echo "Estado: {$message->status}\n";
        echo "Creado: {$message->created_at}\n";
        echo "\n";
        echo "🌐 VER EN LA INTERFAZ:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "https://channels.functionbytes.com/chat/conversations?inbox={$this->inbox->id}\n";
        echo "\n";
        echo "✅ Los datos ya están en la base de datos y son visibles en la interfaz\n";
        echo "\n";
    }

    /**
     * Generate valid Facebook webhook signature.
     */
    private function generateSignature($payload): string
    {
        $body = json_encode($payload);
        $secret = config('channels.facebook.app_secret');
        $signature = hash_hmac('sha256', $body, $secret, false);

        return 'sha256='.$signature;
    }
}
