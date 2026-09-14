<?php

namespace Modules\Supplier\Models\Ai;

use App\Models\User;
use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Supplier\Database\Factories\Ai\AiContentFactory;
use Modules\Supplier\Models\Content\ContentLog;
use Modules\Supplier\Models\Content\ContentValidation;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Models\Supplier\Supplier;

class AiContent extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_ai_contents';

    public const STATUS_PENDING_GENERATION = 'pending_generation';

    public const STATUS_GENERATING = 'generating';

    public const STATUS_PENDING_VALIDATION = 'pending_validation';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_NEEDS_REVISION = 'needs_revision';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_PUBLISHED_HIDDEN = 'published_hidden';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ERROR_INSUFFICIENT_INFO = 'error_insufficient_info';

    public const STATUS_ERROR_SOURCE_UNAVAILABLE = 'error_source_unavailable';

    public const STATUS_ERROR_GENERATION_FAILED = 'error_generation_failed';

    public const STATUS_ON_HOLD = 'on_hold';

    public const ACTION_CREATED = 'created';

    public const ACTION_GENERATION_STARTED = 'generation_started';

    public const ACTION_GENERATION_COMPLETED = 'generation_completed';

    public const ACTION_GENERATION_FAILED = 'generation_failed';

    public const ACTION_VALIDATED = 'validated';

    public const ACTION_REJECTED = 'rejected';

    public const ACTION_REVISION_REQUESTED = 'revision_requested';

    public const ACTION_EDITED = 'edited';

    public const ACTION_PUBLISHED = 'published';

    public const ACTION_SYNCED_TO_ERP = 'synced_to_erp';

    public const ACTION_HELD = 'held';

    public const ACTION_RESTORED = 'restored';

    protected $fillable = [
        'uid',
        'supplier_id',
        'supplier_product_id',
        'product_id',
        'erp_reference',
        'model_id',
        'ean',
        'status',
        'generated_name',
        'short_description',
        'long_description',
        'bullet_points',
        'technologies',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'source_attributes',
        'sources_used',
        'prompt_id',
        'generation_metadata',
        'error_message',
        'validated_by',
        'validated_at',
        'rejection_reason',
        'published_at',
        'synced_to_erp_at',
        'content_hash',
        'assigned_to',
        'assigned_at',
        'sources_history',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bullet_points' => 'array',
            'technologies' => 'array',
            'source_attributes' => 'array',
            'sources_used' => 'array',
            'generation_metadata' => 'array',
            'sources_history' => 'array',
            'validated_at' => 'datetime',
            'published_at' => 'datetime',
            'synced_to_erp_at' => 'datetime',
            'assigned_at' => 'datetime',
            'supplier_id' => 'integer',
            'supplier_product_id' => 'integer',
            'product_id' => 'integer',
            'prompt_id' => 'integer',
            'validated_by' => 'integer',
            'assigned_to' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $content) {
            if (is_null($content->uid)) {
                $content->uid = (string) Str::ulid();
            }
            if (is_null($content->status)) {
                $content->status = self::STATUS_PENDING_GENERATION;
            }
        });

        static::created(function (self $content) {
            $content->log(self::ACTION_CREATED);
        });
    }

    protected static function newFactory(): AiContentFactory
    {
        return AiContentFactory::new();
    }

    public function supplierProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'supplier_product_id');
    }

    public function contentStatus(): BelongsTo
    {
        return $this->belongsTo(AiContentStatus::class, 'status', 'key');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class, 'prompt_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function validation(): HasOne
    {
        return $this->hasOne(ContentValidation::class, 'content_id');
    }

    public function cost(): HasMany
    {
        return $this->hasMany(AiCost::class, 'content_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ContentLog::class, 'content_id');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_GENERATION);
    }

    public function scopeNeedsValidation(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_VALIDATION);
    }

    public function scopeInReview(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_REVIEW);
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VALIDATED);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeHasErrors(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_ERROR_INSUFFICIENT_INFO,
            self::STATUS_ERROR_SOURCE_UNAVAILABLE,
            self::STATUS_ERROR_GENERATION_FAILED,
        ]);
    }

    public function scopeForSupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeUid(Builder $query, string $uid): Builder
    {
        return $query->where('uid', $uid);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeAssignedToUser(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeOnHold(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ON_HOLD);
    }

    /**
     * Exclude on_hold content from a query unless a status filter was
     * explicitly requested — used so the general "Disponibles"/"Mis
     * asignados" listing doesn't mix in items parked as "Sin contenido".
     */
    public function scopeExcludeOnHoldByDefault(Builder $query, bool $hasExplicitStatusFilter): Builder
    {
        return $hasExplicitStatusFilter ? $query : $query->where('status', '!=', self::STATUS_ON_HOLD);
    }

    public function log(
        string $action,
        ?string $previousStatus = null,
        ?string $newStatus = null,
        ?array $details = null,
        ?int $userId = null
    ): ContentLog {
        return $this->logs()->create([
            'action' => $action,
            'previous_status' => $previousStatus ?? $this->getOriginal('status'),
            'new_status' => $newStatus ?? $this->status,
            'user_id' => $userId ?? Auth::id(),
            'details' => $details,
            'ip_address' => request()?->ip(),
        ]);
    }

    public function transitionTo(string $newStatus, ?array $extraData = []): bool
    {
        $previousStatus = $this->status;

        $this->update(array_merge(['status' => $newStatus], $extraData));

        $action = $this->getActionFromTransition($previousStatus, $newStatus);
        $this->log($action, $previousStatus, $newStatus);

        return true;
    }

    public function validate(?int $userId = null, ?string $notes = null): bool
    {
        $this->validated_by = $userId ?? Auth::id();
        $this->validated_at = now();
        $this->status = self::STATUS_VALIDATED;
        $this->save();

        $this->log(self::ACTION_VALIDATED, null, null, $notes ? ['notes' => $notes] : null);

        return true;
    }

    public function reject(string $reason, ?int $userId = null): bool
    {
        $this->rejection_reason = $reason;
        $this->status = self::STATUS_REJECTED;
        $this->save();

        $this->log(self::ACTION_REJECTED, null, null, ['reason' => $reason], $userId);

        return true;
    }

    public function requestRevision(string $notes, ?int $userId = null): bool
    {
        $this->status = self::STATUS_NEEDS_REVISION;
        $this->save();

        $this->log(self::ACTION_REVISION_REQUESTED, null, null, ['notes' => $notes], $userId);

        return true;
    }

    public function publish(): bool
    {
        $this->published_at = now();
        $this->status = self::STATUS_PUBLISHED;
        $this->save();

        $this->log(self::ACTION_PUBLISHED);

        return true;
    }

    /**
     * Park content in the "Sin contenido" holding area, out of the normal
     * review queues, with an optional internal note (ej. "No subir hasta
     * Septiembre"). Works from any pre-publish status.
     */
    public function holdWithNote(?string $notes = null, ?int $userId = null): bool
    {
        $previousStatus = $this->status;
        $this->status = self::STATUS_ON_HOLD;
        if ($notes !== null && $notes !== '') {
            $this->notes = $notes;
        }
        $this->save();

        $this->log(self::ACTION_HELD, $previousStatus, self::STATUS_ON_HOLD, $notes ? ['notes' => $notes] : null, $userId);

        return true;
    }

    /**
     * Take content out of "Sin contenido" and put it back in the review
     * queue (Por validar).
     */
    public function restoreFromHold(?int $userId = null): bool
    {
        $previousStatus = $this->status;
        $this->status = self::STATUS_PENDING_VALIDATION;
        $this->save();

        $this->log(self::ACTION_RESTORED, $previousStatus, self::STATUS_PENDING_VALIDATION, null, $userId);

        return true;
    }

    public function publishHidden(): bool
    {
        $this->published_at = now();
        $this->status = self::STATUS_PUBLISHED_HIDDEN;
        $this->save();

        $this->log(self::ACTION_PUBLISHED);

        return true;
    }

    public function syncToErp(): bool
    {
        $this->synced_to_erp_at = now();
        $this->save();

        $this->log(self::ACTION_SYNCED_TO_ERP);

        return true;
    }

    public function markAsGenerating(): bool
    {
        return $this->transitionTo(self::STATUS_GENERATING);
    }

    public function markAsGenerated(): bool
    {
        return $this->transitionTo(self::STATUS_PENDING_VALIDATION);
    }

    public function markAsFailed(string $errorMessage, string $errorStatus = self::STATUS_ERROR_GENERATION_FAILED): bool
    {
        return $this->transitionTo($errorStatus, ['error_message' => $errorMessage]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING_GENERATION;
    }

    public function isGenerating(): bool
    {
        return $this->status === self::STATUS_GENERATING;
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isOnHold(): bool
    {
        return $this->status === self::STATUS_ON_HOLD;
    }

    public function hasErrors(): bool
    {
        return in_array($this->status, [
            self::STATUS_ERROR_INSUFFICIENT_INFO,
            self::STATUS_ERROR_SOURCE_UNAVAILABLE,
            self::STATUS_ERROR_GENERATION_FAILED,
        ]);
    }

    /**
     * Calculate a quality score (0–100) based on content completeness.
     */
    public function getQualityScoreAttribute(): int
    {
        $score = 0;

        if ($this->generated_name !== null && strlen($this->generated_name) > 5) {
            $score += 20;
        }
        if ($this->long_description !== null && strlen($this->long_description) > 200) {
            $score += 25;
        }
        if ($this->short_description !== null && strlen($this->short_description) > 30) {
            $score += 15;
        }
        if ($this->seo_title !== null) {
            $score += 15;
        }
        if ($this->seo_keywords !== null) {
            $score += 10;
        }
        if ($this->sources_used !== null && count($this->sources_used) > 0) {
            $score += 15;
        }

        return $score;
    }

    /**
     * Calculate an SEO score (0–100) based on field length rules.
     *
     * @return array{score: int, color: string, checks: array<int, array{label: string, pass: bool}>}
     */
    public function seoScore(): array
    {
        $checks = [
            [
                'label' => 'Título SEO (30-70 chars)',
                'pass' => $this->seo_title !== null && strlen($this->seo_title) >= 30 && strlen($this->seo_title) <= 70,
            ],
            [
                'label' => 'Meta descripción (100-165 chars)',
                'pass' => $this->seo_description !== null && strlen($this->seo_description) >= 100 && strlen($this->seo_description) <= 165,
            ],
            [
                'label' => 'Nombre generado no vacío',
                'pass' => ! empty($this->generated_name),
            ],
            [
                'label' => 'Descripción corta (50-400 chars)',
                'pass' => $this->short_description !== null && strlen($this->short_description) >= 50 && strlen($this->short_description) <= 400,
            ],
            [
                'label' => 'Keywords definidas',
                'pass' => ! empty($this->seo_keywords),
            ],
        ];

        $passed = count(array_filter($checks, fn ($c) => $c['pass']));
        $score = (int) round(($passed / count($checks)) * 100);

        $color = match (true) {
            $score >= 70 => 'success',
            $score >= 40 => 'warning',
            default => 'danger',
        };

        return compact('score', 'color', 'checks');
    }

    /**
     * Check whether another record already has the same long_description content.
     */
    public static function hasDuplicate(string $longDescription, ?int $excludeId = null): bool
    {
        $hash = md5(trim($longDescription));

        return static::query()
            ->where('content_hash', $hash)
            ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    protected function getActionFromTransition(string $from, string $to): string
    {
        return match ($to) {
            self::STATUS_GENERATING => self::ACTION_GENERATION_STARTED,
            self::STATUS_PENDING_VALIDATION => self::ACTION_GENERATION_COMPLETED,
            self::STATUS_VALIDATED => self::ACTION_VALIDATED,
            self::STATUS_REJECTED => self::ACTION_REJECTED,
            self::STATUS_NEEDS_REVISION => self::ACTION_REVISION_REQUESTED,
            self::STATUS_PUBLISHED => self::ACTION_PUBLISHED,
            self::STATUS_ERROR_GENERATION_FAILED,
            self::STATUS_ERROR_INSUFFICIENT_INFO,
            self::STATUS_ERROR_SOURCE_UNAVAILABLE => self::ACTION_GENERATION_FAILED,
            default => self::ACTION_EDITED,
        };
    }
}
