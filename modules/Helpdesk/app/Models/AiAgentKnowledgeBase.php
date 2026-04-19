<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AiAgentKnowledgeBase extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agent_knowledge_base';

    protected $casts = [
        'metadata' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'ai_agent_id',
        'title',
        'content',
        'type',
        'source_url',
        'source_type',
        'source_id',
        'embedding',
        'embedding_model',
        'metadata',
        'tags',
        'summary',
        'usage_count',
        'last_used_at',
        'is_active',
    ];

    // ==================== Relationships ====================

    /**
     * The AI agent this knowledge belongs to
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

    public function scopeBySource($query, string $sourceType, ?int $sourceId = null)
    {
        $query->where('source_type', $sourceType);

        if ($sourceId) {
            $query->where('source_id', $sourceId);
        }

        return $query;
    }

    public function scopeSearch($query, string $searchTerm)
    {
        return $query->whereRaw(
            'MATCH(title, content) AGAINST(? IN NATURAL LANGUAGE MODE)',
            [$searchTerm]
        );
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
     * Get type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'document' => 'ti ti-file-text',
            'faq' => 'ti ti-help',
            'article' => 'ti ti-article',
            'manual' => 'ti ti-book',
            'url' => 'ti ti-link',
            default => 'ti ti-circle',
        };
    }

    /**
     * Get excerpt from content
     */
    public function getExcerptAttribute(): string
    {
        return Str::limit(strip_tags($this->content), 150);
    }

    // ==================== Methods ====================

    /**
     * Activate the knowledge entry
     */
    public function activate(): self
    {
        $this->update(['is_active' => true]);

        return $this;
    }

    /**
     * Deactivate the knowledge entry
     */
    public function deactivate(): self
    {
        $this->update(['is_active' => false]);

        return $this;
    }

    /**
     * Record knowledge usage
     */
    public function recordUsage(): self
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);

        return $this;
    }

    /**
     * Generate embedding for the content
     */
    public function generateEmbedding(string $model = 'text-embedding-ada-002'): self
    {
        // This would call the embedding API
        // For now, just placeholder
        $this->update([
            'embedding_model' => $model,
            'embedding' => null, // Would be the actual embedding vector
        ]);

        return $this;
    }

    /**
     * Generate summary using AI
     */
    public function generateSummary(): self
    {
        // This would call the LLM to generate a summary
        // For now, just use excerpt
        $this->update([
            'summary' => $this->excerpt,
        ]);

        return $this;
    }

    /**
     * Find similar knowledge entries using embeddings
     */
    public function findSimilar(int $limit = 5)
    {
        if (! $this->embedding) {
            return collect();
        }

        // This would use vector similarity search
        // For now, just return related by tags
        return static::where('id', '!=', $this->id)
            ->where('ai_agent_id', $this->ai_agent_id)
            ->active()
            ->limit($limit)
            ->get();
    }
}
