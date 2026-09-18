<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SlackIntegration extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_slack_integrations';

    protected $fillable = [
        'webhook_url',
        'channel_name',
        'events',
        'is_active',
    ];

    protected $hidden = ['webhook_url'];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public const AVAILABLE_EVENTS = [
        'sla_breach' => 'Incumplimiento de SLA',
        'urgent_assigned' => 'Conversacion urgente asignada',
        'csat_low' => 'CSAT bajo (≤ 2)',
        'sentiment_negative' => 'Sentimiento negativo detectado',
    ];

    public function setWebhookUrlAttribute(string $value): void
    {
        $this->attributes['webhook_url'] = Crypt::encryptString($value);
    }

    public function getWebhookUrlAttribute(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value; // already plain (legacy)
        }
    }

    public function supportsEvent(string $event): bool
    {
        return in_array($event, $this->events ?? [], true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
