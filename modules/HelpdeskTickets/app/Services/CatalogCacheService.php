<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Group;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketStatus;

class CatalogCacheService
{
    private const TTL = 3600;

    /**
     * Short TTL: agent availability changes often, catalogs do not.
     */
    private const AGENTS_TTL = 60;

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

    /**
     * Available + verified users shown as assignable agents in the tickets
     * CRUD (index/create/edit/show). Cached briefly to avoid re-querying on
     * every request.
     */
    public static function agents(): Collection
    {
        return Cache::remember('helpdesk:catalogs:agents', self::AGENTS_TTL, fn () => User::select(['id', 'firstname', 'lastname', 'email'])
            ->where('available', true)
            ->where('verified', true)
            ->orderBy('firstname')
            ->get());
    }

    public static function invalidate(): void
    {
        Cache::forget('helpdesk:catalogs:default-status');
        Cache::forget('helpdesk:catalogs:statuses');
        Cache::forget('helpdesk:catalogs:categories');
        Cache::forget('helpdesk:catalogs:groups');
        Cache::forget('helpdesk:catalogs:agents');
    }
}
