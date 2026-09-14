<?php

namespace Modules\Reviews\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewEvent extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'product_review_events';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'array',
        'actor_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /** Cómo se lee cada movimiento en el historial de la ficha. */
    public function getLabelAttribute(): string
    {
        return [
            'received' => 'Recibida de la tienda',
            'approved' => 'Aprobada',
            'rejected' => 'Retirada',
            'published' => 'Publicada en la tienda',
            'publish_failed' => 'No se pudo publicar',
            'unpublish_failed' => 'No se pudo retirar',
            'answered' => 'Respondida',
            'edited' => 'Cambiada en la tienda',
            'deleted' => 'Borrada en la tienda',
            'conflict' => 'La tienda y el panel discrepan',
            'translated' => 'Traducida',
            'translation_edited' => 'Traducción corregida',
            'translation_approved' => 'Traducción aprobada',
            'translation_unapproved' => 'Aprobación de traducción retirada',
            'translations_published' => 'Traducciones publicadas',
            'translations_publish_failed' => 'No se pudieron publicar las traducciones',
        ][$this->event] ?? $this->event;
    }

    /** Nombre de quien lo hizo, o null si fue automático. */
    public function getActorNameAttribute(): ?string
    {
        if (! $this->actor_id) {
            return null;
        }

        $user = User::find($this->actor_id);

        if (! $user) {
            return null;
        }

        return trim(($user->firstname ?? '').' '.($user->lastname ?? '')) ?: ($user->email ?? null);
    }

    /** Los detalles del movimiento, en texto corrido. */
    public function getDetailAttribute(): ?string
    {
        if (! $this->data) {
            return null;
        }

        $partes = [];

        foreach ($this->data as $clave => $valor) {
            if (is_bool($valor)) {
                $valor = $valor ? 'sí' : 'no';
            } elseif (is_array($valor)) {
                $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
            } elseif ($valor === null || $valor === '') {
                continue;
            }

            $partes[] = str_replace('_', ' ', $clave).': '.$valor;
        }

        return $partes ? implode(' · ', $partes) : null;
    }

    public function getSourceLabelAttribute(): string
    {
        return [
            'panel' => 'Desde el panel',
            'prestashop' => 'Desde la tienda',
            'system' => 'Automático',
        ][$this->source] ?? $this->source;
    }
}
