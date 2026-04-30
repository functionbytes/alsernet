<?php

namespace Modules\Chat\Models\Conversations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Concerns\BelongsToAccount;

class ConversationStatus extends Model
{
    use BelongsToAccount;

    // Status slug constants
    public const STATUS_OPEN = 'open';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'chat_conversation_statuses';

    protected $fillable = [
        'account_id',
        'name',
        'slug',
        'color',
        'is_open',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'status_id');
    }

    /**
     * Scope to get only active statuses
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only open statuses
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_open', true);
    }

    /**
     * Scope to order statuses by order field
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Scope to filter by slug
     */
    public function scopeWhereSlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }
}
