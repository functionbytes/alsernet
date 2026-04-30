<?php

namespace Modules\Chat\Config;

class PerformanceThresholds
{
    // Agent workload
    public const MAX_CONVERSATIONS_PER_AGENT = 15;

    // Response times (minutes)
    public const MAX_RESPONSE_TIME_MINUTES = 60;

    public const TARGET_FIRST_RESPONSE_MINUTES = 5;

    // Conversation alerts
    public const MAX_UNASSIGNED_CONVERSATIONS = 5;

    // Quality metrics
    public const MIN_RESOLUTION_RATE_PERCENT = 70;

    public const MIN_CSAT_SCORE = 4;

    // Cache TTLs (minutes)
    public const CACHE_OVERVIEW_METRICS_TTL = 5;

    public const CACHE_AGENT_METRICS_TTL = 5;

    public const CACHE_TRENDS_TTL = 15;

    public const CACHE_ALERTS_TTL = 3;
}
