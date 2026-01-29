<?php

namespace Modules\Mailing\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\Mailing\Enums\SubscriberStatus;

/**
 * Subscriber-specific query scopes
 *
 * Common scopes for Subscriber model and related entities
 * Based on Acelle's subscriber lifecycle and filtering patterns
 */
trait HasSubscriberScopes
{
    /**
     * Scope to get only active subscribers
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::ACTIVE);
    }

    /**
     * Scope to get subscribed subscribers (alias for active)
     */
    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::ACTIVE)
            ->whereNotNull('subscribed_at');
    }

    /**
     * Scope to get unsubscribed subscribers
     */
    public function scopeUnsubscribed(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::UNSUBSCRIBED);
    }

    /**
     * Scope to get bounced subscribers
     */
    public function scopeBounced(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::BOUNCED);
    }

    /**
     * Scope to get spam-reported subscribers
     */
    public function scopeSpamReported(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::SPAM_REPORTED);
    }

    /**
     * Scope to get blacklisted subscribers
     */
    public function scopeBlacklisted(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::BLACKLISTED);
    }

    /**
     * Scope to get pending subscribers (awaiting confirmation)
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::PENDING);
    }

    /**
     * Scope to get subscribers that need syncing with Mailrelay
     */
    public function scopeNeedsSyncing(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('mailrelay_id')
                ->orWhereNull('last_synced_at')
                ->orWhere('last_synced_at', '<', now()->subHours(24));
        });
    }

    /**
     * Scope to get synced subscribers (those with mailrelay_id)
     */
    public function scopeSynced(Builder $query): Builder
    {
        return $query->whereNotNull('mailrelay_id');
    }

    /**
     * Scope to get subscribers by email
     */
    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Scope to search subscribers by email or name
     */
    public function scopeSearchSubscribers(Builder $query, ?string $searchTerm): Builder
    {
        if (empty($searchTerm)) {
            return $query;
        }

        $searchTerm = "%{$searchTerm}%";

        return $query->where(function ($q) use ($searchTerm) {
            $q->where('email', 'like', $searchTerm)
                ->orWhere('name', 'like', $searchTerm);
        });
    }

    /**
     * Scope to filter verified/validated subscribers
     */
    public function scopeValidated(Builder $query): Builder
    {
        return $query->whereNotNull('validated_at');
    }

    /**
     * Scope to filter unvalidated subscribers
     */
    public function scopeUnvalidated(Builder $query): Builder
    {
        return $query->whereNull('validated_at');
    }

    /**
     * Scope to get subscribers in a specific group
     */
    public function scopeInGroup(Builder $query, int $groupId): Builder
    {
        return $query->whereHas('groups', function ($q) use ($groupId) {
            $q->where('mailing_groups.id', $groupId);
        });
    }

    /**
     * Scope to get subscribers not in a specific group
     */
    public function scopeNotInGroup(Builder $query, int $groupId): Builder
    {
        return $query->whereDoesntHave('groups', function ($q) use ($groupId) {
            $q->where('mailing_groups.id', $groupId);
        });
    }

    /**
     * Scope to get subscribers who subscribed within a date range
     */
    public function scopeSubscribedBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('subscribed_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get subscribers who subscribed today
     */
    public function scopeSubscribedToday(Builder $query): Builder
    {
        return $query->whereDate('subscribed_at', today());
    }

    /**
     * Scope to get recently active subscribers
     */
    public function scopeRecentlyActive(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', SubscriberStatus::ACTIVE)
            ->where('subscribed_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get subscribers with custom field value
     */
    public function scopeWithCustomField(Builder $query, string $fieldKey, mixed $value): Builder
    {
        return $query->whereJsonContains('custom_fields->'.$fieldKey, $value);
    }

    /**
     * Scope to filter sendable subscribers (active and validated)
     */
    public function scopeSendable(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::ACTIVE)
            ->whereNotNull('validated_at');
    }
}
