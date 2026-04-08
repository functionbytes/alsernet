<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoAuditLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seo_audit_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'seo_meta_id',
        'url',
        'score',
        'grade',
        'issues_count',
        'issues',
        'passed_count',
        'audited_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'issues' => 'array',
        'audited_at' => 'datetime',
        'score' => 'integer',
        'issues_count' => 'integer',
        'passed_count' => 'integer',
    ];

    /**
     * Get the SEO meta record this log belongs to.
     */
    public function seoMeta(): BelongsTo
    {
        return $this->belongsTo(SeoMeta::class, 'seo_meta_id');
    }

    /**
     * Scope to order by most recent audits first.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('audited_at', 'desc');
    }

    /**
     * Scope to filter logs for a specific SEO meta record.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeForMeta($query, int $metaId)
    {
        return $query->where('seo_meta_id', $metaId);
    }
}
