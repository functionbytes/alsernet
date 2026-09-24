<?php

namespace Modules\HelpdeskPrestashop\Models\Ext;

use Illuminate\Database\Eloquent\Model;

/**
 * Vínculo pedido de PrestaShop ↔ conversación del helpdesk (extensión
 * "orderlink"). Una fila por par (conversation_id, ps_order_id).
 *
 * @property int $id
 * @property int $conversation_id
 * @property int|null $customer_id
 * @property int $ps_order_id
 * @property string|null $ps_order_reference
 * @property string $source
 * @property int|null $linked_by
 */
class OrderlinkLink extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ps_order_links';

    protected $fillable = [
        'conversation_id',
        'customer_id',
        'ps_order_id',
        'ps_order_reference',
        'source',
        'linked_by',
    ];

    protected function casts(): array
    {
        return [
            'conversation_id' => 'integer',
            'customer_id' => 'integer',
            'ps_order_id' => 'integer',
            'linked_by' => 'integer',
        ];
    }
}
