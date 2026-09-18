<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

class ConversationListNPlusOneTest extends HelpdeskTestCase
{
    public function test_inbox_list_eager_loading_avoids_n_plus_one(): void
    {
        $this->actingAs($this->manager);

        $conversations = Conversation::factory()->count(5)->create();

        foreach ($conversations as $conversation) {
            ConversationItem::factory()->count(2)->create([
                'conversation_id' => $conversation->id,
                'type' => 'message',
                'user_id' => null,
            ]);
        }

        // Mismo eager loading que ConversationsController::index / listJson.
        $loaded = Conversation::query()
            ->with([
                'customer', 'status', 'assignee', 'inbox', 'lastMessage',
                'reads' => fn ($q) => $q->where('user_id', $this->manager->id),
            ])
            ->withCount(['items as incoming_messages_count' => fn ($q) => $q->where('type', 'message')->whereNull('user_id')])
            ->whereIn('id', $conversations->pluck('id'))
            ->get();

        DB::connection('helpdesk')->flushQueryLog();
        DB::connection('helpdesk')->enableQueryLog();

        // Mapear las 5 filas con los datos precargados no debe disparar una
        // query por fila (getLatestMessage / unreadCountForInbox usan las
        // relaciones y el withCount cargados).
        $loaded->each(fn (Conversation $conversation) => $conversation->toInboxArray());

        $queryCount = count(DB::connection('helpdesk')->getQueryLog());
        DB::connection('helpdesk')->disableQueryLog();

        // Sin eager loading serían ~3 queries × 5 filas = 15+. Con eager loading,
        // el conteo es constante e independiente del número de filas.
        $this->assertLessThanOrEqual(2, $queryCount);
    }
}
