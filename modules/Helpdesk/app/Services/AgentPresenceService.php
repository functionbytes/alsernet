<?php

namespace Modules\Helpdesk\Services;

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Modules\Helpdesk\Events\AgentPresenceChanged;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Conversation;

class AgentPresenceService
{
    private const REDIS_KEY = 'helpdesk:presence:agents';

    private const ONLINE_TTL = 90; // seconds

    /**
     * @param  array<string, mixed>|null  $preferences  Away-mode preferences to
     *                                                  merge into agent_settings.preferences
     *                                                  (away_message, auto_return, reassign,
     *                                                  reassign_agent_id).
     */
    public function setState(int $userId, string $state, ?array $preferences = null): void
    {
        $settings = AgentSettings::query()->firstOrCreate(
            ['user_id' => $userId],
            ['presence_state' => AgentSettings::PRESENCE_OFFLINE]
        );

        if ($preferences !== null) {
            $settings->update([
                'preferences' => array_merge($settings->preferences ?? [], $preferences),
            ]);
        }

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

        $this->clearWidgetConfigCache($userId);
    }

    /**
     * Current agent presence state plus the persisted away-mode preferences,
     * used to hydrate the "Cambiar disponibilidad" modal.
     *
     * @return array{state: string, raw_state: string, away_message: string, auto_return: string, reassign: string, reassign_agent_id: int|null}
     */
    public function getSettings(int $userId): array
    {
        $settings = AgentSettings::query()->where('user_id', $userId)->first();
        $prefs = $settings?->preferences ?? [];

        return [
            'state' => $this->getState($userId),
            'raw_state' => $settings?->presence_state ?? AgentSettings::PRESENCE_OFFLINE,
            'away_message' => $prefs['away_message'] ?? '',
            'auto_return' => $prefs['auto_return'] ?? 'manual',
            'reassign' => $prefs['reassign'] ?? 'keep',
            'reassign_agent_id' => $prefs['reassign_agent_id'] ?? null,
        ];
    }

    /**
     * Unassign the agent's open conversations so they return to the inbox queue,
     * or hand them off to a specific agent when $toUserId is given. Called when
     * an agent goes away/busy/offline with the "reassign" preference set to
     * "team" (null $toUserId) or "agent" (specific $toUserId). Returns the
     * number of conversations released/reassigned.
     */
    public function reassignActiveConversations(int $userId, ?int $toUserId = null): int
    {
        $count = Conversation::query()
            ->where('assignee_id', $userId)
            ->open()
            ->update(['assignee_id' => $toUserId]);

        if ($count > 0) {
            AgentSettings::query()
                ->where('user_id', $userId)
                ->update(['current_open_count' => 0]);
        }

        return $count;
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

    /**
     * Returns true when at least one agent with an active Redis heartbeat is
     * assigned to the given inbox and is in an available or busy state.
     * Used by the livechat widget to show real-time agent availability.
     */
    public function hasAvailableAgentsForInbox(int $inboxId): bool
    {
        $onlineIds = $this->getOnlineAgents();

        if (empty($onlineIds)) {
            return false;
        }

        return AgentSettings::query()
            ->whereIn('user_id', $onlineIds)
            ->whereIn('presence_state', [AgentSettings::PRESENCE_AVAILABLE, AgentSettings::PRESENCE_BUSY])
            ->whereExists(fn ($q) => $q
                ->selectRaw('1')
                ->from('helpdesk_agent_inbox_capacity')
                ->whereColumn('helpdesk_agent_inbox_capacity.user_id', 'helpdesk_agent_settings.user_id')
                ->where('helpdesk_agent_inbox_capacity.inbox_id', $inboxId))
            ->exists();
    }

    /**
     * Forget the livechat widget config cache for every Web channel whose inbox
     * the given agent belongs to, so the next widget load reflects the new
     * presence state without waiting for the full 5-minute TTL.
     */
    private function clearWidgetConfigCache(int $userId): void
    {
        $inboxIds = \DB::connection('helpdesk')
            ->table('helpdesk_agent_inbox_capacity')
            ->where('user_id', $userId)
            ->pluck('inbox_id');

        if ($inboxIds->isEmpty()) {
            return;
        }

        $channelIds = \DB::connection('helpdesk')
            ->table('helpdesk_channel_webs')
            ->join('helpdesk_inboxes', 'helpdesk_inboxes.channel_id', '=', 'helpdesk_channel_webs.id')
            ->where('helpdesk_inboxes.channel_type', 'Modules\\HelpdeskLivechat\\Models\\Channels\\Web')
            ->whereIn('helpdesk_inboxes.id', $inboxIds)
            ->pluck('helpdesk_channel_webs.id');

        foreach ($channelIds as $channelId) {
            \Cache::forget("widget_config_{$channelId}");
        }
    }

    public function getAgentsList(?int $inboxId = null): array
    {
        $query = AgentSettings::query()
            ->with('user:id,firstname,lastname,email');

        if ($inboxId !== null) {
            $query->whereHas('user', function ($q) use ($inboxId) {
                $q->whereHas('inboxMemberships', fn ($m) => $m->where('inbox_id', $inboxId));
            });
        }

        return $query->get()->map(function (AgentSettings $settings) {
            $user = $settings->user;

            // Sin re-consultar la BD por agente: reutiliza la columna ya cargada y
            // solo comprueba el heartbeat en Redis (offline si expiró).
            $presence = Redis::ttl(self::REDIS_KEY.':'.$settings->user_id) > 0
                ? ($settings->presence_state ?? AgentSettings::PRESENCE_OFFLINE)
                : AgentSettings::PRESENCE_OFFLINE;

            return [
                'user_id' => $settings->user_id,
                'name' => $user?->name ?? 'Unknown',
                'email' => $user?->email ?? '',
                'presence_state' => $presence,
                'last_heartbeat_at' => $settings->last_heartbeat_at?->toIso8601String(),
                'presence_changed_at' => $settings->presence_changed_at?->toIso8601String(),
            ];
        })->values()->all();
    }
}
