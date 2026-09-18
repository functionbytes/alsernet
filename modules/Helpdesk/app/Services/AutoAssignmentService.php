<?php

namespace Modules\Helpdesk\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Setting;

/**
 * Global auto-assignment strategy (#78 ve-auto-assign). Reads a runtime-editable
 * strategy from helpdesk_settings and resolves the agent for a new conversation:
 * round-robin, least-load, skills (delegates to SkillsRoutingService) or manual.
 */
class AutoAssignmentService
{
    public const STRATEGIES = ['round_robin', 'least_load', 'skills', 'manual'];

    public const RETRIES = ['off', '2', '5', '15'];

    public const FALLBACKS = ['queue', 'supervisor'];

    public function __construct(
        private readonly SkillsRoutingService $skills,
    ) {}

    /**
     * @return array{enabled: bool, strategy: string, retry: string, fallback: string}
     */
    public function config(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'strategy' => (string) Setting::get('auto_assign.strategy', config('helpdesk.auto_assignment.strategy', 'round_robin')),
            'retry' => (string) Setting::get('auto_assign.retry', 'off'),
            'fallback' => (string) Setting::get('auto_assign.fallback', 'queue'),
        ];
    }

    /**
     * Whether auto-assignment is active. Persisted in settings (editable from the
     * modal); falls back to the config file default when never set.
     */
    public function isEnabled(): bool
    {
        $stored = Setting::get('auto_assign.enabled');

        if ($stored === null) {
            return (bool) config('helpdesk.auto_assignment.enabled', false);
        }

        return filter_var($stored, FILTER_VALIDATE_BOOL);
    }

    public function saveConfig(string $strategy, string $retry, string $fallback, ?bool $enabled = null): void
    {
        DB::transaction(function () use ($strategy, $retry, $fallback, $enabled): void {
            Setting::set('auto_assign.strategy', $strategy, 'auto_assign');
            Setting::set('auto_assign.retry', $retry, 'auto_assign');
            Setting::set('auto_assign.fallback', $fallback, 'auto_assign');

            if ($enabled !== null) {
                Setting::set('auto_assign.enabled', $enabled ? '1' : '0', 'auto_assign');
            }
        });
    }

    /**
     * Resolve the agent to assign per the configured strategy, or null.
     */
    public function resolveAgent(Conversation $conversation): ?int
    {
        return match ($this->config()['strategy']) {
            'manual' => null,
            'skills' => $this->skills->routeBySkills($conversation),
            'least_load' => $this->leastLoad($this->availableAgents($conversation)),
            default => $this->roundRobin($this->availableAgents($conversation)),
        };
    }

    /**
     * Available agents assigned to the conversation's inbox that accept new work.
     *
     * @return array<int, int>
     */
    public function availableAgents(Conversation $conversation): array
    {
        $userIds = AgentInboxCapacity::query()
            ->where('inbox_id', $conversation->inbox_id)
            ->where('accepts_new', true)
            ->pluck('user_id')
            ->all();

        return $this->skills->filterAvailableAgents($userIds);
    }

    /**
     * Apply the configured fallback when no agent was resolved. "queue" leaves the
     * conversation unassigned; "supervisor" assigns it to a supervisor/admin.
     */
    public function applyFallback(Conversation $conversation): void
    {
        $config = $this->config();

        // "Manual" no auto-asigna en absoluto (ni al supervisor); "queue" deja
        // la conversación sin asignar en la cola del inbox.
        if ($config['strategy'] === 'manual' || $config['fallback'] !== 'supervisor') {
            return;
        }

        $supervisorId = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['helpdesk-admin', 'manager', 'super-admin']))
            ->orderBy('id')
            ->value('id');

        if ($supervisorId) {
            $conversation->assignTo($supervisorId);
        }
    }

    /**
     * @param  array<int, int>  $agents
     */
    private function roundRobin(array $agents): ?int
    {
        if (empty($agents)) {
            return null;
        }

        sort($agents);

        // Serializa la lectura-incremento del puntero entre workers concurrentes
        // (evita la carrera TOCTOU que asignaría el mismo agente dos veces). Si no
        // se obtiene el lock, asigna igual sin avanzar el puntero (degradación suave).
        $selected = Cache::lock('helpdesk:auto_assign:rr', 5)->get(function () use ($agents) {
            $last = (int) Setting::get('auto_assign.rr_pointer', 0);

            $next = null;
            foreach ($agents as $id) {
                if ($id > $last) {
                    $next = $id;
                    break;
                }
            }
            $next ??= $agents[0];

            Setting::set('auto_assign.rr_pointer', (string) $next, 'auto_assign');

            return $next;
        });

        return $selected ?: $agents[0];
    }

    /**
     * @param  array<int, int>  $agents
     */
    private function leastLoad(array $agents): ?int
    {
        if (empty($agents)) {
            return null;
        }

        $openCount = DB::connection('helpdesk')
            ->table('helpdesk_conversations')
            ->join('helpdesk_conversation_statuses', 'helpdesk_conversations.status_id', '=', 'helpdesk_conversation_statuses.id')
            ->whereIn('helpdesk_conversations.assignee_id', $agents)
            ->where('helpdesk_conversation_statuses.is_open', true)
            ->whereNull('helpdesk_conversations.deleted_at')
            ->select('helpdesk_conversations.assignee_id', DB::raw('COUNT(*) as open_count'))
            ->groupBy('helpdesk_conversations.assignee_id')
            ->pluck('open_count', 'assignee_id');

        usort($agents, fn ($a, $b) => ($openCount[$a] ?? 0) <=> ($openCount[$b] ?? 0));

        return $agents[0];
    }
}
