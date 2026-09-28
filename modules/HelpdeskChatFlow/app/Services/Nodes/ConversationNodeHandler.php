<?php

namespace Modules\HelpdeskChatFlow\Services\Nodes;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\CustomerTag;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowHandoffSummary;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\Concerns\PostsBotMessages;
use Modules\HelpdeskChatFlow\Services\Concerns\RendersNodeMessages;

/**
 * Nodes that act on the conversation itself rather than talk to the customer:
 * tagging, attributes, handoff to a human, closing and ticket creation.
 */
class ConversationNodeHandler implements NodeHandler
{
    use PostsBotMessages, RendersNodeMessages;

    public const TYPES = ['action', 'add_tag', 'set_attribute', 'transfer', 'close', 'create_ticket', 'end'];

    public function __construct(
        private readonly ChatFlowLocalizer $localizer,
        private readonly ChatFlowHandoffSummary $handoff,
    ) {}

    public function types(): array
    {
        return self::TYPES;
    }

    public function handle(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        return match ($node['type']) {
            'action' => $this->executeAction($node, $session, $conversation),
            'add_tag' => $this->executeAddTag($node, $session, $conversation),
            'set_attribute' => $this->executeSetAttribute($node, $session, $conversation),
            'transfer' => $this->executeTransfer($node, $session, $conversation),
            'close' => $this->executeClose($node, $session, $conversation),
            'create_ticket' => $this->executeCreateTicket($node, $session, $conversation),
            'end' => $this->executeEnd($node, $session, $conversation),
        };
    }

    private function executeAction(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];

        match ($data['action_type'] ?? '') {
            'assign_agent' => $conversation->update(['assignee_id' => $data['agent_id'] ?? null]),
            'change_status' => $conversation->update(['status_id' => $data['status_id'] ?? null]),
            'add_tag' => $this->attachTags($conversation, $data['tags'] ?? []),
            default => null,
        };

        return $this->getFirstChildId($node, $session);
    }

    private function executeTransfer(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $message = $this->localizeForCustomer($data['message'] ?? 'Un momento, te transfiero con un agente.', $session);

        if ($message) {
            $this->postBotMessage($conversation, $node['id'], $message);
        }

        if (($session->flowConditions()['handoff_summary'] ?? false) || ($data['summary'] ?? false)) {
            $this->handoff->postFor($conversation);
        }

        // Release first so the assignment broadcast finds the conversation back
        // in the inbox, then assign — which notifies the agent/group in real time.
        $conversation->releaseFromBot();
        $this->assignConversation($conversation, $data);

        $session->update(['status' => 'transferred', 'ended_at' => now()]);

        return null;
    }

    /**
     * Assign the conversation to a specific agent and/or group when the transfer
     * node specifies one.
     *
     * @param  array<string,mixed>  $data
     */
    private function assignConversation(Conversation $conversation, array $data): void
    {
        $agentId = ! empty($data['assignee_id']) ? (int) $data['assignee_id'] : null;
        $groupId = ! empty($data['group_id']) ? (int) $data['group_id'] : null;

        // assignTo()/assignToGroup() broadcast the inbox change and notify the
        // agent (or every group member) in real time — unlike a plain update().
        if ($agentId) {
            $conversation->assignTo($agentId);

            if ($groupId) {
                $conversation->update(['group_id' => $groupId]);
            }

            return;
        }

        if ($groupId) {
            $conversation->assignToGroup($groupId);
        }
    }

    private function executeClose(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $farewell = trim($data['farewell'] ?? '');

        if ($farewell) {
            $this->postBotMessage($conversation, $node['id'], $this->localizeForCustomer($farewell, $session));
        }

        // Always release: even a cleanly-resolved session must return the
        // conversation to the inbox, because the trigger only fires on the
        // conversation's first customer message (ExecuteChatFlowNodeJob) — a
        // later reply after this close would otherwise stay permanently
        // invisible to agents (handled_by_bot never cleared).
        $conversation->releaseFromBot();
        $session->update(['status' => 'completed', 'ended_at' => now()]);

        return null;
    }

    /**
     * Nodo "crear ticket": el chatbot abre un ticket trazable (con SLA) cuando no
     * resuelve. Va SIEMPRE por el contrato del core, así que si HelpdeskTickets
     * está deshabilitado el fallback devuelve null y el flujo degrada limpio.
     * El número de ticket queda en el contexto de la sesión y se comunica al
     * cliente. Idempotente: no crea un segundo ticket si el nodo se re-ejecuta.
     */
    private function executeCreateTicket(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $context = $session->context ?? [];

        if (! empty($context['created_ticket_number'])) {
            return $this->getFirstChildId($node, $session);
        }

        $result = app(TicketServiceContract::class)->createFromConversation($conversation, array_filter([
            'source' => 'chatflow',
            'subject' => $this->interpolateContext((string) ($data['subject'] ?? ''), $context) ?: null,
            'priority' => $data['priority'] ?? null,
            'category_id' => ! empty($data['category_id']) ? (int) $data['category_id'] : null,
        ], fn ($v) => $v !== null && $v !== ''));

        if ($result === null) {
            Log::warning('ChatFlow create_ticket: Tickets no disponible; se omite', [
                'conversation_id' => $conversation->id,
                'session_id' => $session->id,
            ]);

            return $this->getFirstChildId($node, $session);
        }

        $session->update([
            'context' => array_merge($context, [
                'created_ticket_id' => $result['id'],
                'created_ticket_number' => $result['ticket_number'],
            ]),
        ]);

        $confirmation = $this->localizeForCustomer(
            $data['confirmation'] ?? 'He creado el ticket :number para dar seguimiento a tu solicitud.',
            $session,
        );

        $this->postBotMessage(
            $conversation,
            $node['id'],
            str_replace([':number', '{{ticket_number}}'], $result['ticket_number'], $confirmation),
        );

        return $this->getFirstChildId($node, $session);
    }

    private function executeAddTag(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $tags = $node['data']['tags'] ?? [];

        if (! empty($tags)) {
            $this->attachTags($conversation, $tags);

            $context = $session->context ?? [];
            $existing = $context['added_tags'] ?? [];
            $session->update([
                'context' => array_merge($context, ['added_tags' => array_values(array_unique(array_merge($existing, $tags)))]),
            ]);
        }

        return $this->getFirstChildId($node, $session);
    }

    /**
     * Attaches each tag name to the conversation (find-or-create by slug, same
     * pattern as AutoTagService::categorizeAndAttach) and, when the
     * conversation has a linked customer, to the customer too — the editor's
     * add_tag node explicitly documents "se agregan a la conversación y al
     * cliente". Both add_tag (dedicated node) and the action node's add_tag
     * option used to only write to `context.added_tags`, a flow-only variable
     * nothing outside the flow could see.
     *
     * @param  array<int, mixed>  $names
     */
    private function attachTags(Conversation $conversation, array $names): void
    {
        $customer = $conversation->customer;

        foreach ($names as $name) {
            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            $slug = Str::slug($name);
            $tag = ConversationTag::query()->where('slug', $slug)->first()
                ?? ConversationTag::query()->create(['name' => $name, 'slug' => $slug, 'is_active' => true]);

            if (! $conversation->conversationTags()->where('tag_id', $tag->id)->exists()) {
                $conversation->conversationTags()->attach($tag->id);
            }

            if ($customer) {
                $customerTag = CustomerTag::findOrCreateByName($name);
                if (! $customer->tags()->where('tag_id', $customerTag->id)->exists()) {
                    $customer->tags()->attach($customerTag->id);
                }
            }
        }
    }

    private function executeSetAttribute(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $attribute = $data['attribute'] ?? '';
        $value = $this->interpolateContext($data['value'] ?? '', $session->context ?? []);

        match ($attribute) {
            'priority' => $conversation->update(['priority' => $value]),
            'status' => $conversation->update(['status_id' => (int) $value]),
            'assignee' => $conversation->update(['assignee_id' => (int) $value]),
            'custom' => $session->update([
                'context' => array_merge($session->context ?? [], [$data['custom_key'] ?? $attribute => $value]),
            ]),
            default => null,
        };

        return $this->getFirstChildId($node, $session);
    }

    private function executeEnd(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $isTransfer = ($data['action'] ?? '') === 'transfer_to_agent';

        // Release in every case (see executeClose() for why a "resolved"
        // ending must not leave the conversation permanently hidden).
        $conversation->releaseFromBot();
        $session->update(['status' => $isTransfer ? 'transferred' : 'completed', 'ended_at' => now()]);

        return null;
    }
}
