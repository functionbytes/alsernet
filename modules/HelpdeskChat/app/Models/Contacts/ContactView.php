<?php

namespace Modules\HelpdeskChat\Models\Contacts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskChat\Models\Accounts\Account;

class ContactView extends Model
{
    protected $fillable = [
        'account_id',
        'user_id',
        'name',
        'view_type',
        'filters',
        'sort_by',
        'sort_direction',
        'is_shared',
        'is_default',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_shared' => 'boolean',
            'is_default' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Get the account that owns the saved view.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who created the saved view.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter views for a specific account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope to filter views for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter views by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('view_type', $type);
    }

    /**
     * Scope to get shared views.
     */
    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    /**
     * Scope to get views accessible by a user (own + shared).
     */
    public function scopeAccessibleBy($query, int $userId, int $accountId)
    {
        return $query->where('account_id', $accountId)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('is_shared', true);
            });
    }

    /**
     * Scope to order views by position.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('created_at');
    }

    /**
     * Get a specific filter value.
     */
    public function getFilter(string $key, mixed $default = null): mixed
    {
        return data_get($this->filters, $key, $default);
    }

    /**
     * Set a specific filter value.
     */
    public function setFilter(string $key, mixed $value): void
    {
        $filters = $this->filters ?? [];
        data_set($filters, $key, $value);
        $this->filters = $filters;
    }

    /**
     * Check if this view has a specific filter.
     */
    public function hasFilter(string $key): bool
    {
        return array_key_exists($key, $this->filters ?? []);
    }

    /**
     * Get display name with owner info for shared views.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->is_shared && $this->user) {
            return "{$this->name} ({$this->user->name})";
        }

        return $this->name;
    }
}
