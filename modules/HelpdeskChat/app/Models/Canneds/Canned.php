<?php

namespace Modules\HelpdeskChat\Models\Canneds;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Canned extends Model
{
    protected $table = 'helpdesk_canneds';

    protected $fillable = [
        'account_id',
        'user_id',
        'title',
        'description',
        'visibility',
        'short_code',
        'content',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the account that owns the canned response.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user that created this canned response.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only public responses.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', 'everyone');
    }

    /**
     * Scope to get responses accessible by a specific user.
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('visibility', 'everyone')
                ->orWhere(function ($subQuery) use ($user) {
                    $subQuery->where('visibility', 'personal')
                        ->where('user_id', $user->id);
                })
                ->orWhere(function ($subQuery) use ($user) {
                    $subQuery->where('visibility', 'team')
                        ->where('user_id', $user->id); // Team visibility for now means created by user
                });
        });
    }

    /**
     * Scope to search by short code or content.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('short_code', 'LIKE', "%{$term}%")
                ->orWhere('title', 'LIKE', "%{$term}%")
                ->orWhere('content', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Increment usage count and update last used timestamp.
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get available template variables.
     */
    public static function getAvailableVariables(): array
    {
        return [
            'contact.name' => 'Contact name',
            'contact.email' => 'Contact email',
            'contact.phone' => 'Contact phone number',
            'agent.name' => 'Agent name',
            'agent.email' => 'Agent email',
            'inbox.name' => 'Inbox name',
            'conversation.id' => 'Conversation ID',
        ];
    }

    /**
     * Replace template variables in content.
     */
    public function render(array $context = []): string
    {
        $content = $this->content;

        // Replace {{variable}} patterns
        foreach ($context as $key => $value) {
            $content = str_replace('{{'.$key.'}}', $value ?? '', $content);
        }

        return $content;
    }

    /**
     * Get visibility badge color.
     */
    public function getVisibilityBadgeColorAttribute(): string
    {
        return match ($this->visibility) {
            'personal' => 'primary',
            'team' => 'warning',
            'everyone' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Get visibility label.
     */
    public function getVisibilityLabelAttribute(): string
    {
        return match ($this->visibility) {
            'personal' => 'Personal',
            'team' => 'Team',
            'everyone' => 'Everyone',
            default => ucfirst($this->visibility),
        };
    }
}
