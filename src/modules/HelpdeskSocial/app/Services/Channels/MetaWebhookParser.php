<?php

namespace Modules\HelpdeskSocial\Services\Channels;

use Carbon\Carbon;
use Modules\HelpdeskSocial\Contracts\WebhookParserInterface;

class MetaWebhookParser implements WebhookParserInterface
{
    public function parse(array $payload): array
    {
        $object = $payload['object'] ?? '';

        return match ($object) {
            'page' => $this->parsePagePayload($payload),
            'instagram' => $this->parseInstagramPayload($payload),
            default => [],
        };
    }

    public function supports(array $payload): bool
    {
        $object = $payload['object'] ?? '';

        return in_array($object, ['page', 'instagram'], true) && isset($payload['entry']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parsePagePayload(array $payload): array
    {
        $events = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            $pageId = $entry['id'];

            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                $field = $change['field'] ?? '';

                if ($field === 'feed' && ($value['item'] ?? '') === 'comment') {
                    $events[] = [
                        'type' => 'comment',
                        'platform' => 'facebook',
                        'page_id' => $pageId,
                        'external_comment_id' => $value['comment_id'] ?? null,
                        'external_post_id' => $value['post_id'] ?? null,
                        'external_parent_id' => $value['parent_id'] ?? null,
                        'external_user_id' => $value['from']['id'] ?? null,
                        'author_name' => $value['from']['name'] ?? 'Usuario',
                        'author_username' => null,
                        'body' => $value['message'] ?? '',
                        'created_at' => isset($value['created_time']) ? Carbon::parse($value['created_time']) : now(),
                        'is_mention' => false,
                        'attachments' => [],
                        'metadata' => [
                            'verb' => $value['verb'] ?? 'add',
                            'item' => $value['item'] ?? 'comment',
                        ],
                    ];
                }

                if ($field === 'mentions') {
                    $events[] = [
                        'type' => 'mention',
                        'platform' => 'facebook',
                        'page_id' => $pageId,
                        'external_comment_id' => $value['comment_id'] ?? null,
                        'external_post_id' => $value['post_id'] ?? null,
                        'external_user_id' => $value['sender_id'] ?? null,
                        'author_name' => $value['sender_name'] ?? 'Usuario',
                        'author_username' => null,
                        'body' => $value['message'] ?? '',
                        'created_at' => isset($value['created_time']) ? Carbon::parse($value['created_time']) : now(),
                        'is_mention' => true,
                        'attachments' => [],
                        'metadata' => ['field' => 'mentions'],
                    ];
                }
            }

            foreach ($entry['messaging'] ?? [] as $messaging) {
                $events[] = $this->parseMessengerEvent($messaging, $pageId);
            }
        }

        return $events;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseInstagramPayload(array $payload): array
    {
        $events = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            $instagramId = $entry['id'];

            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                if (($change['field'] ?? '') === 'mentions') {
                    $events[] = [
                        'type' => 'mention',
                        'platform' => 'instagram',
                        'page_id' => $instagramId,
                        'external_comment_id' => $value['comment_id'] ?? null,
                        'external_post_id' => $value['media_id'] ?? null,
                        'external_user_id' => $value['sender_id'] ?? null,
                        'author_name' => $value['sender_username'] ?? 'Usuario',
                        'author_username' => $value['sender_username'] ?? null,
                        'body' => $value['text'] ?? '',
                        'created_at' => isset($value['created_time']) ? Carbon::parse($value['created_time']) : now(),
                        'is_mention' => true,
                        'attachments' => [],
                        'metadata' => ['field' => 'mentions'],
                    ];
                }
            }

            foreach ($entry['messaging'] ?? [] as $messaging) {
                $events[] = $this->parseInstagramDmEvent($messaging, $instagramId);
            }
        }

        return $events;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseMessengerEvent(array $messaging, string $pageId): array
    {
        $senderId = $messaging['sender']['id'] ?? null;
        $timestamp = (int) ($messaging['timestamp'] ?? 0);

        if ($messaging['message']['is_echo'] ?? false) {
            return [
                'type' => 'echo',
                'platform' => 'facebook',
                'page_id' => $pageId,
                'external_user_id' => $senderId,
                'created_at' => $timestamp ? Carbon::createFromTimestampMs($timestamp) : now(),
                'is_mention' => false,
                'attachments' => [],
                'metadata' => ['is_echo' => true],
            ];
        }

        $message = $messaging['message'] ?? [];
        $postback = $messaging['postback'] ?? null;

        $attachments = [];
        foreach ($message['attachments'] ?? [] as $att) {
            $attachments[] = [
                'type' => $att['type'],
                'url' => $att['payload']['url'] ?? null,
            ];
        }

        return [
            'type' => $postback ? 'postback' : 'message',
            'platform' => 'facebook',
            'page_id' => $pageId,
            'external_comment_id' => $message['mid'] ?? null,
            'external_user_id' => $senderId,
            'author_name' => $messaging['sender']['name'] ?? 'Usuario',
            'author_username' => null,
            'body' => $message['text'] ?? null,
            'created_at' => $timestamp ? Carbon::createFromTimestampMs($timestamp) : now(),
            'is_mention' => false,
            'attachments' => $attachments,
            'quick_reply' => $message['quick_reply'] ?? null,
            'metadata' => [
                'postback' => $postback ? [
                    'title' => $postback['title'] ?? null,
                    'payload' => $postback['payload'] ?? null,
                ] : null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseInstagramDmEvent(array $messaging, string $instagramId): array
    {
        $senderId = $messaging['sender']['id'] ?? null;
        $timestamp = (int) ($messaging['timestamp'] ?? 0);

        if ($messaging['message']['is_echo'] ?? false) {
            return [
                'type' => 'echo',
                'platform' => 'instagram',
                'page_id' => $instagramId,
                'external_user_id' => $senderId,
                'created_at' => $timestamp ? Carbon::createFromTimestampMs($timestamp) : now(),
                'is_mention' => false,
                'attachments' => [],
                'metadata' => ['is_echo' => true],
            ];
        }

        $message = $messaging['message'] ?? [];

        $attachments = [];
        foreach ($message['attachments'] ?? [] as $att) {
            $attachments[] = [
                'type' => $att['type'],
                'url' => $att['payload']['url'] ?? null,
            ];
        }

        return [
            'type' => 'message',
            'platform' => 'instagram',
            'page_id' => $instagramId,
            'external_comment_id' => $message['mid'] ?? null,
            'external_user_id' => $senderId,
            'author_name' => $messaging['sender']['name'] ?? 'Usuario',
            'author_username' => null,
            'body' => $message['text'] ?? null,
            'created_at' => $timestamp ? Carbon::createFromTimestampMs($timestamp) : now(),
            'is_mention' => false,
            'attachments' => $attachments,
            'metadata' => [
                'is_ephemeral' => $message['is_ephemeral'] ?? false,
            ],
        ];
    }
}
