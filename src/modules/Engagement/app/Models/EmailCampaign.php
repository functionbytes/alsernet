<?php

declare(strict_types=1);

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Engagement\Database\Factories\EmailCampaignFactory;
use Modules\Helpdesk\Models\Inbox;

class EmailCampaign extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'engagement_email_campaigns';

    protected $fillable = [
        'inbox_id',
        'name',
        'subject',
        'from_name',
        'from_email',
        'html_content',
        'text_content',
        'provider',
        'provider_list_id',
        'provider_campaign_id',
        'status',
        'segment_conditions',
        'scheduled_at',
        'sent_at',
        'sent_count',
        'open_count',
        'click_count',
        'bounce_count',
        'unsubscribe_count',
    ];

    protected function casts(): array
    {
        return [
            'segment_conditions' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function scopeForInbox($query, int $inboxId)
    {
        return $query->where('inbox_id', $inboxId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    protected static function newFactory(): EmailCampaignFactory
    {
        return EmailCampaignFactory::new();
    }
}
