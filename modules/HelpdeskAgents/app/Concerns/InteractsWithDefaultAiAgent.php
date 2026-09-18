<?php

namespace Modules\HelpdeskAgents\Concerns;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskAgents\Models\AiAgent;

trait InteractsWithDefaultAiAgent
{
    public const DEFAULT_AGENT_CACHE_KEY = 'helpdesk:ai-agent:default';

    public const DEFAULT_AGENT_CACHE_TTL = 300;

    protected function getDefaultAgent(): ?AiAgent
    {
        return Cache::remember(
            self::DEFAULT_AGENT_CACHE_KEY,
            self::DEFAULT_AGENT_CACHE_TTL,
            fn () => AiAgent::query()
                ->default()
                ->first()
                ?? AiAgent::query()->orderBy('id')->first()
        );
    }

    protected function forgetDefaultAgent(): void
    {
        Cache::forget(self::DEFAULT_AGENT_CACHE_KEY);
    }
}
