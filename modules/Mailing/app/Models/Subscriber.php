<?php

namespace Modules\Mailing\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Mailing\Enums\SubscriberStatus;

class Subscriber extends Model
{
    use HasFactory;

    protected $table = 'mails_subscribers';

    protected $fillable = [
        'email',
        'name',
        'status',
        'mailrelay_id',
        'metadata',
        'custom_fields',
        'subscribed_at',
        'unsubscribed_at',
        'validated_at',
        'last_synced_at',
    ];

    protected $casts = [
        'status' => SubscriberStatus::class,
        'metadata' => 'array',
        'custom_fields' => 'array',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'validated_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Relationship with MailingGroup (many-to-many)
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(MailingGroup::class, 'subscriber_mailrelay_group');
    }

    /**
     * Subscribe the subscriber
     */
    public function subscribe(): void
    {
        $this->update([
            'status' => SubscriberStatus::ACTIVE,
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ]);
    }

    /**
     * Unsubscribe the subscriber
     */
    public function unsubscribe(): void
    {
        $this->update([
            'status' => SubscriberStatus::UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ]);
    }

    /**
     * Mark subscriber as bounced
     */
    public function markAsBounced(): void
    {
        $this->update([
            'status' => SubscriberStatus::BOUNCED,
        ]);
    }

    /**
     * Scope to get only active subscribers
     */
    public function scopeActive($query)
    {
        return $query->where('status', SubscriberStatus::ACTIVE);
    }

    /**
     * Scope to get only subscribed subscribers
     */
    public function scopeSubscribed($query)
    {
        return $query->where('status', SubscriberStatus::ACTIVE)
            ->whereNotNull('subscribed_at');
    }

    /**
     * Scope to get subscribers that need syncing with Mailrelay
     */
    public function scopeNeedsSyncing($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('mailrelay_id')
                ->orWhereNull('last_synced_at')
                ->orWhere('last_synced_at', '<', now()->subHours(24));
        });
    }

    /**
     * Scope to get synced subscribers (those with mailrelay_id)
     */
    public function scopeSynced($query)
    {
        return $query->whereNotNull('mailrelay_id');
    }
}
