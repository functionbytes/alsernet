<?php

namespace Modules\Mailing\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\Mailing\Enums\CampaignStatus;

/**
 * Campaign-specific query scopes
 *
 * Common scopes for Campaign model and related entities
 * Based on Acelle's campaign lifecycle and filtering patterns
 */
trait HasCampaignScopes
{
    /**
     * Scope to get draft campaigns
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::DRAFT);
    }

    /**
     * Scope to get scheduled campaigns
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::SCHEDULED);
    }

    /**
     * Scope to get sending campaigns
     */
    public function scopeSending(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::SENDING);
    }

    /**
     * Scope to get sent campaigns
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::SENT);
    }

    /**
     * Scope to get paused campaigns
     */
    public function scopePaused(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::PAUSED);
    }

    /**
     * Scope to get failed campaigns
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::FAILED);
    }

    /**
     * Scope to get queued campaigns
     */
    public function scopeQueued(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::QUEUED);
    }

    /**
     * Scope to get campaigns ready to send (scheduled in the past)
     */
    public function scopeReadyToSend(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::SCHEDULED)
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope to get campaigns scheduled for the future
     */
    public function scopeFutureScheduled(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::SCHEDULED)
            ->where('scheduled_at', '>', now());
    }

    /**
     * Scope to get campaigns that are not drafts
     */
    public function scopeNotDraft(Builder $query): Builder
    {
        return $query->where('status', '!=', CampaignStatus::DRAFT);
    }

    /**
     * Scope to get campaigns that are currently active (sending or scheduled)
     */
    public function scopeActiveCampaigns(Builder $query): Builder
    {
        return $query->whereIn('status', [
            CampaignStatus::SCHEDULED,
            CampaignStatus::SENDING,
            CampaignStatus::QUEUED,
        ]);
    }

    /**
     * Scope to get completed campaigns (sent or failed)
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereIn('status', [
            CampaignStatus::SENT,
            CampaignStatus::FAILED,
        ]);
    }

    /**
     * Scope to filter campaigns by date range
     */
    public function scopeSentBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->where('status', CampaignStatus::SENT)
            ->whereBetween('sent_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get campaigns sent today
     */
    public function scopeSentToday(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::SENT)
            ->whereDate('sent_at', today());
    }

    /**
     * Scope to filter by list
     */
    public function scopeForList(Builder $query, int $listId): Builder
    {
        return $query->where('list_id', $listId);
    }

    /**
     * Scope to filter campaigns with high open rate
     */
    public function scopeHighPerforming(Builder $query, float $minOpenRate = 20.0): Builder
    {
        return $query->whereHas('analytics', function ($q) use ($minOpenRate) {
            $q->whereRaw('(unique_opens / NULLIF(delivered_count, 0) * 100) >= ?', [$minOpenRate]);
        });
    }

    /**
     * Scope to filter campaigns with analytics
     */
    public function scopeWithAnalytics(Builder $query): Builder
    {
        return $query->whereHas('analytics');
    }

    /**
     * Scope to get campaigns by sender
     */
    public function scopeBySender(Builder $query, int $senderId): Builder
    {
        return $query->where('sender_id', $senderId);
    }

    /**
     * Scope to search campaigns by name or subject
     */
    public function scopeSearchCampaigns(Builder $query, ?string $searchTerm): Builder
    {
        if (empty($searchTerm)) {
            return $query;
        }

        $searchTerm = "%{$searchTerm}%";

        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', $searchTerm)
                ->orWhere('subject', 'like', $searchTerm);
        });
    }
}
