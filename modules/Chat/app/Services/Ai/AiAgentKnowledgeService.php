<?php

namespace Modules\Chat\Services\Ai;

use Illuminate\Database\Eloquent\Collection;
use Modules\Chat\Models\Ai\AiAgent;
use Modules\Chat\Models\Ai\AiAgentKnowledgeBase;
use Modules\Chat\Models\Ai\AiAgentTool;

class AiAgentKnowledgeService
{
    // ──────────────────────────────────────────────────────────────
    // Knowledge base
    // ──────────────────────────────────────────────────────────────

    /**
     * Return all knowledge base entries for the given agent, newest first.
     *
     * @return Collection<int, AiAgentKnowledgeBase>
     */
    public function allKnowledge(AiAgent $agent): Collection
    {
        return $agent->knowledgeBase()->orderBy('created_at', 'desc')->get();
    }

    /**
     * Create a new knowledge base entry.
     *
     * @param  array<string, mixed>  $validated
     */
    public function createKnowledge(AiAgent $agent, array $validated): AiAgentKnowledgeBase
    {
        $validated['ai_agent_id'] = $agent->id;

        return AiAgentKnowledgeBase::create($validated);
    }

    /**
     * Update a knowledge base entry.
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateKnowledge(AiAgentKnowledgeBase $knowledge, array $validated): AiAgentKnowledgeBase
    {
        $knowledge->update($validated);

        return $knowledge;
    }

    /**
     * Delete a knowledge base entry.
     */
    public function deleteKnowledge(AiAgentKnowledgeBase $knowledge): void
    {
        $knowledge->delete();
    }

    /**
     * Toggle the is_active flag on a knowledge base entry.
     */
    public function toggleKnowledge(AiAgentKnowledgeBase $knowledge, bool $isActive): void
    {
        $knowledge->update(['is_active' => $isActive]);
    }

    /**
     * Generate (or refresh) the embedding for a knowledge base entry.
     */
    public function generateEmbedding(AiAgentKnowledgeBase $knowledge): void
    {
        $knowledge->generateEmbedding();
    }

    // ──────────────────────────────────────────────────────────────
    // Tools
    // ──────────────────────────────────────────────────────────────

    /**
     * Return all tools for the given agent, newest first.
     *
     * @return Collection<int, AiAgentTool>
     */
    public function allTools(AiAgent $agent): Collection
    {
        return $agent->tools()->orderBy('created_at', 'desc')->get();
    }

    /**
     * Create a new tool for the given agent.
     *
     * @param  array<string, mixed>  $validated
     */
    public function createTool(AiAgent $agent, array $validated): AiAgentTool
    {
        $validated['ai_agent_id'] = $agent->id;

        return AiAgentTool::create($validated);
    }

    /**
     * Update an existing tool.
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateTool(AiAgentTool $tool, array $validated): AiAgentTool
    {
        $tool->update($validated);

        return $tool;
    }

    /**
     * Delete a tool.
     */
    public function deleteTool(AiAgentTool $tool): void
    {
        $tool->delete();
    }

    /**
     * Toggle the is_active flag on a tool.
     */
    public function toggleTool(AiAgentTool $tool, bool $isActive): void
    {
        $tool->update(['is_active' => $isActive]);
    }
}
