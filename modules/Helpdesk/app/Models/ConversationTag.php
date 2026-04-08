<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ConversationTag extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_conversation_tags';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get conversations with this tag
     */
    public function conversations()
    {
        return $this->belongsToMany(
            Conversation::class,
            'helpdesk_conversation_tag_pivot',
            'tag_id',
            'conversation_id'
        )->withTimestamps();
    }

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = static::generateSlug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name') && empty($tag->slug)) {
                $tag->slug = static::generateSlug($tag->name);
            }
        });
    }

    /**
     * Generate unique slug from name
     */
    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = static::where('slug', 'like', $slug.'%')->count();

        if ($count > 0) {
            return $slug.'-'.($count + 1);
        }

        return $slug;
    }

    /**
     * Scope: Active tags only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Search by name
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }

    /**
     * Get usage count (how many conversations have this tag)
     */
    public function getUsageCountAttribute()
    {
        return $this->conversations()->count();
    }

    /**
     * Get badge HTML for display
     */
    public function getBadgeHtmlAttribute()
    {
        $color = $this->color ?? '#6c757d';

        return sprintf(
            '<span class="badge" style="background-color: %s; color: white;">%s</span>',
            htmlspecialchars($color),
            htmlspecialchars($this->name)
        );
    }
}
