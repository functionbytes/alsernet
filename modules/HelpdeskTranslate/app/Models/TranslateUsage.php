<?php

namespace Modules\HelpdeskTranslate\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una fila = una llamada real confirmada al proveedor de traducción.
 * Insert-only (sin updated_at) — ver migración para el porqué de este ledger
 * en vez de reusar TranslationCache.
 */
class TranslateUsage extends Model
{
    const UPDATED_AT = null;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_translate_usage';

    protected $fillable = [
        'provider',
        'operation',
        'feature',
        'characters',
        'source_lang',
        'target_lang',
        'success',
    ];

    protected function casts(): array
    {
        return [
            'characters' => 'integer',
            'success' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
