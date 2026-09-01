<?php

namespace Modules\HelpdeskEmailLog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\HelpdeskEmailLog\Enums\SuppressionReason;

/**
 * @property string $email
 * @property string $module '' = supresión global (todos los módulos)
 * @property SuppressionReason $reason
 * @property ?int $causer_id
 * @property ?string $causer_type
 * @property ?int $email_log_id
 * @property ?string $notes
 */
class EmailSuppression extends Model
{
    protected $fillable = [
        'email', 'module', 'reason', 'causer_id', 'causer_type', 'email_log_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'reason' => SuppressionReason::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->email = mb_strtolower(trim($model->email));
        });
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class);
    }

    public function scopeCoversModule(Builder $query, ?string $module): Builder
    {
        return $query->where(function (Builder $q) use ($module) {
            $q->where('module', '');

            if ($module) {
                $q->orWhere('module', $module);
            }
        });
    }

    /**
     * Si $email está suprimida — en global, o específicamente para $module.
     */
    public static function isSuppressed(string $email, ?string $module = null): bool
    {
        return self::query()
            ->where('email', mb_strtolower(trim($email)))
            ->coversModule($module)
            ->exists();
    }
}
