<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignWebhookLog extends Model
{
    use HasFactory;

    protected $table = 'campaign_webhook_logs';

    protected $fillable = [
        'campaign_webhook_id',
        'event',
        'status',
        'http_code',
        'response',
        'attempt',
        'duration_ms',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'attempt' => 'integer',
            'duration_ms' => 'integer',
            'http_code' => 'integer',
        ];
    }

    public function webhook()
    {
        return $this->belongsTo(CampaignWebhook::class, 'campaign_webhook_id');
    }
}
