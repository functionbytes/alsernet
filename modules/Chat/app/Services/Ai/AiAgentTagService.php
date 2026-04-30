<?php

namespace Modules\Chat\Services\Ai;

use Illuminate\Database\Eloquent\Collection;
use Modules\Chat\Models\Ai\AiAgentTag;

class AiAgentTagService
{
    /**
     * Return all tags ordered by priority descending.
     *
     * @return Collection<int, AiAgentTag>
     */
    public function all(): Collection
    {
        return AiAgentTag::orderBy('priority', 'desc')->get();
    }

    /**
     * Create a new tag from validated data.
     *
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated): AiAgentTag
    {
        return AiAgentTag::create($validated);
    }

    /**
     * Update an existing tag.
     *
     * @param  array<string, mixed>  $validated
     */
    public function update(AiAgentTag $tag, array $validated): AiAgentTag
    {
        $tag->update($validated);

        return $tag;
    }

    /**
     * Delete a tag.
     */
    public function delete(AiAgentTag $tag): void
    {
        $tag->delete();
    }

    /**
     * Toggle the is_active flag on a tag.
     */
    public function toggle(AiAgentTag $tag, bool $isActive): void
    {
        $tag->update(['is_active' => $isActive]);
    }
}
