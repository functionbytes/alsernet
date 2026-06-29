<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\DB;
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
