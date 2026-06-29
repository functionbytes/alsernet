<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\Lang;
use Modules\Mailer\Enums\EndpointLogStatus;

class MailerEndpoint extends Model
{
    use SoftDeletes;

    protected $table = 'mailer_endpoints';

    protected $fillable = [
        'name',
        'slug',
        'source',
        'type',
        'description',
        'mailer_template_id',
        'lang_id',
        'expected_variables',
        'required_variables',
        'variable_mappings',
        'is_active',
        'api_token',
        'requests_count',
        'last_request_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_variables' => 'array',
            'required_variables' => 'array',
            'variable_mappings' => 'array',
            'is_active' => 'boolean',
            'last_request_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Generate a unique API token
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->api_token)) {
                $model->api_token = self::generateToken();
            }
        });
    }

    /**
     * Get the associated email template
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MailerTemplate::class, 'mailer_template_id');
    }

    /**
     * Get the associated language
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Lang::class, 'lang_id');
    }

    /**
     * Get the associated language (alias)
     */
    public function lang(): BelongsTo
    {
        return $this->belongsTo(Lang::class, 'lang_id');
    }

    /**
     * Get the endpoint logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(MailerEndpointLog::class);
    }

    /**
     * Get successful logs
     */
    public function successLogs(): HasMany
    {
        return $this->logs()->where('status', EndpointLogStatus::Success);
    }

    /**
     * Get failed logs
     */
    public function failedLogs(): HasMany
    {
        return $this->logs()->where('status', EndpointLogStatus::Failed);
    }

    /**
     * Calcular success rate en base a success_logs_count (withCount) o requests_count
     * Soporta ambos caminos: listings con withCount y vistas detalle con successLogs()
     */
    public function successRate(?int $successCount = null, ?int $totalCount = null): float
    {
        $total = $totalCount ?? $this->requests_count ?? 0;

        if ($total <= 0) {
            return 0.0;
        }

        if ($successCount === null) {
            $successCount = $this->success_logs_count ?? $this->successLogs()->count();
        }

        return round(($successCount / $total) * 100, 1);
    }

    /**
     * Accessor: $endpoint->success_rate
     */
    public function getSuccessRateAttribute(): float
    {
        return $this->successRate();
    }

    /**
     * Scope: only active endpoints
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: only inactive endpoints
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: filter by source
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }
}
