<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_banners';

    protected $fillable = [
        'title',
        'body',
        'type',
        'cta_text',
        'cta_url',
        'target_segments',
        'starts_at',
        'ends_at',
        'is_active',
        'dismissible',
    ];

    protected function casts(): array
    {
        return [
            'target_segments' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'dismissible' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }
}
