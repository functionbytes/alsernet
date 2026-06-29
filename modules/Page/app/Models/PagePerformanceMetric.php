<?php

namespace Modules\Page\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Page\Database\Factories\PagePerformanceMetricFactory;

class PagePerformanceMetric extends Model
{
    use HasFactory;

    protected static function newFactory(): PagePerformanceMetricFactory
    {
        return PagePerformanceMetricFactory::new();
    }

    protected $fillable = [
        'page_id',
        'strategy',
        'performance_score',
        'accessibility_score',
        'seo_score',
        'best_practices_score',
        'lcp',
        'fid',
        'cls_score',
        'fcp',
        'ttfb',
        'tbt',
        'speed_index',
        'opportunities',
        'diagnostics',
        'page_url',
    ];

    protected function casts(): array
    {
        return [
            'opportunities' => 'array',
            'diagnostics' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function getPerformanceLabelAttribute(): string
    {
        $score = $this->performance_score ?? 0;

        if ($score >= 90) {
            return 'Bueno';
        }

        if ($score >= 50) {
            return 'Necesita mejorar';
        }

        return 'Deficiente';
    }

    public function getPerformanceColorAttribute(): string
    {
        $score = $this->performance_score ?? 0;

        if ($score >= 90) {
            return 'success';
        }

        if ($score >= 50) {
            return 'warning';
        }

        return 'danger';
    }
}
