<?php

namespace Modules\Chat\Tests\Feature\Webhooks;

use Modules\Chat\Models\Channels\Instagram;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerInbox;
use Modules\Chat\Models\Inbox\Inbox;
use Tests\TestCase;

class InstagramWebhookProductionTest extends TestCase
{
    protected Instagram $instagram;

    protected Inbox $inbox;

    protected function setUp(): void
    {
        parent::setUp();

        // Find or create test Instagram account with inbox
        $this->instagram = Instagram::where('account_id', 1)->first();

        if (! $this->instagram) {
            // Create test Instagram if none exists
            $this->instagram = Instagram::create([
                'account_id' => 1,
                'instagram_id' => '17841406029580190_test_'.time(),
                'username' => 'instagram_test_'.time(),
                'user_access_token' => 'test_token_user_'.time(),
                'page_access_token' => 'test_token_page_'.time(),
                'facebook_page_id' => '109442377559389',
            ]);
        }

        // Find or create inbox for Instagram
        $this->inbox = Inbox::where('channel_id', $this->instagram->id)
            ->where('channel_type', Instagram::class)
            ->first();

        if (! $this->inbox) {
            $this->inbox = Inbox::create([
                'account_id' => 1,
                'channel_id' => $this->instagram->id,
                'channel_type' => Instagram::class,
                'name' => 'Instagram Test Inbox',
                'timezone' => 'UTC',
            ]);
        }
    }

    public function test_instagram_webhook_saves_message_to_database()
    {
        $senderId = 'usuario_instagram_'.time();

        // Simulate Instagram webhook POST
        $webhookPayload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => $this->instagram->instagram_id,
                    'time' => now()->timestamp,
                    'messaging' => [
                        [
                            'sender' => ['id' => $senderId],
                            'recipient' => ['id' => $this->instagram->instagram_id],
                            'timestamp' => now()->timestamp * 1000,
                            'message' => [
                                'mid' => 'msg_instagram_'.time(),
                                'text' => '✅ MENSAJE DE PRUEBA DESDE INSTAGRAM - '.now()->format('Y-m-d H:i:s'),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Make POST request to webhook
        $response = $this->postJson('/api/chat/webhooks/instagram', $webhookPayload, [
            'X-Hub-Signature-256' => $this->generateSignature($webhookPayload),
        ]);

        // Webhook should accept (return 200)
        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        // Verify customer was created
        $customer = Customer::where('account_id', 1)
            ->where('identifier', 'instagram_'.$senderId)
            ->first();
        $this->assertNotNull($customer, 'Customer should be created from Instagram sender ID');

        // Verify conversation was created
        $conversation = Conversation::where('account_id', 1)
            ->where('customer_id', $customer->id)
            ->where('inbox_id', $this->inbox->id)
            ->whereNull('closed_at')
            ->first();
        $this->assertNotNull($conversation, 'Conversation should be created for inbox');

        // Verify message was stored
        $message = $conversation->messages()
            ->where('content', 'like', '%MENSAJE DE PRUEBA DESDE INSTAGRAM%')
            ->first();
        $this->assertNotNull($message, 'Message should be created in conversation');

        // Verify CustomerInbox relationship was created
        $customerInbox = CustomerInbox::where('customer_id', $customer->id)
            ->where('inbox_id', $this->inbox->id)
            ->first();
        $this->assertNotNull($customerInbox, 'CustomerInbox relationship should be created automatically');
        $this->assertEquals($senderId, $customerInbox->source_id, 'CustomerInbox should have correct Instagram sender ID');
        $this->assertTrue($customerInbox->hmac_verified, 'CustomerInbox should be marked as verified');

        echo "\n\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║               ✅ PRUEBA EXITOSA EN INSTAGRAM                 ║\n";
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
        echo "\n🔗 RELACIÓN CUSTOMER-INBOX:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "CustomerInbox ID: {$customerInbox->id}\n";
        echo "Source ID (Instagram): {$customerInbox->source_id}\n";
        echo 'HMAC Verified: '.($customerInbox->hmac_verified ? '✅ Sí' : '❌ No')."\n";
        echo "\n";
        echo "✅ Los datos ya están en la base de datos\n";
        echo "✅ La relación CustomerInbox permite enviar mensajes de vuelta\n";
        echo "\n";
    }

    /**
     * Generate valid Instagram webhook signature.
     */
    private function generateSignature($payload): string
    {
        $body = json_encode($payload);
        $secret = config('channels.instagram.app_secret') ?? env('INSTAGRAM_APP_SECRET');
        $signature = hash_hmac('sha256', $body, $secret, false);

        return 'sha256='.$signature;
    }
}
