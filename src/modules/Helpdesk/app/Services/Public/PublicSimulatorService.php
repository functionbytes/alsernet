<?php

namespace Modules\Helpdesk\Services\Public;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Helpdesk\Events\ConversationItemCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\InboxItemChanged;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;
use Modules\Helpdesk\Services\Webhooks\FacebookMessageProcessor;
use Modules\Helpdesk\Services\Webhooks\InstagramMessageProcessor;
use Modules\Helpdesk\Services\Webhooks\WhatsAppMessageProcessor;
use Modules\Helpdesk\Services\WhatsAppBusinessService;
use RuntimeException;

/**
 * Drives the PUBLIC multi-channel conversation simulator.
 *
 * It reuses the exact same inbound pipeline the real webhooks use — fabricating a
 * provider-shaped payload, parsing it with the channel service, then handing the
 * event to the channel's MessageProcessor — so a simulated message is
 * indistinguishable from a real one once it lands in the agent inbox. The first
 * message of a session also tags the conversation as a simulator session so that
 * outbound sends are suppressed (see {@see SimulatorOutboundMessageService}).
 */
class PublicSimulatorService
{
    /** Channels exposed by the public simulator. */
    public const CHANNELS = ['whatsapp', 'facebook', 'instagram', 'web'];

    public function __construct(
        private readonly WhatsAppBusinessService $whatsapp,
        private readonly FacebookMessengerService $facebook,
        private readonly InstagramService $instagram,
        private readonly WhatsAppMessageProcessor $whatsappProcessor,
        private readonly FacebookMessageProcessor $facebookProcessor,
        private readonly InstagramMessageProcessor $instagramProcessor,
    ) {}

    /**
     * Start a simulated session: inject the first inbound message through the real
     * channel pipeline, then tag the resulting conversation as a simulator session.
     *
     * @param  array{phone?: ?string, email?: ?string, prestashop_id?: ?int, gestion_id?: ?int}  $links
     * @return array{conversation_id: int, token: string, channel: string, item: array<string, mixed>}
     */
    public function startSession(string $channel, ?string $name, ?string $identifier, string $message, array $links = [], ?string $simulatedAt = null): array
    {
        $name = $this->cleanName($name);
        $sender = $this->resolveSender($channel, $identifier);
        $simulatedNow = $this->parseSimulatedAt($simulatedAt);

        $item = $this->inject($channel, $sender, $name, $message, $simulatedNow);

        if (! $item) {
            throw new RuntimeException('No se pudo iniciar la conversación simulada.');
        }

        $conversation = $item->conversation;
        $token = Str::random(40);

        // Friendly display name: the Facebook/Instagram processors use the raw
        // sender id as the customer name (the real webhook resolves the profile
        // name via Graph, which the simulator never calls). Apply the entered
        // name so the inbox shows a person, not an opaque PSID/IG id.
        $customer = $conversation->customer;
        if ($customer && $customer->name === $sender && $customer->name !== $name) {
            $customer->forceFill(['name' => $name])->save();
        }

        // Read the freshest metadata from DB: the bot may have written
        // metadata.handled_by_bot inside the inbound pipeline (chat flow), and a
        // stale in-memory instance would otherwise overwrite it here.
        $currentMeta = $conversation->fresh()?->metadata ?? $conversation->metadata ?? [];

        $conversation->forceFill([
            'metadata' => array_merge($currentMeta, array_filter([
                'is_simulator' => true,
                'sim_channel' => $channel,
                'sim_token' => $token,
                // Reused by the public widget broadcast-auth channel convention.
                'widget_pubsub_token' => $token,
                // Persist the simulated clock so follow-up inbound messages stay in it.
                'sim_now' => $simulatedNow?->toIso8601String(),
            ], fn ($v) => $v !== null)),
        ])->save();

        $this->linkCustomer($customer, $links);

        return [
            'conversation_id' => $conversation->id,
            'token' => $token,
            'channel' => $channel,
            'item' => $this->present($item),
        ];
    }

    /**
     * Inject a follow-up inbound (customer) message into an existing simulated
     * conversation, reusing the same channel + sender so the pipeline appends to
     * the same thread.
     */
    public function injectInbound(Conversation $conversation, string $message): ConversationItem
    {
        $channel = (string) data_get($conversation->metadata, 'sim_channel', $conversation->channel ?? 'web');
        $sender = $this->senderFromConversation($conversation, $channel);
        $name = $this->cleanName($conversation->customer?->name);
        $simulatedNow = $this->parseSimulatedAt(data_get($conversation->metadata, 'sim_now'));

        $item = $this->inject($channel, $sender, $name, $message, $simulatedNow);

        if (! $item) {
            throw new RuntimeException('No se pudo registrar el mensaje (posible duplicado).');
        }

        return $item;
    }

    /**
     * Return conversation messages newer than $afterId, oldest first, ready for
     * the panel. Internal agent notes are excluded.
     *
     * @return array<int, array<string, mixed>>
     */
    public function messagesSince(Conversation $conversation, ?int $afterId = null, int $limit = 100): array
    {
        return ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('type', 'message')
            ->where(fn ($q) => $q->whereNull('is_internal')->orWhere('is_internal', false))
            ->when($afterId, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (ConversationItem $item) => $this->present($item))
            ->all();
    }

    /**
     * Normalize a ConversationItem into the flat shape the panel renders.
     *
     * @return array{id: int, body: string, from: string, sender_name: string, created_at: ?string, attachments: array<int, mixed>, link_preview: mixed, content_type: string}
     */
    public function present(ConversationItem $item): array
    {
        $isAgent = ! empty($item->user_id) || data_get($item->metadata, 'injected_as_agent', false);

        $attachments = $item->attachment_urls ?? [];
        if (! is_array($attachments)) {
            $attachments = [];
        }

        return [
            'id' => $item->id,
            'body' => (string) $item->body,
            'from' => $isAgent ? 'agent' : 'customer',
            'sender_name' => $isAgent ? ($item->user?->name ?? 'Agente') : 'Tú',
            'created_at' => optional($item->created_at)->toIso8601String(),
            'attachments' => $attachments,
            'link_preview' => $item->metadata['link_preview'] ?? null,
            'content_type' => $this->resolveContentType($attachments),
            'type' => $item->type ?? 'message',
            // Numbered options the bot offered (native buttons/quick replies on real channels).
            'quick_replies' => data_get($item->metadata, 'bot_options', data_get($item->metadata, 'quick_replies', [])),
            // Carousel cards (products) for native rendering in the preview.
            'cards' => data_get($item->metadata, 'cards', []),
        ];
    }

    /**
     * @param  array<int, mixed>  $attachments
     */
    private function resolveContentType(array $attachments): string
    {
        if (empty($attachments)) {
            return 'text';
        }

        $mime = $attachments[0]['mime_type'] ?? '';

        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'audio/') => 'audio',
            str_starts_with($mime, 'video/') => 'video',
            default => 'file',
        };
    }

    /**
     * Dispatch the inbound injection to the right channel handler, wrapped so that
     * any synchronous outbound triggered by automations is suppressed.
     */
    private function inject(string $channel, string $sender, string $name, string $message, ?CarbonInterface $now = null): ?ConversationItem
    {
        return SimulatorContext::run(fn (): ?ConversationItem => match ($channel) {
            'whatsapp' => $this->injectWhatsApp($sender, $name, $message),
            'facebook' => $this->injectMeta('facebook', $sender, $message),
            'instagram' => $this->injectMeta('instagram', $sender, $message),
            'web' => $this->injectWeb($sender, $name, $message),
            default => null,
        }, $now);
    }

    /**
     * Parse an optional simulated datetime string into a Carbon instance, or null
     * when absent/invalid (the real clock is then used).
     */
    private function parseSimulatedAt(?string $value): ?CarbonInterface
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function injectWhatsApp(string $phone, string $name, string $message): ?ConversationItem
    {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'WABA_SIM',
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['display_phone_number' => '+15551234567', 'phone_number_id' => 'PHONE_NUMBER_ID'],
                        'contacts' => [['profile' => ['name' => $name], 'wa_id' => $phone]],
                        'messages' => [[
                            'from' => $phone,
                            'id' => 'wamid.sim_'.uniqid('', true),
                            'timestamp' => (string) time(),
                            'type' => 'text',
                            'text' => ['body' => $message],
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ];

        foreach ($this->whatsapp->parseWebhookPayload($payload) as $event) {
            if (($event['type'] ?? null) === 'message') {
                return $this->whatsappProcessor->process($event);
            }
        }

        return null;
    }

    private function injectMeta(string $platform, string $senderId, string $message): ?ConversationItem
    {
        [$service, $processor, $payload] = $platform === 'facebook'
            ? [$this->facebook, $this->facebookProcessor, [
                'object' => 'page',
                'entry' => [['id' => 'PAGE_ID', 'time' => time(), 'messaging' => [[
                    'sender' => ['id' => $senderId], 'recipient' => ['id' => 'PAGE_ID'], 'timestamp' => time(),
                    'message' => ['mid' => 'm_sim_'.uniqid('', true), 'text' => $message],
                ]]]],
            ]]
            : [$this->instagram, $this->instagramProcessor, [
                'object' => 'instagram',
                'entry' => [['id' => 'IG_ACCOUNT_ID', 'time' => time(), 'messaging' => [[
                    'sender' => ['id' => $senderId], 'recipient' => ['id' => 'IG_ACCOUNT_ID'], 'timestamp' => time(),
                    'message' => ['mid' => 'ig_sim_'.uniqid('', true), 'text' => $message],
                ]]]],
            ]];

        foreach ($service->parseWebhookPayload($payload) as $event) {
            if (in_array($event['type'] ?? null, ['message', 'story_reply'], true)) {
                return $processor->process($event);
            }
        }

        return null;
    }

    /**
     * Web channel has no external webhook pipeline, so persist the inbound item
     * directly — mirroring exactly what the social processors do (events +
     * broadcast + last_message_at) so the agent inbox updates live.
     */
    private function injectWeb(string $email, string $name, string $message): ConversationItem
    {
        return DB::connection('helpdesk')->transaction(function () use ($email, $name, $message): ConversationItem {
            $customer = Customer::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'language' => 'es'],
            );

            $conversation = $this->findOrCreateWebConversation($customer);

            $item = ConversationItem::create([
                'conversation_id' => $conversation->id,
                'author_id' => $customer->id,
                'type' => 'message',
                'body' => $message,
                'external_id' => 'web_sim_'.uniqid('', true),
                'metadata' => ['platform' => 'web', 'simulator' => true],
            ]);

            $conversation->update(['last_message_at' => now()]);

            broadcast(new ConversationMessageCreated($item));
            event(new ConversationItemCreated($item));

            if ($conversation->assignee_id) {
                event(new InboxItemChanged($conversation->id, $conversation->assignee_id, 'message_added'));
            }

            return $item;
        });
    }

    private function findOrCreateWebConversation(Customer $customer): Conversation
    {
        $existing = Conversation::query()
            ->where('channel', 'web')
            ->where('customer_id', $customer->id)
            ->whereNull('closed_at')
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        $statusId = ConversationStatus::query()
            ->where('is_default', true)
            ->orWhere('is_open', true)
            ->orderByDesc('is_default')
            ->value('id');

        return Conversation::create([
            'customer_id' => $customer->id,
            'channel' => 'web',
            'external_sender_id' => null,
            'subject' => 'Web · '.$customer->name,
            'priority' => 'normal',
            'status_id' => $statusId,
            'last_message_at' => now(),
        ]);
    }

    /**
     * Build the sender identifier for the first message of a session, generating a
     * unique one when the visitor leaves it blank (so each start is a fresh thread).
     */
    private function resolveSender(string $channel, ?string $identifier): string
    {
        $identifier = trim((string) $identifier);

        if ($identifier !== '') {
            return $identifier;
        }

        return match ($channel) {
            'whatsapp' => '34'.random_int(600000000, 699999999),
            'facebook' => 'PSID_sim_'.Str::upper(Str::random(10)),
            'instagram' => 'IG_sim_'.Str::upper(Str::random(10)),
            'web' => 'guest-'.Str::lower(Str::random(8)).'@simulador.local',
            default => 'sim_'.Str::random(10),
        };
    }

    private function senderFromConversation(Conversation $conversation, string $channel): string
    {
        $customer = $conversation->customer;

        return match ($channel) {
            'whatsapp' => (string) ($conversation->external_sender_id ?: $customer?->whatsapp_phone),
            'facebook', 'instagram' => (string) $conversation->external_sender_id,
            'web' => (string) $customer?->email,
            default => (string) $conversation->external_sender_id,
        };
    }

    private function cleanName(?string $name): string
    {
        $name = trim((string) $name);

        return $name !== '' ? $name : 'Cliente simulado';
    }

    /**
     * Apply optional customer data and e-commerce links so the agent inbox shows
     * PrestaShop / gestión (ERP) context (orders, etc.) for the simulated customer.
     * Mirrors HelpdeskSimulatorController::linkCustomer().
     *
     * @param  array{phone?: ?string, email?: ?string, prestashop_id?: ?int, gestion_id?: ?int}  $links
     */
    private function linkCustomer(?Customer $customer, array $links): void
    {
        if (! $customer) {
            return;
        }

        try {
            $phone = trim((string) ($links['phone'] ?? ''));
            $email = trim((string) ($links['email'] ?? ''));
            $dirty = false;

            if ($phone !== '' && blank($customer->phone)) {
                $customer->phone = $phone;
                $dirty = true;
            }

            if ($email !== '' && blank($customer->email)) {
                $customer->email = $email;
                $dirty = true;
            }

            if ($dirty) {
                $customer->save();
            }

            if (! empty($links['prestashop_id'])) {
                $customer->linkExternalId('prestashop', (string) $links['prestashop_id'], ['linked_via' => 'public-simulator']);
            }

            if (! empty($links['gestion_id'])) {
                $customer->linkExternalId('erp', (string) $links['gestion_id'], ['linked_via' => 'public-simulator']);
            }
        } catch (\Throwable $e) {
            Log::warning('PublicSimulator: no se pudo vincular el cliente', ['error' => $e->getMessage()]);
        }
    }
}
