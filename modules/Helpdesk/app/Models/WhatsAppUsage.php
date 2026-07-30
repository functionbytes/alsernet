<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una fila = un envío saliente de WhatsApp confirmado contra la API de Meta.
 * Insert-only (sin updated_at) — ver migración para el porqué de este ledger.
 */
class WhatsAppUsage extends Model
{
    const UPDATED_AT = null;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_whatsapp_usage';

    protected $fillable = [
        'conversation_id',
        'template_name',
        'category',
        'message_type',
        'success',
    ];

    protected function casts(): array
    {
        return [
            'conversation_id' => 'integer',
            'success' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
