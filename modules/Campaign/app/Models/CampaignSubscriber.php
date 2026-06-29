<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Suscriptor (contacto) global del módulo Campaign. Un mismo email
 * puede pertenecer a varias listas vía la tabla pivot
 * campaign_maillists_subscribers.
 *
 * Aliasado como `Subscriber` en código heredado de Acelle —
 * mantener ambos nombres conviviendo durante el porting.
 *
 * @property int $id
 * @property string $uid
 * @property string $email
 * @property array|null $attributes
 * @property string|null $ip
 * @property string $source
 * @property Carbon|null $subscribed_at
 * @property Carbon|null $unsubscribed_at
 */
class CampaignSubscriber extends Model
{
    use SoftDeletes;

    protected $table = 'campaign_subscribers';

    protected $fillable = [
        'uid',
        'email',
        'first_name',
        'last_name',
        'attributes',
        'ip',
        'source',
        'subscribed_at',
        'unsubscribed_at',
        'confirmed_at',
        'confirmation_code',
    ];

    protected $casts = [
        'attributes' => 'array',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $sub): void {
            if (empty($sub->uid)) {
                $sub->uid = (string) Str::uuid();
            }
            if (! empty($sub->email)) {
                $sub->email = self::normalizeEmail($sub->email);
            }
        });

        static::updating(function (self $sub): void {
            if ($sub->isDirty('email') && ! empty($sub->email)) {
                $sub->email = self::normalizeEmail($sub->email);
            }
        });
    }

    /**
     * Normaliza un email: lowercase, trim, punycode para dominios con Unicode.
     */
    public static function normalizeEmail(string $email): string
    {
        $email = mb_strtolower(trim($email), 'UTF-8');
        $parts = explode('@', $email, 2);
        if (count($parts) === 2 && function_exists('idn_to_ascii')) {
            $domain = idn_to_ascii($parts[1], IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
            if ($domain !== false) {
                $email = $parts[0].'@'.$domain;
            }
        }

        return $email;
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    /**
     * Listas a las que pertenece el suscriptor (con su estado por lista).
     */
    public function mailLists()
    {
        return $this->belongsToMany(
            CampaignMaillist::class,
            'campaign_maillists_subscribers',
            'subscriber_id',
            'mail_list_id',
        )->withPivot(['status', 'tags', 'subscribed_at', 'unsubscribed_at']);
    }

    /**
     * Devuelve el atributo `email` del subscriber (para compat con código que
     * llama $subscriber->getEmail()).
     */
    public function getEmail(): string
    {
        return (string) $this->email;
    }

    /**
     * Devuelve todos los tags/atributos del subscriber para sustituir en plantillas.
     */
    public function getAllTagValues(): array
    {
        return is_array($this->attributes_raw = $this->attributes) ? $this->attributes : [];
    }

    /**
     * Stub para compat con código heredado: en este módulo la URL de
     * desuscripción se generará desde un controller público.
     * Hasta entonces devolvemos null para que `prepareEmail` no falle.
     */
    public function generateUnsubscribeUrl($msgId): ?string
    {
        if (! function_exists('route')) {
            return null;
        }

        try {
            return route(
                'manager.campaigns.maillists.unsubscribe',
                ['subscriber' => $this->uid, 'msgId' => $msgId],
                false,
            );
        } catch (\Throwable) {
            return null;
        }
    }

    public function engagementScore()
    {
        return $this->hasOne(SubscriberEngagementScore::class, 'subscriber_id');
    }

    public function scopeEngagementCategory($query, string $category)
    {
        return $query->join('campaign_subscriber_engagement_scores as ces', 'ces.subscriber_id', '=', 'campaign_subscribers.id')
            ->whereRaw(
                match ($category) {
                    'hot' => 'ces.score >= 70',
                    'warm' => 'ces.score >= 40 AND ces.score < 70',
                    'cold' => 'ces.score >= 10 AND ces.score < 40',
                    'dormant' => 'ces.score >= -20 AND ces.score < 10',
                    'at_risk' => 'ces.score < -20',
                    default => '1=1',
                }
            );
    }
}
