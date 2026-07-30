<?php

namespace Modules\HelpdeskContacts\Services;

use Modules\Helpdesk\Models\Customer;
use Nwidart\Modules\Facades\Module;

/**
 * Aggregates a customer's chat history across live chat and social channels.
 *
 * Every optional module is resolved through Module::find() + class_exists()
 * guards so the Helpdesk panel never breaks when a module is disabled.
 */
class ContactChatsService
{
    /**
     * FQCNs of optional-module classes, kept as plain string literals (never
     * top-of-file imports, never ::class) so they are only ever resolved inside
     * Module::find() + class_exists() guarded blocks.
     */
    private const WIDGET_SESSION = 'Modules\\HelpdeskLivechat\\Models\\WidgetSession';

    private const SOCIAL_CONVERSATION = 'Modules\\HelpdeskSocial\\Models\\SocialConversation';

    /**
     * Build the chats payload for the conversaciones pane second section.
     *
     * @return array{available: bool, chats: array<int, array{source: string, icon: string, preview: string, at: ?string, meta: array<string, mixed>, url: ?string}>}
     */
    public function forCustomer(Customer $customer): array
    {
        $livechatOn = $this->moduleEnabled('HelpdeskLivechat')
            && class_exists(self::WIDGET_SESSION);

        $socialOn = $this->moduleEnabled('HelpdeskSocial')
            && class_exists(self::SOCIAL_CONVERSATION);

        $chats = [];

        if ($livechatOn) {
            $chats = array_merge($chats, $this->livechatSessions($customer));
        }

        if ($socialOn) {
            $chats = array_merge($chats, $this->socialConversations($customer));
        }

        usort($chats, fn ($a, $b) => strcmp((string) $b['at'], (string) $a['at']));

        return [
            'available' => $livechatOn || $socialOn,
            'chats' => array_values($chats),
        ];
    }

    /**
     * Live chat sessions from the widget, mapped to the chats contract shape.
     *
     * @return array<int, array<string, mixed>>
     */
    private function livechatSessions(Customer $customer): array
    {
        $model = app(self::WIDGET_SESSION);

        $sessions = $model->newQuery()
            ->where('customer_id', $customer->id)
            ->latest('last_activity_at')
            ->limit(20)
            ->get();

        return $sessions->map(function ($session): array {
            $device = is_array($session->device) ? $session->device : [];

            return [
                'source' => 'livechat',
                'icon' => 'fas fa-comment-dots',
                'preview' => $session->current_url ?: 'Sesion de chat web',
                'at' => $session->last_activity_at?->toIso8601String()
                    ?? $session->started_at?->toIso8601String(),
                'meta' => [
                    'device' => $device['type'] ?? ($device['platform'] ?? null),
                    'browser' => $device['browser'] ?? null,
                    'ip' => $session->ip_address,
                    'country' => $session->country_code,
                ],
                'url' => null,
            ];
        })->all();
    }

    /**
     * Social conversations matched by the customer's external social IDs.
     * Only runs when the (currently disabled) HelpdeskSocial module is enabled.
     *
     * @return array<int, array<string, mixed>>
     */
    private function socialConversations(Customer $customer): array
    {
        $externalIds = array_filter([
            'facebook' => $customer->facebook_psid,
            'instagram' => $customer->instagram_id,
            'whatsapp' => $customer->whatsapp_phone,
        ]);

        if (empty($externalIds)) {
            return [];
        }

        $model = app(self::SOCIAL_CONVERSATION);

        $conversations = $model->newQuery()
            ->whereIn('participant_external_id', array_values($externalIds))
            ->latest('last_message_at')
            ->limit(20)
            ->get();

        return $conversations->map(fn ($conversation): array => [
            'source' => $this->socialSource((string) $conversation->platform),
            'icon' => $this->socialIcon((string) $conversation->platform),
            'preview' => $conversation->participant_name ?: 'Conversacion social',
            'at' => $conversation->last_message_at?->toIso8601String(),
            'meta' => [
                'platform' => $conversation->platform,
                'messages' => $conversation->message_count,
            ],
            'url' => null,
        ])->all();
    }

    private function socialSource(string $platform): string
    {
        return match ($platform) {
            'facebook', 'messenger' => 'facebook',
            'instagram' => 'instagram',
            'whatsapp' => 'whatsapp',
            default => 'facebook',
        };
    }

    private function socialIcon(string $platform): string
    {
        return match ($platform) {
            'instagram' => 'fab fa-instagram',
            'whatsapp' => 'fab fa-whatsapp',
            default => 'fab fa-facebook-messenger',
        };
    }

    private function moduleEnabled(string $name): bool
    {
        return Module::find($name)?->isEnabled() ?? false;
    }
}
