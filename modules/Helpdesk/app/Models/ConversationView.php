<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationView extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_conversation_views';

    protected $fillable = [
        'name',
        'description',
        'filters',
        'user_id',
        'is_public',
        'is_default',
        'is_system',
        'order',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_public' => 'boolean',
        'is_default' => 'boolean',
        'is_system' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Auto-increment order for new views
        static::creating(function ($view) {
            if (is_null($view->order)) {
                $maxOrder = static::where('user_id', $view->user_id)->max('order') ?? 0;
                $view->order = $maxOrder + 1;
            }

            // Ensure only one default view per user
            if ($view->is_default && $view->user_id) {
                static::where('user_id', $view->user_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function ($view) {
            // Ensure only one default view per user
            if ($view->is_default && $view->isDirty('is_default') && $view->user_id) {
                static::where('id', '!=', $view->id)
                    ->where('user_id', $view->user_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get the user that owns the view.
     * Note: User model is in the default connection, not helpdesk
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->setConnection(config('database.default'));
    }

    /**
     * Scope to get only public views.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get views for a specific user (owned + public).
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere('is_public', true);
        });
    }

    /**
     * Scope to get views ordered by their sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    /**
     * Check if this view can be deleted.
     */
    public function canDelete(): bool
    {
        return ! $this->is_system;
    }

    /**
     * Check if this view can be edited by a user.
     */
    public function canEdit(int $userId): bool
    {
        return ! $this->is_system && ($this->user_id === $userId || is_null($this->user_id));
    }

    /**
     * Get filter summary for display.
     */
    public function getFilterSummary(): string
    {
        if (empty($this->filters)) {
            return 'Sin filtros';
        }

        $summary = [];
        foreach ($this->filters as $key => $value) {
            if (! empty($value)) {
                $summary[] = ucfirst(str_replace('_', ' ', $key));
            }
        }

        return ! empty($summary) ? implode(', ', $summary) : 'Sin filtros';
    }

    /**
     * Reorder views based on an array of IDs.
     */
    public static function reorder(array $ids, ?int $userId = null): void
    {
        foreach ($ids as $order => $id) {
            $query = static::where('id', $id);
            if ($userId) {
                $query->where('user_id', $userId);
            }
            $query->update(['order' => $order + 1]);
        }
    }
}
