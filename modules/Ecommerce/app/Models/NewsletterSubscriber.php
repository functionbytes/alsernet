<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ecommerce\Database\Factories\NewsletterSubscriberFactory;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    public const STATUS_SUBSCRIBED = 'subscribed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    public const STATUS_BOUNCED = 'bounced';

    protected $table = 'ecommerce_newsletter_subscribers';

    protected $fillable = [
        'email',
        'name',
        'status',
        'source',
        'ip_address',
        'subscribed_at',
        'unsubscribed_at',
        'unsubscribe_token',
    ];

    protected static function newFactory(): NewsletterSubscriberFactory
    {
        return NewsletterSubscriberFactory::new();
    }

    public function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUBSCRIBED);
    }

    public static function generateUnsubscribeToken(): string
    {
        return bin2hex(random_bytes(30));
    }
}
