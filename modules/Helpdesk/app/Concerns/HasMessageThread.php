<?php

namespace Modules\Helpdesk\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Models\Customer;

/**
 * Shared query scopes and helpers for Conversation and Ticket.
 *
 * Both models represent a message thread with a customer, an optional assignee,
 * a status-backed open/closed lifecycle, priority, and an archive flag.
 *
 * Methods intentionally NOT included here (diverge between models):
 * - assignee()    — Conversation uses HasCrossDatabaseUserRelation; Ticket uses raw newBelongsTo()
 * - assignTo()    — Ticket creates a system event item; Conversation does not
 * - close()       — Ticket creates system events and uses different cache keys
 * - reopen()      — Same as close()
 * - scopeOpen()   — Conversation uses whereHas(); Ticket uses whereIn(cached ids)
 * - scopeClosed() — Same as scopeOpen()
 */
trait HasMessageThread
{
    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // -------------------------------------------------------------------------
    // Archive lifecycle
    // -------------------------------------------------------------------------

    public function archive(): static
    {
        $this->update(['is_archived' => true]);

        return $this;
    }

    public function unarchive(): static
    {
        $this->update(['is_archived' => false]);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assignee_id', $userId);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assignee_id');
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    // -------------------------------------------------------------------------
    // Boolean helpers
    // -------------------------------------------------------------------------

    public function isOpen(): bool
    {
        return $this->status && $this->status->is_open;
    }

    public function isClosed(): bool
    {
        return ! $this->isOpen();
    }

    public function isAssigned(): bool
    {
        return $this->assignee_id !== null;
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }
}
