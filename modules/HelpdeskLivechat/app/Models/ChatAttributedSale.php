<?php

namespace Modules\HelpdeskLivechat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Models\Conversation;

/**
 * Pedido de la tienda atribuido a una conversación del chat web (último
 * contacto en 30 días). Ver ChatSaleAttributionService.
 */
class ChatAttributedSale extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_chat_attributed_sales';

    protected $fillable = [
        'order_id',
        'order_reference',
        'cart_id',
        'total',
        'currency',
        'conversation_id',
        'agent_id',
        'via_bot',
        'same_session',
        'matched_by',
        'chat_touched_at',
        'ordered_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'via_bot' => 'boolean',
            'same_session' => 'boolean',
            'chat_touched_at' => 'datetime',
            'ordered_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
