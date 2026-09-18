<?php

namespace Modules\Questions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionEvent extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'product_question_events';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['data' => 'array', 'actor_id' => 'integer', 'created_at' => 'datetime'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function getLabelAttribute(): string
    {
        return [
            'received' => 'Recibida de la tienda',
            'approved' => 'Publicada',
            'rejected' => 'Retirada',
            'answered' => 'Respondida',
            'answer_failed' => 'No se pudo enviar la respuesta',
            'published' => 'Publicada en la tienda',
            'publish_failed' => 'No se pudo publicar',
            'edited' => 'Cambiada en la tienda',
            'deleted' => 'Borrada en la tienda',
            'translated' => 'Traducida',
            'translations_published' => 'Traducciones publicadas',
        ][$this->event] ?? $this->event;
    }

    public function getSourceLabelAttribute(): string
    {
        return ['panel' => 'Desde el panel', 'prestashop' => 'Desde la tienda', 'system' => 'Automático'][$this->source] ?? $this->source;
    }

    public function getActorNameAttribute(): ?string
    {
        if (! $this->actor_id) {
            return null;
        }

        $user = User::find($this->actor_id);

        return $user ? (trim(($user->firstname ?? '').' '.($user->lastname ?? '')) ?: $user->email) : null;
    }

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
}
