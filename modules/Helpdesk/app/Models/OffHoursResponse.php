<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;

class OffHoursResponse extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_off_hours_responses';

    protected $fillable = [
        'channel',
        'message',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Find the best matching active response for the given channel.
     * Channel-specific takes priority over global (null channel).
     */
    public static function findForChannel(?string $channel): ?static
    {
        // Try channel-specific first
        if ($channel) {
            $specific = static::query()
                ->where('channel', $channel)
                ->where('is_active', true)
                ->first();

            if ($specific) {
                return $specific;
            }
        }

        // Fall back to global (null channel)
        return static::query()
            ->whereNull('channel')
            ->where('is_active', true)
            ->first();
    }
}
