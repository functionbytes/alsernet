<?php

namespace Modules\Helpdesk\Services\Conversations;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Conversation;

/**
 * Presencia de agentes en una conversación: quién la está mirando o
 * respondiendo, con un heartbeat de TTL corto sobre el cache. Los caducados
 * se purgan al leer.
 */
class ConversationPresenceService
{
    private const TTL_SECONDS = 60;

    private const STALE_SECONDS = 35;

    public function touch(Conversation|int $conversation, User $user, string $action): void
    {
        $id = $this->idOf($conversation);
        $now = now()->timestamp;

        $viewers = $this->prune($this->all($id), $now);
        $viewers[$user->id] = [
            'user_id' => $user->id,
            'name' => $this->displayName($user),
            'action' => $action === 'replying' ? 'replying' : 'viewing',
            'at' => $now,
        ];

        Cache::put($this->key($id), $viewers, self::TTL_SECONDS);
    }

    public function leave(Conversation|int $conversation, User $user): void
    {
        $id = $this->idOf($conversation);

        $viewers = $this->prune($this->all($id), now()->timestamp);
        unset($viewers[$user->id]);

        if ($viewers === []) {
            Cache::forget($this->key($id));

            return;
        }

        Cache::put($this->key($id), $viewers, self::TTL_SECONDS);
    }

    /**
     * @return list<array{user_id: int, name: string, action: string}>
     */
    public function viewers(int $conversationId, ?int $exceptUserId = null): array
    {
        $viewers = $this->prune($this->all($conversationId), now()->timestamp);

        return $this->present($viewers, $exceptUserId);
    }

    /**
     * Lectura sin escritura para varias conversaciones; omite las que no
     * tienen a nadie.
     *
     * @param  array<int, int>  $ids
     * @return array<int, list<array{user_id: int, name: string, action: string}>>
     */
    public function viewersForMany(array $ids, ?int $exceptUserId = null): array
    {
        $result = [];

        foreach ($ids as $id) {
            $viewers = $this->viewers((int) $id, $exceptUserId);

            if ($viewers !== []) {
                $result[(int) $id] = $viewers;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $viewers
     * @return list<array{user_id: int, name: string, action: string}>
     */
    private function present(array $viewers, ?int $exceptUserId): array
    {
        $others = array_filter(
            $viewers,
            fn (array $viewer) => $viewer['user_id'] !== $exceptUserId
        );

        return array_values(array_map(fn (array $viewer) => [
            'user_id' => $viewer['user_id'],
            'name' => $viewer['name'],
            'action' => $viewer['action'],
        ], $others));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function all(int $conversationId): array
    {
        return (array) Cache::get($this->key($conversationId), []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $viewers
     * @return array<int, array<string, mixed>>
     */
    private function prune(array $viewers, int $now): array
    {
        return array_filter(
            $viewers,
            fn (array $viewer) => ($now - ($viewer['at'] ?? 0)) < self::STALE_SECONDS
        );
    }

    private function idOf(Conversation|int $conversation): int
    {
        return $conversation instanceof Conversation ? $conversation->id : $conversation;
    }

    private function displayName(User $user): string
    {
        $name = trim((string) $user->full_name);

        return $name !== '' ? $name : 'Agente #'.$user->id;
    }

    private function key(int $conversationId): string
    {
        return "helpdesk:conversation:{$conversationId}:viewers";
    }
}
