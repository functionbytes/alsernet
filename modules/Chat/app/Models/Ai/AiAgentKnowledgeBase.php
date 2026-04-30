<?php

namespace Modules\Chat\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentKnowledgeBase extends Model
{
    protected $table = 'chat_ai_agent_knowledge_base';

    protected $casts = [
        'tags' => 'array',
        'embedding' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'ai_agent_id',
        'title',
        'content',
        'type',
        'source_url',
        'source_type',
        'tags',
        'summary',
        'embedding',
        'is_active',
    ];

    // ==================== Relationships ====================

    /**
     * The AI agent this knowledge entry belongs to
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeHasEmbedding($query)
    {
        return $query->whereNotNull('embedding');
    }

    // ==================== Accessors ====================

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'document' => 'Documento',
            'faq' => 'FAQ',
            'article' => 'Artículo',
            'manual' => 'Manual',
            'url' => 'URL',
            default => $this->type,
        };
    }

    /**
     * Get icon based on type
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'document' => 'fa-file-alt',
            'faq' => 'fa-question-circle',
            'article' => 'fa-newspaper',
            'manual' => 'fa-book',
            'url' => 'fa-link',
            default => 'fa-file',
        };
    }

    /**
     * Check if embedding exists
     */
    public function getHasEmbeddingAttribute(): bool
    {
        return ! empty($this->embedding);
    }

    // ==================== Methods ====================

    /**
     * Generate embedding for this knowledge entry
     */
    public function generateEmbedding(): void
    {
        // This would call the actual embedding API
        // For now, just mark as having embedding with placeholder
        $this->update([
            'embedding' => ['generated_at' => now()->toIso8601String()],
        ]);
    }

    /**
     * Search knowledge by similarity (placeholder for vector search)
     */
    public static function searchBySimilarity(array $queryEmbedding, int $limit = 5)
    {
        return static::query()
            ->active()
            ->hasEmbedding()
            ->limit($limit)
            ->get();
    }
}
