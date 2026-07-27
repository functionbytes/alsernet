<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Skill;

class SkillsRoutingService
{
    /**
     * Find the best agent for a conversation based on required skills.
     * Returns user_id of the best agent, or null if no match.
     */
    public function routeBySkills(Conversation $conv): ?int
    {
        $requiredSkillIds = $conv->skills()
            ->pluck('helpdesk_skills.id')
            ->all();

        if (empty($requiredSkillIds)) {
            return null;
        }

        $skillCount = count($requiredSkillIds);

        // Find agents who have ALL required skills
        $agentsWithAllSkills = DB::connection('helpdesk')
            ->table('helpdesk_user_skills')
            ->select('user_id', DB::raw('SUM(proficiency) as total_proficiency'))
            ->whereIn('skill_id', $requiredSkillIds)
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT skill_id) = ?', [$skillCount])
            ->orderBy('total_proficiency', 'desc')
            ->pluck('user_id')
            ->all();

        if (empty($agentsWithAllSkills)) {
            return null;
        }

        // Only route to agents who are actually available right now: respect
        // availability/shifts/vacation/capacity/presence configured in AgentSettings
        // (mirrors Group::getNextAgent). Agents without a settings row keep the
        // previous default behaviour (treated as available) so existing setups that
        // never configured availability do not regress to "no agent found".
        $agentsWithAllSkills = $this->filterAvailableAgents($agentsWithAllSkills);

        if (empty($agentsWithAllSkills)) {
            return null;
        }

        // Among qualified agents, pick the one with fewest open conversations
        $openCountByAgent = DB::connection('helpdesk')
            ->table('helpdesk_conversations')
            ->join('helpdesk_conversation_statuses', 'helpdesk_conversations.status_id', '=', 'helpdesk_conversation_statuses.id')
            ->whereIn('helpdesk_conversations.assignee_id', $agentsWithAllSkills)
            ->where('helpdesk_conversation_statuses.is_open', true)
            ->whereNull('helpdesk_conversations.deleted_at')
            ->select('helpdesk_conversations.assignee_id', DB::raw('COUNT(*) as open_count'))
            ->groupBy('helpdesk_conversations.assignee_id')
            ->pluck('open_count', 'assignee_id');

        // Sort agents by open count (ascending), falling back to proficiency order
        usort($agentsWithAllSkills, function ($a, $b) use ($openCountByAgent) {
            $countA = $openCountByAgent[$a] ?? 0;
            $countB = $openCountByAgent[$b] ?? 0;

            return $countA <=> $countB;
        });

        return $agentsWithAllSkills[0] ?? null;
    }

    /**
     * Keep only agents that can receive an assignment right now according to their
     * AgentSettings (availability, working hours, vacation, capacity and presence).
     * Agents without a settings row are kept as available to avoid regressing setups
     * that never configured availability.
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

        return array_values(array_filter($userIds, function ($userId) use ($settings) {
            $agentSettings = $settings->get($userId);

            if ($agentSettings === null) {
                return true;
            }

            // Solo agentes "disponibles" reciben trabajo automático: en descanso
            // (busy), ausente (away) y desconectado (offline) quedan excluidos. Un
            // agente sin estado explícito se considera disponible (no regresar setups).
            $presence = $agentSettings->presence_state ?? AgentSettings::PRESENCE_AVAILABLE;

            return $agentSettings->canReceiveAssignment()
                && $agentSettings->acceptsConversationsNow()
                && ! $agentSettings->hasReachedLimit()
                && $presence === AgentSettings::PRESENCE_AVAILABLE;
        }));
    }

    /**
     * Detecta las skills requeridas por un texto libre (p. ej. asunto+cuerpo de
     * un ticket) y devuelve sus IDs. Reutiliza el mismo diccionario de palabras
     * clave que la detección de conversaciones.
     *
     * @return array<int, int>
     */
    public function detectSkillIds(string $text): array
    {
        return Skill::all()
            ->filter(fn (Skill $skill) => $this->messageRequiresSkill($text, $skill->slug))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * IDs de agentes que poseen TODAS las skills indicadas, ordenados por
     * competencia total descendente y ya filtrados por disponibilidad.
     *
     * @param  array<int, int>  $requiredSkillIds
     * @return array<int, int>
     */
    public function agentsWithAllSkills(array $requiredSkillIds): array
    {
        if (empty($requiredSkillIds)) {
            return [];
        }

        $agents = DB::connection('helpdesk')
            ->table('helpdesk_user_skills')
            ->select('user_id', DB::raw('SUM(proficiency) as total_proficiency'))
            ->whereIn('skill_id', $requiredSkillIds)
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT skill_id) = ?', [count($requiredSkillIds)])
            ->orderBy('total_proficiency', 'desc')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $this->filterAvailableAgents($agents);
    }

    /**
     * Auto-detect required skills from a message body and attach to conversation.
     */
    public function detectAndAttachSkills(Conversation $conv, string $messageBody): void
    {
        $allSkills = Skill::all();

        foreach ($allSkills as $skill) {
            if ($this->messageRequiresSkill($messageBody, $skill->slug)) {
                $conv->skills()->syncWithoutDetaching([$skill->id]);
            }
        }
    }

    protected function messageRequiresSkill(string $body, string $slug): bool
    {
        $keywords = [
            'billing' => ['factura', 'pago', 'cobro', 'invoice', 'billing', 'cargo', 'reembolso'],
            'technical' => ['error', 'bug', 'falla', 'no funciona', 'problema tecnico', 'crash'],
            'sales' => ['precio', 'comprar', 'cotizacion', 'plan', 'oferta'],
            'french' => ['bonjour', 'merci', 'aide', "s'il vous"],
            'english' => ['hello', 'help', 'please', 'thanks'],
        ];

        $terms = $keywords[$slug] ?? [];
        $bodyLower = mb_strtolower($body);

        foreach ($terms as $term) {
            if (str_contains($bodyLower, $term)) {
                return true;
            }
        }

        return false;
    }
}
