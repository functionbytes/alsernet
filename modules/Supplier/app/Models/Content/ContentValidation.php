<?php

namespace Modules\Supplier\Models\Content;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Content\ContentValidationFactory;
use Modules\Supplier\Models\Ai\AiContent;

class ContentValidation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'supplier_content_validations';

    /**
     * Disable updated_at timestamp (only created_at)
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'content_id',
        'quality_score',
        'readability_score',
        'keyword_density',
        'unique_words_ratio',
        'sentence_avg_length',
        'has_required_sections',
        'has_brand_terms',
        'has_prohibited_words',
        'plagiarism_score',
        'validation_status',
        'issues',
        'suggestions',
        'validated_by',
        'validated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'quality_score' => 'decimal:2',
            'readability_score' => 'decimal:2',
            'keyword_density' => 'decimal:4',
            'unique_words_ratio' => 'decimal:4',
            'sentence_avg_length' => 'decimal:2',
            'plagiarism_score' => 'decimal:2',
            'has_required_sections' => 'boolean',
            'has_brand_terms' => 'boolean',
            'has_prohibited_words' => 'boolean',
            'issues' => 'array',
            'suggestions' => 'array',
            'validated_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ContentValidationFactory
    {
        return ContentValidationFactory::new();
    }

    /**
     * Get the content that owns this validation.
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(AiContent::class, 'content_id');
    }

    /**
     * Scope to filter by validation status.
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('validation_status', $status);
    }

    /**
     * Scope to filter passed validations.
     */
    public function scopePassed(Builder $query): Builder
    {
        return $query->where('validation_status', 'passed');
    }

    /**
     * Scope to filter failed validations.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('validation_status', 'failed');
    }

    /**
     * Scope to filter validations that need review.
     */
    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->where('validation_status', 'needs_review');
    }

    /**
     * Scope to filter by minimum quality score.
     */
    public function scopeMinQuality(Builder $query, float $minScore): Builder
    {
        return $query->where('quality_score', '>=', $minScore);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope to filter validations from today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to filter validations from this week.
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Check if validation passed.
     */
    public function isPassed(): bool
    {
        return $this->validation_status === 'passed';
    }

    /**
     * Check if validation failed.
     */
    public function isFailed(): bool
    {
        return $this->validation_status === 'failed';
    }

    /**
     * Check if validation needs manual review.
     */
    public function needsReview(): bool
    {
        return $this->validation_status === 'needs_review';
    }

    /**
     * Get validation issues count.
     */
    public function getIssuesCount(): int
    {
        return is_array($this->issues) ? count($this->issues) : 0;
    }

    /**
     * Get suggestions count.
     */
    public function getSuggestionsCount(): int
    {
        return is_array($this->suggestions) ? count($this->suggestions) : 0;
    }

    /**
     * Check if content has high quality.
     */
    public function isHighQuality(): bool
    {
        return $this->quality_score >= 85;
    }

    /**
     * Check if content has medium quality.
     */
    public function isMediumQuality(): bool
    {
        return $this->quality_score >= 60 && $this->quality_score < 85;
    }

    /**
     * Check if content has low quality.
     */
    public function isLowQuality(): bool
    {
        return $this->quality_score < 60;
    }

    /**
     * Get validation summary for display.
     */
    public function getSummary(): array
    {
        return [
            'quality_score' => $this->quality_score,
            'readability_score' => $this->readability_score,
            'status' => $this->validation_status,
            'issues_count' => $this->getIssuesCount(),
            'suggestions_count' => $this->getSuggestionsCount(),
            'validated_at' => $this->validated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
