<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;

class AgentCalendar extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_agent_calendars';

    protected $fillable = [
        'user_id',
        'provider',
        'booking_url',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public const PROVIDERS = [
        'cal.com' => 'Cal.com',
        'google' => 'Google Calendar',
        'outlook' => 'Outlook / Microsoft Bookings',
        'other' => 'Otro',
    ];

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
