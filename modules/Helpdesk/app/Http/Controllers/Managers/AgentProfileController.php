<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Services\AgentPresenceService;

class AgentProfileController extends Controller
{
    public function __construct(
        private readonly AgentPresenceService $presence,
    ) {
        $this->middleware('can:helpdesk.manage');
    }

    /**
     * Return the agent profile data consumed by the inbox "Perfil de agente" modal.
     *
     * @return JsonResponse<array{
     *     success: bool,
     *     agent: array{id:int,name:string,email:string,role:?string,status:string,statusLabel:string,initials:string},
     *     workload: array{open:int,limit:int,percent:int,available:int},
     *     performance: array{resolved7d:int,csat:?float,avgFirstResponse:?int},
     *     skills: list<array{id:int,name:string}>,
     *     info: array{languages:?string,department:?string,phone:?string,memberSince:?string,tenure:?string},
     *     schedule: array{today:?array{start:string,end:string,enabled:bool},nowMinutes:int,isWorkingDay:bool}
     * }>
     */
    public function show(User $agent): JsonResponse
    {
        $this->authorize('helpdesk.manage');

        $settings = AgentSettings::query()->where('user_id', $agent->id)->first();

        return response()->json([
            'success' => true,
            'agent' => $this->agentHeader($agent, $settings),
            'workload' => $this->workload($agent, $settings),
            'performance' => $this->performance($agent),
            'skills' => $this->skills($agent),
            'info' => $this->info($agent),
            'schedule' => $this->schedule($settings),
        ]);
    }

    /**
     * @return array{id:int,name:string,email:string,role:?string,status:string,statusLabel:string,initials:string}
     */
    private function agentHeader(User $agent, ?AgentSettings $settings): array
    {
        $name = trim(($agent->firstname ?? '').' '.($agent->lastname ?? '')) ?: $agent->email;

        [$status, $statusLabel] = $this->presence($agent, $settings);

        return [
            'id' => $agent->id,
            'name' => $name,
            'email' => $agent->email,
            'role' => $agent->roles->first()?->name,
            'status' => $status,
            'statusLabel' => $statusLabel,
            'initials' => $this->initials($name),
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function presence(User $agent, ?AgentSettings $settings): array
    {
        // Not the SQL `sessions` table: SESSION_DRIVER is redis, so it's never
        // populated — real presence lives in AgentPresenceService's Redis
        // heartbeat instead.
        $isOnline = in_array($agent->id, $this->presence->getOnlineAgents(), true);

        $accepts = $settings?->accepts_conversations ?? 'yes';

        if ($accepts === 'no') {
            return ['away', 'Ausente'];
        }

        if (! $isOnline) {
            return ['offline', 'Offline'];
        }

        return ['online', 'En línea'];
    }

    /**
     * @return array{open:int,limit:int,percent:int,available:int}
     */
    private function workload(User $agent, ?AgentSettings $settings): array
    {
        $open = Conversation::query()
            ->where('assignee_id', $agent->id)
            ->where('is_archived', false)
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->count();

        $limit = (int) ($settings?->max_concurrent_conversations ?? 0);
        if ($limit <= 0) {
            $limit = 15;
        }

        $percent = $limit > 0 ? (int) round(min($open, $limit) / $limit * 100) : 0;

        return [
            'open' => $open,
            'limit' => $limit,
            'percent' => $percent,
            'available' => max($limit - $open, 0),
        ];
    }

    /**
     * Performance over the last 7 days: resolved count, average CSAT, average first response (seconds).
     *
     * @return array{resolved7d:int,csat:?float,avgFirstResponse:?int}
     */
    private function performance(User $agent): array
    {
        $from = now()->subDays(7);
        $to = now();

        $row = DB::connection('helpdesk')
            ->table('helpdesk_conversations')
            ->where('assignee_id', $agent->id)
            ->whereBetween('closed_at', [$from, $to])
            ->selectRaw('COUNT(*) as resolved_count')
            ->selectRaw('AVG(CASE WHEN first_response_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, first_response_at) END) as avg_response_sec')
            ->first();

        $csat = CsatRating::query()
            ->where('agent_id', $agent->id)
            ->whereNotNull('answered_at')
            ->whereBetween('answered_at', [$from, $to])
            ->avg('rating');

        return [
            'resolved7d' => (int) ($row->resolved_count ?? 0),
            'csat' => $csat !== null ? round((float) $csat, 1) : null,
            'avgFirstResponse' => $row?->avg_response_sec !== null ? (int) round($row->avg_response_sec) : null,
        ];
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    private function skills(User $agent): array
    {
        return DB::connection('helpdesk')
            ->table('helpdesk_user_skills as us')
            ->join('helpdesk_skills as s', 's.id', '=', 'us.skill_id')
            ->where('us.user_id', $agent->id)
            ->orderBy('s.name')
            ->get(['s.id', 's.name'])
            ->map(fn ($s) => ['id' => (int) $s->id, 'name' => (string) $s->name])
            ->all();
    }

    /**
     * @return array{languages:?string,department:?string,phone:?string,memberSince:?string,tenure:?string}
     */
    private function info(User $agent): array
    {
        $department = $agent->groups->pluck('name')->implode(' · ') ?: null;

        $createdAt = $agent->created_at;
        $memberSince = $createdAt?->toIso8601String();
        $tenure = $createdAt ? $this->tenure($createdAt) : null;

        return [
            'languages' => $agent->locale ? strtoupper($agent->locale) : null,
            'department' => $department,
            'phone' => $agent->cellphone ?: null,
            'memberSince' => $memberSince,
            'tenure' => $tenure,
        ];
    }

    private function tenure(Carbon $since): string
    {
        $years = (int) $since->diffInYears(now());
        $months = (int) $since->copy()->addYears($years)->diffInMonths(now());

        if ($years >= 1) {
            $label = $years.' año'.($years === 1 ? '' : 's');
            if ($months > 0) {
                $label .= ' '.$months.' mes'.($months === 1 ? '' : 'es');
            }

            return $label;
        }

        if ($months >= 1) {
            return $months.' mes'.($months === 1 ? '' : 'es');
        }

        return 'Menos de un mes';
    }

    /**
     * Today's working window for the timeline bar.
     *
     * @return array{today:?array{start:string,end:string,enabled:bool},nowMinutes:int,isWorkingDay:bool}
     */
    private function schedule(?AgentSettings $settings): array
    {
        $now = now();
        $nowMinutes = ($now->hour * 60) + $now->minute;
        $dayKey = strtolower($now->format('l'));

        $hours = $settings?->working_hours ?? null;
        $today = null;
        $isWorkingDay = false;

        if (is_array($hours) && isset($hours[$dayKey])) {
            $day = $hours[$dayKey];
            $enabled = (bool) ($day['enabled'] ?? false);
            $today = [
                'start' => (string) ($day['start'] ?? '09:00'),
                'end' => (string) ($day['end'] ?? '18:00'),
                'enabled' => $enabled,
            ];
            $isWorkingDay = $enabled;
        }

        return [
            'today' => $today,
            'nowMinutes' => $nowMinutes,
            'isWorkingDay' => $isWorkingDay,
        ];
    }

    private function initials(string $name): string
    {
        $initials = collect(preg_split('/\s+/', $name))
            ->filter()
            ->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : '?';
    }
}
