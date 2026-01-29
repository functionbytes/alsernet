<?php

namespace Modules\Mailing\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Sending Server specific query scopes
 *
 * Common scopes for SendingServer model and related entities
 * Based on Acelle's server management and quota patterns
 */
trait HasMailingServerScopes
{
    /**
     * Scope to get active sending servers
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get inactive sending servers
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope to get servers with errors
     */
    public function scopeWithErrors(Builder $query): Builder
    {
        return $query->where('status', 'error')
            ->orWhereNotNull('last_error');
    }

    /**
     * Scope to get servers that are available to send (active and not quota exceeded)
     */
    public function scopeAvailableToSend(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->where('quota_value', 0) // Unlimited
                    ->orWhereRaw('emailing_sent_today < quota_value');
            });
    }

    /**
     * Scope to filter by server type (smtp, sendgrid, mailgun, etc.)
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get SMTP servers
     */
    public function scopeSmtp(Builder $query): Builder
    {
        return $query->where('type', 'smtp');
    }

    /**
     * Scope to get API-based servers (SendGrid, Mailgun, SES, etc.)
     */
    public function scopeApiServers(Builder $query): Builder
    {
        return $query->whereIn('type', [
            'sendgrid',
            'mailgun',
            'ses',
            'postmark',
            'sparkpost',
            'mailjet',
        ]);
    }

    /**
     * Scope to get servers that need connection check
     */
    public function scopeNeedsConnectionCheck(Builder $query, int $hours = 24): Builder
    {
        return $query->where(function ($q) use ($hours) {
            $q->whereNull('last_connection_check_at')
                ->orWhere('last_connection_check_at', '<', now()->subHours($hours));
        });
    }

    /**
     * Scope to get servers with high bounce rate
     */
    public function scopeHighBounceRate(Builder $query, float $threshold = 5.0): Builder
    {
        return $query->where('bounce_rate', '>=', $threshold);
    }

    /**
     * Scope to get servers with high complaint rate
     */
    public function scopeHighComplaintRate(Builder $query, float $threshold = 0.1): Builder
    {
        return $query->where('complaint_rate', '>=', $threshold);
    }

    /**
     * Scope to get servers that sent recently
     */
    public function scopeRecentlySent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('last_sent_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope to get servers with quota exceeded
     */
    public function scopeQuotaExceeded(Builder $query): Builder
    {
        return $query->where('quota_value', '>', 0)
            ->whereRaw('emailing_sent_today >= quota_value');
    }

    /**
     * Scope to order by sending capacity (quota remaining)
     */
    public function scopeOrderByCapacity(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->selectRaw('*, (quota_value - emailing_sent_today) as capacity_remaining')
            ->orderBy('capacity_remaining', $direction);
    }

    /**
     * Scope to get servers owned by specific user
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to search servers by name or type
     */
    public function scopeSearch(Builder $query, ?string $searchTerm): Builder
    {
        if (empty($searchTerm)) {
            return $query;
        }

        $searchTerm = "%{$searchTerm}%";

        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', $searchTerm)
                ->orWhere('type', 'like', $searchTerm)
                ->orWhere('host', 'like', $searchTerm);
        });
    }
}
