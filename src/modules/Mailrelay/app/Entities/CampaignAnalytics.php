<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAnalytics extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'mails_campaign_analytics';

    protected $fillable = [
        'campaign_id',
        'sent_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
        'unsubscribed_count',
        'open_rate',
        'click_rate',
        'last_synced_at',
    ];

    protected $casts = [
        'open_rate' => 'decimal:2',
        'click_rate' => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function calculateRates(): void
    {
        $openRate = $this->sent_count > 0
            ? round(($this->opened_count / $this->sent_count) * 100, 2)
            : 0;

        $clickRate = $this->sent_count > 0
            ? round(($this->clicked_count / $this->sent_count) * 100, 2)
            : 0;

        $this->open_rate = $openRate;
        $this->click_rate = $clickRate;
    }

    public function updateFromMailrelay(array $data): void
    {
        $this->fill([
            'sent_count' => $data['sent_count'] ?? $this->sent_count,
            'opened_count' => $data['opened_count'] ?? $this->opened_count,
            'clicked_count' => $data['clicked_count'] ?? $this->clicked_count,
            'bounced_count' => $data['bounced_count'] ?? $this->bounced_count,
            'unsubscribed_count' => $data['unsubscribed_count'] ?? $this->unsubscribed_count,
            'last_synced_at' => now(),
        ]);

        $this->calculateRates();
        $this->save();
    }
}
