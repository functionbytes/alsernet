<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

use App\Models\User;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Inbox;
use Spatie\Permission\PermissionRegistrar;

/**
 * Búsqueda del inbox contra GET /panel/helpdesk/conversations/list?search=.
 *
 * Los términos de menos de 4 caracteres usan LIKE (no FULLTEXT) porque InnoDB
 * no ve en MATCH...AGAINST las filas sin confirmar de la transacción del test;
 * por eso los marcadores de los fixtures son cortos (p. ej. "zq1").
 */
class ConversationSearchTest extends InboxTestCase
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<int, int>
     */
    private function searchIds(array $params, ?User $user = null): array
    {
        $response = $this->actingAs($user ?? $this->manager)
            ->getJson(route('manager.helpdesk.conversations.list', $params));
        $this->assertSame(200, $response->status(), substr($response->getContent(), 0, 300));

        preg_match_all('/data-bv-conv-id="(\d+)"/', $response->json('html'), $m);

        return array_map('intval', $m[1]);
    }

    private function conversationFor(array $customer = [], array $conversation = []): Conversation
    {
        return $this->createConversation($conversation + [
            'customer_id' => $this->createCustomer($customer)->id,
            'is_archived' => false,
        ]);
    }

    public function test_search_by_subject(): void
    {
        $match = $this->conversationFor([], ['subject' => 'Pedido zq1 retrasado']);
        $other = $this->conversationFor([], ['subject' => 'Otra cosa']);

        $ids = $this->searchIds(['search' => 'zq1']);

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_search_is_case_insensitive(): void
    {
        $match = $this->conversationFor([], ['subject' => 'Factura ZQ2']);

        $this->assertContains($match->id, $this->searchIds(['search' => 'zq2']));
    }

    public function test_search_is_accent_insensitive(): void
    {
        $match = $this->conversationFor(['name' => 'Zoé']);

        $this->assertContains($match->id, $this->searchIds(['search' => 'zoe']));
    }

    public function test_search_by_customer_email(): void
    {
        $match = $this->conversationFor(['email' => 'zq4-cliente@example.com']);
        $other = $this->conversationFor(['email' => 'otro@example.com']);

        $ids = $this->searchIds(['search' => 'zq4']);

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_search_by_customer_name(): void
    {
        $match = $this->conversationFor(['name' => 'Wilfredo zq5']);

        $this->assertContains($match->id, $this->searchIds(['search' => 'zq5']));
    }

    public function test_search_by_phone_fragment(): void
    {
        $match = $this->conversationFor(['phone' => '+34600123456']);
        $other = $this->conversationFor(['phone' => '+34699999999']);

        $ids = $this->searchIds(['search' => '0123']);

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_search_by_phone_typed_with_spaces_matches_compact_stored_phone(): void
    {
        $match = $this->conversationFor(['phone' => '+34600123456']);

        $this->assertContains($match->id, $this->searchIds(['search' => '+34 600 123 456']));
    }

    public function test_search_by_phone_without_spaces_matches_phone_stored_with_spaces(): void
    {
        $match = $this->conversationFor(['phone' => '+34 600 123 456']);

        $this->assertContains($match->id, $this->searchIds(['search' => '+34600123456']));
    }

    public function test_numeric_search_matches_conversation_id(): void
    {
        $match = $this->conversationFor();
        $other = $this->conversationFor(['name' => 'Sin relacion']);

        $ids = $this->searchIds(['search' => (string) $match->id]);

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_percent_wildcard_is_treated_literally(): void
    {
        $literal = $this->conversationFor([], ['subject' => 'Descuento 5%z']);
        $other = $this->conversationFor([], ['subject' => 'Descuento 50 z']);

        $ids = $this->searchIds(['search' => '5%z']);

        $this->assertContains($literal->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_underscore_wildcard_is_treated_literally(): void
    {
        $literal = $this->conversationFor([], ['subject' => 'ref a_b']);
        $other = $this->conversationFor([], ['subject' => 'ref axb']);

        $ids = $this->searchIds(['search' => 'a_b']);

        $this->assertContains($literal->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_lone_percent_does_not_match_everything(): void
    {
        $other = $this->conversationFor([], ['subject' => 'Sin simbolo']);

        $this->assertNotContains($other->id, $this->searchIds(['search' => '%']));
    }

    public function test_whitespace_only_search_behaves_like_no_search(): void
    {
        $conversation = $this->conversationFor([], ['subject' => 'Dos palabras']);

        $this->assertContains($conversation->id, $this->searchIds(['search' => '   ']));
    }

    public function test_search_zero_is_not_silently_ignored(): void
    {
        $match = $this->conversationFor([], ['subject' => 'Codigo 0']);

        $this->assertContains($match->id, $this->searchIds(['search' => '0']));
    }

    public function test_very_long_search_does_not_error_and_returns_nothing(): void
    {
        $this->conversationFor([], ['subject' => 'Normal']);

        $this->assertSame([], $this->searchIds(['search' => str_repeat('x', 5000)]));
    }

    public function test_special_characters_do_not_break_the_query(): void
    {
        $match = $this->conversationFor([], ['subject' => "O'B \"zq6\" <b>"]);

        foreach (["O'B", '"zq6"', '<script>alert(1)</script>', "'; DROP TABLE x;--", '+-<>()~*"@'] as $term) {
            $this->searchIds(['search' => $term]);
        }

        $this->assertContains($match->id, $this->searchIds(['search' => "O'B"]));
    }

    public function test_search_term_is_escaped_in_rendered_input(): void
    {
        $response = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.list', ['search' => '"><script>x</script>']))
            ->assertOk();

        $this->assertStringNotContainsString('<script>x</script>', $response->json('html'));
    }

    public function test_search_input_keeps_the_term_after_a_list_refresh(): void
    {
        $response = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.list', ['search' => 'zq7']))
            ->assertOk();

        $this->assertStringContainsString('value="zq7"', $response->json('html'));
    }

    public function test_search_combines_with_priority_filter(): void
    {
        $urgent = $this->conversationFor([], ['subject' => 'Caso zq8 a', 'priority' => 'urgent']);
        $low = $this->conversationFor([], ['subject' => 'Caso zq8 b', 'priority' => 'low']);

        $ids = $this->searchIds(['search' => 'zq8', 'priority' => 'urgent']);

        $this->assertContains($urgent->id, $ids);
        $this->assertNotContains($low->id, $ids);
    }

    public function test_search_combines_with_mine_chip(): void
    {
        $mine = $this->conversationFor([], ['subject' => 'Caso zq9 a', 'assignee_id' => $this->manager->id]);
        $theirs = $this->conversationFor([], ['subject' => 'Caso zq9 b', 'assignee_id' => User::factory()->create()->id]);

        $ids = $this->searchIds(['search' => 'zq9', 'mine' => 1]);

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
    }

    public function test_search_combines_with_inbox_filter(): void
    {
        $inboxA = Inbox::create(['name' => 'A '.uniqid(), 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
        $inboxB = Inbox::create(['name' => 'B '.uniqid(), 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
        $inA = $this->conversationFor([], ['subject' => 'Caso zr1 a', 'inbox_id' => $inboxA->id]);
        $inB = $this->conversationFor([], ['subject' => 'Caso zr1 b', 'inbox_id' => $inboxB->id]);

        $ids = $this->searchIds(['search' => 'zr1', 'inbox' => $inboxA->id]);

        $this->assertContains($inA->id, $ids);
        $this->assertNotContains($inB->id, $ids);
    }

    public function test_restricted_agent_search_never_leaks_other_inboxes(): void
    {
        $own = Inbox::create(['name' => 'Propia '.uniqid(), 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
        $foreign = Inbox::create(['name' => 'Ajena '.uniqid(), 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
        $agent = User::factory()->create();
        $agent->givePermissionTo(['helpdesk.view', 'helpdesk.conversations.view']);
        AgentInboxCapacity::create(['user_id' => $agent->id, 'inbox_id' => $own->id, 'max_concurrent' => 5, 'accepts_new' => true]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $visible = $this->conversationFor([], ['subject' => 'Caso zr2 a', 'inbox_id' => $own->id]);
        $hidden = $this->conversationFor([], ['subject' => 'Caso zr2 b', 'inbox_id' => $foreign->id]);

        $ids = $this->searchIds(['search' => 'zr2'], $agent);

        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($hidden->id, $ids);
    }

    public function test_restricted_to_own_agent_search_only_finds_assigned_conversations(): void
    {
        $inbox = Inbox::create(['name' => 'Mia '.uniqid(), 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
        $agent = User::factory()->create();
        $agent->givePermissionTo(['helpdesk.view', 'helpdesk.conversations.view', 'helpdesk.conversations.view-assigned-only']);
        AgentInboxCapacity::create(['user_id' => $agent->id, 'inbox_id' => $inbox->id, 'max_concurrent' => 5, 'accepts_new' => true]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $assigned = $this->conversationFor([], ['subject' => 'Caso zr3 a', 'inbox_id' => $inbox->id, 'assignee_id' => $agent->id]);
        $unassigned = $this->conversationFor([], ['subject' => 'Caso zr3 b', 'inbox_id' => $inbox->id]);

        $ids = $this->searchIds(['search' => 'zr3'], $agent);

        $this->assertContains($assigned->id, $ids);
        $this->assertNotContains($unassigned->id, $ids);
    }

    public function test_search_excludes_soft_deleted_conversations(): void
    {
        $deleted = $this->conversationFor([], ['subject' => 'Caso zr4']);
        $deleted->delete();

        $this->assertNotContains($deleted->id, $this->searchIds(['search' => 'zr4']));
    }

    public function test_search_finds_soft_deleted_conversations_in_trash_view(): void
    {
        $deleted = $this->conversationFor([], ['subject' => 'Caso zr5']);
        $deleted->delete();

        $this->assertContains($deleted->id, $this->searchIds(['search' => 'zr5', 'view' => 'deleted']));
    }

    public function test_search_finds_bot_handled_conversations_that_the_inbox_hides(): void
    {
        $bot = $this->conversationFor([], ['subject' => 'Caso zr6', 'metadata' => ['handled_by_bot' => true]]);

        $this->assertNotContains($bot->id, $this->searchIds([]));
        $this->assertContains($bot->id, $this->searchIds(['search' => 'zr6']));
    }

    public function test_search_hides_archived_conversations_by_default(): void
    {
        $archived = $this->conversationFor([], ['subject' => 'Caso zr7', 'is_archived' => true]);

        $this->assertNotContains($archived->id, $this->searchIds(['search' => 'zr7']));
        $this->assertContains($archived->id, $this->searchIds(['search' => 'zr7', 'archived' => 1]));
    }

    public function test_search_does_not_search_message_bodies(): void
    {
        $conversation = $this->conversationFor([], ['subject' => 'Sin coincidencia']);
        $this->createMessage($conversation, 'texto unico zr8 en el cuerpo');

        $this->assertNotContains($conversation->id, $this->searchIds(['search' => 'zr8']));
    }

    public function test_search_is_paginated_to_fifty_results(): void
    {
        $this->conversationFor()->forceFill([])->save();
        foreach (range(1, 52) as $i) {
            $this->conversationFor([], ['subject' => "Masivo zr9 $i"]);
        }

        $this->assertCount(50, $this->searchIds(['search' => 'zr9']));
    }
}
