<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampaignRecipient extends Model
{
    protected $table = 'ecommerce_email_campaign_recipients';

    protected $fillable = [
        'campaign_id',
        'customer_id',
        'email',
        'token',
        'sent_at',
        'opened_at',
        'clicked_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
