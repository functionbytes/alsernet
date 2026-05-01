<?php

namespace Modules\Helpdesk\Models\Campaigns;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Broadcast extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_broadcasts';

    protected $fillable = [
        'name',
        'channel',
        'template_type',
        'body',
        'template_id',
        'template_params',
        'filters',
        'status',
        'scheduled_at',
        'sent_at',
        'recipients_count',
        'delivered_count',
        'failed_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'template_params' => 'array',
            'filters' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public const CHANNELS = ['whatsapp', 'facebook', 'instagram', 'email', 'widget'];

    public const TEMPLATE_TYPES = ['text', 'hsm'];

    public const STATUSES = ['draft', 'scheduled', 'sending', 'sent', 'failed'];

    public function recipients(): HasMany
    {
        return $this->hasMany(BroadcastRecipient::class, 'broadcast_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function markAsSending(): void
    {
        $this->update(['status' => 'sending']);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
