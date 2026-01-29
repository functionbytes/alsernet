<?php

namespace Modules\Mailing\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Template-specific query scopes
 *
 * Common scopes for Template, EmailTemplate, and Layout models
 * Based on Acelle's template management patterns
 */
trait HasTemplateScopes
{
    /**
     * Scope to get active templates
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get inactive templates
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by user/owner
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get system templates (no user_id)
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope to get user-created templates
     */
    public function scopeUserCreated(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope to search templates by name or description
     */
    public function scopeSearch(Builder $query, ?string $searchTerm): Builder
    {
        if (empty($searchTerm)) {
            return $query;
        }

        $searchTerm = "%{$searchTerm}%";

        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', $searchTerm)
                ->orWhere('description', 'like', $searchTerm)
                ->orWhere('subject', 'like', $searchTerm);
        });
    }

    /**
     * Scope to get recently created templates
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get featured/popular templates
     */
    public function scopeFeatured(Builder $query): Builder
    {
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'is_featured')) {
            return $query->where('is_featured', true);
        }

        return $query;
    }

    /**
     * Scope to order templates by name
     */
    public function scopeOrderByName(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('name', $direction);
    }

    /**
     * Scope to get templates with specific settings
     */
    public function scopeWithSettings(Builder $query, string $key, mixed $value): Builder
    {
        return $query->whereJsonContains('settings->'.$key, $value);
    }
}
