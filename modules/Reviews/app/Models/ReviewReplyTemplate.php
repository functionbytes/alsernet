<?php

namespace Modules\Reviews\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Reviews\Database\Factories\ReviewReplyTemplateFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ReviewReplyTemplate extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'review_reply_templates';

    protected static function newFactory()
    {
        return ReviewReplyTemplateFactory::new();
    }

    protected $fillable = [
        'name',
        'body',
        'category',
        'language',
        'is_active',
        'usage_count',
        'created_by',
        'review_google_location_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'usage_count' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'body', 'category', 'language', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ReviewGoogleLocation::class, 'review_google_location_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ReviewTemplateTranslation::class, 'template_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ReviewTemplateVersion::class, 'review_reply_template_id');
    }

    public function latestVersion(): ?ReviewTemplateVersion
    {
        return $this->versions()->orderByDesc('version_number')->first();
    }

    public function getTextForLanguage(string $language): string
    {
        $translation = $this->translations->firstWhere('language', $language);

        return $translation?->template_text ?? $this->body;
    }

    /** @return string[] */
    public function availableLanguages(): array
    {
        $languages = $this->translations->pluck('language')->toArray();

        if (! in_array($this->language ?? 'es', $languages, true)) {
            array_unshift($languages, $this->language ?? 'es');
        }

        return $languages;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    public function scopeByLocation($query, int $locationId)
    {
        return $query->where(function ($q) use ($locationId) {
            $q->where('review_google_location_id', $locationId)
                ->orWhereNull('review_google_location_id');
        });
    }

    public function scopePositive($query)
    {
        return $query->where('category', 'positive');
    }

    public function scopeNegative($query)
    {
        return $query->where('category', 'negative');
    }

    public function scopeNeutral($query)
    {
        return $query->where('category', 'neutral');
    }

    public function scopeGeneral($query)
    {
        return $query->where('category', 'general');
    }

    public function scopeMostUsed($query)
    {
        return $query->orderByDesc('usage_count');
    }

    public function render(array $variables = []): string
    {
        $content = $this->body;

        foreach ($variables as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        return $content;
    }

    public function renderForLanguage(string $language, array $variables = []): string
    {
        $content = $this->getTextForLanguage($language);

        foreach ($variables as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        return $content;
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function getAvailableVariables(): array
    {
        return [
            '{reviewer_name}' => 'Nombre del reviewer',
            '{location_name}' => 'Nombre del negocio/ubicación',
            '{star_rating}' => 'Calificación (1-5)',
            '{comment_summary}' => 'Resumen del comentario',
            '{date}' => 'Fecha de la reseña',
        ];
    }

    /**
     * Get actual variable values from a review.
     * Centralizes template variable logic to prevent duplication.
     */
    public function getVariablesForReview(Review $review): array
    {
        return [
            '{reviewer_name}' => $review->reviewer_name,
            '{location_name}' => $review->location?->name ?? '',
            '{star_rating}' => $review->star_rating->value(),
            '{date}' => $review->review_time?->format('d/m/Y') ?? '',
            '{comment_summary}' => $review->comment ? substr($review->comment, 0, 100) : '',
        ];
    }

    /**
     * Render template with values from a review.
     * Convenience method combining getVariablesForReview + render.
     */
    public function renderForReview(Review $review): string
    {
        return $this->render($this->getVariablesForReview($review));
    }

    /**
     * Render template in a specific language with values from a review.
     */
    public function renderForReviewInLanguage(Review $review, string $language): string
    {
        return $this->renderForLanguage($language, $this->getVariablesForReview($review));
    }
}
