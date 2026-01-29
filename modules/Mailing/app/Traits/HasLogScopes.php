<?php

namespace Modules\Mailing\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Log-specific query scopes
 *
 * Common scopes for BounceLog, FeedbackLog, ActivityLog, and other log models
 * Based on Acelle's logging and analytics patterns
 */
trait HasLogScopes
{
    /**
     * Scope to get logs from today
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to get logs from yesterday
     */
    public function scopeYesterday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today()->subDay());
    }

    /**
     * Scope to get logs from this week
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Scope to get logs from this month
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * Scope to get logs from last N days
     */
    public function scopeLastDays(Builder $query, int $days): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to filter logs by date range
     */
    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get logs by email
     */
    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Scope to get processed logs
     */
    public function scopeProcessed(Builder $query): Builder
    {
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'processed')) {
            return $query->where('processed', true);
        }

        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'subscriber_updated')) {
            return $query->where('subscriber_updated', true);
        }

        return $query;
    }

    /**
     * Scope to get unprocessed logs
     */
    public function scopeUnprocessed(Builder $query): Builder
    {
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'processed')) {
            return $query->where('processed', false);
        }

        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'subscriber_updated')) {
            return $query->where('subscriber_updated', false);
        }

        return $query;
    }

    /**
     * Scope to filter by bounce type (for BounceLog)
     */
    public function scopeByBounceType(Builder $query, string $type): Builder
    {
        return $query->where('bounce_type', $type);
    }

    /**
     * Scope to get hard bounces
     */
    public function scopeHardBounces(Builder $query): Builder
    {
        return $query->where('bounce_type', 'hard');
    }

    /**
     * Scope to get soft bounces
     */
    public function scopeSoftBounces(Builder $query): Builder
    {
        return $query->where('bounce_type', 'soft');
    }

    /**
     * Scope to get complaints
     */
    public function scopeComplaints(Builder $query): Builder
    {
        return $query->where('bounce_type', 'complaint');
    }

    /**
     * Scope to order logs by most recent first
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to order logs by oldest first
     */
    public function scopeOldest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Scope to filter logs by handler
     */
    public function scopeByHandler(Builder $query, int $handlerId): Builder
    {
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'bounce_handler_id')) {
            return $query->where('bounce_handler_id', $handlerId);
        }

        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'feedback_loop_handler_id')) {
            return $query->where('feedback_loop_handler_id', $handlerId);
        }

        return $query;
    }

    /**
     * Scope to search logs by email or reason
     */
    public function scopeSearch(Builder $query, ?string $searchTerm): Builder
    {
        if (empty($searchTerm)) {
            return $query;
        }

        $searchTerm = "%{$searchTerm}%";

        return $query->where(function ($q) use ($searchTerm) {
            $q->where('email', 'like', $searchTerm);

            if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'reason')) {
                $q->orWhere('reason', 'like', $searchTerm);
            }

            if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'message')) {
                $q->orWhere('message', 'like', $searchTerm);
            }
        });
    }

    /**
     * Scope to get logs with errors
     */
    public function scopeWithErrors(Builder $query): Builder
    {
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'error')) {
            return $query->whereNotNull('error');
        }

        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'last_error')) {
            return $query->whereNotNull('last_error');
        }

        return $query;
    }
}
