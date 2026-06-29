<?php

namespace Modules\HelpdeskAgents\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AiAgentKnowledgeBase extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agent_knowledge_base';

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'tags' => 'array',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

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

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->type) {
                'document' => 'Documento',
                'faq' => 'FAQ',
                'article' => 'Artículo',
                'manual' => 'Manual',
                'url' => 'URL',
                default => $this->type,
            },
        );
    }

    protected function excerpt(): Attribute
    {
        return Attribute::make(
            get: fn (): string => Str::limit(strip_tags($this->content), 150),
        );
    }

    // ==================== Methods ====================

    public function activate(): static
    {
        $this->update(['is_active' => true]);

        return $this;
    }

    public function deactivate(): static
    {
        $this->update(['is_active' => false]);

        return $this;
    }

    public function recordUsage(): static
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);

        return $this;
    }

    public function generateEmbedding(string $model = 'text-embedding-ada-002'): static
    {
        $this->update([
            'embedding_model' => $model,
            'embedding' => null,
        ]);

        return $this;
    }

    public function generateSummary(): static
    {
        $this->update(['summary' => $this->excerpt]);

        return $this;
    }

    public function findSimilar(int $limit = 5)
    {
        if (! $this->embedding) {
            return collect();
        }

        return static::where('id', '!=', $this->id)
            ->where('ai_agent_id', $this->ai_agent_id)
            ->active()
            ->limit($limit)
            ->get();
    }
}
