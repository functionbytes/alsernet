<?php

namespace Modules\HelpdeskAiPrompts\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;

/**
 * Reads the active prompt library (base prompt, knowledge blocks, cases) with
 * a short cache. Selection of the "most specific" block always follows:
 * channel+locale > channel > locale > global.
 */
class PromptLibrary
{
    public const CACHE_TTL = 300; // 5 min

    public const CACHE_KEY_BASE_BLOCKS = 'helpdesk_ai_prompts:blocks:base';

    public const CACHE_KEY_KNOWLEDGE_BLOCKS = 'helpdesk_ai_prompts:blocks:knowledge';

    public const CACHE_KEY_CASES = 'helpdesk_ai_prompts:cases';

    public function base(?string $channel, ?string $locale): ?string
    {
        $best = $this->mostSpecific($this->cachedBlocks('base'), $channel, $locale);

        return $best?->content;
    }

    /**
     * @param  array<int, string>  $keys  Empty = no filter (every active knowledge block)
     * @return array<string, string> key => content
     */
    public function knowledge(array $keys, ?string $channel, ?string $locale): array
    {
        $grouped = $this->cachedBlocks('knowledge')
            ->when($keys !== [], fn (Collection $blocks) => $blocks->filter(fn (AiPromptBlock $b) => in_array($b->key, $keys, true)))
            ->groupBy('key');

        $result = [];

        foreach ($grouped as $key => $candidates) {
            $best = $this->mostSpecific($candidates, $channel, $locale);

            if ($best !== null) {
                $result[$key] = $best->content;
            }
        }

        return $result;
    }

    /**
     * Active effective cases for a channel: a channel-specific override
     * replaces the global case with the same key. Sorted by priority desc.
     *
     * @return Collection<int, AiPromptCase>
     */
    public function cases(?string $channel): Collection
    {
        $effective = [];

        foreach ($this->cachedCases() as $case) {
            // A case scoped to a different channel never applies here.
            if ($case->channel !== null && $case->channel !== $channel) {
                continue;
            }

            $current = $effective[$case->key] ?? null;

            if ($current === null || ($case->channel !== null && $current->channel === null)) {
                $effective[$case->key] = $case;
            }
        }

        return collect(array_values($effective))->sortByDesc('priority')->values();
    }

    /**
     * @param  Collection<int, AiPromptBlock>  $blocks
     */
    private function mostSpecific(Collection $blocks, ?string $channel, ?string $locale): ?AiPromptBlock
    {
        $best = null;
        $bestScore = -1;

        foreach ($blocks as $block) {
            $score = $this->specificityScore($block, $channel, $locale);

            if ($score === null || $score <= $bestScore) {
                continue;
            }

            $best = $block;
            $bestScore = $score;
        }

        return $best;
    }

    /**
     * null = the block does not apply to this channel/locale at all.
     */
    private function specificityScore(AiPromptBlock $block, ?string $channel, ?string $locale): ?int
    {
        $channelMatches = $block->channel === null || $block->channel === $channel;
        $localeMatches = $block->locale === null || $block->locale === $locale;

        if (! $channelMatches || ! $localeMatches) {
            return null;
        }

        return ($block->channel !== null ? 2 : 0) + ($block->locale !== null ? 1 : 0);
    }

    /**
     * @return Collection<int, AiPromptBlock>
     */
    private function cachedBlocks(string $kind): Collection
    {
        $key = $kind === 'base' ? self::CACHE_KEY_BASE_BLOCKS : self::CACHE_KEY_KNOWLEDGE_BLOCKS;

        return Cache::remember($key, self::CACHE_TTL, fn () => AiPromptBlock::query()
            ->where('kind', $kind)
            ->active()
            ->get());
    }

    /**
     * @return Collection<int, AiPromptCase>
     */
    private function cachedCases(): Collection
    {
        return Cache::remember(self::CACHE_KEY_CASES, self::CACHE_TTL, fn () => AiPromptCase::query()
            ->active()
            ->get());
    }
}
