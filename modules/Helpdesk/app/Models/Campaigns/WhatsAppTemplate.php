<?php

namespace Modules\Helpdesk\Models\Campaigns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_whatsapp_templates';

    protected $fillable = [
        'external_id',
        'display_name',
        'language',
        'category',
        'status',
        'body_template',
        'header_type',
        'header_value',
        'footer_text',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public const CATEGORIES = ['utility', 'marketing', 'authentication'];

    public const STATUSES = ['approved', 'rejected', 'pending'];

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeMarketing(Builder $query): Builder
    {
        return $query->where('category', 'marketing');
    }

    /**
     * Count placeholders like {{1}}, {{2}} in the body template.
     */
    public function getParamCountAttribute(): int
    {
        if (blank($this->body_template)) {
            return 0;
        }

        preg_match_all('/\{\{\d+\}\}/', $this->body_template, $matches);

        return count(array_unique($matches[0]));
    }
}
