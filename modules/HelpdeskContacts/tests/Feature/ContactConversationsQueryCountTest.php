<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use Tests\TestCase;

/**
 * Regresión N+1 del tab Conversaciones (ContactAggregatorService):
 * el último mensaje se resuelve desde la relación precargada lastMessage
 * (with(['lastMessage'])), nunca con una query por conversación.
 */
class ContactConversationsQueryCountTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    public function test_conversaciones_query_count_does_not_grow_with_conversations(): void
    {
        $customer = Customer::factory()->create();

        $conversations = Conversation::factory()->count(5)->create(['customer_id' => $customer->id]);
        foreach ($conversations as $conversation) {
            ConversationItem::factory()->count(2)->create(['conversation_id' => $conversation->id]);
        }

        $service = app(ContactAggregatorService::class);

        DB::connection('helpdesk')->flushQueryLog();
        DB::connection('helpdesk')->enableQueryLog();

        $result = $service->conversaciones($customer->fresh());

        $queryCount = count(DB::connection('helpdesk')->getQueryLog());
        DB::connection('helpdesk')->disableQueryLog();

        $this->assertCount(5, $result['conversations']);

        // 1 query de conversaciones + 3 eager loads (status, inbox, lastMessage).
        // Un N+1 del último mensaje dispararía esto a >= 9 (una por fila).
        $this->assertLessThanOrEqual(6, $queryCount, 'El tab Conversaciones no debe hacer una query por fila para el último mensaje.');
    }

    public function test_conversaciones_preview_comes_from_latest_message_body(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Primer mensaje',
            'created_at' => now()->subMinutes(10),
        ]);
        ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Último mensaje del hilo',
            'created_at' => now(),
        ]);

        $result = app(ContactAggregatorService::class)->conversaciones($customer->fresh());

        $this->assertStringContainsString('Último mensaje del hilo', $result['conversations'][0]['preview']);
    }
}
