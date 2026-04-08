<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiAgentTag extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agent_tags';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'description',
        'color',
        'icon',
        'system_prompt_addition',
        'priority',
        'is_active',
        'metadata',
    ];

    // ==================== Relationships ====================

    /**
     * Conversations that have this tag
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(
            Conversation::class,
            'helpdesk_conversation_tag',
            'tag_id',
            'conversation_id'
        )->withPivot('tagged_at');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    // ==================== Accessors ====================

    /**
     * Get the tag's badge HTML
     */
    public function getBadgeHtmlAttribute(): string
    {
        $icon = $this->icon ? "<i class='{$this->icon} me-1'></i>" : '';

        return "<span class='badge' style='background-color: {$this->color}; color: #fff;'>{$icon}{$this->name}</span>";
    }

    // ==================== Methods ====================

    /**
     * Activate the tag
     */
    public function activate(): self
    {
        $this->update(['is_active' => true]);

        return $this;
    }

    /**
     * Deactivate the tag
     */
    public function deactivate(): self
    {
        $this->update(['is_active' => false]);

        return $this;
    }

    /**
     * Get usage count
     */
    public function getUsageCount(): int
    {
        return $this->conversations()->count();
    }
}
