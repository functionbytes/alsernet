<?php

declare(strict_types=1);

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Engagement\Database\Factories\MobileDeviceFactory;
use Modules\Helpdesk\Models\Inbox;

class MobileDevice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'engagement_mobile_devices';

    protected $fillable = [
        'inbox_id',
        'device_token',
        'platform',
        'os_version',
        'app_version',
        'locale',
        'metadata',
        'last_seen_at',
        'push_enabled',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_seen_at' => 'datetime',
            'push_enabled' => 'boolean',
        ];
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function scopeActive($query)
    {
        return $query->where('push_enabled', true);
    }

    public function scopeForInbox($query, int $inboxId)
    {
        return $query->where('inbox_id', $inboxId);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    protected static function newFactory(): MobileDeviceFactory
    {
        return MobileDeviceFactory::new();
    }
}
