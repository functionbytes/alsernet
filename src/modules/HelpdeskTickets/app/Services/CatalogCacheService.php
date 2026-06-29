<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Group;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketStatus;

class CatalogCacheService
{
    private const TTL = 3600;

    public static function defaultStatus(): ?TicketStatus
    {
        return Cache::remember('helpdesk:catalogs:default-status', self::TTL, fn () => TicketStatus::where('is_default', true)->first());
    }

    public static function statuses(): Collection
    {
        return Cache::remember('helpdesk:catalogs:statuses', self::TTL, fn () => TicketStatus::active()->ordered()->get());
    }

    public static function categories(): Collection
    {
        return Cache::remember('helpdesk:catalogs:categories', self::TTL, fn () => TicketCategory::active()->ordered()->get());
    }

    public static function groups(): Collection
    {
        return Cache::remember('helpdesk:catalogs:groups', self::TTL, fn () => Group::orderBy('name')->get());
    }

    public static function invalidate(): void
    {
        Cache::forget('helpdesk:catalogs:default-status');
        Cache::forget('helpdesk:catalogs:statuses');
        Cache::forget('helpdesk:catalogs:categories');
        Cache::forget('helpdesk:catalogs:groups');
    }
}
