<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketCannedReply extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_canned_replies';

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'html_body',
        'category',
        'tags',
        'short_code',
        'is_global',
        'is_active',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_global' => 'boolean',
            'is_active' => 'boolean',
            'usage_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected $appends = [
        'preview',
    ];

    /**
     * Get the user who owns this canned reply
     * Note: User model is in the default connection, not helpdesk
     */
    public function user()
    {
        $defaultConnection = config('database.default');
        $defaultDatabase = config("database.connections.{$defaultConnection}.database");

        $instance = (new User)->setConnection(null);
        $relation = $this->newBelongsTo(
            $instance->newQuery(),
            $this,
            'user_id',
            'id',
            'user'
        );

        // Override the query to use the correct database for users table
        $relation->getQuery()->from("{$defaultDatabase}.users");

        return $relation;
    }

    /**
     * Get ticket categories linked to this canned reply
     */
    public function ticketCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            TicketCategory::class,
            'helpdesk_ticket_category_ticket_canned_reply',
            'ticket_canned_reply_id',
            'ticket_category_id'
        )->withPivot('order')
            ->withTimestamps();
    }

    /**
     * Scope: Get global replies (available to all agents)
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * Scope: Get active replies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get replies for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere('is_global', true);
        });
    }

    /**
     * Scope: Search by title, body, or tags
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('title', 'like', "%{$term}%")
            ->orWhere('body', 'like', "%{$term}%")
            ->orWhere('category', 'like', "%{$term}%");
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Filter by tags
     */
    public function scopeWithTags($query, $tags)
    {
        $tags = is_array($tags) ? $tags : [$tags];

        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /**
     * Scope: Most used replies
     */
    public function scopeMostUsed($query, $limit = 10)
    {
        return $query->orderByDesc('usage_count')->limit($limit);
    }

    /**
     * Scope: Recently added
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->latest()->limit($limit);
    }

    /**
     * Get preview of the reply (first 100 chars of plain text)
     */
    public function getPreviewAttribute()
    {
        $text = strip_tags($this->html_body ?? $this->content);

        return strlen($text) > 100 ? substr($text, 0, 100).'...' : $text;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    /**
     * Check if reply can be edited by a user
     */
    public function canBeEditedBy($userId)
    {
        return $this->user_id === $userId || auth()->user()?->can('manager.helpdesk.tickets.manage-canned-replies');
    }

    /**
     * Get rich content (prefer HTML over plain text)
     */
    public function getRichContentAttribute()
    {
        return $this->html_body ?? $this->attributes['content'] ?? null;
    }
}
