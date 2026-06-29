<?php

namespace Modules\Sitemap\Models;

use Illuminate\Database\Eloquent\Model;

class SitemapGeneration extends Model
{
    protected $fillable = [
        'status',
        'url_count',
        'duration_ms',
        'error_message',
        'source',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'url_count' => 'integer',
            'duration_ms' => 'integer',
            'generated_by' => 'integer',
        ];
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function durationInSeconds(): float
    {
        return round($this->duration_ms / 1000, 2);
    }
}
