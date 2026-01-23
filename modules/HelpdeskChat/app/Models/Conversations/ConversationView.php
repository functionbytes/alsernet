<?php

namespace Modules\HelpdeskChat\Models\Conversations;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationView extends Model
{
    protected $table = 'helpdesk_conversation_views';

    protected $fillable = [
        'account_id',
        'user_id',
        'name',
        'description',
        'filters',
        'is_default',
        'is_shared',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_default' => 'boolean',
            'is_shared' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get views for a specific user (personal + shared views)
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere('is_shared', true);
        });
    }

    /**
     * Scope to order views by order field
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
    }
}
