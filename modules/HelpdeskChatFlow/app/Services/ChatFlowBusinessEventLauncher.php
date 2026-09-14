<?php

namespace Modules\HelpdeskChatFlow\Services;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * Launches a proactive (outbound) chat flow in response to a business event
 * (abandoned cart, order status change, order ready). Resolves the helpdesk
 * customer, picks a deliverable channel (WhatsApp), finds the flow configured
 * for that event, and starts it with the event's context.
 */
class ChatFlowBusinessEventLauncher
{
    public function __construct(
        private readonly ChatFlowOutboundService $outbound,
    ) {}

    /**
     * @param  array{email?: ?string, name?: ?string, ps_id?: ?int, erp_id?: ?int}  $identity
     * @param  array<string, mixed>  $context
     */
    public function launch(string $businessEvent, array $identity, array $context = []): ?ChatFlowSession
    {
        $flow = $this->resolveFlow($businessEvent);
        if (! $flow) {
            return null; // no flow configured for this event → nothing to do
        }

        $customer = $this->resolveCustomer($identity);
        if (! $customer) {
            return null;
        }

        $channel = $this->preferredChannel($customer);
        if (! $channel) {
            return null; // no channel the bot can deliver to
        }

        $conversation = $this->findOrCreateConversation($customer, $channel);

        $seed = array_merge([
            'customer_name' => $customer->name,
            'business_event' => $businessEvent,
        ], $context);

        return $this->outbound->launch($flow, $conversation, $seed);
    }

    private function resolveFlow(string $businessEvent): ?ChatFlow
    {
        return ChatFlow::query()
            ->active()
            ->where('trigger_conditions->business_event', $businessEvent)
            ->orderByDesc('priority')
            ->first();
    }

    /**
     * @param  array{email?: ?string, name?: ?string, ps_id?: ?int, erp_id?: ?int}  $identity
     */
    private function resolveCustomer(array $identity): ?Customer
    {
        $email = $identity['email'] ?? null;

        // Reject syntactically invalid emails from manipulated event payloads.
        if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = null;
        }

        if ($email) {
            $existing = Customer::on('helpdesk')->where('email', $email)->first();
            if ($existing) {
                return $existing;
            }
        }

        if (! empty($identity['ps_id']) && ($c = Customer::findByExternalId('prestashop', (string) $identity['ps_id']))) {
            return $c;
        }

        if (! empty($identity['erp_id']) && ($c = Customer::findByExternalId('erp', (string) $identity['erp_id']))) {
            return $c;
        }

        if ($email) {
            return Customer::on('helpdesk')->create([
                'email' => $email,
                'name' => $identity['name'] ?? $email,
                'language' => 'es',
            ]);
        }

        return null;
    }

    /**
     * The bot can only deliver proactive messages over WhatsApp for now (email
     * outbound is not handled by the bot dispatcher).
     */
    private function preferredChannel(Customer $customer): ?string
    {
        return filled($customer->whatsapp_phone) ? 'whatsapp' : null;
    }

    private function findOrCreateConversation(Customer $customer, string $channel): Conversation
    {
        $existing = Conversation::on('helpdesk')
            ->where('customer_id', $customer->id)
            ->where('channel', $channel)
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        $statusId = ConversationStatus::on('helpdesk')
            ->where('is_default', true)
            ->orWhere('is_open', true)
            ->orderByDesc('is_default')
            ->value('id');

        $inboxId = Inbox::on('helpdesk')->where('channel_type', $channel)->value('id');

        return Conversation::on('helpdesk')->create([
            'customer_id' => $customer->id,
            'channel' => $channel,
            'inbox_id' => $inboxId,
            'external_sender_id' => $customer->whatsapp_phone,
            'subject' => 'Proactivo · '.$customer->name,
            'priority' => 'normal',
            'status_id' => $statusId,
            'last_message_at' => now(),
        ]);
    }
}
