<?php

namespace Modules\Mailing\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Common local scopes for Mailing module models
 *
 * This trait provides reusable query scopes that are common across
 * multiple models in the Acelle/Mailrelay system
 */
trait HasCommonScopes
{
    /**
     * Scope to filter by status (generic)
     */
    public function scopeByStatus(Builder $query, string|array $status): Builder
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }

        return $query->where('status', $status);
    }

    /**
     * Scope to exclude specific statuses
     */
    public function scopeExceptStatus(Builder $query, string|array $status): Builder
    {
        if (is_array($status)) {
            return $query->whereNotIn('status', $status);
        }

        return $query->where('status', '!=', $status);
    }

    /**
     * Scope to filter active records
     */
    public function scopeActive(Builder $query): Builder
    {
        // Handle different active column names
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'is_active')) {
            return $query->where('is_active', true);
        }

        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'status')) {
            return $query->where('status', 'active');
        }

        return $query;
    }

    /**
     * Scope to filter inactive records
     */
    public function scopeInactive(Builder $query): Builder
    {
        // Handle different active column names
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'is_active')) {
            return $query->where('is_active', false);
        }

        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'status')) {
            return $query->where('status', 'inactive');
        }

        return $query;
    }

    /**
     * Scope to filter records created within a date range
     */
    public function scopeCreatedBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter records created after a specific date
     */
    public function scopeCreatedAfter(Builder $query, string $date): Builder
    {
        return $query->where('created_at', '>=', $date);
    }

    /**
     * Scope to filter records created in the last N days
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to filter records by user/customer ownership
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        // Try different column names
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'user_id')) {
            return $query->where('user_id', $userId);
        }

        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'customer_id')) {
            return $query->where('customer_id', $userId);
        }

        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'admin_id')) {
            return $query->where('admin_id', $userId);
        }

        return $query;
    }

    /**
     * Scope to search by name (generic text search)
     */
    public function scopeSearch(Builder $query, ?string $searchTerm): Builder
    {
        if (empty($searchTerm)) {
            return $query;
        }

        $searchTerm = "%{$searchTerm}%";

        return $query->where(function ($q) use ($searchTerm) {
            // Search in common columns
            if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'name')) {
                $q->where('name', 'like', $searchTerm);
            }

            if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'subject')) {
                $q->orWhere('subject', 'like', $searchTerm);
            }

            if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'email')) {
                $q->orWhere('email', 'like', $searchTerm);
            }

            if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'description')) {
                $q->orWhere('description', 'like', $searchTerm);
            }
        });
    }

    /**
     * Scope to order by custom field (generic)
     */
    public function scopeOrdered(Builder $query, string $column = 'created_at', string $direction = 'desc'): Builder
    {
        return $query->orderBy($column, $direction);
    }

    /**
     * Scope to filter records with UID
     */
    public function scopeByUid(Builder $query, string $uid): Builder
    {
        return $query->where('uid', $uid);
    }

    /**
     * Scope to get published/visible records
     */
    public function scopePublished(Builder $query): Builder
    {
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'is_published')) {
            return $query->where('is_published', true);
        }

        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'visible')) {
            return $query->where('visible', true);
        }

        return $query;
    }

    /**
     * Scope to filter verified records
     */
    public function scopeVerified(Builder $query): Builder
    {
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'verified_at')) {
            return $query->whereNotNull('verified_at');
        }

        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'validated_at')) {
            return $query->whereNotNull('validated_at');
        }

        return $query;
    }

    /**
     * Scope to filter unverified records
     */
    public function scopeUnverified(Builder $query): Builder
    {
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'verified_at')) {
            return $query->whereNull('verified_at');
        }

        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'validated_at')) {
            return $query->whereNull('validated_at');
        }

        return $query;
    }
}
