<?php

namespace Modules\Helpdesk\Services;

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Modules\Helpdesk\Events\AgentPresenceChanged;
use Modules\Helpdesk\Models\AgentSettings;

class AgentPresenceService
{
    private const REDIS_KEY = 'helpdesk:presence:agents';

    private const ONLINE_TTL = 90; // seconds

    public function setState(int $userId, string $state): void
    {
        $settings = AgentSettings::query()->firstOrCreate(
            ['user_id' => $userId],
            ['presence_state' => AgentSettings::PRESENCE_OFFLINE]
        );

        $oldState = $settings->presence_state ?? AgentSettings::PRESENCE_OFFLINE;

        if ($oldState === $state) {
            return;
        }

        $settings->update([
            'presence_state' => $state,
            'presence_changed_at' => now(),
        ]);

        $user = User::find($userId);

        if ($user) {
            AgentPresenceChanged::dispatch($user, $oldState, $state);
        }

        if ($state !== AgentSettings::PRESENCE_OFFLINE) {
            $this->heartbeat($userId);
        }
    }

    public function getState(int $userId): string
    {
        $ttl = Redis::ttl(self::REDIS_KEY.':'.$userId);

        if ($ttl <= 0) {
            return AgentSettings::PRESENCE_OFFLINE;
        }

        return AgentSettings::query()
            ->where('user_id', $userId)
            ->value('presence_state') ?? AgentSettings::PRESENCE_OFFLINE;
    }

    public function heartbeat(int $userId): void
    {
        Redis::setex(self::REDIS_KEY.':'.$userId, self::ONLINE_TTL, 1);

        AgentSettings::query()
            ->where('user_id', $userId)
            ->update(['last_heartbeat_at' => now()]);
    }

    public function getOnlineAgents(): array
    {
        $userIds = AgentSettings::query()
            ->whereNotNull('last_heartbeat_at')
            ->where('last_heartbeat_at', '>=', now()->subSeconds(self::ONLINE_TTL))
            ->pluck('user_id')
            ->all();

        return array_filter($userIds, fn ($id) => Redis::ttl(self::REDIS_KEY.':'.$id) > 0);
    }

    public function cleanup(): int
    {
        $stale = AgentSettings::query()
            ->where('presence_state', '!=', AgentSettings::PRESENCE_OFFLINE)
            ->where(function ($q) {
                $q->whereNull('last_heartbeat_at')
                    ->orWhere('last_heartbeat_at', '<', now()->subSeconds(self::ONLINE_TTL));
            })
            ->get();

        $count = 0;

        foreach ($stale as $settings) {
            $redisKey = self::REDIS_KEY.':'.$settings->user_id;

            if (Redis::ttl($redisKey) > 0) {
                continue;
            }

            $oldState = $settings->presence_state;

            $settings->update([
                'presence_state' => AgentSettings::PRESENCE_OFFLINE,
                'presence_changed_at' => now(),
            ]);

            $user = User::find($settings->user_id);

            if ($user) {
                AgentPresenceChanged::dispatch($user, $oldState, AgentSettings::PRESENCE_OFFLINE);
            }

            $count++;
        }

        return $count;
    }

    public function getAgentsList(?int $inboxId = null): array
    {
        $query = AgentSettings::query()
            ->with('user:id,name,email');

        if ($inboxId !== null) {
            $query->whereHas('user', function ($q) use ($inboxId) {
                $q->whereHas('inboxMemberships', fn ($m) => $m->where('inbox_id', $inboxId));
            });
        }

        return $query->get()->map(function (AgentSettings $settings) {
            $user = $settings->user;

            return [
                'user_id' => $settings->user_id,
                'name' => $user?->name ?? 'Unknown',
                'email' => $user?->email ?? '',
                'presence_state' => $this->getState($settings->user_id),
                'last_heartbeat_at' => $settings->last_heartbeat_at?->toIso8601String(),
                'presence_changed_at' => $settings->presence_changed_at?->toIso8601String(),
            ];
        })->values()->all();
    }
}
