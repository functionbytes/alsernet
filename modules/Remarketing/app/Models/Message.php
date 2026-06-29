<?php

namespace Modules\Remarketing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $table = 'remarketing_messages';

    protected $fillable = [
        'store_id',
        'customer_id',
        'campaign_id',
        'variant_id',
        'automation_run_id',
        'email',
        'subject',
        'status',
        'is_holdout',
        'provider',
        'provider_message_id',
        'open_token',
        'click_token',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'bounce_type',
        'complained_at',
        'sent_at',
        'revenue',
    ];

    protected function casts(): array
    {
        return [
            'revenue' => 'decimal:2',
            'is_holdout' => 'boolean',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'bounced_at' => 'datetime',
            'complained_at' => 'datetime',
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function automationRun(): BelongsTo
    {
        return $this->belongsTo(AutomationRun::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(CampaignVariant::class);
    }
}
