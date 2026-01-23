<?php

namespace Modules\HelpdeskChat\Models\Conversations;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ConversationTag extends Model
{
    protected $table = 'helpdesk_conversation_tags';

    protected $fillable = [
        'account_id',
        'name',
        'slug',
        'color',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(
            Conversation::class,
            'helpdesk_conversation_tag',
            'helpdesk_conversation_tag_id',
            'conversation_id'
        )->withTimestamps();
    }

    /**
     * Scope to get only active tags
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
