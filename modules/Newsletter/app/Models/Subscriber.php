<?php

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Newsletter\Enums\SubscriberStatus;

class Subscriber extends Model
{
    protected $table = 'newsletter_subscribers';

    protected $fillable = [
        'email',
        'name',
        'status',
        'ip_address',
        'mailjet_id',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriberStatus::class,
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::Subscribed);
    }

    public function scopeUnsubscribed(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::Unsubscribed);
    }

    public function subscribe(): void
    {
        $this->update([
            'status' => SubscriberStatus::Subscribed,
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ]);
    }

    public function unsubscribe(): void
    {
        $this->update([
            'status' => SubscriberStatus::Unsubscribed,
            'unsubscribed_at' => now(),
        ]);
    }
}
