<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\HelpdeskAgents\Models\AgentShift;
use Modules\HelpdeskAgents\Models\AgentVacation;

/**
 * Shared agent-availability filter used by every auto-assignment strategy
 * (round-robin, least-load, and the ticket-side AssignmentService). Extracted
 * from the former SkillsRoutingService, which this logic has nothing to do
 * with skills per se — it only respects each agent's AgentSettings
 * (availability, working hours, vacation, capacity and presence).
 *
 * QUAL-01: also respects the shift schedule and approved time off managed in
 * the "Turnos y guardias" panel (Modules\HelpdeskAgents: AgentShift /
 * AgentVacation). That panel already had real UI/forms/models but was never
 * wired into assignment — see modules/HelpdeskAgents/README.md for the
 * decision behind integrating it here instead of removing it.
 *
 * AgentShift/AgentVacation are a soft dependency on the HelpdeskAgents module
 * (same pattern as Modules\Helpdesk\Services\AI\ArticleSuggestionService):
 * guarded with class_exists() and try/catch so a missing table or a disabled
 * module never breaks an assignment — it just stops narrowing by shift.
 */
class AgentAvailabilityService
{
    /**
     * Keep only agents that can receive an assignment right now according to their
     * AgentSettings (availability, working hours, vacation, capacity and presence)
     * and, when configured, their shift schedule / approved time off.
     *
     * Agents without a settings row are kept as available to avoid regressing setups
     * that never configured availability. The same non-regression rule applies to
     * shifts: an agent with no shift rows at all is never required to be "on shift"
     * to receive work, since most agents never configured the schedule panel.
     *
     * Shifts and vacations are resolved with one batched query each for every
     * candidate agent — never per-agent inside a loop.
     *
     * @param  array<int, int>  $userIds
     * @return array<int, int>
     */
    public function filterAvailableAgents(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $settings = AgentSettings::query()
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        $onApprovedVacation = $this->usersOnApprovedVacation($userIds);
        [$usersWithShifts, $usersOnShiftNow] = $this->shiftAvailability($userIds);

        return array_values(array_filter($userIds, function ($userId) use ($settings, $onApprovedVacation, $usersWithShifts, $usersOnShiftNow) {
            $agentSettings = $settings->get($userId);

            if ($agentSettings !== null && ! $this->passesAgentSettings($agentSettings)) {
                return false;
            }

            if (in_array($userId, $onApprovedVacation, true)) {
                return false;
            }

            // Solo se exige turno activo a quien tiene turnos configurados: un
            // agente sin filas en helpdesk_agent_shifts nunca configuró el panel
            // y se mantiene disponible (no regresar setups previos a QUAL-01).
            if (in_array($userId, $usersWithShifts, true) && ! in_array($userId, $usersOnShiftNow, true)) {
                return false;
            }

            return true;
        }));
    }

    /**
     * Solo agentes "disponibles" reciben trabajo automático: en descanso
     * (busy), ausente (away) y desconectado (offline) quedan excluidos. Un
     * agente sin estado explícito se considera disponible (no regresar setups).
     */
    private function passesAgentSettings(AgentSettings $agentSettings): bool
    {
        $presence = $agentSettings->presence_state ?? AgentSettings::PRESENCE_AVAILABLE;

        return $agentSettings->canReceiveAssignment()
            && $agentSettings->acceptsConversationsNow()
            && ! $agentSettings->hasReachedLimit()
            && $presence === AgentSettings::PRESENCE_AVAILABLE;
    }

    /**
     * IDs con una ausencia aprobada que cubre "ahora mismo", en una única
     * consulta en lote para todos los candidatos.
     *
     * @param  array<int, int>  $userIds
     * @return array<int, int>
     */
    private function usersOnApprovedVacation(array $userIds): array
    {
        if (! class_exists(AgentVacation::class)) {
            return [];
        }

        try {
            return AgentVacation::query()
                ->whereIn('user_id', $userIds)
                ->where('status', 'approved')
                ->whereDate('starts_at', '<=', now())
                ->whereDate('ends_at', '>=', now())
                ->pluck('user_id')
                ->all();
        } catch (\Throwable $e) {
            Log::warning('AgentAvailabilityService: vacation lookup failed, ignoring vacations', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Turnos de todos los candidatos resueltos en una única consulta. Devuelve
     * dos listas de IDs: quién tiene algún turno configurado (el filtro de
     * turno solo aplica a ellos) y, de esos, quién tiene un turno activo ahora
     * mismo (evaluado en la zona horaria propia de cada turno, no la del
     * servidor).
     *
     * @param  array<int, int>  $userIds
     * @return array{0: array<int, int>, 1: array<int, int>}
     */
    private function shiftAvailability(array $userIds): array
    {
        if (! class_exists(AgentShift::class)) {
            return [[], []];
        }

        try {
            $shifts = AgentShift::query()->whereIn('user_id', $userIds)->get();
        } catch (\Throwable $e) {
            Log::warning('AgentAvailabilityService: shift lookup failed, ignoring shifts', [
                'error' => $e->getMessage(),
            ]);

            return [[], []];
        }

        $configured = $shifts->pluck('user_id')->unique()->values()->all();

        $activeNow = $shifts
            ->filter(fn (AgentShift $shift) => $shift->is_active && $this->isShiftActiveNow($shift))
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();

        return [$configured, $activeNow];
    }

    /**
     * Whether a shift covers "now", evaluated in the shift's own timezone
     * (never the server's). Overnight shifts (end < start, e.g. 22:00–06:00)
     * cross midnight: the evening leg belongs to the configured day_of_week,
     * the early-morning leg already falls on the next calendar day.
     */
    private function isShiftActiveNow(AgentShift $shift): bool
    {
        $now = Carbon::now($shift->timezone ?: 'UTC');
        $today = $now->dayOfWeek;
        $time = $now->format('H:i:s');
        $day = (int) $shift->day_of_week;

        if ($shift->end_time < $shift->start_time) {
            $eveningLeg = $today === $day && $time >= $shift->start_time;
            $morningLeg = $today === ($day + 1) % 7 && $time <= $shift->end_time;

            return $eveningLeg || $morningLeg;
        }

        return $today === $day && $time >= $shift->start_time && $time <= $shift->end_time;
    }
}
