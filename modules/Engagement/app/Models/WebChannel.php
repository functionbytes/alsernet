<?php

declare(strict_types=1);

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Engagement\Database\Factories\WebChannelFactory;
use Modules\Helpdesk\Models\Inbox;

class WebChannel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'engagement_web_channels';

    protected $fillable = [
        'inbox_id',
        'name',
        'website_token',
        'domain',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByToken($query, string $token)
    {
        return $query->where('website_token', $token);
    }

    protected static function newFactory(): WebChannelFactory
    {
        return WebChannelFactory::new();
    }
}
