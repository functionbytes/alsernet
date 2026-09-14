<?php

namespace Modules\HelpdeskBirthday\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un cumpleañero de la campaña del día, con la hora exacta en la que le toca
 * recibir el correo (scheduled_at).
 */
class BirthdayRecipient extends Model
{
    public const STATUS_PENDING = 'pending';

    /** Reservado: el job ya está en la cola. Evita el doble envío. */
    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    /** Nunca se intentó: supresión, sin email, o excluido por consentimiento. */
    public const STATUS_SKIPPED = 'skipped';

    public const SKIP_SUPPRESSED = 'suppressed';

    public const SKIP_INVALID_EMAIL = 'invalid_email';

    public const SKIP_DUPLICATE = 'duplicate';

    /** Gestión no llegó a emitirle su bono: sin regalo no se felicita. */
    public const SKIP_NO_COUPON = 'no_coupon';

    /** Se le pasó el día. Una felicitación con retraso es peor que ninguna. */
    public const SKIP_EXPIRED = 'expired';

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_birthday_recipients';

    protected $fillable = [
        'coupon_code',
        'coupon_verification_code',
        'coupon_amount',
        'coupon_min_purchase',
        'coupon_valid_from',
        'coupon_valid_to',
        'coupon_status',
        'coupon_data',
        'coupon_generated_at',
        'coupon_error',
        'campaign_id',
        'erp_customer_id',
        'email',
        'name',
        'lang',
        'birth_date',
        'scheduled_at',
        'status',
        'skip_reason',
        'email_log_id',
        'sent_at',
        'attempts',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'coupon_generated_at' => 'datetime',
            'coupon_valid_from' => 'date',
            'coupon_valid_to' => 'date',
            'coupon_data' => 'array',
            'birth_date' => 'date',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BirthdayCampaign::class, 'campaign_id');
    }

    /**
     * Destinatarios a los que ya les toca salir.
     */
    public function scopeDue(Builder $query, ?\DateTimeInterface $now = null): Builder
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now ?? now());
    }

    public function scopeUnfinished(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_SENDING]);
    }

    /**
     * Los que ya tienen su bono emitido en Gestión.
     *
     * Es la condición para encolar: un correo de cumpleaños sin el bono dentro
     * no se puede repetir al día siguiente, así que antes que mandarlo vacío se
     * espera a que Gestión lo emita.
     */
    public function scopeWithCoupon(Builder $query): Builder
    {
        return $query->whereNotNull('coupon_code')->where('coupon_code', '!=', '');
    }

    /**
     * El código tal como lo teclea el cliente en la tienda:
     * «{idbono}-{codigo_verificacion}», que es como PrestaShop crea el
     * cart_rule (CartRule::createCartRuleAlvarez).
     *
     * Se guardan las dos partes por separado porque consultar o consumir el
     * bono en Gestión las necesita sueltas, pero al cliente hay que darle el
     * código entero: solo con el id no puede canjear nada.
     */
    public function publicCode(): ?string
    {
        $code = trim((string) $this->coupon_code);

        if ($code === '') {
            return null;
        }

        $verification = trim((string) $this->coupon_verification_code);

        return $verification !== '' ? $code.'-'.$verification : $code;
    }

    /**
     * Nombre de pila para la plantilla; si el ERP no lo trae, un saludo neutro.
     */
    public function displayName(): string
    {
        $name = trim((string) $this->name);

        return $name !== '' ? $name : __('helpdeskbirthday::messages.default_customer_name');
    }
}
