<?php

namespace Modules\HelpdeskBirthday\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un bono gastado en la tienda: qué pedido salió de él, cuánto movió y si
 * Gestión llegó a descontarlo.
 *
 * Es una copia de lo que hay en PrestaShop, no la fuente. Se refresca con
 * `helpdeskbirthday:sync-redemptions`; el panel lee de aquí para no depender de
 * que la tienda responda y, sobre todo, para conservar lo que la tienda borra:
 * la `cart_rule` desaparece al consumirse.
 */
class BirthdayRedemption extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_birthday_redemptions';

    protected $fillable = [
        'campaign_id',
        'recipient_id',
        'coupon_code',
        'code_source',
        'voucher_name',
        'ps_order_id',
        'ps_order_reference',
        'ps_cart_rule_id',
        'ps_order_line_id',
        'ps_customer_id',
        'customer_email',
        'order_state',
        'order_valid',
        'order_total',
        'discount',
        'ordered_at',
        'erp_marked',
        'erp_bono',
        'erp_operation',
        'erp_response',
        'erp_sale_amount',
        'erp_marked_at',
        'attributed',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'order_valid' => 'boolean',
            'erp_marked' => 'boolean',
            'attributed' => 'boolean',
            'order_total' => 'decimal:2',
            'discount' => 'decimal:2',
            'erp_sale_amount' => 'decimal:2',
            'ordered_at' => 'datetime',
            'erp_marked_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BirthdayCampaign::class, 'campaign_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(BirthdayRecipient::class, 'recipient_id');
    }

    /** Canjes de gente a la que le mandamos el correo. */
    public function scopeAttributed(Builder $query): Builder
    {
        return $query->where('attributed', true);
    }

    /**
     * Bonos que la tienda descontó y Gestión no registró: dinero que el cliente
     * se llevó y que sigue vivo en el ERP, listo para gastarse otra vez.
     */
    public function scopeUnreconciled(Builder $query): Builder
    {
        return $query->where('erp_marked', false);
    }

    /** El histórico anterior al módulo: canjes reales sin campaña detrás. */
    public function scopeHistorical(Builder $query): Builder
    {
        return $query->whereNull('campaign_id');
    }

    /**
     * Se puede reintentar el marcado en Gestión sólo si sabemos QUÉ bono era.
     * Sin código no hay nada que marcar, por mucho que el descuadre exista.
     */
    public function canBeMarkedInErp(): bool
    {
        return ! $this->erp_marked
            && trim((string) $this->coupon_code) !== ''
            && str_contains((string) $this->coupon_code, '-');
    }
}
